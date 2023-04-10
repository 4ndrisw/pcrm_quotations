<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'quotation_notes')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "quotation_notes` (
      `id` int(11) NOT NULL,
      `content` mediumtext DEFAULT NULL,
      `quotationid` int(11) NOT NULL,
      `staffid` int(11) NOT NULL,
      `dateadded` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'quotation_notes`
      ADD PRIMARY KEY (`id`);
    ');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'quotation_notes`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
