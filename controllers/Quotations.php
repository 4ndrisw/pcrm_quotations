<?php
defined('BASEPATH') or exit('No direct script access allowed');

use modules\quotations\services\quotations\QuotationsPipeline;


class Quotations extends AdminController
{
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('quotations_model');
        $this->load->model('currencies_model');
        include_once(module_libs_path('quotations') . 'mails/Quotation_mail_template.php');
        //$this->load->library('module_name/library_name'); 
        $this->load->library('quotation_mail_template'); 
        //include_once(module_libs_path(QUOTATIONS_MODULE_NAME) . 'mails/Quotation_send_to_customer.php');
        //$this->load->library('module_name/library_name'); 
        //$this->load->library('quotation_send_to_customer'); 


    }

    public function index($quotation_id = '')
    {
        $this->list_quotations($quotation_id);
    }

    public function list_quotations($quotation_id = '')
    {
        close_setup_menu();

        if (!has_permission('quotations', '', 'view') && !has_permission('quotations', '', 'view_own') && get_option('allow_staff_view_quotations_assigned') == 0) {
            access_denied('quotations');
        }
        
        log_activity($quotation_id);

        $isPipeline = $this->session->userdata('quotations_pipeline') == 'true';

        if ($isPipeline && !$this->input->get('status')) {
            $data['title']           = _l('quotations_pipeline');
            $data['bodyclass']       = 'quotations-pipeline';
            $data['switch_pipeline'] = false;
            // Direct access
            if (is_numeric($quotation_id)) {
                $data['quotationid'] = $quotation_id;
            } else {
                $data['quotationid'] = $this->session->flashdata('quotationid');
            }

            $this->load->view('admin/quotations/pipeline/manage', $data);
        } else {

            // Pipeline was initiated but user click from home page and need to show table only to filter
            if ($this->input->get('status') && $isPipeline) {
                $this->pipeline(0, true);
            }

            $data['quotation_id']           = $quotation_id;
            $data['switch_pipeline']       = true;
            $data['title']                 = _l('quotations');
            $data['statuses']              = $this->quotations_model->get_statuses();
            $data['quotations_sale_agents'] = $this->quotations_model->get_sale_agents();
            $data['years']                 = $this->quotations_model->get_quotations_years();
            
            log_activity(json_encode($data));
            /*
            if($quotation_id){
                $this->load->view('admin/quotations/manage_small_table', $data);
            }else{
                $this->load->view('admin/quotations/manage_table', $data);
            }
            */
                $this->load->view('admin/quotations/manage_table', $data);
        }
    }

    public function table()
    {
        if (
            !has_permission('quotations', '', 'view')
            && !has_permission('quotations', '', 'view_own')
            && get_option('allow_staff_view_quotations_assigned') == 0
        ) {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('quotations', 'tables/quotations'));
        
    }
    
    public function small_table()
    {
        if (
            !has_permission('quotations', '', 'view')
            && !has_permission('quotations', '', 'view_own')
            && get_option('allow_staff_view_quotations_assigned') == 0
        ) {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('quotations', 'tables/quotations_small_table'));
        
    }

    public function quotation_relations($rel_id, $rel_type)
    {
        $this->app->get_table_data(module_views_path('quotations', 'tables/quotations_relations', [
            'rel_id'   => $rel_id,
            'rel_type' => $rel_type,
        ]));
    }

    public function delete_attachment($id)
    {
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo $this->quotations_model->delete_attachment($id);
        } else {
            ajax_access_denied();
        }
    }

    public function clear_signature($id)
    {
        if (has_permission('quotations', '', 'delete')) {
            $this->quotations_model->clear_signature($id);
        }

        redirect(admin_url('quotations/list_quotations/' . $id .'#' . $id .'#' . $id));
    }

    public function sync_data()
    {
        if (has_permission('quotations', '', 'create') || has_permission('quotations', '', 'edit')) {
            $has_permission_view = has_permission('quotations', '', 'view');

            $this->db->where('rel_id', $this->input->post('rel_id'));
            $this->db->where('rel_type', $this->input->post('rel_type'));

            if (!$has_permission_view) {
                $this->db->where('addedfrom', get_staff_user_id());
            }

            $address = trim($this->input->post('address'));
            $address = nl2br($address);
            $this->db->update(db_prefix() . 'quotations', [
                'phone'   => $this->input->post('phone'),
                'zip'     => $this->input->post('zip'),
                'country' => $this->input->post('country'),
                'state'   => $this->input->post('state'),
                'address' => $address,
                'city'    => $this->input->post('city'),
            ]);

            if ($this->db->affected_rows() > 0) {
                echo json_encode([
                    'message' => _l('all_data_synced_successfully'),
                ]);
            } else {
                echo json_encode([
                    'message' => _l('sync_quotations_up_to_date'),
                ]);
            }
        }
    }

    public function quotation($id = '')
    {
        if ($this->input->post()) {
            $quotation_data = $this->input->post();
            if ($id == '') {
                if (!has_permission('quotations', '', 'create')) {
                    access_denied('quotations');
                }
                $id = $this->quotations_model->add($quotation_data);
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('quotation')));
                    if ($this->set_quotation_pipeline_autoload($id)) {
                        redirect(admin_url('quotations'));
                    } else {
                        redirect(admin_url('quotations/list_quotations/' . $id .'#' . $id));
                    }
                }
            } else {
                if (!has_permission('quotations', '', 'edit')) {
                    access_denied('quotations');
                }
                $success = $this->quotations_model->update($quotation_data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('quotation')));
                }
                if ($this->set_quotation_pipeline_autoload($id)) {
                    redirect(admin_url('quotations'));
                } else {
                    redirect(admin_url('quotations/list_quotations/' . $id .'#' . $id));
                }
            }
        }
        if ($id == '') {
            $title = _l('add_new', _l('quotation_lowercase'));
        } else {
            $data['quotation'] = $this->quotations_model->get($id);

            if (!$data['quotation'] || !user_can_view_quotation($id)) {
                blank_page(_l('quotation_not_found'));
            }

            $data['quotation']    = $data['quotation'];
            $data['is_quotation'] = true;
            $title               = _l('edit', _l('quotation_lowercase'));
        }

        $this->load->model('taxes_model');
        $data['taxes'] = $this->taxes_model->get();
        $this->load->model('invoice_items_model');
        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->invoice_items_model->get_grouped();
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $data['statuses']      = $this->quotations_model->get_statuses();
        $data['staff']         = $this->staff_model->get('', ['active' => 1]);
        $data['currencies']    = $this->currencies_model->get();
        $data['base_currency'] = $this->currencies_model->get_base_currency();

        $data['title'] = $title;
        $this->load->view('admin/quotations/quotation', $data);
    }

    public function get_template()
    {
        $name = $this->input->get('name');
        echo $this->load->view('admin/quotations/templates/' . $name, [], true);
    }

    public function send_expiry_reminder($id)
    {
        $canView = user_can_view_quotation($id);
        if (!$canView) {
            access_denied('quotations');
        } else {
            if (!has_permission('quotations', '', 'view') && !has_permission('quotations', '', 'view_own') && $canView == false) {
                access_denied('quotations');
            }
        }

        $success = $this->quotations_model->send_expiry_reminder($id);
        if ($success) {
            set_alert('success', _l('sent_expiry_reminder_success'));
        } else {
            set_alert('danger', _l('sent_expiry_reminder_fail'));
        }
        if ($this->set_quotation_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('quotations/list_quotations/' . $id .'#' . $id));
        }
    }

    public function clear_acceptance_info($id)
    {
        if (is_admin()) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'quotations', get_acceptance_info_array(true));
        }

        redirect(admin_url('quotations/list_quotations/' . $id .'#' . $id));
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

        $quotation_number = format_quotation_number($id);
        $pdf->Output($quotation_number . '.pdf', $type);
    }

    public function get_quotation_data_ajax($id, $to_return = false)
    {
        if (!has_permission('quotations', '', 'view') && !has_permission('quotations', '', 'view_own') && get_option('allow_staff_view_quotations_assigned') == 0) {
            echo _l('access_denied');
            die;
        }

        $quotation = $this->quotations_model->get($id, [], true);

        if (!$quotation || !user_can_view_quotation($id)) {
            echo _l('quotation_not_found');
            die;
        }

        
        //$this->quotations_mail_template->set_rel_id($quotation->id);
        include_once(module_libs_path(QUOTATIONS_MODULE_NAME) . 'mails/Quotation_send_to_customer.php');

        //$data = quotation_prepare_mail_preview_data('quotation_send_to_customer', $quotation->email);

        $merge_fields = [];

        $merge_fields[] = [
            [
                'name' => 'Items Table',
                'key'  => '{quotation_items}',
            ],
        ];

        $merge_fields = array_merge($merge_fields, $this->app_merge_fields->get_flat('quotations', 'other', '{email_signature}'));
        $data['quotations_sale_agents'] = $this->quotations_model->get_sale_agents();
        $data['quotation_statuses']     = $this->quotations_model->get_statuses();
        $data['members']               = $this->staff_model->get('', ['active' => 1]);
        $data['quotation_merge_fields'] = $merge_fields;
        $data['quotation']              = $quotation;
        $data['totalNotes']            = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'quotation']);

        if ($to_return == false) {
            $this->load->view('admin/quotations/quotations_preview_template', $data);
        } else {
            return $this->load->view('admin/quotations/quotations_preview_template', $data, true);
        }
    }

/*
    public function add_note($rel_id)
    {
        if ($this->input->post() && user_can_view_quotation($rel_id)) {
            $this->misc_model->add_note($this->input->post(), 'quotation', $rel_id);
            echo $rel_id;
        }
    }

    public function get_notes($id)
    {
        if (user_can_view_quotation($id)) {
            $data['notes'] = $this->misc_model->get_notes($id, 'quotation');
            $this->load->view('admin/includes/sales_notes_template', $data);
        }
    }
    public function convert_to_quotation($id)
    {
        if (!has_permission('quotations', '', 'create')) {
            access_denied('quotations');
        }
        if ($this->input->post()) {
            $this->load->model('quotations_model');
            $quotation_id = $this->quotations_model->add($this->input->post());
            if ($quotation_id) {
                set_alert('success', _l('quotation_converted_to_quotation_success'));
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . 'quotations', [
                    'quotation_id' => $quotation_id,
                    'status'      => 3,
                ]);
                log_activity('Quotation Converted to Estimate [EstimateID: ' . $quotation_id . ', QuotationID: ' . $id . ']');

                hooks()->do_action('quotation_converted_to_quotation', ['quotation_id' => $id, 'quotation_id' => $quotation_id]);

                redirect(admin_url('quotations/quotation/' . $quotation_id));
            } else {
                set_alert('danger', _l('quotation_converted_to_quotation_fail'));
            }
            if ($this->set_quotation_pipeline_autoload($id)) {
                redirect(admin_url('quotations'));
            } else {
                redirect(admin_url('quotations/list_quotations/' . $id .'#' . $id));
            }
        }
    }
*/
    public function convert_to_invoice($id)
    {
        if (!has_permission('invoices', '', 'create')) {
            access_denied('invoices');
        }
        if ($this->input->post()) {
            $this->load->model('invoices_model');
            $invoice_id = $this->invoices_model->add($this->input->post());
            if ($invoice_id) {
                set_alert('success', _l('quotation_converted_to_invoice_success'));
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . 'quotations', [
                    'invoice_id' => $invoice_id,
                    'status'     => 3,
                ]);
                log_activity('Quotation Converted to Invoice [InvoiceID: ' . $invoice_id . ', QuotationID: ' . $id . ']');
                hooks()->do_action('quotation_converted_to_invoice', ['quotation_id' => $id, 'invoice_id' => $invoice_id]);
                redirect(admin_url('invoices/invoice/' . $invoice_id));
            } else {
                set_alert('danger', _l('quotation_converted_to_invoice_fail'));
            }
            if ($this->set_quotation_pipeline_autoload($id)) {
                redirect(admin_url('quotations'));
            } else {
                redirect(admin_url('quotations/list_quotations/' . $id .'#' . $id));
            }
        }
    }

    public function get_invoice_convert_data($id)
    {
        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);
        $this->load->model('taxes_model');
        $data['taxes']         = $this->taxes_model->get();
        $data['currencies']    = $this->currencies_model->get();
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        $this->load->model('invoice_items_model');
        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->invoice_items_model->get_grouped();
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $data['staff']          = $this->staff_model->get('', ['active' => 1]);
        $data['quotation']       = $this->quotations_model->get($id);
        $data['billable_tasks'] = [];
        $data['add_items']      = $this->_parse_items($data['quotation']);

        if ($data['quotation']->rel_type == 'lead') {
            $this->db->where('leadid', $data['quotation']->rel_id);
            $data['customer_id'] = $this->db->get(db_prefix() . 'clients')->row()->userid;
        } else {
            $data['customer_id'] = $data['quotation']->rel_id;
        }
        $data['custom_fields_rel_transfer'] = [
            'belongs_to' => 'quotation',
            'rel_id'     => $id,
        ];
        $this->load->view('admin/quotations/invoice_convert_template', $data);
    }

    public function get_quotation_convert_data($id)
    {
        $this->load->model('taxes_model');
        $data['taxes']         = $this->taxes_model->get();
        $data['currencies']    = $this->currencies_model->get();
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        $this->load->model('invoice_items_model');
        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->invoice_items_model->get_grouped();
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $data['staff']     = $this->staff_model->get('', ['active' => 1]);
        $data['quotation']  = $this->quotations_model->get($id);
        $data['add_items'] = $this->_parse_items($data['quotation']);

        $this->load->model('quotations_model');
        $data['quotation_statuses'] = $this->quotations_model->get_statuses();
        if ($data['quotation']->rel_type == 'lead') {
            $this->db->where('leadid', $data['quotation']->rel_id);
            $data['customer_id'] = $this->db->get(db_prefix() . 'clients')->row()->userid;
        } else {
            $data['customer_id'] = $data['quotation']->rel_id;
        }

        $data['custom_fields_rel_transfer'] = [
            'belongs_to' => 'quotation',
            'rel_id'     => $id,
        ];

        $this->load->view('admin/quotations/quotation_convert_template', $data);
    }

    private function _parse_items($quotation)
    {
        $items = [];
        foreach ($quotation->items as $item) {
            $taxnames = [];
            $taxes    = get_quotation_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                array_push($taxnames, $tax['taxname']);
            }
            $item['taxname']        = $taxnames;
            $item['parent_item_id'] = $item['id'];
            $item['id']             = 0;
            $items[]                = $item;
        }

        return $items;
    }

    /* Send quotation to email */
    public function send_to_email($id)
    {
        $canView = user_can_view_quotation($id);
        if (!$canView) {
            access_denied('quotations');
        } else {
            if (!has_permission('quotations', '', 'view') && !has_permission('quotations', '', 'view_own') && $canView == false) {
                access_denied('quotations');
            }
        }

        if ($this->input->post()) {
            try {
                $success = $this->quotations_model->send_quotation_to_email(
                    $id,
                    $this->input->post('attach_pdf'),
                    $this->input->post('cc')
                );
            } catch (Exception $e) {
                $message = $e->getMessage();
                echo $message;
                if (strpos($message, 'Unable to get the size of the image') !== false) {
                    show_pdf_unable_to_get_image_size_error();
                }
                die;
            }

            if ($success) {
                set_alert('success', _l('quotation_sent_to_email_success'));
            } else {
                set_alert('danger', _l('quotation_sent_to_email_fail'));
            }

            if ($this->set_quotation_pipeline_autoload($id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('quotations/list_quotations/' . $id .'#' . $id));
            }
        }
    }

    public function copy($id)
    {
        if (!has_permission('quotations', '', 'create')) {
            access_denied('quotations');
        }
        $new_id = $this->quotations_model->copy($id);
        if ($new_id) {
            set_alert('success', _l('quotation_copy_success'));
            $this->set_quotation_pipeline_autoload($new_id);
            redirect(admin_url('quotations/quotation/' . $new_id));
        } else {
            set_alert('success', _l('quotation_copy_fail'));
        }
        if ($this->set_quotation_pipeline_autoload($id)) {
            redirect(admin_url('quotations'));
        } else {
            redirect(admin_url('quotations/list_quotations/' . $id .'#' . $id));
        }
    }

    public function mark_action_status($status, $id)
    {
        if (!has_permission('quotations', '', 'edit')) {
            access_denied('quotations');
        }
        $success = $this->quotations_model->mark_action_status($status, $id);
        if ($success) {
            set_alert('success', _l('quotation_status_changed_success'));
        } else {
            set_alert('danger', _l('quotation_status_changed_fail'));
        }
        if ($this->set_quotation_pipeline_autoload($id)) {
            redirect(admin_url('quotations'));
        } else {
            redirect(admin_url('quotations/list_quotations/' . $id .'#' . $id));
        }
    }

    public function delete($id)
    {
        if (!has_permission('quotations', '', 'delete')) {
            access_denied('quotations');
        }
        $response = $this->quotations_model->delete($id);
        if ($response == true) {
            set_alert('success', _l('deleted', _l('quotation')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('quotation_lowercase')));
        }
        redirect(admin_url('quotations'));
    }

    public function get_relation_data_values($rel_id, $rel_type)
    {
        echo json_encode($this->quotations_model->get_relation_data_values($rel_id, $rel_type));
    }

    public function add_quotation_comment()
    {
        if ($this->input->post()) {
            echo json_encode([
                'success' => $this->quotations_model->add_comment($this->input->post()),
            ]);
        }
    }
     
    public function edit_comment($id)
    {
        if ($this->input->post()) {
            echo json_encode([
                'success' => $this->quotations_model->edit_comment($this->input->post(), $id),
                'message' => _l('comment_updated_successfully'),
            ]);
        }
    }

    public function get_quotation_comments($id)
    {
        $data['comments'] = $this->quotations_model->get_comments($id);
        $this->load->view('admin/quotations/comments_template', $data);
    }

    public function remove_comment($id)
    {
        $this->db->where('id', $id);
        $comment = $this->db->get(db_prefix() . 'quotation_comments')->row();
        if ($comment) {
            if ($comment->staffid != get_staff_user_id() && !is_admin()) {
                echo json_encode([
                    'success' => false,
                ]);
                die;
            }
            echo json_encode([
                'success' => $this->quotations_model->remove_comment($id),
            ]);
        } else {
            echo json_encode([
                'success' => false,
            ]);
        }
    }

    public function add_quotation_note()
    {
        if ($this->input->post()) {
            echo json_encode([
                'success' => $this->quotations_model->add_note($this->input->post()),
            ]);
        }
    }

    public function edit_note($id)
    {
        if ($this->input->post()) {
            echo json_encode([
                'success' => $this->quotations_model->edit_note($this->input->post(), $id),
                'message' => _l('note_updated_successfully'),
            ]);
        }
    }

    public function get_quotation_notes($id)
    {
        $data['notes'] = $this->quotations_model->get_notes($id);
        $this->load->view('admin/quotations/notes_template', $data);
    }

    public function remove_note($id)
    {
        $this->db->where('id', $id);
        $note = $this->db->get(db_prefix() . 'quotation_notes')->row();
        if ($note) {
            if ($note->staffid != get_staff_user_id() && !is_admin()) {
                echo json_encode([
                    'success' => false,
                ]);
                die;
            }
            echo json_encode([
                'success' => $this->quotations_model->remove_note($id),
            ]);
        } else {
            echo json_encode([
                'success' => false,
            ]);
        }
    }


    public function save_quotation_data()
    {
        if (!has_permission('quotations', '', 'edit') && !has_permission('quotations', '', 'create')) {
            header('HTTP/1.0 400 Bad error');
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied'),
            ]);
            die;
        }
        $success = false;
        $message = '';

        $this->db->where('id', $this->input->post('quotation_id'));
        $this->db->update(db_prefix() . 'quotations', [
            'content' => html_purify($this->input->post('content', false)),
        ]);

        $success = $this->db->affected_rows() > 0;
        $message = _l('updated_successfully', _l('quotation'));

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    // Pipeline
    public function pipeline($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'true';
        } else {
            $set = 'false';
        }
        $this->session->set_userdata([
            'quotations_pipeline' => $set,
        ]);
        if ($manual == false) {
            redirect(admin_url('quotations'));
        }
    }

    public function pipeline_open($id)
    {
        if (has_permission('quotations', '', 'view') || has_permission('quotations', '', 'view_own') || get_option('allow_staff_view_quotations_assigned') == 1) {
            $data['quotation']      = $this->get_quotation_data_ajax($id, true);
            $data['quotation_data'] = $this->quotations_model->get($id);
            $this->load->view('admin/quotations/pipeline/quotation', $data);
        }
    }

    public function update_pipeline()
    {
        if (has_permission('quotations', '', 'edit')) {
            $this->quotations_model->update_pipeline($this->input->post());
        }
    }

    public function get_pipeline()
    {
        if (has_permission('quotations', '', 'view') || has_permission('quotations', '', 'view_own') || get_option('allow_staff_view_quotations_assigned') == 1) {
            $data['statuses'] = $this->quotations_model->get_statuses();
            $this->load->view('admin/quotations/pipeline/pipeline', $data);
        }
    }

    public function pipeline_load_more()
    {
        $status = $this->input->get('status');
        $page   = $this->input->get('page');

        $quotations = (new QuotationsPipeline($status))
        ->search($this->input->get('search'))
        ->sortBy(
            $this->input->get('sort_by'),
            $this->input->get('sort')
        )
        ->page($page)->get();

        foreach ($quotations as $quotation) {
            $this->load->view('admin/quotations/pipeline/_kanban_card', [
                'quotation' => $quotation,
                'status'   => $status,
            ]);
        }
    }

    public function set_quotation_pipeline_autoload($id)
    {
        if ($id == '') {
            return false;
        }

        if ($this->session->has_userdata('quotations_pipeline') && $this->session->userdata('quotations_pipeline') == 'true') {
            $this->session->set_flashdata('quotationid', $id);

            return true;
        }

        return false;
    }

    public function get_due_date()
    {
        if ($this->input->post()) {
            $date    = $this->input->post('date');
            $duedate = '';
            if (get_option('quotation_due_after') != 0) {
                $date    = to_sql_date($date);
                $d       = date('Y-m-d', strtotime('+' . get_option('quotation_due_after') . ' DAY', strtotime($date)));
                $duedate = _d($d);
                echo $duedate;
            }
        }
    }
}
