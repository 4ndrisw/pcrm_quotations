<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Myquotation extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('quotations_model');
        $this->load->model('currencies_model');
        //include_once(module_libs_path(QUOTATIONS_MODULE_NAME) . 'mails/Quotation_mail_template.php');
        //$this->load->library('module_name/library_name'); 
        //$this->load->library('quotation_mail_template'); 
        //include_once(module_libs_path(QUOTATIONS_MODULE_NAME) . 'mails/Quotation_send_to_customer.php');
        //$this->load->library('module_name/library_name'); 
        //$this->load->library('quotation_send_to_customer'); 


    }

    public function show($id, $hash)
    {
        check_quotation_restrictions($id, $hash);
        $quotation = $this->quotations_model->get($id);

        if ($quotation->rel_type == 'customer' && !is_client_logged_in()) {
            load_client_language($quotation->rel_id);
        } else if($quotation->rel_type == 'lead') {
            load_lead_language($quotation->rel_id);
        }

        $identity_confirmation_enabled = get_option('quotation_accept_identity_confirmation');
        if ($this->input->post()) {
            $action = $this->input->post('action');
            switch ($action) {
                case 'quotation_comment':
                    // comment is blank
                    if (!$this->input->post('content')) {
                        redirect($this->uri->uri_string());
                    }
                    $data               = $this->input->post();
                    $data['quotationid'] = $id;
                    $this->quotations_model->add_comment($data, true);
                    redirect($this->uri->uri_string() . '?tab=discussion');

                    break;
                case 'accept_quotation':
                    $success = $this->quotations_model->mark_action_status(3, $id, true);
                    if ($success) {
                        process_digital_signature_image($this->input->post('signature', false), PROPOSAL_ATTACHMENTS_FOLDER . $id);

                        $this->db->where('id', $id);
                        $this->db->update(db_prefix().'quotations', get_acceptance_info_array());
                        redirect($this->uri->uri_string(), 'refresh');
                    }

                    break;
                case 'decline_quotation':
                    $success = $this->quotations_model->mark_action_status(2, $id, true);
                    if ($success) {
                        redirect($this->uri->uri_string(), 'refresh');
                    }

                    break;
            }
        }

        $number_word_lang_rel_id = 'unknown';
        if ($quotation->rel_type == 'customer') {
            $number_word_lang_rel_id = $quotation->rel_id;
        }
        $this->load->library('app_number_to_word', [
            'client_id' => $number_word_lang_rel_id,
        ],'numberword');

        $this->disableNavigation();
        $this->disableSubMenu();

        $data['title']     = $quotation->subject;
        $data['can_be_accepted']               = false;
        $data['quotation']  = hooks()->apply_filters('quotation_html_pdf_data', $quotation);
        $data['bodyclass'] = 'quotation quotation-view';

        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }

        $this->app_scripts->theme('sticky-js','assets/plugins/sticky/sticky.js');

        $data['comments'] = $this->quotations_model->get_comments($id);
        add_views_tracking('quotation', $id);
        hooks()->do_action('quotation_html_viewed', $id);
        hooks()->add_action('app_admin_head', 'quotations_head_component');
        
        $this->app_css->remove('reset-css','customers-area-default');

        $data                      = hooks()->apply_filters('quotation_customers_area_view_data', $data);
        no_index_customers_area();
        $this->data($data);

        $this->view('themes/'. active_clients_theme() .'/views/quotations/quotation_html');
        
        $this->layout();
    }


    public function pdf($id)
    {
        if (!$id) {
            redirect(admin_url('quotations'));
        }

        $canView = user_can_view_quotation($id);
        if (!$canView) {
            access_denied('quotations');
        } else {
            if (!has_permission('quotations', '', 'view') && !has_permission('quotations', '', 'view_own') && $canView == false) {
                access_denied('quotations');
            }
        }

        $quotation = $this->quotations_model->get($id);
        $notes = explode('--', $quotation->client_note);
        $note = '<ul>';

        foreach($notes as $row){
            if($row !==''){
                $note .= '<li>' . $row .'</li>';
            }
        }
        $note .= '</ul>';
        $quotation->note = $note;

        $terms = explode('==', $quotation->terms);
        $term = '<ol>';

        foreach($terms as $row){
            if($row !==''){
                $term .= '<li>' . $row .'</li>';
            }
        }
        $term .= '</ol>';
        $quotation->term = $term;


        $quotation_number = format_quotation_number($id);

        try {
            $pdf = quotation_pdf($quotation);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output(format_quotation_number($id).'-'. $quotation->quotation_to . '.pdf', $type);
    }
}
