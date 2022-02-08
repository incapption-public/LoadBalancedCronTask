<?php

namespace Incapption\DistributedCronjob;

use Incapption\DistributedCronjob\Enums\ProcessType;
use Incapption\DistributedCronjob\Exceptions\DistributedCronjobsException;
use MyCLabs\Enum\Enum;

class DistributedCronjob
{
    /**
     * @var Enum
     */
    private $type;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @var string
     */
    private $job;

    /**
     * @var bool
     */
    private $inTime;

    public function __construct()
    {
        $this->timezone = 'UTC';
        $this->job = '';
        $this->minutes = [];
        $this->inTime = false;
    }

    private function isInTime(): bool
    {
        return $this->inTime;
    }

    private function setInTime(bool $bool): void
    {
        $this->inTime = true;
    }

    public function setTimezone(string $timezone)
    {
        $this->timezone = $timezone;
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

    public function job($job): DistributedCronjob
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
        $dateTime = new \DateTime('now', new \DateTimeZone($this->timezone));
        $currentMinute = intval($dateTime->format('i'));

        if($currentMinute % 5 === 0)
        {
            self::setInTime(true);
        }

        return $this;
    }

}