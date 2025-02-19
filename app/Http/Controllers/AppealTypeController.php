<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\AppealType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppealTypeController extends Controller
{
    use Response;

    public function index(): JsonResponse
    {
        $appealTypes = AppealType::all();
        return $this->success($appealTypes);
    }

    public function show($id): JsonResponse
    {
        $appealType = AppealType::findOrFail($id);
        return $this->success($appealType);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'name_ru' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
        ]);

        $appealType = AppealType::create([
            'name' => $request->name,
            'name_ru' => $request->name_ru,
            'name_en' => $request->name_en,
        ]);

        return $this->success($appealType); // 201 = Created
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'name_ru' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'enable' => 'nullable|in:0,1',
        ]);

        $appealType = AppealType::findOrFail($id);

        $appealType->update([
            'name' => $request->name,
            'name_ru' => $request->name_ru,
            'name_en' => $request->name_en,
            'enable' => $request->enable ?? $appealType->enable,
        ]);

        return $this->success($appealType);
    }

    public function destroy($id): JsonResponse
    {
        $appealType = AppealType::findOrFail($id);
        $appealType->delete();
        return $this->success(['message' => 'Appeal type deleted successfully.']);
    }
}
