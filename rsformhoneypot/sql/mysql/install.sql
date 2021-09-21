ALTER TABLE `#__rsform_forms` ADD COLUMN `HoneypotState` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `#__rsform_forms` ADD COLUMN `HoneypotName` varchar(255) DEFAULT '';
ALTER TABLE `#__rsform_forms` ADD COLUMN `HoneypotUrl` varchar(255) DEFAULT '';
