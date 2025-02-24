<?php

namespace App\Http\Controllers;

use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupplierController extends Controller
{
    private SupplierService $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }
    public function start(): bool
    {
        return $this->supplierService->start();
    }

    public function sendPaymentInfo(Request $request): JsonResponse
    {
        return $this->supplierService->sendPaymentInfo($request->supplier_id, $request->utid);
    }
}
