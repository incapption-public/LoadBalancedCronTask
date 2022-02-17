<?php

namespace Incapption\LoadBalancedCronTask;

use DateTime;
use Incapption\LoadBalancedCronTask\Abstracts\CronTaskAbstract;
use Incapption\LoadBalancedCronTask\Enums\ProcessType;
use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\Helper\Timing;

class LoadBalancedCronTask
{
    /**
     * @var ProcessType
     */
    private $processType;

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
     * @var ?Timing
     */
    private $timing;

    /**
     * @var bool
     */
    private $alreadyScheduled;

    /**
     * @var false
     */
    private $hasProcessType;

    /**
     * @var string
     */
    private $workerName;

    /**
     * @throws LoadBalancedCronTaskException
     */
    public function __construct()
    {
        $this->timezone = 'UTC';
        $this->task = null;
        $this->inTime = false;
        $this->timing = null;
        $this->alreadyScheduled = false;
        $this->hasProcessType = false;

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
            $this->timing = new Timing($this->dateTime);
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

    private function checkHasProcessType(): void
    {
        if($this->hasProcessType === true)
        {
            throw new LoadBalancedCronTaskException('ProcessType already set => local(), loadBalanced()');
        }

        $this->hasProcessType = true;
    }

    private function checkAlreadyScheduled(): void
    {
        if($this->alreadyScheduled === true)
        {
            throw new LoadBalancedCronTaskException('this task is already scheduled. You can not chain schedule functions.');
        }

        $this->alreadyScheduled = true;
    }

    public function mockTestEnvironment(?string $sqlite, string $dateTime = 'now'): LoadBalancedCronTask
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
        self::checkHasProcessType();
        $this->processType = ProcessType::LOCAL();
        return $this;
    }

    public function loadBalanced(): LoadBalancedCronTask
    {
        self::checkHasProcessType();
        $this->processType = ProcessType::LOAD_BALANCED();
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

    public function setWorkerName(string $name): LoadBalancedCronTask
    {
        $this->workerName = $name;

        return $this;
    }

    public function task(CronTaskAbstract $task): LoadBalancedCronTask
    {
        $this->task = $task;

        return $this;
    }

    public function everyMinute(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled();
        self::setInTime(true);
        return $this;
    }

    public function everyFiveMinutes(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled();

        if($this->timing->isEveryNthMinutes(5))
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function everyTenMinutes(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled();

        if($this->timing->isEveryNthMinutes(10))
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function everyFifteenMinutes(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled();

        if($this->timing->isEveryNthMinutes(15))
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function everyThirtyMinutes(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled();

        if($this->timing->isEveryNthMinutes(30))
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function hourly(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled();

        if($this->timing->isHourAt(0))
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function hourlyAt(int $minute): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled();

        if($this->timing->isHourAt($minute))
        {
            self::setInTime(true);
        }

        return $this;
    }

    public function monthly(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled();

        if($this->timing->isMonthOn(1, '00:00'))
        {
            self::setInTime(true);
        }
        return $this;
    }

    public function monthlyOn(int $dayOfMonth, string $time): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled();

        if($this->timing->isMonthOn($dayOfMonth, $time))
        {
            self::setInTime(true);
        }
        return $this;
    }

    public function lastDayOfMonth(string $time = '00:00'): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled();

        if($this->timing->isLastDayOfMonthAt($time))
        {
            self::setInTime(true);
        }
        return $this;
    }

    public function run(): bool
    {
        // check if processType is set
        if ($this->processType instanceof ProcessType === false)
        {
            throw new LoadBalancedCronTaskException('No processType is set. Choose between local() or loadBalanced()');
        }

        // check if task has a name
        if (empty($this->task->getName()) || !is_string($this->task->getName()))
        {
            throw new LoadBalancedCronTaskException('This task has no name. A name must be set.');
        }

        // check if current task is in time
        if(self::isInTime() === false)
        {
            return false;
        }

        if ($this->processType->getValue() === ProcessType::LOCAL()->getValue())
        {
            return $this->task->task();
        }
        else if ($this->processType->getValue() === ProcessType::LOAD_BALANCED()->getValue())
        {
            // try to insert the task into lbct_tasks
            try
            {
                $query = $this->pdo->prepare('INSERT INTO lbct_tasks (unique_hash, task_running, worker) 
                                                VALUES (:unique_hash, :task_name, :worker)');
                $query->execute([
                    'unique_hash' => md5($this->task->getName().$this->dateTime->format('Y-m-d H:i:00')),
                    'task_name' => $this->task->getName(),
                    'worker' => $this->workerName
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