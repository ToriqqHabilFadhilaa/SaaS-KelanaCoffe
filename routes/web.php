<?php

use App\Http\Controllers\AuthController;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CsvExportService;
use App\Services\MidtransPaymentService;
use App\Services\OrderInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.process');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:6,1')->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

Route::get('/email/verify/{id}/{hash}', function (Request $request, string $id, string $hash) {
    $user = User::findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403);
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    return redirect()->route('login')->with('status', 'Email berhasil diverifikasi. Silakan masuk.');
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->validate([
        'email' => ['required', 'email'],
    ], [
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
    ]);

    $user = User::where('email', $request->email)->first();

    if ($user && ! $user->hasVerifiedEmail()) {
        $user->sendEmailVerificationNotification();
    }

    return back()->with('status', 'Link verifikasi telah dikirim ulang.');
})->middleware('throttle:6,1')->name('verification.resend');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/dashboard', function () {
    return match (auth('web')->user()->role) {
        User::ROLE_ADMIN => redirect()->route('admin.dashboard'),
        User::ROLE_KASIR => redirect()->route('cashier.dashboard'),
        User::ROLE_INVENTORY => redirect()->route('inventory.dashboard'),
        default => abort(403),
    };
})->middleware('auth')->name('dashboard');

Route::get('/admin/dashboard', function () {
    /** @var User $user */
    $user = auth('web')->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $todayStart = now()->startOfDay();
    $todayEnd = now()->endOfDay();
    $completedToday = Order::query()
        ->completed()
        ->whereBetween('paid_at', [$todayStart, $todayEnd]);

    $topProducts = OrderItem::query()
        ->select('product_name', DB::raw('SUM(qty) as total_qty'))
        ->whereHas('order', fn ($query) => $query->completed()->whereBetween('paid_at', [$todayStart, $todayEnd]))
        ->groupBy('product_name')
        ->orderByDesc('total_qty')
        ->limit(5)
        ->get();

    return view('admin.dashboard', [
        'salesToday' => (float) $completedToday->sum('total'),
        'ordersToday' => (int) $completedToday->count(),
        'pendingOrders' => Order::query()->where('status', Order::STATUS_PENDING)->count(),
        'lowStockCount' => Ingredient::query()->lowStock()->count(),
        'userCount' => User::query()->count(),
        'recentOrders' => Order::query()->with('user')->latest()->limit(8)->get(),
        'topProducts' => $topProducts,
        'recentMovements' => StockMovement::query()->with(['ingredient', 'user'])->latest()->limit(8)->get(),
    ]);
})->middleware('auth')->name('admin.dashboard');

Route::get('/admin/reports', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $validated = $request->validate([
        'from' => ['nullable', 'date'],
        'to' => ['nullable', 'date', 'after_or_equal:from'],
    ]);

    $from = isset($validated['from'])
        ? Carbon::parse($validated['from'])->startOfDay()
        : now()->startOfMonth();
    $to = isset($validated['to'])
        ? Carbon::parse($validated['to'])->endOfDay()
        : now()->endOfDay();

    $completedOrders = Order::query()
        ->completed()
        ->whereBetween('paid_at', [$from, $to]);

    return view('admin.reports', [
        'from' => $from,
        'to' => $to,
        'salesTotal' => (float) (clone $completedOrders)->sum('total'),
        'ordersCount' => (int) (clone $completedOrders)->count(),
        'paymentBreakdown' => (clone $completedOrders)
            ->select('payment_method', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as sales_total'))
            ->groupBy('payment_method')
            ->orderBy('payment_method')
            ->get(),
        'dailySales' => (clone $completedOrders)
            ->selectRaw('DATE(paid_at) as report_date, SUM(total) as sales_total, COUNT(*) as orders_count')
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->orderBy('report_date')
            ->get(),
        'orders' => (clone $completedOrders)->with('user')->latest('paid_at')->paginate(15)->withQueryString(),
    ]);
})->middleware('auth')->name('admin.reports');

Route::get('/admin/reports/export.csv', function (Request $request, CsvExportService $csvExport) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $validated = $request->validate([
        'from' => ['nullable', 'date'],
        'to' => ['nullable', 'date', 'after_or_equal:from'],
    ]);
    $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : now()->startOfMonth();
    $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : now()->endOfDay();

    return response()->streamDownload(function () use ($from, $to, $csvExport): void {
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order', 'Dibayar', 'Kasir', 'Metode', 'Total']);

        Order::query()
            ->completed()
            ->whereBetween('paid_at', [$from, $to])
            ->with('user')
            ->orderBy('id')
            ->lazyById(500)
            ->each(function (Order $order) use ($output, $csvExport): void {
                fputcsv($output, [
                    $csvExport->safeCell($order->order_number),
                    $csvExport->safeCell($order->paid_at?->format('Y-m-d H:i:s') ?? ''),
                    $csvExport->safeCell($order->user?->name ?? 'Guest'),
                    $csvExport->safeCell(strtoupper($order->payment_method)),
                    $order->total,
                ]);
            });

        fclose($output);
    }, 'kelana-sales-report-'.$from->toDateString().'-'.$to->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
})->middleware('auth')->name('admin.reports.export');

Route::get('/admin/orders/{order}', function (Request $request, Order $order) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $order->load(['user', 'items.product']);

    return view('admin.order-detail', compact('order'));
})->middleware('auth')->name('admin.orders.show');

Route::post('/admin/orders/{order}/cancel', function (Request $request, Order $order, MidtransPaymentService $midtransService, OrderInventoryService $inventoryService) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    if ($order->payment_method === 'qris' && $order->status === Order::STATUS_PENDING && $midtransService->isConfigured()) {
        try {
            $midtransService->synchronizeOrder($order, $inventoryService);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['order' => 'Status pembayaran belum dapat diverifikasi. Coba lagi sebelum membatalkan.']);
        }

        $order->refresh();

        if ($order->status === Order::STATUS_CANCELLED) {
            return redirect()->route('admin.orders.show', $order)->with('status', 'Pembayaran sudah kedaluwarsa; order dibatalkan dan stok dikembalikan.');
        }
    }

    $cancelled = DB::transaction(fn () => $inventoryService->restoreForCancellation($order, $user));

    if (! $cancelled) {
        return back()->withErrors(['order' => 'Order sudah diproses atau tidak dapat dibatalkan.']);
    }

    return redirect()->route('admin.orders.show', $order)->with('status', 'Order pending dibatalkan dan stok dikembalikan.');
})->middleware('auth')->name('admin.orders.cancel');

Route::get('/admin/products', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    return view('admin.products', [
        'products' => Product::query()->with('category')->orderBy('name')->paginate(12),
    ]);
})->middleware('auth')->name('admin.products');

Route::get('/admin/products/create', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    return view('admin.product-form', [
        'product' => null,
        'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        'ingredients' => Ingredient::query()->orderBy('name')->get(),
        'recipeRows' => [['ingredient_id' => '', 'qty_needed' => '']],
    ]);
})->middleware('auth')->name('admin.products.create');

Route::get('/admin/products/{product}/edit', function (Request $request, Product $product) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $product->load('ingredients');
    $recipeRows = $product->ingredients->map(fn ($ingredient) => [
        'ingredient_id' => (string) $ingredient->id,
        'qty_needed' => (string) $ingredient->pivot->qty_needed,
    ])->values()->all();

    return view('admin.product-form', [
        'product' => $product,
        'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
        'ingredients' => Ingredient::query()->orderBy('name')->get(),
        'recipeRows' => $recipeRows !== [] ? $recipeRows : [['ingredient_id' => '', 'qty_needed' => '']],
    ]);
})->middleware('auth')->name('admin.products.edit');

Route::post('/admin/products', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $validated = $request->validate([
        'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
        'name' => ['required', 'string', 'max:255'],
        'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
        'description' => ['nullable', 'string', 'max:5000'],
        'price' => ['required', 'numeric', 'min:0.01'],
        'is_active' => ['required', 'boolean'],
        'ingredients' => ['required', 'array', 'min:1'],
        'ingredients.*.ingredient_id' => ['required', 'integer', 'distinct', Rule::exists('ingredients', 'id')->whereNull('deleted_at')],
        'ingredients.*.qty_needed' => ['required', 'numeric', 'gt:0'],
    ]);

    $product = DB::transaction(function () use ($validated) {
        $product = Product::query()->create(collect($validated)->only([
            'category_id', 'name', 'sku', 'description', 'price', 'is_active',
        ])->all());

        $recipe = collect($validated['ingredients'])->mapWithKeys(fn ($row) => [
            $row['ingredient_id'] => ['qty_needed' => $row['qty_needed']],
        ])->all();

        $product->ingredients()->sync($recipe);

        return $product;
    });

    return redirect()->route('admin.products.edit', $product)->with('status', 'Produk berhasil dibuat. Tambahkan gambar dari halaman daftar produk.');
})->middleware('auth')->name('admin.products.store');

Route::put('/admin/products/{product}', function (Request $request, Product $product) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $validated = $request->validate([
        'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
        'name' => ['required', 'string', 'max:255'],
        'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product->id)],
        'description' => ['nullable', 'string', 'max:5000'],
        'price' => ['required', 'numeric', 'min:0.01'],
        'is_active' => ['required', 'boolean'],
        'ingredients' => ['required', 'array', 'min:1'],
        'ingredients.*.ingredient_id' => ['required', 'integer', 'distinct', Rule::exists('ingredients', 'id')->whereNull('deleted_at')],
        'ingredients.*.qty_needed' => ['required', 'numeric', 'gt:0'],
    ]);

    DB::transaction(function () use ($validated, $product) {
        $product->update(collect($validated)->only([
            'category_id', 'name', 'sku', 'description', 'price', 'is_active',
        ])->all());

        $recipe = collect($validated['ingredients'])->mapWithKeys(fn ($row) => [
            $row['ingredient_id'] => ['qty_needed' => $row['qty_needed']],
        ])->all();

        $product->ingredients()->sync($recipe);
    });

    return redirect()->route('admin.products')->with('status', 'Produk berhasil diperbarui.');
})->middleware('auth')->name('admin.products.update');

Route::get('/admin/suppliers', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    return view('admin.suppliers', [
        'suppliers' => Supplier::query()->orderBy('name')->paginate(12),
    ]);
})->middleware('auth')->name('admin.suppliers');

Route::post('/admin/suppliers', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'address' => ['nullable', 'string', 'max:500'],
        'is_active' => ['nullable', 'boolean'],
    ], [
        'name.required' => 'Nama supplier wajib diisi.',
    ]);

    Supplier::query()->create([
        'name' => $validated['name'],
        'phone' => $validated['phone'] ?? null,
        'address' => $validated['address'] ?? null,
        'is_active' => $validated['is_active'] ?? true,
    ]);

    return redirect()->route('admin.suppliers')->with('status', 'Supplier baru berhasil ditambahkan.');
})->middleware('auth')->name('admin.suppliers.store');

Route::patch('/admin/suppliers/{supplier}', function (Request $request, Supplier $supplier) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'address' => ['nullable', 'string', 'max:500'],
        'is_active' => ['required', 'boolean'],
    ]);

    $supplier->update($validated);

    return redirect()->route('admin.suppliers')->with('status', 'Data supplier berhasil diperbarui.');
})->middleware('auth')->name('admin.suppliers.update');

Route::get('/admin/categories', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    return view('admin.categories', [
        'categories' => Category::query()->withCount('products')->orderBy('name')->paginate(15),
    ]);
})->middleware('auth')->name('admin.categories');

Route::post('/admin/categories', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $request->merge(['slug' => Str::slug((string) $request->input('name'))]);
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
        'is_active' => ['required', 'boolean'],
    ]);

    Category::query()->create($validated);

    return redirect()->route('admin.categories')->with('status', 'Kategori berhasil dibuat.');
})->middleware('auth')->name('admin.categories.store');

Route::patch('/admin/categories/{category}', function (Request $request, Category $category) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $request->merge(['slug' => Str::slug((string) $request->input('name'))]);
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
        'is_active' => ['required', 'boolean'],
    ]);

    $category->update($validated);

    return redirect()->route('admin.categories')->with('status', 'Kategori berhasil diperbarui.');
})->middleware('auth')->name('admin.categories.update');

Route::get('/admin/users', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    return view('admin.users', [
        'users' => User::query()->orderBy('role')->orderBy('name')->paginate(20),
        'currentUserId' => $user->id,
    ]);
})->middleware('auth')->name('admin.users');

Route::post('/admin/users', function (Request $request) {
    /** @var User $admin */
    $admin = $request->user();

    abort_unless($admin instanceof User && $admin->isAdmin(), 403);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_KASIR, User::ROLE_INVENTORY])],
    ]);

    User::query()->create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => $validated['password'],
        'role' => $validated['role'],
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    return redirect()->route('admin.users')->with('status', 'Akun staf berhasil dibuat.');
})->middleware('auth')->name('admin.users.store');

Route::patch('/admin/users/{managedUser}', function (Request $request, User $managedUser) {
    /** @var User $admin */
    $admin = $request->user();

    abort_unless($admin instanceof User && $admin->isAdmin(), 403);

    $validated = $request->validate([
        'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_KASIR, User::ROLE_INVENTORY])],
        'is_active' => ['required', 'boolean'],
    ]);

    if ($managedUser->is($admin) && ($validated['role'] !== User::ROLE_ADMIN || ! $validated['is_active'])) {
        return back()->withErrors(['user' => 'Akun admin yang sedang digunakan tidak dapat diturunkan role atau dinonaktifkan.']);
    }

    $updated = DB::transaction(function () use ($managedUser, $validated) {
        $lockedUser = User::query()->lockForUpdate()->findOrFail($managedUser->id);
        $willRemoveAdminAccess = $lockedUser->isAdmin()
            && $lockedUser->is_active
            && ($validated['role'] !== User::ROLE_ADMIN || ! $validated['is_active']);

        if ($willRemoveAdminAccess) {
            $activeAdminCount = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where('is_active', true)
                ->lockForUpdate()
                ->count();

            if ($activeAdminCount <= 1) {
                return false;
            }
        }

        $lockedUser->update($validated);

        return true;
    });

    if (! $updated) {
        return back()->withErrors(['user' => 'Minimal harus ada satu akun admin aktif.']);
    }

    return redirect()->route('admin.users')->with('status', 'Akses pengguna berhasil diperbarui.');
})->middleware('auth')->name('admin.users.update');

Route::post('/admin/products/{product}/image', function (Request $request, Product $product) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    $validated = $request->validate([
        'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=400,min_height=400,max_width=3000,max_height=3000'],
    ], [
        'image.required' => 'Gambar produk wajib dipilih.',
        'image.image' => 'File harus berupa gambar.',
        'image.max' => 'Ukuran gambar maksimal 2 MB.',
        'image.dimensions' => 'Ukuran gambar minimal 400x400 dan maksimal 3000x3000 piksel.',
    ]);

    $oldImage = $product->image;
    $newImage = $validated['image']->store('products', 'public');

    if (! $newImage) {
        return back()->withErrors(['image' => 'Gambar gagal disimpan. Silakan coba lagi.']);
    }

    $product->update(['image' => $newImage]);

    if ($oldImage) {
        Storage::disk('public')->delete($oldImage);
    }

    return redirect()->route('admin.products')->with('status', 'Gambar '.$product->name.' berhasil diperbarui.');
})->middleware('auth')->name('admin.products.image');

Route::get('/admin/export/orders.csv', function (Request $request, CsvExportService $csvExport) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    return response()->streamDownload(function () use ($csvExport): void {
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order', 'Tanggal', 'Kasir', 'Metode', 'Status', 'Total']);

        Order::query()->with('user')->orderBy('id')->lazyById(500)->each(function (Order $order) use ($output, $csvExport): void {
            fputcsv($output, [
                $csvExport->safeCell($order->order_number),
                $csvExport->safeCell($order->created_at->format('Y-m-d H:i:s')),
                $csvExport->safeCell($order->user?->name ?? 'Guest'),
                $csvExport->safeCell(strtoupper($order->payment_method)),
                $csvExport->safeCell($order->status),
                $order->total,
            ]);
        });

        fclose($output);
    }, 'kelana-orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
})->middleware('auth')->name('admin.export.orders');

Route::get('/admin/export/stock-movements.csv', function (Request $request, CsvExportService $csvExport) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    return response()->streamDownload(function () use ($csvExport): void {
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Tanggal', 'Bahan', 'Tipe', 'Alasan', 'Jumlah', 'Sebelum', 'Sesudah', 'User', 'Catatan']);

        StockMovement::query()->with(['ingredient', 'user'])->orderBy('id')->lazyById(500)->each(function (StockMovement $movement) use ($output, $csvExport): void {
            fputcsv($output, [
                $csvExport->safeCell($movement->created_at->format('Y-m-d H:i:s')),
                $csvExport->safeCell($movement->ingredient?->name ?? 'Bahan dihapus'),
                $csvExport->safeCell($movement->type),
                $csvExport->safeCell($movement->reason),
                $movement->qty,
                $movement->stock_before,
                $movement->stock_after,
                $csvExport->safeCell($movement->user?->name ?? 'System'),
                $csvExport->safeCell($movement->note),
            ]);
        });

        fclose($output);
    }, 'kelana-stock-movements-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
})->middleware('auth')->name('admin.export.stock-movements');

Route::get('/cashier/dashboard', function () {
    /** @var User $user */
    $user = auth('web')->user();

    abort_unless($user instanceof User && $user->isKasir(), 403);

    $categories = Category::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    $products = Product::query()
        ->with(['category', 'ingredients'])
        ->where('is_active', true)
        ->orderBy('name')
        ->get()
        ->map(function (Product $product) {
            $product->available_stock = $product->ingredients->isNotEmpty()
                ? (int) floor(
                    $product->ingredients
                        ->map(fn ($ingredient) => $ingredient->stock / max(1, (float) $ingredient->pivot->qty_needed))
                        ->min()
                )
                : null;

            return $product;
        });

    return view('cashier.dashboard', compact('categories', 'products'));
})->middleware('auth')->name('cashier.dashboard');

Route::get('/cashier/history', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isKasir(), 403);

    $query = Order::query()
        ->with('items')
        ->whereBelongsTo($user);

    $status = $request->string('status')->toString();
    if (in_array($status, [Order::STATUS_PENDING, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED], true)) {
        $query->where('status', $status);
    }

    $paymentMethod = $request->string('payment_method')->toString();
    if (in_array($paymentMethod, ['cash', 'qris', 'debit', 'credit'], true)) {
        $query->where('payment_method', $paymentMethod);
    }

    $search = trim($request->string('search')->toString());
    if ($search !== '') {
        $query->where('order_number', 'like', "%{$search}%");
    }

    $orders = $query->latest()->paginate(10)->withQueryString();

    return view('cashier.orders', [
        'orders' => $orders,
        'pageTitle' => 'History Pesanan',
        'historyRoute' => 'cashier.history',
    ]);
})->middleware('auth')->name('cashier.history');

Route::get('/cashier/completed', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isKasir(), 403);

    $query = Order::query()
        ->with('items')
        ->whereBelongsTo($user)
        ->completed();

    $paymentMethod = $request->string('payment_method')->toString();
    if (in_array($paymentMethod, ['cash', 'qris', 'debit', 'credit'], true)) {
        $query->where('payment_method', $paymentMethod);
    }

    $search = trim($request->string('search')->toString());
    if ($search !== '') {
        $query->where('order_number', 'like', "%{$search}%");
    }

    $orders = $query->latest()->paginate(10)->withQueryString();

    return view('cashier.orders', [
        'orders' => $orders,
        'pageTitle' => 'Transaksi Selesai',
        'historyRoute' => 'cashier.completed',
    ]);
})->middleware('auth')->name('cashier.completed');

Route::post('/cashier/checkout', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isKasir(), 403);

    $request->validate([
        'items' => ['required', 'json'],
        'payment_method' => ['required', 'in:cash,qris'],
    ], [
        'items.required' => 'Keranjang pembelian wajib diisi.',
        'payment_method.required' => 'Metode pembayaran wajib dipilih.',
    ]);

    $items = json_decode((string) $request->input('items', '[]'), true);

    if (! is_array($items) || $items === []) {
        return back()->withErrors(['items' => 'Keranjang pembelian wajib diisi.'])->withInput();
    }

    $cart = [];
    foreach ($items as $row) {
        $productId = (int) ($row['product_id'] ?? 0);
        $qty = (int) ($row['qty'] ?? 0);

        if ($productId <= 0 || $qty <= 0) {
            return back()->withErrors(['items' => 'Format item pesanan tidak valid.'])->withInput();
        }

        $product = Product::query()->with('ingredients')->where('is_active', true)->find($productId);

        if (! $product) {
            return back()->withErrors(['items' => 'Produk tidak ditemukan.'])->withInput();
        }

        $availableStock = $product->ingredients->isEmpty()
            ? null
            : (int) floor(
                $product->ingredients
                    ->map(fn ($ingredient) => $ingredient->stock / max(1, (float) $ingredient->pivot->qty_needed))
                    ->min()
            );

        if ($availableStock !== null && $qty > $availableStock) {
            return back()->withErrors([
                'items' => 'Stok untuk produk '.$product->name.' tidak cukup. Maksimal '.$availableStock.' item.',
            ])->withInput();
        }

        $cart[] = [
            'product' => $product,
            'qty' => $qty,
        ];
    }

    $subtotal = 0;
    foreach ($cart as $line) {
        $subtotal += (float) $line['product']->price * $line['qty'];
    }

    $tax = round($subtotal * 0.10, 2);
    $total = round($subtotal + $tax, 2);
    $paymentMethod = $request->input('payment_method');
    $paidAmount = $paymentMethod === 'cash'
        ? (float) $request->input('cash_received', $total)
        : 0;

    if ($paymentMethod === 'cash' && $paidAmount < $total) {
        return back()->withErrors(['items' => 'Uang diterima kurang dari total pembayaran.'])->withInput();
    }

    $normalizedMethod = match ($paymentMethod) {
        'cash', 'qris' => $paymentMethod,
        default => throw ValidationException::withMessages([
            'payment_method' => 'Metode pembayaran tidak didukung.',
        ]),
    };
    $isPending = $normalizedMethod === 'qris';
    $orderNumber = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));

    $order = DB::transaction(function () use ($cart, $tax, $total, $normalizedMethod, $paidAmount, $user, $isPending, $orderNumber) {
        $order = Order::query()->create([
            'order_number' => $orderNumber,
            'midtrans_order_id' => $isPending ? $orderNumber : null,
            'user_id' => $user->id,
            'subtotal' => $subtotal = array_reduce($cart, fn ($carry, $line) => $carry + ((float) $line['product']->price * $line['qty']), 0),
            'discount' => 0,
            'tax' => $tax,
            'total' => $total,
            'paid_amount' => $paidAmount,
            'change_amount' => $normalizedMethod === 'cash' ? max(0, $paidAmount - $total) : 0,
            'payment_method' => $normalizedMethod,
            'status' => $isPending ? Order::STATUS_PENDING : Order::STATUS_COMPLETED,
            'paid_at' => $isPending ? null : now(),
        ]);

        foreach ($cart as $line) {
            $product = $line['product'];
            $qty = $line['qty'];
            $lineTotal = (float) $product->price * $qty;

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->price,
                'qty' => $qty,
                'subtotal' => $lineTotal,
            ]);

            foreach ($product->ingredients as $ingredient) {
                $usedQty = (float) $ingredient->pivot->qty_needed * $qty;
                $lockedIngredient = Ingredient::query()->lockForUpdate()->findOrFail($ingredient->id);
                $stockBefore = (float) $lockedIngredient->stock;

                if ($stockBefore < $usedQty) {
                    throw ValidationException::withMessages([
                        'items' => 'Stok untuk produk '.$product->name.' berubah dan tidak cukup.',
                    ]);
                }

                $stockAfter = $stockBefore - $usedQty;

                $lockedIngredient->update(['stock' => $stockAfter]);

                StockMovement::query()->create([
                    'ingredient_id' => $lockedIngredient->id,
                    'user_id' => $user->id,
                    'type' => StockMovement::TYPE_OUT,
                    'reason' => StockMovement::REASON_SALE,
                    'qty' => $usedQty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'note' => 'Penjualan '.$order->order_number,
                ]);
            }
        }

        return $order;
    });

    return redirect()->route('cashier.receipt', ['order' => $order->id])->with('status', 'Pembayaran berhasil diproses.');
})->middleware('auth')->name('cashier.checkout');

Route::get('/cashier/receipt/{order}', function (Request $request, Order $order) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isKasir(), 403);
    abort_unless($order->user_id === $user->id, 403);

    $order->load(['items', 'user']);

    $midtransService = app(MidtransPaymentService::class);
    $inventoryService = app(OrderInventoryService::class);
    $midtransToken = null;
    $midtransConfigured = $midtransService->isConfigured();

    if ($order->payment_method === 'qris' && $order->status === Order::STATUS_PENDING && $midtransConfigured) {
        try {
            $midtransService->synchronizeOrder($order, $inventoryService);
        } catch (Throwable $exception) {
            report($exception);
        }

        $order->refresh();

        if ($order->status !== Order::STATUS_PENDING) {
            return redirect()->route('cashier.receipt', ['order' => $order->id]);
        }

        try {
            $midtransToken = $order->midtrans_token ?: $midtransService->createSnapToken($order);
            $order->update(['midtrans_token' => $midtransToken]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    return view('cashier.receipt', compact('order', 'midtransConfigured', 'midtransToken'));
})->middleware('auth')->name('cashier.receipt');

Route::post('/midtrans/notification', function (Request $request, MidtransPaymentService $midtransService, OrderInventoryService $inventoryService) {
    $payload = $request->all();

    abort_unless($midtransService->hasValidSignature($payload), 403);

    $order = Order::query()
        ->where('midtrans_order_id', $payload['order_id'] ?? '')
        ->orWhere('order_number', $payload['order_id'] ?? '')
        ->firstOrFail();

    $midtransService->applyStatus($order, $payload, $inventoryService);

    return response()->json(['received' => true]);
})->name('midtrans.notification');

Route::post('/cashier/orders/{order}/cancel', function (Request $request, Order $order, MidtransPaymentService $midtransService, OrderInventoryService $inventoryService) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isKasir(), 403);
    abort_unless($order->user_id === $user->id, 403);

    if ($order->payment_method === 'qris' && $order->status === Order::STATUS_PENDING && $midtransService->isConfigured()) {
        try {
            $midtransService->synchronizeOrder($order, $inventoryService);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['order' => 'Status pembayaran belum dapat diverifikasi. Coba lagi sebelum membatalkan.']);
        }

        $order->refresh();

        if ($order->status === Order::STATUS_CANCELLED) {
            return redirect()->route('cashier.history')->with('status', 'Pembayaran sudah kedaluwarsa; order dibatalkan dan stok dikembalikan.');
        }
    }

    $cancelled = DB::transaction(function () use ($order, $user) {
        return app(OrderInventoryService::class)->restoreForCancellation($order, $user);
    });

    if (! $cancelled) {
        return back()->withErrors(['order' => 'Pesanan sudah diproses dan tidak dapat dibatalkan.']);
    }

    return redirect()->route('cashier.history')->with('status', 'Pesanan dibatalkan dan stok dikembalikan.');
})->middleware('auth')->name('cashier.cancel');

Route::get('/inventory/dashboard', function () {
    /** @var User $user */
    $user = auth('web')->user();

    abort_unless($user instanceof User && $user->isInventory(), 403);

    $search = trim((string) request('search', ''));
    $stockFilter = request('stock');

    $ingredientsQuery = Ingredient::query()->with('supplier')->orderBy('name');

    if ($search !== '') {
        $ingredientsQuery->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    if ($stockFilter === 'low') {
        $ingredientsQuery->lowStock();
    }

    $ingredients = $ingredientsQuery->paginate(10)->withQueryString();

    return view('inventory.dashboard', [
        'ingredients' => $ingredients,
        'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
        'ingredientCount' => Ingredient::query()->count(),
        'lowStockCount' => Ingredient::query()->lowStock()->count(),
        'supplierCount' => Supplier::query()->where('is_active', true)->count(),
        'recentMovements' => StockMovement::query()
            ->with(['ingredient', 'user'])
            ->latest()
            ->limit(8)
            ->get(),
    ]);
})->middleware('auth')->name('inventory.dashboard');

Route::post('/inventory/stock-in', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isInventory(), 403);

    $validated = $request->validate([
        'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
        'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
        'qty' => ['required', 'numeric', 'min:0.01'],
        'note' => ['nullable', 'string', 'max:500'],
    ], [
        'ingredient_id.required' => 'Bahan wajib dipilih.',
        'qty.required' => 'Jumlah stok wajib diisi.',
        'qty.min' => 'Jumlah stok harus lebih besar dari nol.',
    ]);

    DB::transaction(function () use ($validated, $user) {
        $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($validated['ingredient_id']);
        $stockBefore = (float) $ingredient->stock;
        $receivedQty = (float) $validated['qty'];
        $stockAfter = $stockBefore + $receivedQty;

        $ingredient->update(['stock' => $stockAfter]);

        StockMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'user_id' => $user->id,
            'type' => StockMovement::TYPE_IN,
            'reason' => StockMovement::REASON_PURCHASE,
            'qty' => $receivedQty,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'note' => $validated['note'] ?? 'Penerimaan stok',
        ]);
    });

    return redirect()->route('inventory.dashboard')->with('status', 'Stok berhasil ditambahkan.');
})->middleware('auth')->name('inventory.stock-in');

Route::post('/inventory/stock-out', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isInventory(), 403);

    $validated = $request->validate([
        'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
        'qty' => ['required', 'numeric', 'min:0.01'],
        'reason' => ['required', 'in:damage,adjustment'],
        'note' => ['nullable', 'string', 'max:500'],
    ], [
        'qty.required' => 'Jumlah stok wajib diisi.',
        'qty.min' => 'Jumlah stok harus lebih besar dari nol.',
        'reason.required' => 'Alasan pengeluaran wajib dipilih.',
    ]);

    DB::transaction(function () use ($validated, $user) {
        $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($validated['ingredient_id']);
        $stockBefore = (float) $ingredient->stock;
        $removedQty = (float) $validated['qty'];

        if ($removedQty > $stockBefore) {
            throw ValidationException::withMessages([
                'qty' => 'Jumlah pengeluaran melebihi stok yang tersedia.',
            ]);
        }

        $stockAfter = $stockBefore - $removedQty;
        $ingredient->update(['stock' => $stockAfter]);

        StockMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'user_id' => $user->id,
            'type' => StockMovement::TYPE_OUT,
            'reason' => $validated['reason'],
            'qty' => $removedQty,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'note' => $validated['note'] ?? 'Koreksi stok',
        ]);
    });

    return redirect()->route('inventory.dashboard')->with('status', 'Stok berhasil dikoreksi.');
})->middleware('auth')->name('inventory.stock-out');

Route::get('/inventory/movements', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isInventory(), 403);

    $filters = $request->validate([
        'type' => ['nullable', Rule::in([StockMovement::TYPE_IN, StockMovement::TYPE_OUT])],
        'reason' => ['nullable', Rule::in([StockMovement::REASON_PURCHASE, StockMovement::REASON_SALE, StockMovement::REASON_DAMAGE, StockMovement::REASON_ADJUSTMENT])],
        'search' => ['nullable', 'string', 'max:255'],
        'from' => ['nullable', 'date'],
        'to' => ['nullable', 'date', 'after_or_equal:from'],
    ]);

    $query = StockMovement::query()->with(['ingredient', 'user'])->latest();
    $type = $filters['type'] ?? '';
    $reason = $filters['reason'] ?? '';
    $search = trim($filters['search'] ?? '');

    if ($type !== '') {
        $query->where('type', $type);
    }

    if ($reason !== '') {
        $query->where('reason', $reason);
    }

    if ($search !== '') {
        $query->whereHas('ingredient', function ($ingredientQuery) use ($search) {
            $ingredientQuery->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    if (! empty($filters['from'])) {
        $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
    }

    if (! empty($filters['to'])) {
        $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
    }

    return view('inventory.movements', [
        'movements' => $query->paginate(20)->withQueryString(),
    ]);
})->middleware('auth')->name('inventory.movements');
