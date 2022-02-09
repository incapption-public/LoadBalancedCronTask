<?php

namespace Incapption\LoadBalancedCronTask\Tests\CronTasks;

use Incapption\LoadBalancedCronTask\Abstracts\CronTaskAbstract;

class DefaultCronTask extends CronTaskAbstract
{
    public function __construct()
    {
        $this->name = 'DefaultJob';
    }

    public function task(): bool
    {
        return true;
    }
}