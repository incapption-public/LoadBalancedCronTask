<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;

class LoadBalancedMonthlyTest extends TestSetup
{
    public function __construct()
    {
        parent::__construct();
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
    public function load_balanced_task_monthly_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-07-01 01:00:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->monthly()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-07-02 00:00:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->monthly()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_monthlyOn()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-07-20 13:30:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->monthlyOn(20, '13:30')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_monthlyOn_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-07-01 00:00:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->monthlyOn(20, '13:30')
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-07-20 13:29:59')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->monthlyOn(20, '13:30')
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-07-20 13:31:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->monthlyOn(20, '13:30')
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }
}