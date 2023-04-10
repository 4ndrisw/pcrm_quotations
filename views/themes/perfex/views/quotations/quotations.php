<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s section-heading section-quotations">
  <div class="panel-body">
    <h4 class="no-margin section-text"><?php echo _l('quotations'); ?></h4>
  </div>
</div>
<div class="panel_s">
  <div class="panel-body">
    <table class="table dt-table table-quotations" data-order-col="3" data-order-type="desc">
      <thead>
        <tr>
          <th class="th-quotation-number"><?php echo _l('quotation') . ' #'; ?></th>
          <th class="th-quotation-subject"><?php echo _l('quotation_subject'); ?></th>
          <th class="th-quotation-total"><?php echo _l('quotation_total'); ?></th>
          <th class="th-quotation-open-till"><?php echo _l('quotation_open_till'); ?></th>
          <th class="th-quotation-date"><?php echo _l('quotation_date'); ?></th>
          <th class="th-quotation-status"><?php echo _l('quotation_status'); ?></th>
          <?php
          $custom_fields = get_custom_fields('quotation',array('show_on_client_portal'=>1));
          foreach($custom_fields as $field){ ?>
            <th><?php echo $field['name']; ?></th>
          <?php } ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($quotations as $quotation){ ?>
          <tr>
            <td>
              <a href="<?php echo site_url('quotation/'.$quotation['id'].'/'.$quotation['hash']); ?>" class="td-quotation-url">
                <?php echo format_quotation_number($quotation['id']); ?>
                <?php
                if ($quotation['invoice_id']) {
                  echo '<br /><span class="text-success quotation-invoiced">' . _l('quotation_invoiced') . '</span>';
                }
                ?>
              </a>
              <td>
                <a href="<?php echo site_url('quotation/'.$quotation['id'].'/'.$quotation['hash']); ?>" class="td-quotation-url-subject">
                  <?php echo $quotation['subject']; ?>
                </a>
                <?php
                if ($quotation['invoice_id'] != NULL) {
                  $invoice = $this->invoices_model->get($quotation['invoice_id']);
                  echo '<br /><a href="' . site_url('invoice/' . $invoice->id . '/' . $invoice->hash) . '" target="_blank" class="td-quotation-invoice-url">' . format_invoice_number($invoice->id) . '</a>';
                } else if ($quotation['quotation_id'] != NULL) {
                  $quotation = $this->quotations_model->get($quotation['quotation_id']);
                  echo '<br /><a href="' . site_url('quotation/' . $quotation->id . '/' . $quotation->hash) . '" target="_blank" class="td-quotation-quotation-url">' . format_quotation_number($quotation->id) . '</a>';
                }
                ?>
              </td>
              <td data-order="<?php echo $quotation['total']; ?>">
                <?php
                if ($quotation['currency'] != 0) {
                 echo app_format_money($quotation['total'], get_currency($quotation['currency']));
               } else {
                 echo app_format_money($quotation['total'], get_base_currency());
               }
               ?>
             </td>
             <td data-order="<?php echo $quotation['open_till']; ?>"><?php echo _d($quotation['open_till']); ?></td>
             <td data-order="<?php echo $quotation['date']; ?>"><?php echo _d($quotation['date']); ?></td>
             <td><?php echo format_quotation_status($quotation['status']); ?></td>
             <?php foreach($custom_fields as $field){ ?>
               <td><?php echo get_custom_field_value($quotation['id'],$field['id'],'quotation'); ?></td>
             <?php } ?>
           </tr>
         <?php } ?>
       </tbody>
     </table>
   </div>
 </div>
