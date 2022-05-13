<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;

class LoadBalancedDailyTest extends TestSetup
{
    public function __construct()
    {
        parent::__construct();
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_daily()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 00:00:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->daily()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_daily_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 00:01:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->daily()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_daily_at()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 17:12:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->dailyAt('17:12')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 17:13:33')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->dailyAt('17:13')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_daily_at_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 17:11:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->dailyAt('17:12')
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 17:13:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->dailyAt('17:12')
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

}