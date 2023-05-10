<?php

defined('BASEPATH') or exit('No direct script access allowed');

$baseCurrency = get_base_currency();

$aColumns = [
    db_prefix() . 'quotations.id',
    'subject',
    'quotation_to',
    'total',
    'date',
    'open_till',
    'datecreated',
    'status',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'quotations';

$where  = [];
$filter = [];

if ($this->ci->input->post('leads_related')) {
    array_push($filter, 'OR rel_type="lead"');
}
if ($this->ci->input->post('customers_related')) {
    array_push($filter, 'OR rel_type="customer"');
}
if ($this->ci->input->post('expired')) {
    array_push($filter, 'OR open_till IS NOT NULL AND open_till <"' . date('Y-m-d') . '" AND status NOT IN(2,3)');
}

$statuses  = $this->ci->quotations_model->get_statuses();
$statusIds = [];

foreach ($statuses as $status) {
    if ($this->ci->input->post('quotations_' . $status)) {
        array_push($statusIds, $status);
    }
}
if (count($statusIds) > 0) {
    array_push($filter, 'AND status IN (' . implode(', ', $statusIds) . ')');
}

$agents    = $this->ci->quotations_model->get_sale_agents();
$agentsIds = [];
foreach ($agents as $agent) {
    if ($this->ci->input->post('sale_agent_' . $agent['sale_agent'])) {
        array_push($agentsIds, $agent['sale_agent']);
    }
}
if (count($agentsIds) > 0) {
    array_push($filter, 'AND assigned IN (' . implode(', ', $agentsIds) . ')');
}

$years      = $this->ci->quotations_model->get_quotations_years();
$yearsArray = [];
foreach ($years as $year) {
    if ($this->ci->input->post('year_' . $year['year'])) {
        array_push($yearsArray, $year['year']);
    }
}
if (count($yearsArray) > 0) {
    array_push($filter, 'AND YEAR(date) IN (' . implode(', ', $yearsArray) . ')');
}

if (count($filter) > 0) {
    array_push($where, 'AND (' . prepare_dt_filter($filter) . ')');
}

if (!has_permission('quotations', '', 'view')) {
    array_push($where, 'AND ' . get_quotations_sql_where_staff(get_staff_user_id()));
}

$join          = [];
$custom_fields = get_table_custom_fields('quotation');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);

    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'quotations.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$aColumns = hooks()->apply_filters('quotations_table_sql_columns', $aColumns);

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'currency',
    'rel_id',
    'rel_type',
    'invoice_id',
    'hash',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    //$numberOutput = '<a href="' . admin_url('quotations/list_quotations/' . $aRow[db_prefix() . 'quotations.id']. '#' . $aRow[db_prefix() . 'quotations.id']) . '" onclick="init_quotation(' . $aRow[db_prefix() . 'quotations.id'] . '); return false;">' . format_quotation_number($aRow[db_prefix() . 'quotations.id']) . '</a>';
    //$numberOutput = '<a href="' . admin_url('quotations#' . $aRow[db_prefix() . 'quotations.id']) . '" target="_blank">' . format_quotation_number($aRow[db_prefix() . 'quotations.id']) . ' AA</a>';
    //$numberOutput = '<a href="' . admin_url('quotations/list_quotations/' . $aRow[db_prefix() . 'quotations.id']. '#' . $aRow[db_prefix() . 'quotations.id']) . '" target="_blank">' . format_quotation_number($aRow[db_prefix() . 'quotations.id']) . '</a>';
    //$numberOutput = '<a href="' . admin_url('quotations/list_quotations/' . $aRow[db_prefix() . 'quotations.id']. '#' . $aRow[db_prefix() . 'quotations.id']) . '">' . format_quotation_number($aRow[db_prefix() . 'quotations.id']) . '</a>';



    // If is from client area table
    $numberOutput = '<a href="' . admin_url('quotations/list_quotations/' . $aRow[db_prefix() . 'quotations.id']. '#' . $aRow[db_prefix() . 'quotations.id']) . '" onclick="init_quotation(' . $aRow[db_prefix() . 'quotations.id'] . '); return false;">' . format_quotation_number($aRow[db_prefix() . 'quotations.id']) . '</a>';

    $numberOutput .= '<div class="row-options">';

    $numberOutput .= '<a href="' . site_url('quotations/show/' . $aRow[db_prefix() . 'quotations.id'] . '/' . $aRow['hash']) . '" target="_blank">' . _l('view') . '</a>';
    if (has_permission('quotations', '', 'edit')) {
        $numberOutput .= ' | <a href="' . admin_url('quotations/quotation/' . $aRow[db_prefix() . 'quotations.id']) . '">' . _l('edit') . '</a>';
    }
    $numberOutput .= '</div>';

    $row[] = $numberOutput;

    $row[] = '<a href="' . admin_url('quotations/list_quotations/' . $aRow[db_prefix() . 'quotations.id']) . '" onclick="init_quotation(' . $aRow[db_prefix() . 'quotations.id'] . '); return false;">' . $aRow['subject'] . '</a>';
    
    if($aRow['rel_id'] != ''){
        if ($aRow['rel_type'] == 'lead') {
            $toOutput = '<a href="#" onclick="init_lead(' . $aRow['rel_id'] . ');return false;" target="_blank" data-toggle="tooltip" data-title="' . _l('lead') . '">' . $aRow['quotation_to'] . '</a>';
        } elseif ($aRow['rel_type'] == 'customer') {
            $toOutput = '<a href="' . admin_url('clients/client/' . $aRow['rel_id']) . '" target="_blank" data-toggle="tooltip" data-title="' . _l('client') . '">' . $aRow['quotation_to'] . '</a>';
        }
    }else{
        $toOutput = $aRow['quotation_to'];
    }

    $row[] = $toOutput;

    $amount = app_format_money($aRow['total'], ($aRow['currency'] != 0 ? get_currency($aRow['currency']) : $baseCurrency));

    if ($aRow['invoice_id']) {
        $amount .= '<br /> <span class="hide"> - </span><span class="text-success">' . _l('quotation_invoiced') . '</span>';
    }

    $row[] = $amount;


    $row[] = _d($aRow['date']);

    $row[] = _d($aRow['open_till']);

    $row[] = _d($aRow['datecreated']);

            $span = '';
                //if (!$locked) {
                    $span .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
                    $span .= '<a href="#" style="font-size:14px;vertical-align:middle;" class="dropdown-toggle text-dark" id="tableLeadsStatus-' . $aRow[db_prefix() . 'quotations.id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $span .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
                    $span .= '</a>';

                    $span .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableLeadsStatus-' . $aRow[db_prefix() . 'quotations.id'] . '">';
                    foreach ($statuses as $quotationChangeStatus) {
                        if ($aRow['status'] != $quotationChangeStatus) {
                            $span .= '<li>
                          <a href="#" onclick="quotation_mark_as(' . $quotationChangeStatus . ',' . $aRow[db_prefix() . 'quotations.id'] . '); return false;">
                             ' . format_quotation_status($quotationChangeStatus) . '
                          </a>
                       </li>';
                        }
                    }
                    $span .= '</ul>';
                    $span .= '</div>';
                //}
                $span .= '</span>';

            $outputStatus = '<span class="label label-danger inline-block">' . _l('quotation_status_draft') . $span;

            if ($aRow['status'] == 1) {
                $outputStatus = '<span class="label label-default inline-block">' . _l('quotation_status_draft') . $span;
            } elseif ($aRow['status'] == 2) {
                $outputStatus = '<span class="label label-danger inline-block">' . _l('quotation_status_declined') . $span;
            } elseif ($aRow['status'] == 3) {
                $outputStatus = '<span class="label label-success inline-block">' . _l('quotation_status_accepted') . $span;
            } elseif ($aRow['status'] == 4) {
                $outputStatus = '<span class="label label-info inline-block">' . _l('quotation_status_sent') . $span;
            } elseif ($aRow['status'] == 5) {
                $outputStatus = '<span class="label label-warning inline-block">' . _l('quotation_status_expired') . $span;
            } elseif ($aRow['status'] == 6) {
                $outputStatus = '<span class="label label-success inline-block">' . _l('quotation_status_approved') . '</span>';
            }

            $_data = $outputStatus;

    $row[] = $outputStatus;
    //$row[] = format_quotation_status($aRow['status']);

    // Custom fields add values
    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row['DT_RowClass'] = 'has-row-options';

    $row = hooks()->apply_filters('quotations_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}
