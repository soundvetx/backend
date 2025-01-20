<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Mail\ExamRequestMail;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ExamRequestService
{
    public function generate(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('generate'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('generate'));
        }

        $template = File::get(resource_path('templates/ExamRequest.html'));

        $logoUrl = Storage::url('logos/logo-full-primary.png');

        $paymentMethodContent = $parameters['paymentMethod'];

        if ($parameters['paymentMethod'] === 'Pet Love') {
            $paymentMethodContent .= ' - CHIP: ' . $parameters['chip'];
        }

        if ($parameters['patientAgePeriod'] === 'Anos' && $parameters['patientAge'] == 1) {
            $parameters['patientAgePeriod'] = 'Ano';
        }

        if ($parameters['patientAgePeriod'] === 'Meses' && $parameters['patientAge'] == 1) {
            $parameters['patientAgePeriod'] = 'Mês';
        }

        $patientAgeContent = $parameters['patientAge'] . ' ' .  strtolower($parameters['patientAgePeriod']);

        $examsWithoutContrast = [];

        if (!empty($parameters['softTissuesWithoutContrast'])) {
            $examsWithoutContrast[] = "<p><strong class='text-color'>Tecidos Moles:</strong> " . join(', ', $parameters['softTissuesWithoutContrast']) . '</p>';
        }

        if (!empty($parameters['skullItems'])) {
            $examsWithoutContrast[] = "<p><strong class='text-color'>Crânio:</strong> " . join(', ', $parameters['skullItems']) . '</p>';
        }

        if (!empty($parameters['axialSkeletonItems'])) {
            $examsWithoutContrast[] = "<p><strong class='text-color'>Esqueleto Axial:</strong> " . join(', ', $parameters['axialSkeletonItems']) . '</p>';
        }

        if (!empty($parameters['appendicularSkeletonThoracicLimb'])) {
            if ($parameters['paymentMethod'] === 'Pet Love') {
                $examsWithoutContrast[] = "<p><strong class='text-color'>Membro Torácico " . $parameters['appendicularSkeletonThoracicLimb'] . ':</strong> ' . join(', ', $parameters['appendicularSkeletonThoracicLimbOptions']) . '</p>';
            } else {
                $examsWithoutContrast[] = "<p><strong class='text-color'>Membro Torácico " . $parameters['appendicularSkeletonThoracicLimb'] . '</strong></p>';
            }
        }

        if (!empty($parameters['appendicularSkeletonPelvicLimb'])) {
            if ($parameters['paymentMethod'] === 'Pet Love') {
                $examsWithoutContrast[] = "<p><strong class='text-color'>Membro Pélvico " . $parameters['appendicularSkeletonPelvicLimb'] . ':</strong> ' . join(', ', $parameters['appendicularSkeletonPelvicLimbOptions']) . '</p>';
            } else {
                $examsWithoutContrast[] = "<p><strong class='text-color'>Membro Pélvico " . $parameters['appendicularSkeletonPelvicLimb'] . '</strong></p>';
            }
        }

        if (!empty($parameters['appendicularSkeletonPelvis'])) {
            $examsWithoutContrast[] = "<p><strong class='text-color'>Pelve:</strong> " . join(', ', $parameters['appendicularSkeletonPelvis']) . '</p>';
        }

        $examsWithoutContrastContent = '';

        if (!empty($examsWithoutContrast)) {
            $examsWithoutContrastContent = '
                <div class="container">
                    <h4 class="title">Imagem - Raio X - Sem contraste</h4>

                    <div class="list primary-color">' . join('', $examsWithoutContrast) . '</div>
                </div>
            ';
        }

        $examsWithContrast = [];

        if (!empty($parameters['softTissuesWithContrast'])) {
            $examsWithContrast[] = "<p><strong class='text-color'>Tecidos Moles:</strong> " . join(', ', $parameters['softTissuesWithContrast']) . '</p>';
        }

        $examsWithContrastContent = '';

        if (!empty($examsWithContrast)) {
            $examsWithContrastContent = '
                <div class="container">
                    <h4 class="title">Imagem - Raio X - Com contraste</h4>

                    <div class="list primary-color">' . join('', $examsWithContrast) . '</div>
                </div>
            ';
        }

        $observationsContent = '<p>' . $parameters['observations'] . '</p>';
        $currentFullDate = now()->translatedFormat('d \d\e F \d\e Y');

        $template = str_replace('{{ logoUrl }}', $logoUrl, $template);
        $template = str_replace('{{ paymentMethod }}', $paymentMethodContent, $template);
        $template = str_replace('{{ veterinarianClinic }}', $parameters['veterinarianClinic'], $template);
        $template = str_replace('{{ veterinarianName }}', $parameters['veterinarianName'], $template);
        $template = str_replace('{{ veterinarianCrmv }}', $parameters['veterinarianCrmv'], $template);
        $template = str_replace('{{ veterinarianUf }}', $parameters['veterinarianUf'], $template);
        $template = str_replace('{{ patientName }}', $parameters['patientName'], $template);
        $template = str_replace('{{ patientAge }}', $patientAgeContent, $template);
        $template = str_replace('{{ patientSpecies }}', $parameters['patientSpecies'], $template);
        $template = str_replace('{{ patientBreed }}', $parameters['patientBreed'], $template);
        $template = str_replace('{{ patientSex }}', $parameters['patientSex'], $template);
        $template = str_replace('{{ patientTutor }}', $parameters['patientTutor'], $template);
        $template = str_replace('{{ examsWithoutContrast }}', $examsWithoutContrastContent, $template);
        $template = str_replace('{{ examsWithContrast }}', $examsWithContrastContent, $template);
        $template = str_replace('{{ observations }}', $observationsContent, $template);
        $template = str_replace('{{ currentFullDate }}', $currentFullDate, $template);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($template);
        $dompdf->render();

        $filePath = 'exam-requests/' . time() . '.pdf';
        Storage::put($filePath, $dompdf->output());

        return Storage::url($filePath);
    }

    public function send(array $parameters)
    {
        $validator = Validator::make($parameters, $this->getValidations('send'));

        if ($validator->fails()) {
            throw ValidationException::validator($validator, $this->getErrorCodes('send'));
        }

        Mail::to(env('MAIL_FROM_ADDRESS'))->send(new ExamRequestMail(
            $parameters['veterinarianClinic'],
            $parameters['veterinarianName'],
            $parameters['patientName'],
            $parameters['examRequestUrl'],
        ));

        return true;
    }

    private function getValidations(string $method)
    {
        return match ($method) {
            'generate' => [
                'veterinarianClinic' => ['required', 'string', 'min:1'],
                'veterinarianName' => ['required', 'string', 'min:1'],
                'veterinarianCrmv' => ['required', 'string', 'min:1'],
                'veterinarianUf' => ['required', 'string', 'min:1'],
                'patientName' => ['required', 'string', 'min:1'],
                'patientSpecies' => ['required', 'string', 'min:1'],
                'patientSex' => ['required', 'string', 'min:1'],
                'patientAge' => ['required', 'integer', 'min:1'],
                'patientAgePeriod' => ['required', 'string', 'min:1'],
                'patientBreed' => ['required', 'string', 'min:1'],
                'patientTutor' => ['required', 'string', 'min:1'],
                'chip' => ['required_if:paymentMethod,Pet Love'],
                'paymentMethod' => ['required', 'string', 'min:1'],
                'softTissuesWithContrast' => ['nullable', 'array'],
                'softTissuesWithoutContrast' => ['nullable', 'array'],
                'skullItems' => ['nullable', 'array'],
                'axialSkeletonItems' => ['nullable', 'array'],
                'appendicularSkeletonThoracicLimb' => ['nullable', 'string'],
                'appendicularSkeletonThoracicLimbOptions' => ['nullable', 'array'],
                'appendicularSkeletonPelvicLimb' => ['nullable', 'string'],
                'appendicularSkeletonPelvicLimbOptions' => ['nullable', 'array'],
                'appendicularSkeletonPelvis' => ['nullable', 'array'],
                'observations' => ['required', 'string', 'min:1'],
            ],
            'send' => [
                'veterinarianClinic' => ['required', 'string', 'min:1'],
                'veterinarianName' => ['required', 'string', 'min:1'],
                'patientName' => ['required', 'string', 'min:1'],
                'examRequestUrl' => ['required', 'string', 'min:1'],
            ],
            default => [],
        };
    }

    private function getErrorCodes(string $method)
    {
        return match ($method) {
            'generate' => [
                'veterinarianClinic.required' => 'ER001',
                'veterinarianClinic.string' => 'ER001',
                'veterinarianClinic.min' => 'ER001',
                'veterinarianName.required' => 'ER001',
                'veterinarianName.string' => 'ER001',
                'veterinarianName.min' => 'ER001',
                'veterinarianCrmv.required' => 'ER001',
                'veterinarianCrmv.string' => 'ER001',
                'veterinarianCrmv.min' => 'ER001',
                'veterinarianUf.required' => 'ER001',
                'veterinarianUf.string' => 'ER001',
                'veterinarianUf.min' => 'ER001',
                'patientName.required' => 'ER001',
                'patientName.string' => 'ER001',
                'patientName.min' => 'ER001',
                'patientSpecies.required' => 'ER001',
                'patientSpecies.string' => 'ER001',
                'patientSpecies.min' => 'ER001',
                'patientSex.required' => 'ER001',
                'patientSex.string' => 'ER001',
                'patientSex.min' => 'ER001',
                'patientAge.required' => 'ER001',
                'patientAge.integer' => 'ER001',
                'patientAge.min' => 'ER001',
                'patientAgePeriod.required' => 'ER001',
                'patientAgePeriod.string' => 'ER001',
                'patientAgePeriod.min' => 'ER001',
                'patientBreed.required' => 'ER001',
                'patientBreed.string' => 'ER001',
                'patientBreed.min' => 'ER001',
                'patientTutor.required' => 'ER001',
                'patientTutor.string' => 'ER001',
                'patientTutor.min' => 'ER001',
                'chip.required_if' => 'ER001',
                'paymentMethod.required' => 'ER001',
                'paymentMethod.string' => 'ER001',
                'paymentMethod.min' => 'ER001',
                'softTissuesWithContrast.array' => 'ER001',
                'softTissuesWithoutContrast.array' => 'ER001',
                'skullItems.array' => 'ER001',
                'axialSkeletonItems.array' => 'ER001',
                'appendicularSkeletonThoracicLimb.string' => 'ER001',
                'appendicularSkeletonThoracicLimbOptions.array' => 'ER001',
                'appendicularSkeletonPelvicLimb.string' => 'ER001',
                'appendicularSkeletonPelvicLimbOptions.array' => 'ER001',
                'appendicularSkeletonPelvis.array' => 'ER001',
                'observations.required' => 'ER001',
                'observations.string' => 'ER001',
                'observations.min' => 'ER001',
            ],
            'send' => [
                'veterinarianClinic.required' => 'ER001',
                'veterinarianClinic.string' => 'ER001',
                'veterinarianClinic.min' => 'ER001',
                'veterinarianName.required' => 'ER001',
                'veterinarianName.string' => 'ER001',
                'veterinarianName.min' => 'ER001',
                'patientName.required' => 'ER001',
                'patientName.string' => 'ER001',
                'patientName.min' => 'ER001',
                'examRequestUrl.required' => 'ER001',
                'examRequestUrl.string' => 'ER001',
                'examRequestUrl.min' => 'ER001',
            ],
            default => [],
        };
    }
}
