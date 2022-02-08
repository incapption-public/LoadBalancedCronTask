<?php

namespace Incapption\DistributedCronjob\Abstracts;

abstract class DistributedCronjobWrapperAbstract
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
    public abstract function job(): bool;
}