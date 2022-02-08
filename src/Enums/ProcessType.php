<?php

namespace Incapption\DistributedCronjobs\Enums;

use MyCLabs\Enum\Enum;

/**
 * @method static self LOCAL()
 * @method static self DISTRIBUTED()
 */
class ProcessType extends Enum
{
    private const LOCAL = 'local';
    private const DISTRIBUTED = 'distributed';
}