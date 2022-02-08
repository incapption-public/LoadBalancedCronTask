<?php

namespace Incapption\DistributedCronjob\Tests\Cronjobs;

use Incapption\DistributedCronjob\Abstracts\DistributedCronjobWrapperAbstract;

class DefaultCronjob extends DistributedCronjobWrapperAbstract
{
    public function __construct()
    {
        $this->name = 'DefaultJob';
    }

    public function job(): bool
    {
        return true;
    }
}