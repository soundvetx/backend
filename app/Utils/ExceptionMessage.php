<?php

namespace App\Utils;

class ExceptionMessage
{
    private $server;
    private $client;

    public function __construct(array $messages)
    {
        $this->server = $messages['server'];
        $this->client = $messages['client'];
    }

    public function getServerMessage()
    {
        return $this->server;
    }

    public function getClientMessage()
    {
        return $this->client;
    }
}
