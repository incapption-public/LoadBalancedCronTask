<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;

class LocalHourlyTest extends TestSetup
{
    public function __construct()
    {
        parent::__construct();
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_hourly()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:00:21')
            ->local()
            ->task((new DefaultCronTask()))
            ->hourly()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_hourly_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:01:00')
            ->local()
            ->task((new DefaultCronTask()))
            ->hourly()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_hourly_on_specific_minute()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:42:00')
            ->local()
            ->task((new DefaultCronTask()))
            ->hourlyAt(42)
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:00:00')
            ->local()
            ->task((new DefaultCronTask()))
            ->hourlyAt(0)
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:59:00')
            ->local()
            ->task((new DefaultCronTask()))
            ->hourlyAt(59)
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_hourly_on_specific_minute_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-02-08 20:28:00')
            ->local()
            ->task((new DefaultCronTask()))
            ->hourlyAt(31)
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

    /** @test */
    public function local_task_hourly_on_specific_minute_out_of_range()
    {
        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:59:02')
                ->task((new DefaultCronTask()))
                ->local()
                ->hourlyAt(60)
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('parameter must be an integer between 0 and 59.', $e->getMessage(),
                'task will not run');
        }

        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:59:02')
                ->task((new DefaultCronTask()))
                ->local()
                ->hourlyAt(-1)
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('parameter must be an integer between 0 and 59.', $e->getMessage(),
                'task will not run');
        }
    }
}