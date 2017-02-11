-- DROP SCHEMA `json_snapshot_store`;
-- CREATE SCHEMA `json_snapshot_store`;
-- USE `json_snapshot_store`;

-- DROP TABLE IF EXISTS `snapshots`;
CREATE TABLE `snapshots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aggregate_type` varchar(255) NOT NULL,
  `aggregate_id` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `version` int(11) NOT NULL,
  `snapshot` json NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
