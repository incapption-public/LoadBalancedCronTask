# Load Balanced Cron Task

## Introduction
This is a lightweight package for distributing your cron tasks. It is used to distribute tasks 
across a network of **identical servers** so that they are run only once and not multiple times. 
>It is important that your application network uses only one single central mysql database. 
A table is created in the database in which current tasks are inserted. 
A primary key ensures that the task only runs once (a kind of locking process).

![alt text](scheme.png)

## Requirements
- MySQL Database
- PHP PDO Extension (ext-pdo, ext-pdo_mysql)

## 1. Installation

### 1.1 Composer

Install `Load Balanced Cron Task` using Composer.

```bash
$ composer require incapption/load-balanced-cron-task
```

### 1.2 Create MySQL Table

This package needs a small table in your database. 
It's used for locking running tasks, to make sure that only on app instance could run a distributed cron task.

```sql
CREATE TABLE IF NOT EXISTS `lbct_tasks` (
`unique_hash` varchar(32) NOT NULL,
`task_running` varchar(256) NOT NULL,
`timing` datetime NOT NULL,
`worker` varchar(256),
PRIMARY KEY (`unique_hash`)
);
```