-- DROP SCHEMA `json_event_store`;
-- CREATE SCHEMA `json_event_store`;
-- USE `json_event_store`;

-- DROP TABLE IF EXISTS `streams`;
CREATE TABLE `streams` (
  `id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `event` json NOT NULL,
  `metadata` json NOT NULL,
  `occurred_on` datetime NOT NULL,
  `version` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `stream_id` (`stream_id`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`)
) ENGINE=InnoDB;
