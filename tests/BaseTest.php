<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;

class BaseTest extends TestSetup
{
    public function __construct()
    {
        parent::__construct();
    }

    /** @test */
    public function local_task_no_multi_scheduling()
    {
        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:25:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->everyMinute()
                ->everyFiveMinutes()
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('this task is already scheduled. You can not chain schedule functions.',
                $e->getMessage(), 'task will not run');
        }
    }

    /** @test */
    public function local_task_no_process_type()
    {
        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:25:02')
                ->task((new DefaultCronTask()))
                ->everyMinute()
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('No processType is set. Choose between local() or loadBalanced()', $e->getMessage(),
                'task will not run');
        }
    }

    /** @test */
    public function local_task_two_process_types()
    {
        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:25:02')
                ->task((new DefaultCronTask()))
                ->local()
                ->loadBalanced()
                ->everyMinute()
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('ProcessType already set => local(), loadBalanced()', $e->getMessage(),
                'task will not run');
        }

        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:25:02')
                ->task((new DefaultCronTask()))
                ->loadBalanced()
                ->local()
                ->everyMinute()
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('ProcessType already set => local(), loadBalanced()', $e->getMessage(),
                'task will not run');
        }
    }
}