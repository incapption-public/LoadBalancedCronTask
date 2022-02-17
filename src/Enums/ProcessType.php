<?php

namespace Incapption\LoadBalancedCronTask\Enums;

use MyCLabs\Enum\Enum;

/**
 * @method static self LOCAL()
 * @method static self LOAD_BALANCED()
 */
class ProcessType extends Enum
{
    private const LOCAL = 'local';
    private const LOAD_BALANCED = 'loadBalanced';
}