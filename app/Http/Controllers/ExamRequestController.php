<?php

namespace App\Http\Controllers;

use App\Services\ExamRequestService;
use Illuminate\Http\Request;

class ExamRequestController extends Controller
{
    private $examRequestService;

    public function __construct(ExamRequestService $examRequestService)
    {
        $this->examRequestService = $examRequestService;
    }

    public function generate(Request $request) {
        $examRequestUrl = $this->examRequestService->generate($request->all());

        return response()->json([
            'message' => [
                'serverMessage' => 'Report generated successfully',
                'clientMessage' => 'Arquivo do exame gerado com sucesso.',
            ],
            'data' => [
                'url' => $examRequestUrl,
            ],
        ]);
    }
}
