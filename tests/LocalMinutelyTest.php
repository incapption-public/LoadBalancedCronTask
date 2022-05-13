<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;

class LocalMinutelyTest extends TestSetup
{
    public function __construct()
    {
        parent::__construct();
    }

    /** @test */
    public function local_task_every_minute()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:25:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->everyMinute()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_every_five_minute()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:25:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->everyFiveMinutes()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_every_five_minute_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:26:15')
            ->local()
            ->task((new DefaultCronTask()))
            ->everyFiveMinutes()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }
}