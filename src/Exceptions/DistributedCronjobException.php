<?php

namespace Incapption\DistributedCronjob\Exceptions;

use Exception;
use Throwable;

class DistributedCronjobException extends Exception
{
    public function __construct($message, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}