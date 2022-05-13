<?php

namespace Incapption\LoadBalancedCronTask\Tests;


use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;

class LocalMonthlyTest extends TestSetup
{
    public function __construct()
    {
        parent::__construct();
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_monthly()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-12-01 00:00:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->monthly()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-11-01 00:00:35')
            ->local()
            ->task((new DefaultCronTask()))
            ->monthly()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_monthly_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-12-02 00:00:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->monthly()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-11-01 00:01:35')
            ->local()
            ->task((new DefaultCronTask()))
            ->monthly()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_monthlyOn()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-12-15 15:30:02')
            ->local()
            ->task((new DefaultCronTask()))
            ->monthlyOn(15, '15:30')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');

        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment(null, '2022-11-01 00:30:35')
            ->local()
            ->task((new DefaultCronTask()))
            ->monthlyOn(1, '00:30')
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function local_task_monthlyOn_corrupt_format()
    {
        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-15 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->monthlyOn(15, '5:30')
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('a specific time must be in the format of "15:34" => H:i', $e->getMessage(),
                'task will not run');
        }

        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-15 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->monthlyOn(15, '15:3')
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('a specific time must be in the format of "15:34" => H:i', $e->getMessage(),
                'task will not run');
        }

        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-15 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->monthlyOn(15, '12')
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('a specific time must be in the format of "15:34" => H:i', $e->getMessage(),
                'task will not run');
        }

        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-15 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->monthlyOn(0, '15:30')
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('first parameter must be an integer between 1 and 31.', $e->getMessage(),
                'task will not run');
        }

        try {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-15 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->monthlyOn(32, '15:30')
                ->run();
        } catch (LoadBalancedCronTaskException $e) {
            $this->assertEquals('first parameter must be an integer between 1 and 31.', $e->getMessage(),
                'task will not run');
        }
    }
}