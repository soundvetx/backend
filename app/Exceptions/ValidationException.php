<?php

namespace App\Exceptions;

use App\Utils\ExceptionMessage;
use App\Utils\Transformer;
use Exception;
use Illuminate\Validation\Validator;

class ValidationException extends BaseException
{
    protected $field;

    public function __construct(string $field, string $errorCode = 'ER000', ExceptionMessage $exceptionMessage = null)
    {
        $this->field = $field;
        parent::__construct($errorCode, $exceptionMessage);
    }

    public static function validator(Validator $validator, array $errors, Exception $previous = null)
    {
        $exceptionMessage = new ExceptionMessage([
            'server' => $validator->errors()->first(),
            'client' => 'Dados inválidos',
        ]);
        $failedRules = $validator->failed();
        $field = array_key_first($failedRules);
        $rule = array_key_first($failedRules[$field]);
        $errorCode = $errors[$field . '.' . Transformer::camelToSnakeCase($rule)];

        $validationException = new ValidationException($field, $errorCode, $exceptionMessage, $previous);

        return $validationException;
    }

    public function render()
    {
        return response()->json([
            'message' => [
                'serverMessage' => $this->exceptionMessage->getServerMessage(),
                'clientMessage' => $this->exceptionMessage->getClientMessage(),
            ],
            'error' => [
                'title' => $this->title,
                'code' => $this->errorCode,
                'field' => $this->field,
            ],
        ], $this->httpStatusCode);
    }
}
