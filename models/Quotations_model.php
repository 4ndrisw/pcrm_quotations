<?php

use app\services\AbstractKanban;
use app\services\quotations\QuotationsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Quotations_model extends App_Model
{
    private $statuses;

    private $copy = false;

    public function __construct()
    {
        parent::__construct();
        $this->statuses = hooks()->apply_filters('before_set_quotation_statuses', [
            1,
            5,
            2,
            3,
            6,
            4,
        ]);
    }

    public function get_statuses()
    {
        return $this->statuses;
    }

    public function get_sale_agents()
    {
        return $this->db->query('SELECT DISTINCT(assigned) as sale_agent FROM ' . db_prefix() . 'quotations WHERE assigned != 0')->result_array();
    }

    public function get_quotations_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'quotations')->result_array();
    }

    /**
     * Inserting new quotation function
     * @param mixed $data $_POST data
     */
    public function add($data)
    {
        $data['allow_comments'] = isset($data['allow_comments']) ? 1 : 0;

        $save_and_send = isset($data['save_and_send']);

        $tags = isset($data['tags']) ? $data['tags'] : '';

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $quotationRequestID = false;
        if (isset($data['quotation_request_id'])) {
            $quotationRequestID = $data['quotation_request_id'];
            unset($data['quotation_request_id']);
        }

        $data['address'] = trim($data['address']);
        $data['address'] = nl2br($data['address']);

        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['addedfrom']   = get_staff_user_id();
        $data['hash']        = app_generate_hash();

        $data['prefix'] = get_option('quotation_prefix');

        $data['number_format'] = get_option('quotation_number_format');
        
        if (empty($data['rel_type'])) {
            unset($data['rel_type']);
            unset($data['rel_id']);
        } else {
            if (empty($data['rel_id'])) {
                unset($data['rel_type']);
                unset($data['rel_id']);
            }
        }

        $items = [];
        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }

        if ($this->copy == false) {
            $data['content'] = '{quotation_items}';
        }

        $hook = hooks()->apply_filters('before_create_quotation', [
            'data'  => $data,
            'items' => $items,
        ]);


        $data  = $hook['data'];
        $items = $hook['items'];
        unset($data['tags'],$data['item_select'], $data['description'], $data['long_description'],
              $data['quantity'], $data['unit'],$data['rate'],$data['save_and_send']
             );
        $this->db->insert(db_prefix() . 'quotations', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {

            // Update next quotation number in settings
            $this->db->where('name', 'next_quotation_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            if ($quotationRequestID !== false && $quotationRequestID != '') {
                $this->load->model('quotation_request_model');
                $completedStatus = $this->quotation_request_model->get_status_by_flag('completed');
                $this->quotation_request_model->update_request_status([
                    'requestid' => $quotationRequestID,
                    'status'    => $completedStatus->id,
                ]);
            }

            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            handle_tags_save($tags, $insert_id, 'quotation');

            foreach ($items as $key => $item) {
                if ($itemid = add_new_sales_item_post($item, $insert_id, 'quotation')) {
                    _maybe_insert_post_item_tax($itemid, $item, $insert_id, 'quotation');
                }
            }

            $quotation = $this->get($insert_id);
            if ($quotation->assigned != 0) {
                if ($quotation->assigned != get_staff_user_id()) {
                    $notified = add_notification([
                        'description'     => 'not_quotation_assigned_to_you',
                        'touserid'        => $quotation->assigned,
                        'fromuserid'      => get_staff_user_id(),
                        'link'            => 'quotations/list_quotations/' . $insert_id,
                        'additional_data' => serialize([
                            $quotation->subject,
                        ]),
                    ]);
                    if ($notified) {
                        pusher_trigger_notification([$quotation->assigned]);
                    }
                }
            }

            if ($data['rel_type'] == 'lead') {
                $this->load->model('leads_model');
                $this->leads_model->log_lead_activity($data['rel_id'], 'not_lead_activity_created_quotation', false, serialize([
                    '<a href="' . admin_url('quotations/list_quotations/' . $insert_id) . '" target="_blank">' . $data['subject'] . '</a>',
                ]));
            }

            update_sales_total_tax_column($insert_id, 'quotation', db_prefix() . 'quotations');

            log_activity('New Quotation Created [ID: ' . $insert_id . ']');

            if ($save_and_send === true) {
                $this->send_quotation_to_email($insert_id);
            }

            hooks()->do_action('quotation_created', $insert_id);

            return $insert_id;
        }

        return false;
    }

    /**
     * Update quotation
     * @param  mixed $data $_POST data
     * @param  mixed $id   quotation id
     * @return boolean
     */
    public function update($data, $id)
    {
        $affectedRows = 0;

        $data['allow_comments'] = isset($data['allow_comments']) ? 1 : 0;

        $current_quotation = $this->get($id);

        $save_and_send = isset($data['save_and_send']);

        if (empty($data['rel_type'])) {
            $data['rel_id']   = null;
            $data['rel_type'] = '';
        } else {
            if (empty($data['rel_id'])) {
                $data['rel_id']   = null;
                $data['rel_type'] = '';
            }
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        $items = [];
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
        }

        $newitems = [];
        if (isset($data['newitems'])) {
            $newitems = $data['newitems'];
            unset($data['newitems']);
        }

        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $id, 'quotation')) {
                $affectedRows++;
            }
        }

        $data['address'] = trim($data['address']);
        $data['address'] = nl2br($data['address']);

        $hook = hooks()->apply_filters('before_quotation_updated', [
            'data'          => $data,
            'items'         => $items,
            'newitems'      => $newitems,
            'removed_items' => isset($data['removed_items']) ? $data['removed_items'] : [],
        ], $id);

        $data                  = $hook['data'];
        $data['removed_items'] = $hook['removed_items'];
        $newitems              = $hook['newitems'];
        $items                 = $hook['items'];

        // Delete items checked to be removed from database
        foreach ($data['removed_items'] as $remove_item_id) {
            if (handle_removed_sales_item_post($remove_item_id, 'quotation')) {
                $affectedRows++;
            }
        }

        unset($data['removed_items']);
        unset($data['tags']);
        unset($data['item_select']);
        unset($data['description']);
        unset($data['long_description']);
        unset($data['quantity']);
        unset($data['unit']);
        unset($data['rate']);
        unset($data['taxname']);
        unset($data['save_and_send']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quotations', $data);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            $quotation_now = $this->get($id);
            if ($current_quotation->assigned != $quotation_now->assigned) {
                if ($quotation_now->assigned != get_staff_user_id()) {
                    $notified = add_notification([
                        'description'     => 'not_quotation_assigned_to_you',
                        'touserid'        => $quotation_now->assigned,
                        'fromuserid'      => get_staff_user_id(),
                        'link'            => 'quotations/list_quotations/' . $id,
                        'additional_data' => serialize([
                            $quotation_now->subject,
                        ]),
                    ]);
                    if ($notified) {
                        pusher_trigger_notification([$quotation_now->assigned]);
                    }
                }
            }
        }

        foreach ($items as $key => $item) {
            if (update_sales_item_post($item['itemid'], $item)) {
                $affectedRows++;
            }

            if (isset($item['custom_fields'])) {
                if (handle_custom_fields_post($item['itemid'], $item['custom_fields'])) {
                    $affectedRows++;
                }
            }

            if (!isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                if (delete_taxes_from_item($item['itemid'], 'quotation')) {
                    $affectedRows++;
                }
            } else {
                $item_taxes        = get_quotation_item_taxes($item['itemid']);
                $_item_taxes_names = [];
                foreach ($item_taxes as $_item_tax) {
                    array_push($_item_taxes_names, $_item_tax['taxname']);
                }
                $i = 0;
                foreach ($_item_taxes_names as $_item_tax) {
                    if (!in_array($_item_tax, $item['taxname'])) {
                        $this->db->where('id', $item_taxes[$i]['id'])
                        ->delete(db_prefix() . 'item_tax');
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                    $i++;
                }
                if (_maybe_insert_post_item_tax($item['itemid'], $item, $id, 'quotation')) {
                    $affectedRows++;
                }
            }
        }

        foreach ($newitems as $key => $item) {
            if ($new_item_added = add_new_sales_item_post($item, $id, 'quotation')) {
                _maybe_insert_post_item_tax($new_item_added, $item, $id, 'quotation');
                $affectedRows++;
            }
        }

        if ($affectedRows > 0) {
            update_sales_total_tax_column($id, 'quotation', db_prefix() . 'quotations');
            log_activity('Quotation Updated [ID:' . $id . ']');
        }

        if ($save_and_send === true) {
            $this->send_quotation_to_email($id);
        }

        if ($affectedRows > 0) {
            hooks()->do_action('after_quotation_updated', $id);

            return true;
        }

        return false;
    }

    /**
     * Get quotations
     * @param  mixed $id quotation id OPTIONAL
     * @return mixed
     */
    public function get($id = '', $where = [], $for_editor = false)
    {
        $this->db->where($where);

        if (is_client_logged_in()) {
            $this->db->where('status !=', 0);
        }

        $this->db->select('*,' . db_prefix() . 'currencies.id as currencyid, ' . db_prefix() . 'quotations.id as id, ' . db_prefix() . 'currencies.name as currency_name');
        $this->db->from(db_prefix() . 'quotations');
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'quotations.currency', 'left');

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'quotations.id', $id);
            $quotation = $this->db->get()->row();
            if ($quotation) {
                $quotation->attachments                           = $this->get_attachments($id);
                $quotation->items                                 = get_items_by_type('quotation', $id);
                $quotation->visible_attachments_to_customer_found = false;
                foreach ($quotation->attachments as $attachment) {
                    if ($attachment['visible_to_customer'] == 1) {
                        $quotation->visible_attachments_to_customer_found = true;

                        break;
                    }
                }
                /*
                 *next_feature
                if ($for_editor == false) {
                    $quotation = parse_quotation_content_merge_fields($quotation);
                }
                */
            }

            $quotation->client = $this->clients_model->get($quotation->rel_id);

            if (!$quotation->client) {
                $quotation->client          = new stdClass();
                $quotation->client->company = $quotation->deleted_customer_name;
            }
            
            return $quotation;
        }

        return $this->db->get()->result_array();
    }

    public function clear_signature($id)
    {
        $this->db->select('signature');
        $this->db->where('id', $id);
        $quotation = $this->db->get(db_prefix() . 'quotations')->row();

        if ($quotation) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'quotations', ['signature' => null]);

            if (!empty($quotation->signature)) {
                unlink(get_upload_path_by_type('quotation') . $id . '/' . $quotation->signature);
            }

            return true;
        }

        return false;
    }

    public function update_pipeline($data)
    {
        $this->mark_action_status($data['status'], $data['quotationid']);
        AbstractKanban::updateOrder($data['order'], 'pipeline_order', 'quotations', $data['status']);
    }

    public function get_attachments($quotation_id, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $quotation_id);
        }
        $this->db->where('rel_type', 'quotation');
        $result = $this->db->get(db_prefix() . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }

        return $result->result_array();
    }

    /**
     *  Delete quotation attachment
     * @param   mixed $id  attachmentid
     * @return  boolean
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('quotation') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Quotation Attachment Deleted [ID: ' . $attachment->rel_id . ']');
            }
            if (is_dir(get_upload_path_by_type('quotation') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('quotation') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('quotation') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Add quotation comment
     * @param mixed  $data   $_POST comment data
     * @param boolean $client is request coming from the client side
     */
    public function add_comment($data, $client = false)
    {
        if (is_staff_logged_in()) {
            $client = false;
        }

        if (isset($data['action'])) {
            unset($data['action']);
        }
        $data['dateadded'] = date('Y-m-d H:i:s');
        if ($client == false) {
            $data['staffid'] = get_staff_user_id();
        }
        $data['content'] = nl2br($data['content']);
        $this->db->insert(db_prefix() . 'quotation_comments', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            $quotation = $this->get($data['quotationid']);

            // No notifications client when quotation is with draft status
            if ($quotation->status == '6' && $client == false) {
                return true;
            }

            if ($client == true) {
                // Get creator and assigned
                $this->db->select('staffid,email,phonenumber');
                $this->db->where('staffid', $quotation->addedfrom);
                $this->db->or_where('staffid', $quotation->assigned);
                $staff_quotation = $this->db->get(db_prefix() . 'staff')->result_array();
                $notifiedUsers  = [];
                foreach ($staff_quotation as $member) {
                    $notified = add_notification([
                        'description'     => 'not_quotation_comment_from_client',
                        'touserid'        => $member['staffid'],
                        'fromcompany'     => 1,
                        'fromuserid'      => 0,
                        'link'            => 'quotations/list_quotations/' . $data['quotationid'],
                        'additional_data' => serialize([
                            $quotation->subject,
                        ]),
                    ]);

                    if ($notified) {
                        array_push($notifiedUsers, $member['staffid']);
                    }

                    $template     = mail_template('quotation_comment_to_staff', $quotation->id, $member['email']);
                    $merge_fields = $template->get_merge_fields();
                    $template->send();
                    // Send email/sms to admin that client commented
                    $this->app_sms->trigger(SMS_TRIGGER_PROPOSAL_NEW_COMMENT_TO_STAFF, $member['phonenumber'], $merge_fields);
                }
                pusher_trigger_notification($notifiedUsers);
            } else {
                // Send email/sms to client that admin commented
                $template     = mail_template('quotation_comment_to_customer', $quotation);
                $merge_fields = $template->get_merge_fields();
                $template->send();
                $this->app_sms->trigger(SMS_TRIGGER_PROPOSAL_NEW_COMMENT_TO_CUSTOMER, $quotation->phone, $merge_fields);
            }

            return true;
        }

        return false;
    }

    public function edit_comment($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quotation_comments', [
            'content' => nl2br($data['content']),
        ]);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get quotation comments
     * @param  mixed $id quotation id
     * @return array
     */
    public function get_comments($id)
    {
        $this->db->where('quotationid', $id);
        $this->db->order_by('dateadded', 'ASC');

        return $this->db->get(db_prefix() . 'quotation_comments')->result_array();
    }

    /**
     * Get quotation single comment
     * @param  mixed $id  comment id
     * @return object
     */
    public function get_comment($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'quotation_comments')->row();
    }

    /**
     * Remove quotation comment
     * @param  mixed $id comment id
     * @return boolean
     */
    public function remove_comment($id)
    {
        $comment = $this->get_comment($id);
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'quotation_comments');
        if ($this->db->affected_rows() > 0) {
            log_activity('Quotation Comment Removed [QuotationID:' . $comment->quotationid . ', Comment Content: ' . $comment->content . ']');

            return true;
        }

        return false;
    }



    /**
     * Add quotation note
     * @param mixed  $data   $_POST note data
     * @param boolean $client is request coming from the client side
     */
    public function add_note($data, $client = false)
    {
        if (is_staff_logged_in()) {
            $client = false;
        }

        if (isset($data['action'])) {
            unset($data['action']);
        }
        $data['dateadded'] = date('Y-m-d H:i:s');
        if ($client == false) {
            $data['staffid'] = get_staff_user_id();
        }
        $data['content'] = nl2br($data['content']);
        $this->db->insert(db_prefix() . 'quotation_notes', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            $quotation = $this->get($data['quotationid']);

            // No notifications client when quotation is with draft status
            if ($quotation->status == '6' && $client == false) {
                return true;
            }

            if ($client == true) {
                // Get creator and assigned
                $this->db->select('staffid,email,phonenumber');
                $this->db->where('staffid', $quotation->addedfrom);
                $this->db->or_where('staffid', $quotation->assigned);
                $staff_quotation = $this->db->get(db_prefix() . 'staff')->result_array();
                $notifiedUsers  = [];
                foreach ($staff_quotation as $member) {
                    $notified = add_notification([
                        'description'     => 'not_quotation_note_from_client',
                        'touserid'        => $member['staffid'],
                        'fromcompany'     => 1,
                        'fromuserid'      => 0,
                        'link'            => 'quotations/list_quotations/' . $data['quotationid'],
                        'additional_data' => serialize([
                            $quotation->subject,
                        ]),
                    ]);

                    if ($notified) {
                        array_push($notifiedUsers, $member['staffid']);
                    }

                    $template     = mail_template('quotation_note_to_staff', $quotation->id, $member['email']);
                    $merge_fields = $template->get_merge_fields();
                    $template->send();
                    // Send email/sms to admin that client noteed
                    $this->app_sms->trigger(SMS_TRIGGER_PROPOSAL_NEW_note_TO_STAFF, $member['phonenumber'], $merge_fields);
                }
                pusher_trigger_notification($notifiedUsers);
            } else {
                // Send email/sms to client that admin noteed
                $template     = mail_template('quotation_note_to_customer', $quotation);
                $merge_fields = $template->get_merge_fields();
                $template->send();
                $this->app_sms->trigger(SMS_TRIGGER_PROPOSAL_NEW_note_TO_CUSTOMER, $quotation->phone, $merge_fields);
            }

            return true;
        }

        return false;
    }

    public function edit_note($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quotation_notes', [
            'content' => nl2br($data['content']),
        ]);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get quotation notes
     * @param  mixed $id quotation id
     * @return array
     */
    public function get_notes($id)
    {
        $this->db->where('quotationid', $id);
        $this->db->order_by('dateadded', 'ASC');

        return $this->db->get(db_prefix() . 'quotation_notes')->result_array();
    }

    /**
     * Get quotation single note
     * @param  mixed $id  note id
     * @return object
     */
    public function get_note($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'quotation_notes')->row();
    }

    /**
     * Remove quotation note
     * @param  mixed $id note id
     * @return boolean
     */
    public function remove_note($id)
    {
        $note = $this->get_note($id);
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'quotation_notes');
        if ($this->db->affected_rows() > 0) {
            log_activity('Quotation note Removed [QuotationID:' . $note->quotationid . ', note Content: ' . $note->content . ']');

            return true;
        }

        return false;
    }

    /**
     * Copy quotation
     * @param  mixed $id quotation id
     * @return mixed
     */
    public function copy($id)
    {
        $this->copy      = true;
        $quotation        = $this->get($id, [], true);
        $not_copy_fields = [
            'addedfrom',
            'id',
            'datecreated',
            'hash',
            'status',
            'invoice_id',
            'quotation_id',
            'is_expiry_notified',
            'date_converted',
            'signature',
            'acceptance_firstname',
            'acceptance_lastname',
            'acceptance_email',
            'acceptance_date',
            'acceptance_ip',
        ];
        $fields      = $this->db->list_fields(db_prefix() . 'quotations');
        $insert_data = [];
        foreach ($fields as $field) {
            if (!in_array($field, $not_copy_fields)) {
                $insert_data[$field] = $quotation->$field;
            }
        }

        $insert_data['addedfrom']   = get_staff_user_id();
        $insert_data['datecreated'] = date('Y-m-d H:i:s');
        $insert_data['date']        = _d(date('Y-m-d'));
        $insert_data['status']      = 6;
        $insert_data['hash']        = app_generate_hash();

        // in case open till is expired set new 7 days starting from current date
        if ($insert_data['open_till'] && get_option('quotation_due_after') != 0) {
            $insert_data['open_till'] = _d(date('Y-m-d', strtotime('+' . get_option('quotation_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }

        $insert_data['newitems'] = [];
        $custom_fields_items     = get_custom_fields('items');
        $key                     = 1;
        foreach ($quotation->items as $item) {
            $insert_data['newitems'][$key]['description']      = $item['description'];
            $insert_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $insert_data['newitems'][$key]['qty']              = $item['qty'];
            $insert_data['newitems'][$key]['unit']             = $item['unit'];
            $insert_data['newitems'][$key]['taxname']          = [];
            $taxes                                             = get_quotation_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($insert_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $insert_data['newitems'][$key]['rate']  = $item['rate'];
            $insert_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $insert_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }

        $id = $this->add($insert_data);

        if ($id) {
            $custom_fields = get_custom_fields('quotation');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($quotation->id, $field['id'], 'quotation', false);
                if ($value == '') {
                    continue;
                }
                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'quotation',
                    'value'   => $value,
                ]);
            }

            $tags = get_tags_in($quotation->id, 'quotation');
            handle_tags_save($tags, $id, 'quotation');

            log_activity('Copied Quotation ' . format_quotation_number($quotation->id));

            return $id;
        }

        return false;
    }

    /**
     * Take quotation action (change status) manually
     * @param  mixed $status status id
     * @param  mixed  $id     quotation id
     * @param  boolean $client is request coming from client side or not
     * @return boolean
     */
    public function mark_action_status($status, $id, $client = false)
    {
        $original_quotation = $this->get($id);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'quotations', [
            'status' => $status,
        ]);

        if ($this->db->affected_rows() > 0) {
            // Client take action
            if ($client == true) {
                $revert = false;
                // Declined
                if ($status == 2) {
                    $message = 'not_quotation_quotation_declined';
                } elseif ($status == 3) {
                    $message = 'not_quotation_quotation_accepted';
                // Accepted
                } else {
                    $revert = true;
                }
                // This is protection that only 3 and 4 statuses can be taken as action from the client side
                if ($revert == true) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'quotations', [
                        'status' => $original_quotation->status,
                    ]);

                    return false;
                }

                // Get creator and assigned;
                $this->db->where('staffid', $original_quotation->addedfrom);
                $this->db->or_where('staffid', $original_quotation->assigned);
                $staff_quotation = $this->db->get(db_prefix() . 'staff')->result_array();
                $notifiedUsers  = [];
                foreach ($staff_quotation as $member) {
                    $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => $message,
                            'link'            => 'quotations/list_quotations/' . $id,
                            'additional_data' => serialize([
                                format_quotation_number($id),
                            ]),
                        ]);
                    if ($notified) {
                        array_push($notifiedUsers, $member['staffid']);
                    }
                }

                pusher_trigger_notification($notifiedUsers);

                // Send thank you to the customer email template
                if ($status == 3) {
                    foreach ($staff_quotation as $member) {
                        send_mail_template('quotation_accepted_to_staff', $original_quotation, $member['email']);
                    }

                    send_mail_template('quotation_accepted_to_customer', $original_quotation);

                    hooks()->do_action('quotation_accepted', $id);
                } else {

                    // Client declined send template to admin
                    foreach ($staff_quotation as $member) {
                        send_mail_template('quotation_declined_to_staff', $original_quotation, $member['email']);
                    }

                    hooks()->do_action('quotation_declined', $id);
                }
            } else {
                // in case admin mark as open the the open till date is smaller then current date set open till date 7 days more
                if ((date('Y-m-d', strtotime($original_quotation->open_till)) < date('Y-m-d')) && $status == 1) {
                    $open_till = date('Y-m-d', strtotime('+7 DAY', strtotime(date('Y-m-d'))));
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'quotations', [
                        'open_till' => $open_till,
                    ]);
                }
            }

            log_activity('Quotation Status Changes [QuotationID:' . $id . ', Status:' . format_quotation_status($status, '', false) . ',Client Action: ' . (int) $client . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete quotation
     * @param  mixed $id quotation id
     * @return boolean
     */
    public function delete($id)
    {
        $this->clear_signature($id);
        $quotation = $this->get($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'quotations');
        if ($this->db->affected_rows() > 0) {
            if (!is_null($quotation->short_link)) {
                app_archive_short_link($quotation->short_link);
            }

            delete_tracked_emails($id, 'quotation');

            $this->db->where('quotationid', $id);
            $this->db->delete(db_prefix() . 'quotation_comments');
            // Get related tasks
            $this->db->where('rel_type', 'quotation');
            $this->db->where('rel_id', $id);

            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'quotation');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('relid IN (SELECT id from ' . db_prefix() . 'itemable WHERE rel_type="quotation" AND rel_id="' . $this->db->escape_str($id) . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'quotation');
            $this->db->delete(db_prefix() . 'itemable');


            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'quotation');
            $this->db->delete(db_prefix() . 'item_tax');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'quotation');
            $this->db->delete(db_prefix() . 'taggables');

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'quotation');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_type', 'quotation');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_type', 'quotation');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'views_tracking');

            log_activity('Quotation Deleted [QuotationID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Get relation quotation data. Ex lead or customer will return the necesary db fields
     * @param  mixed $rel_id
     * @param  string $rel_type customer/lead
     * @return object
     */
    public function get_relation_data_values($rel_id, $rel_type)
    {
        $data = new StdClass();
        if ($rel_type == 'customer') {
            $this->db->where('userid', $rel_id);
            $_data = $this->db->get(db_prefix() . 'clients')->row();

            $primary_contact_id = get_primary_contact_user_id($rel_id);

            if ($primary_contact_id) {
                $contact     = $this->clients_model->get_contact($primary_contact_id);
                $data->email = $contact->email;
            }

            $data->phone            = $_data->phonenumber;
            $data->is_using_company = false;
            if (isset($contact)) {
                $data->to = $contact->firstname . ' ' . $contact->lastname;
            } else {
                if (!empty($_data->company)) {
                    $data->to               = $_data->company;
                    $data->is_using_company = true;
                }
            }
            $data->company = $_data->company;
            $data->address = clear_textarea_breaks($_data->address);
            $data->zip     = $_data->zip;
            $data->country = $_data->country;
            $data->state   = $_data->state;
            $data->city    = $_data->city;

            $default_currency = $this->clients_model->get_customer_default_currency($rel_id);
            if ($default_currency != 0) {
                $data->currency = $default_currency;
            }
        } elseif ($rel_type = 'lead') {
            $this->db->where('id', $rel_id);
            $_data       = $this->db->get(db_prefix() . 'leads')->row();
            $data->phone = $_data->phonenumber;

            $data->is_using_company = false;

            if (empty($_data->company)) {
                $data->to = $_data->name;
            } else {
                $data->to               = $_data->company;
                $data->is_using_company = true;
            }

            $data->company = $_data->company;
            $data->address = $_data->address;
            $data->email   = $_data->email;
            $data->zip     = $_data->zip;
            $data->country = $_data->country;
            $data->state   = $_data->state;
            $data->city    = $_data->city;
        }

        return $data;
    }

    /**
     * Sent quotation to email
     * @param  mixed  $id        quotationid
     * @param  string  $template  email template to sent
     * @param  boolean $attachpdf attach quotation pdf or not
     * @return boolean
     */
    public function send_expiry_reminder($id)
    {
        $quotation = $this->get($id);

        // For all cases update this to prevent sending multiple reminders eq on fail
        $this->db->where('id', $quotation->id);
        $this->db->update(db_prefix() . 'quotations', [
            'is_expiry_notified' => 1,
        ]);

        $template     = mail_template('quotation_expiration_reminder', $quotation);
        $merge_fields = $template->get_merge_fields();

        $template->send();

        if (can_send_sms_based_on_creation_date($quotation->datecreated)) {
            $sms_sent = $this->app_sms->trigger(SMS_TRIGGER_PROPOSAL_EXP_REMINDER, $quotation->phone, $merge_fields);
        }

        return true;
    }

    public function send_quotation_to_email($id, $attachpdf = true, $cc = '')
    {
        // Quotation status is draft update to sent
        if (total_rows(db_prefix() . 'quotations', ['id' => $id, 'status' => 6]) > 0) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'quotations', ['status' => 4]);
        }

        $quotation = $this->get($id);

        $sent = send_mail_template('quotation_send_to_customer', $quotation, $attachpdf, $cc);

        if ($sent) {

            // Set to status sent
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'quotations', [
                'status' => 4,
            ]);

            hooks()->do_action('quotation_sent', $id);

            return true;
        }

        return false;
    }

    public function do_kanban_query($status, $search = '', $page = 1, $sort = [], $count = false)
    {
        _deprecated_function('Quotation_model::do_kanban_query', '2.9.2', 'QuotationsPipeline class');

        $kanBan = (new QuotationsPipeline($status))
            ->search($search)
            ->page($page)
            ->sortBy($sort['sort'] ?? null, $sort['sort_by'] ?? null);

        if ($count) {
            return $kanBan->countAll();
        }

        return $kanBan->get();
    }

    /**
     * Update canban quotation status when drag and drop
     * @param  array $data quotation data
     * @return boolean
     */
    public function update_quotation_status($data)
    {
        $this->db->select('status');
        $this->db->where('id', $data['quotationid']);
        $_old = $this->db->get(db_prefix() . 'quotations')->row();

        $old_status = '';

        if ($_old) {
            $old_status = format_quotation_status($_old->status);
        }

        $affectedRows   = 0;
        $current_status = format_quotation_status($data['status']);


        $this->db->where('id', $data['quotationid']);
        $this->db->update(db_prefix() . 'quotations', [
            'status' => $data['status'],
        ]);

        $_log_message = '';

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            if ($current_status != $old_status && $old_status != '') {
                $_log_message    = 'not_quotation_activity_status_updated';
                $additional_data = serialize([
                    get_staff_full_name(),
                    $old_status,
                    $current_status,
                ]);

                hooks()->do_action('quotation_status_changed', [
                    'quotation_id'    => $data['quotationid'],
                    'old_status' => $old_status,
                    'new_status' => $current_status,
                ]);
            }
            $this->db->where('id', $data['quotationid']);
            $this->db->update(db_prefix() . 'quotations', [
                'last_status_change' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($affectedRows > 0) {
            if ($_log_message == '') {
                return true;
            }
            $this->log_quotation_activity($data['quotationid'], $_log_message, false, $additional_data);

            return true;
        }

        return false;
    }
    
    /**
     * Log quotation activity to database
     * @param mixed $id quotationid
     * @param string $description activity description
     */
    public function log_quotation_activity($id, $description = '', $client = false, $additional_data = '')
    {
        $staffid   = get_staff_user_id();
        $full_name = get_staff_full_name(get_staff_user_id());
        if (DEFINED('CRON')) {
            $staffid   = '[CRON]';
            $full_name = '[CRON]';
        } elseif ($client == true) {
            $staffid   = null;
            $full_name = '';
        }

        $this->db->insert(db_prefix() . 'quotation_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $id,
            'rel_type'        => 'quotation',
            'staffid'         => $staffid,
            'full_name'       => $full_name,
            'additional_data' => $additional_data,
        ]);
    }
}
