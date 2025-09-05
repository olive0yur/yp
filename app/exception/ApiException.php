<?php

namespace app\exception;

class ApiException extends \RuntimeException
{
    public function __construct($message, $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
