<?php

namespace App\Exceptions;

use App\Utils\ExceptionMessage;

class ResourceNotFoundException extends BaseException
{
    protected $entity;

    public function __construct(string $entity, ExceptionMessage $exceptionMessage = null)
    {
        $this->entity = $entity;
        parent::__construct('ER003', $exceptionMessage);
    }

    public function getEntity()
    {
        return $this->entity;
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
                'entity' => $this->entity,
            ],
        ], $this->httpStatusCode);
    }
}
