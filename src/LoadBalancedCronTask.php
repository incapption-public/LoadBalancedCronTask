<?php

namespace Incapption\LoadBalancedCronTask;

use DateTime;
use DateTimeZone;
use Exception;
use Incapption\LoadBalancedCronTask\Abstracts\CronTaskAbstract;
use Incapption\LoadBalancedCronTask\Enums\ProcessType;
use Incapption\LoadBalancedCronTask\Exceptions\LoadBalancedCronTaskException;
use Incapption\LoadBalancedCronTask\Helper\Timing;
use PDO;
use PDOException;

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
     * @var string
     */
    private $schedule;

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

    /**
     * @param  string  $dateTime
     * @throws LoadBalancedCronTaskException
     */
    private function setDateTime(string $dateTime = 'now'): void
    {
        try {
            $this->dateTime = new DateTime($dateTime, new DateTimeZone($this->timezone));
            $this->timing = new Timing($this->dateTime);
        } catch (Exception $e) {
            throw new LoadBalancedCronTaskException($e->getMessage());
        }
    }

    public function mockTestEnvironment(?string $sqlite, string $dateTime = 'now'): LoadBalancedCronTask
    {
        if (!is_null($sqlite)) {
            try {
                $this->pdo = new PDO($sqlite);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new LoadBalancedCronTaskException($e->getMessage());
            }

            self::checkMysqlTableExists();
        }

        self::setDateTime($dateTime);

        return $this;
    }

    /**
     * check if lbct_tasks table exists in database
     * @throws LoadBalancedCronTaskException
     */
    private function checkMysqlTableExists(): void
    {
        try {
            $this->pdo->query("SELECT * FROM lbct_tasks LIMIT 1");
        } catch (PDOException $e) {
            throw new LoadBalancedCronTaskException($e->getMessage());
        }
    }

    /**
     * @param  string  $timezone
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

    private function checkHasProcessType(): void
    {
        if ($this->hasProcessType === true) {
            throw new LoadBalancedCronTaskException('ProcessType already set => local(), loadBalanced()');
        }

        $this->hasProcessType = true;
    }

    public function loadBalanced(): LoadBalancedCronTask
    {
        self::checkHasProcessType();
        $this->processType = ProcessType::LOAD_BALANCED();

        return $this;
    }

    /**
     * @param  string  $host
     * @param  string  $user
     * @param  string  $password
     * @param  string  $database
     * @param  int  $port
     * @return $this
     * @throws LoadBalancedCronTaskException
     */
    public function mysql(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306
    ): LoadBalancedCronTask {
        try {
            $this->pdo = new PDO('mysql:host='.$host.';port='.$port.';dbname='.$database, $user, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("set names utf8");
        } catch (PDOException $e) {
            throw new LoadBalancedCronTaskException($e->getMessage());
        }

        self::checkMysqlTableExists();

        return $this;
    }

    /**
     * The unique name of the machine / host. Perhaps the ip-address
     * @param  string  $name
     * @return $this
     */
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
        self::checkAlreadyScheduled(__FUNCTION__);
        self::setInTime(true);

        return $this;
    }

    private function checkAlreadyScheduled(string $methodName): void
    {
        if ($this->alreadyScheduled === true) {
            throw new LoadBalancedCronTaskException('this task is already scheduled. You can not chain schedule functions.');
        }

        $this->schedule = $methodName;
        $this->alreadyScheduled = true;
    }

    private function setInTime(bool $bool): void
    {
        $this->inTime = true;
    }

    public function everyTwoMinutes(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__);

        if ($this->timing->isEveryNthMinutes(2)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function everyThreeMinutes(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__);

        if ($this->timing->isEveryNthMinutes(3)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function everyFiveMinutes(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__);

        if ($this->timing->isEveryNthMinutes(5)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function everyTenMinutes(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__);

        if ($this->timing->isEveryNthMinutes(10)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function everyFifteenMinutes(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__);

        if ($this->timing->isEveryNthMinutes(15)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function everyThirtyMinutes(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__);

        if ($this->timing->isEveryNthMinutes(30)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function hourly(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__);

        if ($this->timing->isHourAt(0)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function hourlyAt(int $minute): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__.'('.$minute.')');

        if ($this->timing->isHourAt($minute)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function daily(): LoadBalancedCronTask
    {
        return self::dailyAt();
    }

    public function dailyAt(string $time = '00:00'): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__.'('.$time.')');

        if ($this->timing->isDailyAt($time)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function monthly(): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__);

        if ($this->timing->isMonthOn(1, '00:00')) {
            self::setInTime(true);
        }

        return $this;
    }

    public function monthlyOn(int $dayOfMonth, string $time): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__.'('.$dayOfMonth.', '.$time.')');

        if ($this->timing->isMonthOn($dayOfMonth, $time)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function lastDayOfMonth(string $time = '00:00'): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__.'('.$time.')');

        if ($this->timing->isLastDayOfMonthAt($time)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function lastDayOfMonthOffset(int $offsetDays = 0, string $time = '00:00'): LoadBalancedCronTask
    {
        self::checkAlreadyScheduled(__FUNCTION__.'('.$offsetDays.', '.$time.')');

        if ($this->timing->isLastDayOfMonthOffsetAt($offsetDays, $time)) {
            self::setInTime(true);
        }

        return $this;
    }

    public function run(): bool
    {
        // check if processType is set
        if ($this->processType instanceof ProcessType === false) {
            throw new LoadBalancedCronTaskException('No processType is set. Choose between local() or loadBalanced()');
        }

        // check if task has a name
        if (empty($this->task->getName()) || !is_string($this->task->getName())) {
            throw new LoadBalancedCronTaskException('This task has no name. A name is mandatory.');
        }

        // check if current task is in time
        if (self::isInTime() === false) {
            return false;
        }

        if ($this->processType->getValue() === ProcessType::LOCAL()->getValue()) {
            return $this->task->task();
        }

        if ($this->processType->getValue() === ProcessType::LOAD_BALANCED()->getValue()) {
            try {
                // try to insert the task into lbct_tasks
                $query = $this->pdo->prepare('INSERT INTO lbct_tasks (unique_hash, task, schedule, date_created, worker) 
                                                VALUES (:unique_hash, :task, :schedule, :date_created, :worker)');
                $query->execute([
                    'unique_hash' => md5($this->task->getName().$this->schedule.$this->dateTime->format('Y-m-d H:i:00')),
                    'task' => $this->task->getName(),
                    'schedule' => $this->schedule,
                    'date_created' => $this->dateTime->format('Y-m-d H:i:s'),
                    'worker' => $this->workerName
                ]);
            } catch (PDOException $e) {
                /**
                 * 23000 = SQLSTATE[23000]: Integrity constraint violation (primary key already exists)
                 */
                if ($e->getCode() == "23000") {
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

    private function isInTime(): bool
    {
        return $this->inTime;
    }
}