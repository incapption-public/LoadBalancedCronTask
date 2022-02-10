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

    private function getThisWorker(): string
    {
        if (isset($_SERVER['REMOTE_ADDR']))
        {
            return $_SERVER['REMOTE_ADDR'];
        }
        else if (isset($_SERVER['SERVER_ADDR']))
        {
            return $_SERVER['SERVER_ADDR'];
        }
        else if (isset($_SERVER['HOSTNAME']))
        {
            return $_SERVER['HOSTNAME'];
        }

        return "undefined";
    }

    public function mockTestEnvironment(?string $sqlite, string $dateTime = 'now')
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
        // check if task has a name
        if (empty($this->task->getName()) || !is_string($this->task->getName()))
        {
            throw new LoadBalancedCronTaskException('This task has no name. A name must be set.');
        }

        // check if current task is in time
        if(self::isInTime() === false)
            return false;

        if ($this->type->getValue() === ProcessType::LOCAL()->getValue())
        {
            return $this->task->task();
        }
        else if ($this->type->getValue() === ProcessType::DISTRIBUTED()->getValue())
        {
            // try to insert the task into lbct_tasks
            try {
                $query = $this->pdo->prepare('INSERT INTO lbct_tasks (unique_hash, task_running, worker) VALUES (:unique_hash, :task_name, :worker)');
                $query->execute([
                    'unique_hash' => md5($this->task->getName().$this->dateTime->format('Y-m-d H:i:00')),
                    'task_name' => $this->task->getName(),
                    'worker' => self::getThisWorker()
                ]);
            }
            catch(\PDOException $e)
            {
                /**
                 * 23000 = SQLSTATE[23000]: Integrity constraint violation (primary key already exists)
                 */
                if ($e->getCode() == "23000")
                {
                    // task already inserted -> abort
                    return false;
                }

                throw new LoadBalancedCronTaskException($e->getMessage());
            }

            // run the task
            return $this->task->task();
        }

        return false;
    }
}