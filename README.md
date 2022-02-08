# DistributedCronjobs

```sql
CREATE TABLE IF NOT EXISTS `dcj_running_cronjobs` (
`running_job` varchar(256) NOT NULL,
`date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`running_job`)
) DEFAULT CHARSET=utf8;
```