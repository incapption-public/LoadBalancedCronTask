<?php

namespace Incapption\LoadBalancedCronTask\Abstracts;

abstract class CronTaskAbstract
{
    /**
     * @var ?string
     */
    public $name = null;

    public abstract function __construct();

    public function getName(): string
    {
        return $this->name;
    }

    public abstract function task(): bool;
}