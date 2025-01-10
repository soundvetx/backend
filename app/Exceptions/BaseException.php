<?php

namespace App\Exceptions;

use App\Utils\ExceptionMessage;
use Exception;

class BaseException extends Exception
{
    protected $errorCode;
    protected $title;
    protected $exceptionMessage;
    protected $httpStatusCode;

    public function __construct(string $errorCode = 'ER000', ExceptionMessage $exceptionMessage = null)
    {
        $error = config('errors.' . $errorCode);

        if (empty($error)) {
            $error = config('errors.ER000');
        }

        $this->errorCode = $error['error_code'];
        $this->title = $error['title'];
        $this->exceptionMessage = $exceptionMessage ?? new ExceptionMessage($error['message']);
        $this->httpStatusCode = $error['http_status_code'];

        parent::__construct($this->exceptionMessage->getServerMessage(), $this->httpStatusCode);
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }

    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
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
            ],
        ], $this->httpStatusCode);
    }
}
