<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Injects theme CSS
 * @return null
 */
function quotations_head_component()
{
        echo '<link rel="stylesheet" type="text/css" id="quotations-css" href="'. base_url('modules/quotations/assets/css/quotations.css').'">';
    $CI = &get_instance();
    if (($CI->uri->segment(1) == 'admin' && $CI->uri->segment(2) == 'quotations') ||
        $CI->uri->segment(1) == 'quotations'){
    }
}


/**
 * Injects theme CSS
 * @return null
 */
function quotations_footer_js_component()
{
        echo '<script src="' . base_url('modules/quotations/assets/js/quotations.js') . '"></script>';
    $CI = &get_instance();
    if (($CI->uri->segment(1) == 'admin' && $CI->uri->segment(2) == 'quotations') ||
        ($CI->uri->segment(1) == 'admin' && $CI->uri->segment(2) == 'list_quotations') ||
        $CI->uri->segment(1) == 'quotations'){
    }
}


/**
 * Prepare general quotation pdf
 * @since  Version 1.0.2
 * @param  object $quotation quotation as object with all necessary fields
 * @param  string $tag tag for bulk pdf exporter
 * @return mixed object
 */
function quotation_pdf($quotation, $tag = '')
{
    return app_pdf('quotation',  module_libs_path(QUOTATIONS_MODULE_NAME) . 'pdf/Quotation_pdf', $quotation, $tag);
}


/**
 * Get quotation short_url
 * @since  Version 2.7.3
 * @param  object $quotation
 * @return string Url
 */
function get_quotation_shortlink($quotation)
{
    $long_url = site_url("quotation/{$quotation->id}/{$quotation->hash}");
    if (!get_option('bitly_access_token')) {
        return $long_url;
    }

    // Check if quotation has short link, if yes return short link
    if (!empty($quotation->short_link)) {
        return $quotation->short_link;
    }

    // Create short link and return the newly created short link
    $short_link = app_generate_short_link([
        'long_url' => $long_url,
        'title'    => format_quotation_number($quotation->id),
    ]);

    if ($short_link) {
        $CI = &get_instance();
        $CI->db->where('id', $quotation->id);
        $CI->db->update(db_prefix() . 'quotations', [
            'short_link' => $short_link,
        ]);

        return $short_link;
    }

    return $long_url;
}

/**
 * Check if quotation hash is equal
 * @param  mixed $id   quotation id
 * @param  string $hash quotation hash
 * @return void
 */
function check_quotation_restrictions($id, $hash)
{
    $CI = &get_instance();
    $CI->load->model('quotations_model');
    if (!$hash || !$id) {
        show_404();
    }
    $quotation = $CI->quotations_model->get($id);
    if (!$quotation || ($quotation->hash != $hash)) {
        show_404();
    }
}

/**
 * Check if quotation email template for expiry reminders is enabled
 * @return boolean
 */
function is_quotations_email_expiry_reminder_enabled()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'quotation-expiry-reminder', 'active' => 1]) > 0;
}

/**
 * Check if there are sources for sending quotation expiry reminders
 * Will be either email or SMS
 * @return boolean
 */
function is_quotations_expiry_reminders_enabled()
{
    return is_quotations_email_expiry_reminder_enabled() || is_sms_trigger_active(SMS_TRIGGER_PROPOSAL_EXP_REMINDER);
}

/**
 * Return quotation status color class based on twitter bootstrap
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function quotation_status_color_class($id, $replace_default_by_muted = false)
{
    if ($id == 1) {
        $class = 'default';
    } elseif ($id == 2) {
        $class = 'danger';
    } elseif ($id == 3) {
        $class = 'success';
    } elseif ($id == 4 || $id == 5) {
        // status sent and revised
        $class = 'info';
    } elseif ($id == 6) {
        $class = 'default';
    }
    if ($class == 'default') {
        if ($replace_default_by_muted == true) {
            $class = 'muted';
        }
    }

    return $class;
}
/**
 * Format quotation status with label or not
 * @param  mixed  $status  quotation status id
 * @param  string  $classes additional label classes
 * @param  boolean $label   to include the label or return just translated text
 * @return string
 */
function format_quotation_status($status, $classes = '', $label = true)
{
    $id = $status;
    if ($status == 1) {
        $status      = _l('quotation_status_draft');
        $label_class = 'default';
    } elseif ($status == 2) {
        $status      = _l('quotation_status_declined');
        $label_class = 'danger';
    } elseif ($status == 3) {
        $status      = _l('quotation_status_accepted');
        $label_class = 'success';
    } elseif ($status == 4) {
        $status      = _l('quotation_status_sent');
        $label_class = 'info';
    } elseif ($status == 5) {
        $status      = _l('quotation_status_expired');
        $label_class = 'warning';
    } elseif ($status == 6) {
        $status      = _l('quotation_status_approved');
        $label_class = 'success';
    }

    if ($label == true) {
        return '<span class="label label-' . $label_class . ' ' . $classes . ' s-status quotation-status-' . $id . '">' . $status . '</span>';
    }

    return $status;
}

/**
 * Function that format quotation number based on the prefix option and the quotation id
 * @param  mixed $id quotation id
 * @return string
 */
/*
function format_quotation_number($id)
{
    $format = get_option('quotation_prefix') . str_pad($id, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);

    return hooks()->apply_filters('quotation_number_format', $format, $id);
}
*/


/**
 * Format quotation number based on description
 * @param  mixed $id
 * @return string
 */
function format_quotation_number($id)
{
    $CI = &get_instance();
    $CI->db->select('date,number,prefix,number_format')->from(db_prefix() . 'quotations')->where('id', $id);
    $quotation = $CI->db->get()->row();

    if (!$quotation) {
        return '';
    }

    $number = quotation_number_format($quotation->number, $quotation->number_format, $quotation->prefix, $quotation->date);

    return hooks()->apply_filters('format_quotation_number', $number, [
        'id'       => $id,
        'quotation' => $quotation,
    ]);
}


function quotation_number_format($number, $format, $applied_prefix, $date)
{
    $originalNumber = $number;
    $prefixPadding  = get_option('number_padding_prefixes');
    if ($format == 1) {
        // Number based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT);
    } elseif ($format == 2) {
        // Year based
        $number = $applied_prefix . date('Y', strtotime($date)) . '.' . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT);
    } elseif ($format == 3) {
        // Number-yy based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT) . '-' . date('y', strtotime($date));
    } elseif ($format == 4) {
        // Number-mm-yyyy based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT) . '.' . date('m', strtotime($date)) . '.' . date('Y', strtotime($date));
    }

    return hooks()->apply_filters('quotation_number_format', $number, [
        'format'         => $format,
        'date'           => $date,
        'number'         => $originalNumber,
        'prefix_padding' => $prefixPadding,
    ]);
}


/**
 * Function that return quotation item taxes based on passed item id
 * @param  mixed $itemid
 * @return array
 */
function get_quotation_item_taxes($itemid)
{
    $CI = &get_instance();
    $CI->db->where('itemid', $itemid);
    $CI->db->where('rel_type', 'quotation');
    $taxes = $CI->db->get(db_prefix() . 'item_tax')->result_array();
    $i     = 0;
    foreach ($taxes as $tax) {
        $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
        $i++;
    }

    return $taxes;
}


/**
 * Calculate quotation percent by status
 * @param  mixed $status          quotation status
 * @param  mixed $total_quotations in case the total is calculated in other place
 * @return array
 */
function get_quotations_percent_by_status($status, $total_quotations = '')
{
    $has_permission_view                 = has_permission('quotations', '', 'view');
    $has_permission_view_own             = has_permission('quotations', '', 'view_own');
    $allow_staff_view_quotations_assigned = get_option('allow_staff_view_quotations_assigned');
    $staffId                             = get_staff_user_id();

    $whereUser = '';
    if (!$has_permission_view) {
        if ($has_permission_view_own) {
            $whereUser = '(addedfrom=' . $staffId;
            if ($allow_staff_view_quotations_assigned == 1) {
                $whereUser .= ' OR assigned=' . $staffId;
            }
            $whereUser .= ')';
        } else {
            $whereUser .= 'assigned=' . $staffId;
        }
    }

    if (!is_numeric($total_quotations)) {
        $total_quotations = total_rows(db_prefix() . 'quotations', $whereUser);
    }

    $data            = [];
    $total_by_status = 0;
    $where           = 'status=' . get_instance()->db->escape_str($status);
    if (!$has_permission_view) {
        $where .= ' AND (' . $whereUser . ')';
    }

    $total_by_status = total_rows(db_prefix() . 'quotations', $where);
    $percent         = ($total_quotations > 0 ? number_format(($total_by_status * 100) / $total_quotations, 2) : 0);

    $data['total_by_status'] = $total_by_status;
    $data['percent']         = $percent;
    $data['total']           = $total_quotations;

    return $data;
}

/**
 * Function that will search possible quotation templates in applicaion/views/admin/quotation/templates
 * Will return any found files and user will be able to add new template
 * @return array
 */
function get_quotation_templates()
{
    $quotation_templates = [];
    if (is_dir(VIEWPATH . 'admin/quotations/templates')) {
        foreach (list_files(VIEWPATH . 'admin/quotations/templates') as $template) {
            $quotation_templates[] = $template;
        }
    }

    return $quotation_templates;
}
/**
 * Check if staff member can view quotation
 * @param  mixed $id quotation id
 * @param  mixed $staff_id
 * @return boolean
 */
function user_can_view_quotation($id, $staff_id = false)
{
    $CI = &get_instance();

    $staff_id = $staff_id ? $staff_id : get_staff_user_id();

    if (has_permission('quotations', $staff_id, 'view')) {
        return true;
    }

    $CI->db->select('id, addedfrom, assigned');
    $CI->db->from(db_prefix() . 'quotations');
    $CI->db->where('id', $id);
    $quotation = $CI->db->get()->row();

    if ((has_permission('quotations', $staff_id, 'view_own') && $quotation->addedfrom == $staff_id)
        || ($quotation->assigned == $staff_id && get_option('allow_staff_view_quotations_assigned') == 1)
    ) {
        return true;
    }

    return false;
}
function parse_quotation_content_merge_fields($quotation)
{
    $id = is_array($quotation) ? $quotation['id'] : $quotation->id;
    $CI = &get_instance();

    $CI->load->library('merge_fields/quotations_merge_fields');
    $CI->load->library('merge_fields/other_merge_fields');

    $merge_fields = [];
    $merge_fields = array_merge($merge_fields, $CI->quotations_merge_fields->format($id));
    $merge_fields = array_merge($merge_fields, $CI->other_merge_fields->format());
    foreach ($merge_fields as $key => $val) {
        $content = is_array($quotation) ? $quotation['content'] : $quotation->content;

        if (stripos($content, $key) !== false) {
            if (is_array($quotation)) {
                $quotation['content'] = str_ireplace($key, $val, $content);
            } else {
                $quotation->content = str_ireplace($key, $val, $content);
            }
        } else {
            if (is_array($quotation)) {
                $quotation['content'] = str_ireplace($key, '', $content);
            } else {
                $quotation->content = str_ireplace($key, '', $content);
            }
        }
    }

    return $quotation;
}

/**
 * Check if staff member have assigned quotations / added as sale agent
 * @param  mixed $staff_id staff id to check
 * @return boolean
 */
function staff_has_assigned_quotations($staff_id = '')
{
    $CI       = &get_instance();
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_user_id();
    $cache    = $CI->app_object_cache->get('staff-total-assigned-quotations-' . $staff_id);
    if (is_numeric($cache)) {
        $result = $cache;
    } else {
        $result = total_rows(db_prefix() . 'quotations', ['assigned' => $staff_id]);
        $CI->app_object_cache->add('staff-total-assigned-quotations-' . $staff_id, $result);
    }

    return $result > 0 ? true : false;
}

function get_quotations_sql_where_staff($staff_id)
{
    $has_permission_view_own            = has_permission('quotations', '', 'view_own');
    $allow_staff_view_invoices_assigned = get_option('allow_staff_view_quotations_assigned');
    $CI                                 = &get_instance();

    $whereUser = '';
    if ($has_permission_view_own) {
        $whereUser = '((' . db_prefix() . 'quotations.addedfrom=' . $CI->db->escape_str($staff_id) . ' AND ' . db_prefix() . 'quotations.addedfrom IN (SELECT staff_id FROM ' . db_prefix() . 'staff_permissions WHERE feature = "quotations" AND capability="view_own"))';
        if ($allow_staff_view_invoices_assigned == 1) {
            $whereUser .= ' OR assigned=' . $CI->db->escape_str($staff_id);
        }
        $whereUser .= ')';
    } else {
        $whereUser .= 'assigned=' . $CI->db->escape_str($staff_id);
    }

    return $whereUser;
}



if (!function_exists('format_quotation_info')) {
    /**
     * Format quotation info format
     * @param  object $quotation quotation from database
     * @param  string $for      where this info will be used? Admin area, HTML preview?
     * @return string
     */
    function format_quotation_info($quotation, $for = '')
    {
        $format = get_option('customer_info_format');

        $countryCode = '';
        $countryName = '';

        if ($country = get_country($quotation->country)) {
            $countryCode = $country->iso2;
            $countryName = $country->short_name;
        }

        $quotationTo = '<b>' . $quotation->quotation_to . '</b>';
        $phone      = $quotation->phone;
        $email      = $quotation->email;

        if ($for == 'admin') {
            $hrefAttrs = '';
            if ($quotation->rel_type == 'lead') {
                $hrefAttrs = ' href="#" onclick="init_lead(' . $quotation->rel_id . '); return false;" data-toggle="tooltip" data-title="' . _l('lead') . '"';
            } else {
                $hrefAttrs = ' href="' . admin_url('clients/client/' . $quotation->rel_id) . '" data-toggle="tooltip" data-title="' . _l('client') . '"';
            }
            $quotationTo = '<a' . $hrefAttrs . '>' . $quotationTo . '</a>';
        }

        if ($for == 'html' || $for == 'admin') {
            $phone = '<a href="tel:' . $quotation->phone . '">' . $quotation->phone . '</a>';
            $email = '<a href="mailto:' . $quotation->email . '">' . $quotation->email . '</a>';
        }

        $format = _info_format_replace('company_name', $quotationTo, $format);
        $format = _info_format_replace('street', $quotation->address, $format);
        $format = _info_format_replace('city', $quotation->city, $format);
        $format = _info_format_replace('state', $quotation->state, $format);

        $format = _info_format_replace('country_code', $countryCode, $format);
        $format = _info_format_replace('country_name', $countryName, $format);

        $format = _info_format_replace('zip_code', $quotation->zip, $format);
        $format = _info_format_replace('phone', $phone, $format);
        $format = _info_format_replace('email', $email, $format);

        $whereCF = [];
        if (is_custom_fields_for_customers_portal()) {
            $whereCF['show_on_client_portal'] = 1;
        }

        // If no custom fields found replace all custom fields merge fields to empty
        $format = _maybe_remove_first_and_last_br_tag($format);

        // Remove multiple white spaces
        $format = preg_replace('/\s+/', ' ', $format);
        $format = trim($format);

        return hooks()->apply_filters('quotation_info_text', $format, ['quotation' => $quotation, 'for' => $for]);
    }
}


/**
 * Prepares email template preview $data for the view
 * @param  string $template    template class name
 * @param  mixed $customer_id_or_email customer ID to fetch the primary contact email or email
 * @return array
 */
function quotation_prepare_mail_preview_data($template, $customer_id_or_email, $mailClassParams = [])
{
    $CI = &get_instance();

    if (is_numeric($customer_id_or_email)) {
        $contact = $CI->clients_model->get_contact(get_primary_contact_user_id($customer_id_or_email));
        $email   = $contact ? $contact->email : '';
    } else {
        $email = $customer_id_or_email;
    }

    $CI->load->model('emails_model');

    $data['template'] = $CI->quotation_mail_template->prepare($email, $template);
    $slug             = $CI->quotation_mail_template->get_default_property_value('slug', $template, $mailClassParams);

    $data['template_name'] = $slug;

    $template_result = $CI->emails_model->get(['slug' => $slug, 'language' => 'english'], 'row');

    $data['template_system_name'] = $template_result->name;
    $data['template_id']          = $template_result->emailtemplateid;

    $data['template_disabled'] = $template_result->active == 0;

    return $data;
}


function quotation_get_mail_template_path($class, &$params)
{
    //log_activity('params get_mail_template_path 1 : ' .time() .' ' . json_encode($params));
    $CI  = &get_instance();

    $dir = module_libs_path(QUOTATIONS_MODULE_NAME, 'mails/');

    // Check if second parameter is module and is activated so we can get the class from the module path
    // Also check if the first value is not equal to '/' e.q. when import is performed we set
    // for some values which are blank to "/"
    if (isset($params[0]) && is_string($params[0]) && $params[0] !== '/' && is_dir(module_dir_path($params[0]))) {
        $module = $CI->app_modules->get($params[0]);

        if ($module['activated'] === 1) {
            $dir = module_libs_path($params[0]) . 'mails/';
        }

        unset($params[0]);
        $params = array_values($params);
        //log_activity('params get_mail_template_path 2 : ' .time() .' ' . json_encode($params));
        //log_activity('params get_mail_template_path 3 : ' .time() .' ' . json_encode($dir));
    }

    return $dir . ucfirst($class) . '.php';
}


/**
 * Return RGBa quotation status color for PDF documents
 * @param  mixed $status_id current quotation status
 * @return string
 */
function quotation_status_color_pdf($status_id)
{
    if ($status_id == 1) {
        $statusColor = '119, 119, 119';
    } elseif ($status_id == 2) {
        // Sent
        $statusColor = '3, 169, 244';
    } elseif ($status_id == 3) {
        //Declines
        $statusColor = '252, 45, 66';
    } elseif ($status_id == 4) {
        //Accepted
        $statusColor = '0, 191, 54';
    } else {
        // Expired
        $statusColor = '255, 111, 0';
    }

    return hooks()->apply_filters('quotation_status_pdf_color', $statusColor, $status_id);
}


/**
 * Check for iso logo upload
 * @return boolean
 */
function handle_iso_logo_upload()
{
    $logoIndex = ['logo', 'logo_dark'];
    $success   = false;

    foreach ($logoIndex as $logo) {
        $index = 'iso_' . $logo;

        if (isset($_FILES[$index]) && !empty($_FILES[$index]['name']) && _perfex_upload_error($_FILES[$index]['error'])) {
            set_alert('warning', _perfex_upload_error($_FILES[$index]['error']));

            return false;
        }
        if (isset($_FILES[$index]['name']) && $_FILES[$index]['name'] != '') {
            hooks()->do_action('before_upload_iso_logo_attachment');
            //$path = get_upload_path_by_type('quotations');
            $path = 'uploads/iso' .'/';


            // Get the temp file path
            $tmpFilePath = $_FILES[$index]['tmp_name'];
            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                // Getting file extension
                $extension          = strtolower(pathinfo($_FILES[$index]['name'], PATHINFO_EXTENSION));
                $allowed_extensions = [
                    'jpg',
                    'jpeg',
                    'png',
                    'gif',
                    'svg',
                ];

                $allowed_extensions = array_unique(
                    hooks()->apply_filters('iso_logo_upload_allowed_extensions', $allowed_extensions)
                );

                if (!in_array($extension, $allowed_extensions)) {
                    set_alert('warning', 'Image extension not allowed.');

                    continue;
                }

                // Setup our new file path
                $filename    = md5($logo . time()) . '.' . $extension;
                $newFilePath = $path . $filename;
                
                _maybe_create_upload_path($path);
                // Upload the file into the iso uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    update_option($index, $filename);
                    $success = true;
                }
            }
        }
    }


    return $success;
}


/**
 * Get staff full name
 * @param  string $userid Optional
 * @return string Firstname and Lastname
 */
function get_staff_phonenumber($userid = '')
{
    $tmpStaffUserId = get_staff_user_id();
    if ($userid == '' || $userid == $tmpStaffUserId) {
        if (isset($GLOBALS['current_user'])) {
            return $GLOBALS['current_user']->phonenumber;
        }
        $userid = $tmpStaffUserId;
    }

    $CI = & get_instance();

    $staff = $CI->app_object_cache->get('staff-phonenumber-data-' . $userid);

    if (!$staff) {
        $CI->db->where('staffid', $userid);
        $staff = $CI->db->select('phonenumber')->from(db_prefix() . 'staff')->get()->row();
        $CI->app_object_cache->add('staff-phonenumber-data-' . $userid, $staff);
    }

    return html_escape($staff ? $staff->phonenumber : '');
}




