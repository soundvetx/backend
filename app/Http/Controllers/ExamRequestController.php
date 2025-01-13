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
                'serverMessage' => 'Exam request file generated successfully',
                'clientMessage' => 'Arquivo do exame gerado com sucesso.',
            ],
            'data' => [
                'url' => $examRequestUrl,
            ],
        ]);
    }

    public function send(Request $request) {
        $this->examRequestService->send($request->all());

        return response()->json([
            'message' => [
                'serverMessage' => 'Exam request file sent successfully',
                'clientMessage' => 'Requisição de exame enviada com sucesso.',
            ],
        ]);
    }
}
