# Load Balanced Cron Task

```sql
CREATE TABLE IF NOT EXISTS `lbct_tasks` (
`task_running` varchar(256) NOT NULL,
`date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`task_running`)
) DEFAULT CHARSET=utf8;
```