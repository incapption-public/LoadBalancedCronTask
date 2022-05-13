<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;

class LoadBalancedMinutelyTest extends TestSetup
{
    public function __construct()
    {
        parent::__construct();
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_every_minute_table_does_not_exist()
    {
        file_put_contents($this->sqlitedbPath, '');

        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment($this->sqlitedb, '2022-02-08 20:26:00')
                ->loadBalanced()
                ->task((new DefaultCronTask()))
                ->everyMinute()
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertStringContainsString('no such table: lbct_tasks', $e->getMessage(),
                'Assert the task will not run because lbct_tasks table does not exist');
        }
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_every_minute()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 20:26:30')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->everyMinute()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function load_balanced_task_every_five_minutes_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment($this->sqlitedb, '2022-02-08 20:26:00')
            ->loadBalanced()
            ->task((new DefaultCronTask()))
            ->everyFiveMinutes()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }
}