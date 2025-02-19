<?php

namespace App\Http\Controllers;

use App\Services\SupplierService;

class TelegramBotController extends Controller
{
    private SupplierService $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }
    public function supplierStart(): bool
    {
        return $this->supplierService->start();
    }
}
