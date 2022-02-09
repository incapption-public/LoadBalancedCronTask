<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use Incapption\LoadBalancedCronTask\LoadBalancedCronTask;
use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\Tests\CronTasks\DefaultCronTask;
use PHPUnit\Framework\TestCase;

class LoadBalancedCronTaskTest extends TestCase
{
    public function setUp(): void
    {
        file_put_contents('tests/SqliteDatabase/test.db', '');

        $pdo = new \PDO('sqlite:tests/SqliteDatabase/test.db');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->query("
            CREATE TABLE IF NOT EXISTS `lbct_tasks` (
            `task_running` varchar(256) NOT NULL,
            `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`task_running`));
        ");
    }

    public function tearDown(): void
    {
        if (file_exists('tests/SqliteDatabase/test.db'))
        {
            unlink('tests/SqliteDatabase/test.db');
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
            ->mockTestEnvironment(null, '2022-02-08 20:00:21', 30)
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
            ->mockTestEnvironment(null, '2022-02-08 20:01:00', 0)
            ->local()
            ->task((new DefaultCronTask()))
            ->hourly()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function distributed_task_every_minute_table_does_not_exist()
    {
        file_put_contents('tests/SqliteDatabase/test.db', '');

        try
        {
            $response = (new LoadBalancedCronTask())
                ->mockTestEnvironment('sqlite:tests/SqliteDatabase/test.db', '2022-02-08 20:26:00', 0)
                ->distributed()
                ->task((new DefaultCronTask()))
                ->everyMinute()
                ->run();
        }
        catch(LoadBalancedCronTaskException $e)
        {
            $this->assertStringContainsString('no such table: lbct_tasks', $e->getMessage(), 'Assert the task will not run because dcj_running_cronjobs table does not exist');
        }
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function distributed_task_every_minute()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment('sqlite:tests/SqliteDatabase/test.db', '2022-02-08 20:26:30', 30)
            ->distributed()
            ->task((new DefaultCronTask()))
            ->everyMinute()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function distributed_task_every_five_minutes_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment('sqlite:tests/SqliteDatabase/test.db', '2022-02-08 20:26:00', 0)
            ->distributed()
            ->task((new DefaultCronTask()))
            ->everyFiveMinutes()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function distributed_task_hourly()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment('sqlite:tests/SqliteDatabase/test.db', '2022-02-08 20:00:00', 0)
            ->distributed()
            ->task((new DefaultCronTask()))
            ->hourly()
            ->run();

        $this->assertEquals(true, $response, 'Assert the task will run');
    }

    /** @test
     * @throws LoadBalancedCronTaskException
     */
    public function distributed_task_hourly_not_in_time()
    {
        $response = (new LoadBalancedCronTask())
            ->mockTestEnvironment('sqlite:tests/SqliteDatabase/test.db', '2022-02-08 20:01:00', 0)
            ->distributed()
            ->task((new DefaultCronTask()))
            ->hourly()
            ->run();

        $this->assertEquals(false, $response, 'Assert the task will not run');
    }
}