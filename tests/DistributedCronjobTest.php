<?php

namespace Incapption\DistributedCronjob\Tests;

use Incapption\DistributedCronjob\DistributedCronjob;
use Incapption\DistributedCronjob\Exceptions\DistributedCronjobException;
use Incapption\DistributedCronjob\Tests\Cronjobs\DefaultCronjob;
use PHPUnit\Framework\TestCase;

class DistributedCronjobTest extends TestCase
{
    public function setUp(): void
    {
        file_put_contents('tests/SqliteDatabase/test.db', '');

        $pdo = new \PDO('sqlite:tests/SqliteDatabase/test.db');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->query("
            CREATE TABLE IF NOT EXISTS `dcj_running_cronjobs` (
            `running_job` varchar(256) NOT NULL,
            `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`running_job`)
            );
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
    public function local_job_every_minute()
    {
        $response = (new DistributedCronjob())
            ->local()
            ->job((new DefaultCronjob()))
            ->everyMinute()
            ->run();

        $this->assertEquals(true, $response, 'Assert the job will run');
    }

    /** @test
     * @throws DistributedCronjobException
     */
    public function local_job_every_five_minute()
    {
        $response = (new DistributedCronjob())
            ->mockTestEnvironment(null, '2022-02-08 20:25:15')
            ->local()
            ->job((new DefaultCronjob()))
            ->everyFiveMinutes()
            ->run();

        $this->assertEquals(true, $response, 'Assert the job will run');
    }

    /** @test
     * @throws DistributedCronjobException
     */
    public function local_job_every_five_minute_not_in_time()
    {
        $response = (new DistributedCronjob())
            ->mockTestEnvironment(null, '2022-02-08 20:26:15')
            ->local()
            ->job((new DefaultCronjob()))
            ->everyFiveMinutes()
            ->run();

        $this->assertEquals(false, $response, 'Assert the job will not run');
    }

    /** @test
     * @throws DistributedCronjobException
     */
    public function distributed_job_every_minute_table_does_not_exist()
    {
        file_put_contents('tests/SqliteDatabase/test.db', '');

        try
        {
            $response = (new DistributedCronjob())
                ->mockTestEnvironment('sqlite:tests/SqliteDatabase/test.db', '2022-02-08 20:26:15', 0)
                ->distributed()
                ->job((new DefaultCronjob()))
                ->everyMinute()
                ->run();
        }
        catch(DistributedCronjobException $e)
        {
            $this->assertStringContainsString('no such table: dcj_running_cronjobs', $e->getMessage(), 'Assert the job will not run because dcj_running_cronjobs table does not exist');
        }
    }

    /** @test
     * @throws DistributedCronjobException
     */
    public function distributed_job_every_minute()
    {
        $response = (new DistributedCronjob())
            ->mockTestEnvironment('sqlite:tests/SqliteDatabase/test.db', '2022-02-08 20:26:15', 0)
            ->distributed()
            ->job((new DefaultCronjob()))
            ->everyMinute()
            ->run();

        $this->assertEquals(true, $response, 'Assert the job will run');
    }

    /** @test
     * @throws DistributedCronjobException
     */
    public function distributed_job_every_five_minutes_not_in_time()
    {
        $response = (new DistributedCronjob())
            ->mockTestEnvironment('sqlite:tests/SqliteDatabase/test.db', '2022-02-08 20:26:15', 0)
            ->distributed()
            ->job((new DefaultCronjob()))
            ->everyFiveMinutes()
            ->run();

        $this->assertEquals(false, $response, 'Assert the job will not run');
    }
}