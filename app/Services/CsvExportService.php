<?php

namespace App\Services;

class CsvExportService
{
    public function safeCell(mixed $value): mixed
    {
        if (! is_string($value) || preg_match('/^[\x00-\x20]*[=+\-@]/', $value) !== 1) {
            return $value;
        }

        return "'".$value;
    }
}
