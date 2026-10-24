<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\StrategyLegService;
use App\Support\Database;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StrategyLegController
{
    private readonly StrategyLegService $service;

    public function __construct(?StrategyLegService $service = null)
    {
        $this->service = $service ?? new StrategyLegService(Database::defaultConnection());
    }

    public function index(Request $request, int $strategy): JsonResponse
    {
        $userId = (int) ($request->user()?->id ?? 0);
        if ($userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
        }

        return response()->json(['success' => true, 'data' => $this->service->listForStrategy($userId, $strategy)]);
    }

    public function store(Request $request, int $strategy): JsonResponse
    {
        $userId = (int) ($request->user()?->id ?? 0);
        if ($userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
        }

        $legs = $request->input('legs', []);
        if (!is_array($legs)) {
            return response()->json(['success' => false, 'message' => 'O payload deve conter um array de pernas.'], 422);
        }

        try {
            $result = $this->service->sync($userId, $strategy, $legs);
            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => $result['message'] ?? 'Falha ao persistir pernas.'], $result['status'] ?? 422);
            }

            return response()->json(['success' => true, 'data' => $result['data']]);
        } catch (\Throwable $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
