<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;

class LocalDailyTest extends TestSetup
{
    public function __construct()
    {
        parent::__construct();
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_daily()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 00:00:01')
            ->local()
            ->task((new DefaultCronTask()))
            ->daily()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_daily_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 00:01:00')
            ->local()
            ->task((new DefaultCronTask()))
            ->daily()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }


    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_daily_at_specific_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:42:00')
            ->local()
            ->task((new DefaultCronTask()))
            ->dailyAt('20:42')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:00:00')
            ->local()
            ->task((new DefaultCronTask()))
            ->dailyAt('20:00')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:59:00')
            ->local()
            ->task((new DefaultCronTask()))
            ->dailyAt('20:59')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_daily_at_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:28:00')
            ->local()
            ->task((new DefaultCronTask()))
            ->dailyAt('20:42')
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

    /** @test */
    public function local_task_daily_at_specific_minute_out_of_range()
    {
        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:59:02')
                ->task((new DefaultCronTask()))
                ->local()
                ->dailyAt('25:01')
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('a specific time must be in the format of "15:34" => H:i', $e->getMessage(),
                'task will not run');
        }

        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:59:02')
                ->task((new DefaultCronTask()))
                ->local()
                ->dailyAt('23:61')
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('a specific time must be in the format of "15:34" => H:i', $e->getMessage(),
                'task will not run');
        }

        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:59:02')
                ->task((new DefaultCronTask()))
                ->local()
                ->dailyAt('25:61')
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('a specific time must be in the format of "15:34" => H:i', $e->getMessage(),
                'task will not run');
        }

    }
}