<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;

class LocalLastDayOfMonthOffsetTest extends TestSetup
{
    public function __construct()
    {
        parent::__construct();
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_last_day_of_month_offset()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-12-29 15:30:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonthOffset(2, '15:30')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-04-30 11:00:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonthOffset(0, '11:00')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-05-21 11:00:39')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonthOffset(10, '11:00')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-26 15:30:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonthOffset(2, '15:30')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2024-02-27 15:30:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonthOffset(2, '15:30')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-12-31 00:00:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonthOffset()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-12-31 00:00:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonthOffset()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_last_day_of_month_offset_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-12-29 15:30:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonthOffset(3, '15:30')
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2024-02-27 15:30:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonthOffset(3, '15:30')
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-12-30 00:00:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->lastDayOfMonthOffset()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_last_day_of_month_offset_corrupt_format()
    {
        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-29 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->lastDayOfMonthOffset(3, '15')
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('a specific time must be in the format of "15:34" => H:i', $e->getMessage(),
                'task will not run');
        }

        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-29 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->lastDayOfMonthOffset(3, '15.00')
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('a specific time must be in the format of "15:34" => H:i', $e->getMessage(),
                'task will not run');
        }
    }

}