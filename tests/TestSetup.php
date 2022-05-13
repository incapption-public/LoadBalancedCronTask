<?php

namespace Incapption\LoadBalancedCronTask\Tests;

use PDO;
use PHPUnit\Framework\TestCase;

class TestSetup extends TestCase
{
    /**
     * @var string
     */
    public $sqlitedbPath;

    public function __construct()
    {
        parent::__construct();
        $this->sqlitedbPath = 'tests/SqliteDatabase/test.db';
        $this->sqlitedb = 'sqlite:tests/SqliteDatabase/test.db';
    }

    public function setUp(): void
    {
        file_put_contents($this->sqlitedbPath, '');

        $pdo = new PDO($this->sqlitedb);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query("
            CREATE TABLE IF NOT EXISTS `lbct_tasks` (
            `unique_hash` varchar(32) NOT NULL,
            `task` varchar(256) NOT NULL,
            `schedule` varchar(256) NOT NULL,
            `date_created` datetime NOT NULL,
            `worker` varchar(256),
            PRIMARY KEY (`unique_hash`)
            );
        ");
    }

    public function tearDown(): void
    {
        if (file_exists($this->sqlitedbPath)) {
            unlink($this->sqlitedbPath);
        }
    }
}