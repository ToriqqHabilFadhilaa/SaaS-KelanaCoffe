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
use App\Services\MidtransPaymentService;
use App\Services\OrderInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    $today = now()->startOfDay();
    $completedToday = Order::query()->completed()->where('created_at', '>=', $today);

    $topProducts = OrderItem::query()
        ->select('product_name', DB::raw('SUM(qty) as total_qty'))
        ->whereHas('order', fn ($query) => $query->completed()->where('created_at', '>=', $today))
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

Route::get('/admin/products', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    return view('admin.products', [
        'products' => Product::query()->with('category')->orderBy('name')->paginate(12),
    ]);
})->middleware('auth')->name('admin.products');

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

    if ($product->image) {
        Storage::disk('public')->delete($product->image);
    }

    $product->update([
        'image' => $validated['image']->store('products', 'public'),
    ]);

    return redirect()->route('admin.products')->with('status', 'Gambar '.$product->name.' berhasil diperbarui.');
})->middleware('auth')->name('admin.products.image');

Route::get('/admin/export/orders.csv', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    return response()->streamDownload(function (): void {
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order', 'Tanggal', 'Kasir', 'Metode', 'Status', 'Total']);

        Order::query()->with('user')->latest()->cursor()->each(function (Order $order) use ($output): void {
            fputcsv($output, [
                $order->order_number,
                $order->created_at->format('Y-m-d H:i:s'),
                $order->user?->name ?? 'Guest',
                strtoupper($order->payment_method),
                $order->status,
                $order->total,
            ]);
        });

        fclose($output);
    }, 'kelana-orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
})->middleware('auth')->name('admin.export.orders');

Route::get('/admin/export/stock-movements.csv', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isAdmin(), 403);

    return response()->streamDownload(function (): void {
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Tanggal', 'Bahan', 'Tipe', 'Alasan', 'Jumlah', 'Sebelum', 'Sesudah', 'User', 'Catatan']);

        StockMovement::query()->with(['ingredient', 'user'])->latest()->cursor()->each(function (StockMovement $movement) use ($output): void {
            fputcsv($output, [
                $movement->created_at->format('Y-m-d H:i:s'),
                $movement->ingredient?->name ?? 'Bahan dihapus',
                $movement->type,
                $movement->reason,
                $movement->qty,
                $movement->stock_before,
                $movement->stock_after,
                $movement->user?->name ?? 'System',
                $movement->note,
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

Route::post('/cashier/orders/{order}/cancel', function (Request $request, Order $order) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user instanceof User && $user->isKasir(), 403);
    abort_unless($order->user_id === $user->id, 403);

    if ($order->status !== Order::STATUS_PENDING) {
        return back()->withErrors(['order' => 'Hanya pembayaran pending yang dapat dibatalkan.']);
    }

    DB::transaction(function () use ($order, $user) {
        app(OrderInventoryService::class)->restoreForCancellation($order, $user);
    });

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
        'ingredientCount' => Ingredient::query()->count(),
        'lowStockCount' => Ingredient::query()->lowStock()->count(),
        'supplierCount' => Supplier::query()->count(),
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

    $query = StockMovement::query()->with(['ingredient', 'user'])->latest();
    $type = $request->string('type')->toString();
    $reason = $request->string('reason')->toString();

    if (in_array($type, [StockMovement::TYPE_IN, StockMovement::TYPE_OUT], true)) {
        $query->where('type', $type);
    }

    if (in_array($reason, [StockMovement::REASON_PURCHASE, StockMovement::REASON_SALE, StockMovement::REASON_DAMAGE, StockMovement::REASON_ADJUSTMENT], true)) {
        $query->where('reason', $reason);
    }

    return view('inventory.movements', [
        'movements' => $query->paginate(20)->withQueryString(),
    ]);
})->middleware('auth')->name('inventory.movements');
