<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;

class LoadBalancedHourlyTest extends TestSetup
{
    public function __construct()
    {
        parent::__construct();
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_hourly()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 20:00:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->hourly()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_hourly_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 20:01:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->hourly()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_monthly()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-07-01 00:00:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->monthly()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_hourly_runs_just_once_with_multiple_nodes()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 10:00:13')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->hourly()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');


        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 10:00:13')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->hourly()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');


        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 10:00:16')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->hourly()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');


        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 10:00:41')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->hourly()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }
}