<?php

namespace Incapption\LoadBalancedCronTask\Exceptions;

use Exception;
use Throwable;

class LoadBalancedCronTaskException extends Exception
{
    public function __construct($message, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}