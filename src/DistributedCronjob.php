<?php

namespace Incapption\DistributedCronjob;

use DateTime;
use Incapption\DistributedCronjob\Abstracts\DistributedCronjobWrapperAbstract;
use Incapption\DistributedCronjob\Enums\ProcessType;
use Incapption\DistributedCronjob\Exceptions\DistributedCronjobException;
use MyCLabs\Enum\Enum;

class DistributedCronjob
{
    /**
     * Seconds waiting before releasing the lock for the job
     * @var int
     */
    private $asyncBuffer = 30;

    /**
     * @var Enum
     */
    private $type;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @var ?DistributedCronjobWrapperAbstract
     */
    private $job;

    /**
     * @var bool
     */
    private $inTime;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ?String
     */
    private $pdo = null;

    /**
     * @throws DistributedCronjobException
     */
    public function __construct()
    {
        $this->timezone = 'UTC';
        $this->job = null;
        $this->inTime = false;

        self::setDateTime();
    }

    private function isInTime(): bool
    {
        return $this->inTime;
    }

    private function setInTime(bool $bool): void
    {
        $this->inTime = true;
    }


    /**
     * @param string $dateTime
     * @throws DistributedCronjobException
     */
    private function setDateTime(string $dateTime = 'now'): void
    {
        try {
            $this->dateTime = new DateTime($dateTime, new \DateTimeZone($this->timezone));
        }
        catch(\Exception $e)
        {
            throw new DistributedCronjobException($e->getMessage());
        }
    }

    /**
     * @throws DistributedCronjobException
     */
    private function checkMysqlTableExists(): void
    {
        // check if dcj_cronjob table exists in database
        try {
            $this->pdo->query("SELECT * FROM dcj_running_cronjobs LIMIT 1");
        }
        catch (\PDOException $e)
        {
            throw new DistributedCronjobException($e->getMessage());
        }
    }

    public function mockTestEnvironment(?string $sqlite, string $dateTime = 'now', int $asyncBuffer = 2)
    {
        if(!is_null($sqlite))
        {
            try {
                $this->pdo = new \PDO($sqlite);
                $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            }
            catch(\PDOException $e)
            {
                throw new DistributedCronjobException($e->getMessage());
            }

            self::checkMysqlTableExists();
        }

        self::setDateTime($dateTime);

        $this->asyncBuffer = $asyncBuffer;

        return $this;
    }

    /**
     * @param string $timezone
     * @throws DistributedCronjobException
     */
    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
        self::setDateTime();
    }

    public function local(): DistributedCronjob
    {
        $this->type = ProcessType::LOCAL();

        return $this;
    }

    public function distributed(): DistributedCronjob
    {
        $this->type = ProcessType::DISTRIBUTED();

        return $this;
    }

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $database
     * @param int $port
     * @return $this
     * @throws DistributedCronjobException
     */
    public function mysql(string $host, string $user, string $password, string $database, int $port = 3306): DistributedCronjob
    {
        try {
            $this->pdo = new \PDO('mysql:host='.$host.';port='.$port.';dbname='.$database, $user, $password);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("set names utf8");
        }
        catch(\PDOException $e)
        {
            throw new DistributedCronjobException($e->getMessage());
        }

        self::checkMysqlTableExists();

        return $this;
    }

    public function job(DistributedCronjobWrapperAbstract $job): DistributedCronjob
    {
        $this->job = $job;

        return $this;
    }

    public function everyMinute(): DistributedCronjob
    {
        self::setInTime(true);
        return $this;
    }

    public function everyFiveMinutes(): DistributedCronjob
    {
        $currentMinute = intval($this->dateTime->format('i'));

        if($currentMinute % 5 === 0)
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function run(): bool
    {
        if(self::isInTime() === false)
            return false;

        if ($this->type->getValue() === ProcessType::LOCAL()->getValue())
        {
            return $this->job->job();
        }
        else if ($this->type->getValue() === ProcessType::DISTRIBUTED()->getValue())
        {
            // check if job has a name
            if (empty($this->job->getName()) || !is_string($this->job->getName()))
            {
                throw new DistributedCronjobException('This job has no name. A name must be set.');
            }

            // try to insert the job into dcj_running_cronjobs
            try {
                $query = $this->pdo->prepare('INSERT INTO dcj_running_cronjobs (running_job) VALUES (:job_name)');
                $query->execute([
                    'job_name' => $this->job->getName()
                ]);
            }
            catch(\PDOException $e)
            {
                /**
                 * 23000 = SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed
                 */
                if ($e->getCode() == "23000")
                {
                    return false;
                }

                throw new DistributedCronjobException($e->getMessage());
            }

            $jobResponse = $this->job->job();

            // delete job from dcj_running_cronjobs
            try
            {
                // wait 30 seconds
                sleep($this->asyncBuffer);

                $query = $this->pdo->prepare('DELETE FROM dcj_running_cronjobs WHERE running_job = :job_name');
                $query->execute([
                    'job_name' => $this->job->getName()
                ]);
            }
            catch(\PDOException $e)
            {
                throw new DistributedCronjobException($e->getMessage());
            }

            return $jobResponse;
        }

        return false;
    }
}