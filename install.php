<?php

defined('BASEPATH') or exit('No direct script access allowed');


require_once('install/quotations.php');
require_once('install/quotation_activity.php');
require_once('install/quotation_comments.php');
require_once('install/quotation_notes.php');

$CI->db->query("
INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('quotation', 'quotation-send-to-client', 'english', 'Send quotation to Customer', 'quotation # {quotation_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached quotation <strong># {quotation_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>quotation status:</strong> {quotation_status}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the quotation on the following link: <a href=\"{quotation_link}\">{quotation_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('quotation', 'quotation-already-send', 'english', 'quotation Already Sent to Customer', 'quotation # {quotation_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your quotation request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the quotation on the following link: <a href=\"{quotation_link}\">{quotation_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('quotation', 'quotation-declined-to-staff', 'english', 'quotation Declined (Sent to Staff)', 'Customer Declined quotation', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined quotation with number <strong># {quotation_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the quotation on the following link: <a href=\"{quotation_link}\">{quotation_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('quotation', 'quotation-accepted-to-staff', 'english', 'quotation Accepted (Sent to Staff)', 'Customer Accepted quotation', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted quotation with number <strong># {quotation_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the quotation on the following link: <a href=\"{quotation_link}\">{quotation_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('quotation', 'quotation-thank-you-to-customer', 'english', 'Thank You Email (Sent to Customer After Accept)', 'Thank for you accepting quotation', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank for for accepting the quotation.</span><br /> <br /><span style=\"font-size: 12pt;\">We look forward to doing business with you.</span><br /> <br /><span style=\"font-size: 12pt;\">We will contact you as soon as possible.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('quotation', 'quotation-expiry-reminder', 'english', 'quotation Expiration Reminder', 'quotation Expiration Reminder', '<p><span style=\"font-size: 12pt;\">Hello {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">The quotation with <strong># {quotation_number}</strong> will expire on <strong>{quotation_expirydate}</strong></span><br /><br /><span style=\"font-size: 12pt;\">You can view the quotation on the following link: <a href=\"{quotation_link}\">{quotation_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
('quotation', 'quotation-send-to-client', 'english', 'Send quotation to Customer', 'quotation # {quotation_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached quotation <strong># {quotation_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>quotation status:</strong> {quotation_status}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the quotation on the following link: <a href=\"{quotation_link}\">{quotation_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('quotation', 'quotation-already-send', 'english', 'quotation Already Sent to Customer', 'quotation # {quotation_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your quotation request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the quotation on the following link: <a href=\"{quotation_link}\">{quotation_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('quotation', 'quotation-declined-to-staff', 'english', 'quotation Declined (Sent to Staff)', 'Customer Declined quotation', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined quotation with number <strong># {quotation_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the quotation on the following link: <a href=\"{quotation_link}\">{quotation_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('quotation', 'quotation-accepted-to-staff', 'english', 'quotation Accepted (Sent to Staff)', 'Customer Accepted quotation', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted quotation with number <strong># {quotation_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the quotation on the following link: <a href=\"{quotation_link}\">{quotation_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('quotation', 'staff-added-as-project-member', 'english', 'Staff Added as Project Member', 'New project assigned to you', '<p>Hi <br /><br />New quotation has been assigned to you.<br /><br />You can view the quotation on the following link <a href=\"{quotation_link}\">quotation__number</a><br /><br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
('quotation', 'quotation-accepted-to-staff', 'english', 'quotation Accepted (Sent to Staff)', 'Customer Accepted quotation', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted quotation with number <strong># {quotation_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the quotation on the following link: <a href=\"{quotation_link}\">{quotation_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0);
");
/*
 *
 */

// Add options for quotations
add_option('delete_only_on_last_quotation', 1);
add_option('quotation_prefix', 'PH-');
add_option('next_quotation_number', 1);
add_option('default_quotation_assigned', 3);
add_option('quotation_number_decrement_on_delete', 0);
add_option('quotation_number_format', 4);
add_option('quotation_year', date('Y'));
add_option('exclude_quotation_from_client_area_with_draft_status', 1);



add_option('predefined_client_note_quotation', '
--Review Dokumen;
--Pemeriksaan Visual;
--Pengujian Operasional;
--Pemeriksaan Perlengkapan Pengaman;
--Pengujian NDT Penetrant;
--Pengujian NDT Thickness;
--Pengujian Thermal Infrared;
--Pengujian Grounding;
--Laporan hasil Riksa Uji.
    ');

add_option('predefined_terms_quotation', '
==Harga belum berikut Pajak PPn 11 %;
==Pengurusan SUKET K3 Disnaker Propinsi;
==Tidak termasuk beban dan alat bantu angkat;
==Termin Pembayaran : 100% setelah Surat Keterangan terbit.
    ');

add_option('quotation_due_after', 1);
add_option('allow_staff_view_quotations_assigned', 1);
add_option('show_assigned_on_quotations', 1);
add_option('require_client_logged_in_to_view_quotation', 0);

add_option('show_project_on_quotation', 1);
add_option('quotations_pipeline_limit', 1);
add_option('default_quotations_pipeline_sort', 1);
add_option('quotation_accept_identity_confirmation', 1);
add_option('quotation_qrcode_size', '160');
add_option('quotation_send_telegram_message', 0);

add_option('next_quotation_number',1);
add_option('quotation_number_format',4);
add_option('quotation_prefix',1);

/*


DROP TABLE `tblquotations`;
DROP TABLE `tblquotation_activity`, `tblquotation_comments`, `tblquotation_notes`;
delete FROM `tbloptions` WHERE `name` LIKE '%quotation%';
DELETE FROM `tblemailtemplates` WHERE `type` LIKE 'quotation';


*/
