<?php

namespace MoloniES\Exceptions;

use Exception;

class MoloniException extends Exception
{
    protected $data;

    public function __construct(string $message, ?array $data = [])
    {
        $this->data = $data;

        parent::__construct($message);
    }

    public function getData(): array
    {
        return $this->data ?? [];
    }
}