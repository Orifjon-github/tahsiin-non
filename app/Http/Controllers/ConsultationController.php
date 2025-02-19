<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\Consultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    use Response;

    public function index(): JsonResponse
    {
        $appealTypes = Consultation::all();
        return $this->success($appealTypes);
    }

    public function show($id): JsonResponse
    {
        $appealType = Consultation::findOrFail($id);
        return $this->success($appealType);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $appealType = Consultation::create($request->all());

        return $this->success($appealType);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $appealType = Consultation::findOrFail($id);

        $appealType->update($request->all());

        return $this->success($appealType);
    }

    public function destroy($id): JsonResponse
    {
        $appealType = Consultation::findOrFail($id);
        $appealType->delete();
        return $this->success(['message' => 'Consultation deleted successfully.']);
    }
}
