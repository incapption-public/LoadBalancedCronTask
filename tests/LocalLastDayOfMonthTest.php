<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;

class LocalLastDayOfMonthTest extends TestSetup
{
    public function __construct()
    {
        parent::__construct();
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_last_day_of_month()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-12-31 15:30:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonth('15:30')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-28 15:30:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonth('15:30')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        // leap year
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2020-02-29 15:30:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonth('15:30')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-04-30 00:00:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonth()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-08-31 23:59:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonth('23:59')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-09-30 23:59:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonth('23:59')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_last_day_of_month_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-09-29 23:59:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonth('23:59')
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-08-01 23:59:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonth('13:00')
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

}