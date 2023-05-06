<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Quotations
Description: Default module for defining quotations
Version: 1.0.1
Requires at least: 2.3.*
*/

define('QUOTATIONS_MODULE_NAME', 'quotations');
define('QUOTATION_ATTACHMENTS_FOLDER', FCPATH . 'uploads/quotations/');

//hooks()->add_filter('before_quotation_updated', '_format_data_quotation_feature');
//hooks()->add_filter('before_quotation_added', '_format_data_quotation_feature');

hooks()->add_action('after_cron_run', 'quotations_notification');
hooks()->add_action('admin_init', 'quotations_module_init_menu_items');
hooks()->add_action('admin_init', 'quotations_permissions');
hooks()->add_action('admin_init', 'quotations_settings_tab');
hooks()->add_action('clients_init', 'quotations_clients_area_menu_items');
//hooks()->add_action('app_admin_head', 'quotations_head_component');
//hooks()->add_action('app_admin_footer', 'quotations_footer_js_component');

hooks()->add_action('staff_member_deleted', 'quotations_staff_member_deleted');

hooks()->add_filter('migration_tables_to_replace_old_links', 'quotations_migration_tables_to_replace_old_links');
hooks()->add_filter('global_search_result_query', 'quotations_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'quotations_global_search_result_output', 10, 2);
hooks()->add_filter('get_dashboard_widgets', 'quotations_add_dashboard_widget');
hooks()->add_filter('module_quotations_action_links', 'module_quotations_action_links');


function quotations_add_dashboard_widget($widgets)
{
    /*
    $widgets[] = [
        'path'      => 'quotations/widgets/quotation_this_week',
        'container' => 'left-8',
    ];
    $widgets[] = [
        'path'      => 'quotations/widgets/project_not_quotationd',
        'container' => 'left-8',
    ];
    */

    return $widgets;
}


function quotations_staff_member_deleted($data)
{
    $CI = &get_instance();
    $CI->db->where('staff_id', $data['id']);
    $CI->db->update(db_prefix() . 'quotations', [
            'staff_id' => $data['transfer_data_to'],
        ]);
}

function quotations_global_search_result_output($output, $data)
{
    if ($data['type'] == 'quotations') {
        $output = '<a href="' . admin_url('quotations/quotation/' . $data['result']['id']) . '">' . format_quotation_number($data['result']['id']) . '</a>';
    }

    return $output;
}

function quotations_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('quotations', '', 'view')) {

        // quotations
        $CI->db->select()
           ->from(db_prefix() . 'quotations')
           ->like(db_prefix() . 'quotations.formatted_number', $q)->limit($limit);
        
        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'quotations',
                'search_heading' => _l('quotations'),
            ];
        
        if(isset($result[0]['result'][0]['id'])){
            return $result;
        }

        // quotations
        $CI->db->select()->from(db_prefix() . 'quotations')->like(db_prefix() . 'clients.company', $q)->or_like(db_prefix() . 'quotations.formatted_number', $q)->limit($limit);
        $CI->db->join(db_prefix() . 'clients',db_prefix() . 'quotations.client_id='.db_prefix() .'clients.userid', 'left');
        $CI->db->order_by(db_prefix() . 'clients.company', 'ASC');

        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'quotations',
                'search_heading' => _l('quotations'),
            ];
    }

    return $result;
}

function quotations_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                'table' => db_prefix() . 'quotations',
                'field' => 'description',
            ];

    return $tables;
}

function quotations_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('quotations', $capabilities, _l('quotations'));
}


/**
* Register activation module hook
*/
register_activation_hook(QUOTATIONS_MODULE_NAME, 'quotations_module_activation_hook');

function quotations_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register deactivation module hook
*/
register_deactivation_hook(QUOTATIONS_MODULE_NAME, 'quotations_module_deactivation_hook');

function quotations_module_deactivation_hook()
{

    $CI = &get_instance();

    if ($CI->db->table_exists(db_prefix() . 'quotations')) {
      $CI->db->query('DROP TABLE `' . db_prefix() . 'quotations`');
    }

    if ($CI->db->table_exists(db_prefix() . 'quotation_activity')) {
      $CI->db->query('DROP TABLE `' . db_prefix() . 'quotation_activity`');
    }

    if ($CI->db->table_exists(db_prefix() . 'quotation_comments')) {
      $CI->db->query('DROP TABLE `' . db_prefix() . 'quotation_comments`');
    }

    if ($CI->db->table_exists(db_prefix() . 'quotation_notes')) {
      $CI->db->query('DROP TABLE `' . db_prefix() . 'quotation_notes`');
    }

   $CI->db->query('DELETE FROM `' . db_prefix() . 'options` WHERE `name` LIKE "%quotation%"');
   $CI->db->query('DELETE FROM `' . db_prefix() . 'itemable` WHERE `rel_type` LIKE "%quotation%"');
   $CI->db->query('DELETE FROM `' . db_prefix() . 'item_tax` WHERE `rel_type` LIKE "%quotation%"');
   $CI->db->query('DELETE FROM `' . db_prefix() . 'reminders` WHERE `rel_type` LIKE "%quotation%"');
   $CI->db->query('DELETE FROM `' . db_prefix() . 'emailtemplates` WHERE `name` LIKE "%quotation%"');

    // quotations
    
    $CI->load->model('quotations/quotations_model');
    $quotations = $CI->db->select('id')
                    ->from(db_prefix() . 'files')
                    ->where('rel_type', 'quotation')
                    ->get()->result();
    foreach($quotations as $quotation){
        $CI->quotations_model->delete_attachment($quotation->id);
    }
}

//hooks()->add_action('deactivate_' . $module . '_module', $function);

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(QUOTATIONS_MODULE_NAME, [QUOTATIONS_MODULE_NAME]);

/**
 * Init quotations module menu items in setup in admin_init hook
 * @return null
 */
function quotations_module_init_menu_items()
{
    $CI = &get_instance();

    $CI->app->add_quick_actions_link([
            'name'       => _l('quotation'),
            'url'        => 'quotations',
            'permission' => 'quotations',
            'icon'     => 'fa-solid fa-money-check',
            'position'   => 57,
            ]);

    if (has_permission('quotations', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('quotations', [
                'slug'     => 'quotations-tracking',
                'name'     => _l('quotations'),
                'icon'     => 'fa-solid fa-money-check',
                'href'     => admin_url('quotations'),
                'position' => 12,
        ]);
    }
}

function module_quotations_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('settings?group=quotations') . '">' . _l('settings') . '</a>';

    return $actions;
}

function quotations_clients_area_menu_items()
{   
    // Show menu item only if client is logged in
    if (is_client_logged_in()) {
        add_theme_menu_item('quotations', [
                    'name'     => _l('quotations'),
                    'href'     => site_url('quotations/list'),
                    'position' => 15,
        ]);
    }
}

/**
 * [perfex_dark_theme_settings_tab net menu item in setup->settings]
 * @return void
 */
function quotations_settings_tab()
{
    $CI = &get_instance();
    $CI->app_tabs->add_settings_tab('quotations', [
        'name'     => _l('settings_group_quotations'),
        'icon'     => 'fa-solid fa-money-check',
        'view'     => 'quotations/quotations_settings',
        'position' => 51,
    ]);
}

$CI = &get_instance();
$CI->load->helper(QUOTATIONS_MODULE_NAME . '/quotations');

if(($CI->uri->segment(1)=='admin' && $CI->uri->segment(2)=='quotations') || $CI->uri->segment(1)=='quotations'){
    $CI->app_css->add(QUOTATIONS_MODULE_NAME.'-css', base_url('modules/'.QUOTATIONS_MODULE_NAME.'/assets/css/'.QUOTATIONS_MODULE_NAME.'.css'));
    $CI->app_scripts->add(QUOTATIONS_MODULE_NAME.'-js', base_url('modules/'.QUOTATIONS_MODULE_NAME.'/assets/js/'.QUOTATIONS_MODULE_NAME.'.js'));
}

