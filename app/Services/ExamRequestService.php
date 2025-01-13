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

        $examItems = array_merge(
            $parameters['softTissues'] ?? [],
            $parameters['skullItems'] ?? [],
            $parameters['axialSkeletonItems'] ?? [],
        );

        $examItemsContent = '';

        if (count($examItems) > 0) {
            $examItemsContent = '<p>' . join('</p><p>', $examItems) . '</p>';
        }

        $observationsContent = '';

        if (($parameters['observations'] ?? '') !== '') {
            $observationsContent = '
                <section>
                    <h2>Observação</h2>
                    <p>' . $parameters['observations'] . '</p>
                </section>
            ';
        }

        $currentFullDate = now()->translatedFormat('d \d\e F \d\e Y');

        $template = str_replace('{{ veterinarianName }}', $parameters['veterinarianName'], $template);
        $template = str_replace('{{ veterinarianCrmv }}', $parameters['veterinarianCrmv'], $template);
        $template = str_replace('{{ veterinarianUf }}', $parameters['veterinarianUf'], $template);
        $template = str_replace('{{ patientName }}', $parameters['patientName'], $template);
        $template = str_replace('{{ patientSpecies }}', $parameters['patientSpecies'], $template);
        $template = str_replace('{{ patientBreed }}', $parameters['patientBreed'], $template);
        $template = str_replace('{{ patientSex }}', $parameters['patientSex'], $template);
        $template = str_replace('{{ patientTutor }}', $parameters['patientTutor'], $template);
        $template = str_replace('{{ examItems }}', $examItemsContent, $template);
        $template = str_replace('{{ observations }}', $observationsContent, $template);
        $template = str_replace('{{ currentFullDate }}', $currentFullDate, $template);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($template);
        $dompdf->render();

        $fileName = time() . '.pdf';
        Storage::put($fileName, $dompdf->output());

        return Storage::url($fileName);
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
                'patientAge' => ['required', 'integer', 'min:0'],
                'patientBreed' => ['required', 'string', 'min:1'],
                'patientTutor' => ['required', 'string', 'min:1'],
                'chip' => ['required', 'string', 'min:1'],
                'paymentMethod' => ['required', 'string', 'min:1'],
                'softTissues' => ['nullable', 'array'],
                'skullItems' => ['nullable', 'array'],
                'axialSkeletonItems' => ['nullable', 'array'],
                'appendicularSkeletonThoracicLimb' => ['nullable', 'string'],
                'appendicularSkeletonThoracicLimbOptions' => ['nullable', 'array'],
                'appendicularSkeletonPelvicLimb' => ['nullable', 'string'],
                'appendicularSkeletonPelvicLimbOptions' => ['nullable', 'array'],
                'appendicularSkeletonPelvis' => ['nullable', 'array'],
                'observations' => ['nullable', 'string'],
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
                'veterinarianName.required' => 'ER001',
                'veterinarianName.string' => 'ER001',
                'veterinarianCrmv.required' => 'ER001',
                'veterinarianCrmv.string' => 'ER001',
                'veterinarianUf.required' => 'ER001',
                'veterinarianUf.string' => 'ER001',
                'patientName.required' => 'ER001',
                'patientName.string' => 'ER001',
                'patientSpecies.required' => 'ER001',
                'patientSpecies.string' => 'ER001',
                'patientSex.required' => 'ER001',
                'patientSex.string' => 'ER001',
                'patientAge.required' => 'ER001',
                'patientAge.integer' => 'ER001',
                'patientAge.min' => 'ER001',
                'patientBreed.required' => 'ER001',
                'patientBreed.string' => 'ER001',
                'patientTutor.required' => 'ER001',
                'patientTutor.string' => 'ER001',
                'chip.required' => 'ER001',
                'chip.string' => 'ER001',
                'paymentMethod.required' => 'ER001',
                'paymentMethod.string' => 'ER001',
                'softTissues.array' => 'ER001',
                'skullItems.array' => 'ER001',
                'axialSkeletonItems.array' => 'ER001',
                'appendicularSkeletonThoracicLimb.string' => 'ER001',
                'appendicularSkeletonThoracicLimbOptions.array' => 'ER001',
                'appendicularSkeletonPelvicLimb.string' => 'ER001',
                'appendicularSkeletonPelvicLimbOptions.array' => 'ER001',
                'appendicularSkeletonPelvis.array' => 'ER001',
                'observations.string' => 'ER001',
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
