CREATE DATABASE IF NOT EXISTS `json_event_store`;
CREATE DATABASE IF NOT EXISTS `json_snapshot_store`;

GRANT ALL ON *.* TO 'event_sourcing'@'%';
