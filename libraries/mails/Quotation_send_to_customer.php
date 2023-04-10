<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Quotation_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $quotation;

    protected $contact;

    public $slug = 'quotation-send-to-client';

    public $rel_type = 'quotation';

    public function __construct($quotation, $contact, $cc = '')
    {
        parent::__construct();

        $this->quotation = $quotation;
        $this->contact = $contact;
        $this->cc      = $cc;
    }

    public function build()
    {
        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->quotations_model->get_attachments($this->quotation->id, $attachment);
                $this->add_attachment([
                                'attachment' => get_upload_path_by_type('quotation') . $this->quotation->id . '/' . $_attachment->file_name,
                                'filename'   => $_attachment->file_name,
                                'type'       => $_attachment->filetype,
                                'read'       => true,
                            ]);
            }
        }

        $this->to($this->contact->email)
        ->set_rel_id($this->quotation->id)
        ->set_merge_fields('client_merge_fields', $this->quotation->client_id, $this->contact->id)
        ->set_merge_fields('quotation_merge_fields', $this->quotation->id);
    }
}
