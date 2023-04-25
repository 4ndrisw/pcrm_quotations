<?php defined('BASEPATH') or exit('No direct script access allowed');



if (!$CI->db->table_exists(db_prefix() . 'quotations')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "quotations` (
      `id` int NOT NULL,
      `subject` varchar(191) NOT NULL,
      `content` longtext,
      `number` int NOT NULL,
      `signed` tinyint(1) NOT NULL DEFAULT '0',
      `prefix` varchar(10) DEFAULT '0',
      `number_format` tinyint(1) NOT NULL DEFAULT '0',
      `addedfrom` int NOT NULL,
      `datecreated` datetime NOT NULL,
      `total` decimal(15,2) DEFAULT NULL,
      `subtotal` decimal(15,2) NOT NULL,
      `total_tax` decimal(15,2) NOT NULL DEFAULT '0.00',
      `adjustment` decimal(15,2) DEFAULT NULL,
      `discount_percent` decimal(15,2) NOT NULL,
      `discount_total` decimal(15,2) NOT NULL,
      `discount_type` varchar(30) DEFAULT NULL,
      `show_quantity_as` int NOT NULL DEFAULT '1',
      `currency` int NOT NULL,
      `open_till` date DEFAULT NULL,
      `date` date NOT NULL,
      `rel_id` int DEFAULT NULL,
      `rel_type` varchar(40) DEFAULT NULL,
      `taxname` varchar(40) DEFAULT NULL,
      `assigned` int DEFAULT NULL,
      `hash` varchar(32) NOT NULL,
      `quotation_to` varchar(191) DEFAULT NULL,
      `country` int NOT NULL DEFAULT '0',
      `zip` varchar(50) DEFAULT NULL,
      `city` varchar(100) DEFAULT NULL,
      `address` varchar(200) DEFAULT NULL,
      `email` varchar(150) DEFAULT NULL,
      `phone` varchar(50) DEFAULT NULL,
      `allow_comments` tinyint(1) NOT NULL DEFAULT '1',
      `state` TINYINT(1) NOT NULL DEFAULT '1', 
      `status` TINYINT(1) NOT NULL DEFAULT '1', 
      `last_status_change` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `quotation_id` int DEFAULT NULL,
      `invoice_id` int DEFAULT NULL,
      `date_converted` datetime DEFAULT NULL,
      `pipeline_order` int DEFAULT '1',
      `client_note` text,
      `terms` text,
      `is_expiry_notified` int NOT NULL DEFAULT '0',
      `acceptance_firstname` varchar(50) DEFAULT NULL,
      `acceptance_lastname` varchar(50) DEFAULT NULL,
      `acceptance_email` varchar(100) DEFAULT NULL,
      `acceptance_date` datetime DEFAULT NULL,
      `acceptance_ip` varchar(40) DEFAULT NULL,
      `signature` varchar(40) DEFAULT NULL,
      `short_link` varchar(100) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'quotations`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `number` (`number`),
      ADD KEY `subject` (`subject`),
      ADD KEY `status` (`status`),
      ADD KEY `date` (`date`),
      ADD KEY `assigned` (`assigned`),
      ADD KEY `quotation_to` (`quotation_to`),
      ADD KEY `last_status_change` (`last_status_change`);
    ');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'quotations`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
