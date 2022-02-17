<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;
use PHPUnit\Framework\TestCase;

class LoadBalancedCronTaskTest extends TestCase
{
    /**
     * @var string
     */
    private $sqlitedbPath;

    public function __construct()
    {
        parent::__construct();
        $this->sqlitedbPath = 'tests/SqliteDatabase/test.db';
        $this->sqlitedb = 'sqlite:tests/SqliteDatabase/test.db';
    }

    public function setUp(): void
    {
        file_put_contents($this->sqlitedbPath, '');

        $pdo = new \PDO($this->sqlitedb);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->query("
            CREATE TABLE IF NOT EXISTS `lbct_tasks` (
            `unique_hash` varchar(32) NOT NULL,
            `task_running` varchar(256),
            `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `worker` varchar(256),
            PRIMARY KEY (`unique_hash`)
            );
        ");
    }

    public function tearDown(): void
    {
        if (file_exists($this->sqlitedbPath))
        {
            unlink($this->sqlitedbPath);
        }
    }

    /** @test */
    public function local_task_no_multi_scheduling()
    {
        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:25:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->everyMinute()
                ->everyFiveMinutes()
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertEquals('this task is already scheduled. You can not chain schedule functions.', $e->getMessage(), 'task will not run');
        }
    }

    /** @test */
    public function local_task_no_process_type()
    {
        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:25:02')
                ->task((new DefaultCronTask()))
                ->everyMinute()
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertEquals('No processType is set. Choose between local() or loadBalanced()', $e->getMessage(), 'task will not run');
        }
    }

    /** @test */
    public function local_task_two_process_types()
    {
        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:25:02')
                ->task((new DefaultCronTask()))
                ->local()
                ->loadBalanced()
                ->everyMinute()
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertEquals('ProcessType already set => local(), loadBalanced()', $e->getMessage(), 'task will not run');
        }

        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:25:02')
                ->task((new DefaultCronTask()))
                ->loadBalanced()
                ->local()
                ->everyMinute()
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertEquals('ProcessType already set => local(), loadBalanced()', $e->getMessage(), 'task will not run');
        }
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
        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:59:02')
                ->task((new DefaultCronTask()))
                ->local()
                ->hourlyAt(60)
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertEquals('parameter must be an integer between 0 and 59.', $e->getMessage(), 'task will not run');
        }

        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-02-08 20:59:02')
                ->task((new DefaultCronTask()))
                ->local()
                ->hourlyAt(-1)
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertEquals('parameter must be an integer between 0 and 59.', $e->getMessage(), 'task will not run');
        }
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
        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-15 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->monthlyOn(15, '5:30')
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertEquals('a specific time must be in the format of "15:34" => H:i', $e->getMessage(), 'task will not run');
        }

        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-15 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->monthlyOn(15, '15:3')
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertEquals('a specific time must be in the format of "15:34" => H:i', $e->getMessage(), 'task will not run');
        }

        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-15 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->monthlyOn(15, '12')
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertEquals('a specific time must be in the format of "15:34" => H:i', $e->getMessage(), 'task will not run');
        }

        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-15 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->monthlyOn(0, '15:30')
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertEquals('first parameter must be an integer between 1 and 31.', $e->getMessage(), 'task will not run');
        }

        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment(null, '2022-12-15 15:30:02')
                ->local()
                ->task((new DefaultCronTask()))
                ->monthlyOn(32, '15:30')
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertEquals('first parameter must be an integer between 1 and 31.', $e->getMessage(), 'task will not run');
        }
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
    public function load_balanced_task_every_minute_table_does_not_exist()
    {
        file_put_contents($this->sqlitedbPath, '');

        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment($this->sqlitedb, '2022-02-08 20:26:00')
                ->loadBalanced()
                ->task((new DefaultCronTask()))
                ->everyMinute()
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertStringContainsString('no such table: lbct_tasks', $e->getMessage(), 'Assert the task will not run because lbct_tasks table does not exist');
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
    public function load_balanced_task_hourly_job_runs_just_once_with_multiple_nodes()
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