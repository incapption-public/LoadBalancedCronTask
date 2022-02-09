<?php

namespace Incapption\LoadBalancedCronTask;

use DateTime;
use Incapption\LoadBalancedCronTask\Abstracts\CronTaskAbstract;
use Incapption\LoadBalancedCronTask\Enums\ProcessType;
use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use MyCLabs\Enum\Enum;

class LoadBalancedCronTask
{
    /**
     * @var bool
     */
    private $isTest = false;

    /**
     * Seconds waiting before releasing the lock for the task after the task is finished
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
     * @var ?CronTaskAbstract
     */
    private $task;

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
     * @throws LoadBalancedCronTaskException
     */
    public function __construct()
    {
        $this->timezone = 'UTC';
        $this->task = null;
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
     * @throws LoadBalancedCronTaskException
     */
    private function setDateTime(string $dateTime = 'now'): void
    {
        try {
            $this->dateTime = new DateTime($dateTime, new \DateTimeZone($this->timezone));
        }
        catch(\Exception $e)
        {
            throw new LoadBalancedCronTaskException($e->getMessage());
        }
    }

    /**
     * @throws LoadBalancedCronTaskException
     */
    private function checkMysqlTableExists(): void
    {
        // check if lbct_tasks table exists in database
        try {
            $this->pdo->query("SELECT * FROM lbct_tasks LIMIT 1");
        }
        catch (\PDOException $e)
        {
            throw new LoadBalancedCronTaskException($e->getMessage());
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
                throw new LoadBalancedCronTaskException($e->getMessage());
            }

            self::checkMysqlTableExists();
        }

        self::setDateTime($dateTime);

        $this->asyncBuffer = $asyncBuffer;

        $this->isTest = true;

        return $this;
    }

    /**
     * @param string $timezone
     * @throws LoadBalancedCronTaskException
     */
    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
        self::setDateTime();
    }

    public function local(): LoadBalancedCronTask
    {
        $this->type = ProcessType::LOCAL();

        return $this;
    }

    public function distributed(): LoadBalancedCronTask
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
     * @throws LoadBalancedCronTaskException
     */
    public function mysql(string $host, string $user, string $password, string $database, int $port = 3306): LoadBalancedCronTask
    {
        try {
            $this->pdo = new \PDO('mysql:host='.$host.';port='.$port.';dbname='.$database, $user, $password);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("set names utf8");
        }
        catch(\PDOException $e)
        {
            throw new LoadBalancedCronTaskException($e->getMessage());
        }

        self::checkMysqlTableExists();

        return $this;
    }

    public function task(CronTaskAbstract $task): LoadBalancedCronTask
    {
        $this->task = $task;

        return $this;
    }

    public function everyMinute(): LoadBalancedCronTask
    {
        self::setInTime(true);
        return $this;
    }

    public function everyFiveMinutes(): LoadBalancedCronTask
    {
        $currentMinute = intval($this->dateTime->format('i'));

        if($currentMinute % 5 === 0)
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function everyTenMinutes(): LoadBalancedCronTask
    {
        $currentMinute = intval($this->dateTime->format('i'));

        if($currentMinute % 10 === 0)
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function everyFifteenMinutes(): LoadBalancedCronTask
    {
        $currentMinute = intval($this->dateTime->format('i'));

        if($currentMinute % 15 === 0)
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function everyThirtyMinutes(): LoadBalancedCronTask
    {
        $currentMinute = intval($this->dateTime->format('i'));

        if($currentMinute % 30 === 0)
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function hourly(): LoadBalancedCronTask
    {
        $currentHour = intval($this->dateTime->format('H'));
        $currentMinute = intval($this->dateTime->format('i'));

        if(($currentHour >= 0 && $currentHour <= 23) && $currentMinute === 0)
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function run(): bool
    {

        if(self::isInTime() === false || intval($this->dateTime->format('s')) > $this->asyncBuffer)
            return false;

        if ($this->type->getValue() === ProcessType::LOCAL()->getValue())
        {
            return $this->task->task();
        }
        else if ($this->type->getValue() === ProcessType::DISTRIBUTED()->getValue())
        {
            // check if task has a name
            if (empty($this->task->getName()) || !is_string($this->task->getName()))
            {
                throw new LoadBalancedCronTaskException('This task has no name. A name must be set.');
            }

            // try to insert the task into lbct_tasks
            try {
                $query = $this->pdo->prepare('INSERT INTO lbct_tasks (task_running) VALUES (:task_name)');
                $query->execute([
                    'task_name' => $this->task->getName()
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

                throw new LoadBalancedCronTaskException($e->getMessage());
            }

            $taskResponse = $this->task->task();

            // delete task from lbct_tasks
            try
            {
                // wait some seconds before deleting the entry from the database and "unlock" it
                if ($this->isTest === false)
                {
                    sleep($this->asyncBuffer);
                }

                $query = $this->pdo->prepare('DELETE FROM lbct_tasks WHERE task_running = :task_name');
                $query->execute([
                    'task_name' => $this->task->getName()
                ]);
            }
            catch(\PDOException $e)
            {
                throw new LoadBalancedCronTaskException($e->getMessage());
            }

            return $taskResponse;
        }

        return false;
    }
}