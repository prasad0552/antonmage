<?php
/**
 * Appmagento
 *
 * @category    Appmagento
 * @package     Appmagento_Sharesuit
 * @copyright   Copyright (c) 2011 http://www.appmagento.com
 */

$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('sharesuit')};
CREATE TABLE {$this->getTable('sharesuit')} (
  `sharesuit_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fbuser` varchar(255) NOT NULL DEFAULT '',
  `content` varchar(255) NOT NULL DEFAULT '',
  `status` smallint(6) NOT NULL DEFAULT '0',
  `type` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sharesuit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- DROP TABLE IF EXISTS {$this->getTable('sharesuit_customer')};
CREATE TABLE {$this->getTable('sharesuit_customer')} (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL,
  `status` smallint(6) NOT NULL DEFAULT '0',
  `type` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup(); 