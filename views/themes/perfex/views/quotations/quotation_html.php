<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="mtop15 preview-top-wrapper">
   <div class="row">
      <div class="col-md-3">
         <div class="mbot30">
            <div class="quotation-html-logo">
               <?php echo get_dark_company_logo(); ?>
            </div>
         </div>
      </div>
      <div class="clearfix"></div>
   </div>
   <div class="top" data-sticky data-sticky-class="preview-sticky-header">
      <div class="container preview-sticky-container">
         <div class="row">
            <div class="col-md-12">
               <div class="col-md-3">
                  <h3 class="bold no-mtop quotation-html-number no-mbot">
                     <span class="sticky-visible hide">
                     <?php echo format_quotation_number($quotation->id); ?>
                     </span>
                  </h3>
                  <h4 class="quotation-html-status mtop7">
                     <?php echo format_quotation_status($quotation->status,'',true); ?>
                  </h4>
               </div>
               <div class="col-md-9">         
                  <?php
                     // Is not accepted, declined and expired
                     if ($quotation->status != 4 && $quotation->status != 3 && $quotation->status != 5) {
                       $can_be_accepted = true;
                       if($identity_confirmation_enabled == '0'){
                         echo form_open($this->uri->uri_string(), array('class'=>'pull-right mtop7 action-button'));
                         echo form_hidden('quotation_action', 4);
                         echo '<button type="submit" data-loading-text="'._l('wait_text').'" autocomplete="off" class="btn btn-success action-button accept"><i class="fa fa-check"></i> '._l('clients_accept_quotation').'</button>';
                         echo form_close();
                       } else {
                         echo '<button type="button" id="accept_action" class="btn btn-success mright5 mtop7 pull-right action-button accept"><i class="fa fa-check"></i> '._l('clients_accept_quotation').'</button>';
                       }
                     } else if($quotation->status == 3){
                       if (($quotation->open_till >= date('Y-m-d') || !$quotation->open_till) && $quotation->status != 5) {
                         $can_be_accepted = true;
                         if($identity_confirmation_enabled == '0'){
                           echo form_open($this->uri->uri_string(),array('class'=>'pull-right mtop7 action-button'));
                           echo form_hidden('quotation_action', 4);
                           echo '<button type="submit" data-loading-text="'._l('wait_text').'" autocomplete="off" class="btn btn-success action-button accept"><i class="fa fa-check"></i> '._l('clients_accept_quotation').'</button>';
                           echo form_close();
                         } else {
                           echo '<button type="button" id="accept_action" class="btn btn-success mright5 mtop7 pull-right action-button accept"><i class="fa fa-check"></i> '._l('clients_accept_quotation').'</button>';
                         }
                       }
                     }
                     // Is not accepted, declined and expired
                     if ($quotation->status != 4 && $quotation->status != 3 && $quotation->status != 5) {
                       echo form_open($this->uri->uri_string(), array('class'=>'pull-right action-button mright5 mtop7'));
                       echo form_hidden('quotation_action', 3);
                       echo '<button type="submit" data-loading-text="'._l('wait_text').'" autocomplete="off" class="btn btn-default action-button accept"><i class="fa fa-remove"></i> '._l('clients_decline_quotation').'</button>';
                       echo form_close();
                     }
                     ?>
                  <?php echo form_open(site_url('quotations/pdf/'.$quotation->id), array('class'=>'pull-right action-button')); ?>
                  <button type="submit" name="quotationpdf" class="btn btn-default action-button download mright5 mtop7" value="quotationpdf">
                  <i class="fa fa-file-pdf-o"></i>
                  <?php echo _l('clients_invoice_html_btn_download'); ?>
                  </button>
                  <?php echo form_close(); ?>
                  <?php if((is_client_logged_in() && has_contact_permission('quotations'))  || is_staff_member()){ ?>
                     <?php 
                        $clients = 'clients';
                        if(is_staff_member()){
                           $clients = 'admin';
                        }
                       ?>
                  <a href="<?php echo site_url($clients.'/quotations/'); ?>" class="btn btn-default pull-right mright5 mtop7 action-button go-to-portal">
                  <?php echo _l('client_go_to_dashboard'); ?>
                  </a>
                  <?php } ?>
               </div>
            </div>
            <div class="clearfix"></div>
         </div>
      </div>

   </div>
</div>
<div class="clearfix"></div>
<div class="panel_s mtop20">
   <div class="panel-body">
      <div class="col-md-10 col-md-offset-1">
         <div class="row mtop20">
            <div class="col-md-6 col-sm-6 transaction-html-info-col-left">
               <h4 class="bold quotation-html-number"><?php echo format_quotation_number($quotation->id); ?></h4>
               <address class="quotation-html-company-info">
                  <?php echo format_organization_info(); ?>
               </address>
            </div>
            <div class="col-sm-6 text-right transaction-html-info-col-right">
               <span class="bold quotation_to"><?php echo _l('quotation_to'); ?>:</span>
               
                  <address class="no-margin quotation-html-info">
                     <?php echo format_quotation_info($quotation, 'html'); ?>
                  </address>
               <p class="no-mbot quotation-html-date">
                  <span class="bold">
                  <?php echo _l('quotation_data_date'); ?>:
                  </span>
                  <?php echo _d($quotation->date); ?>
               </p>
               <?php if(!empty($quotation->open_till)){ ?>
               <p class="no-mbot quotation-html-expiry-date">
                  <span class="bold"><?php echo _l('quotation_data_expiry_date'); ?></span>:
                  <?php echo _d($quotation->open_till); ?>
               </p>
               <?php } ?>
               <?php if(!empty($quotation->reference_no)){ ?>
               <p class="no-mbot quotation-html-reference-no">
                  <span class="bold"><?php echo _l('reference_no'); ?>:</span>
                  <?php echo $quotation->reference_no; ?>
               </p>
               <?php } ?>

               <?php $pdf_custom_fields = get_custom_fields('quotation',array('show_on_pdf'=>1,'show_on_client_portal'=>1));
                  foreach($pdf_custom_fields as $field){
                    $value = get_custom_field_value($quotation->id,$field['id'],'quotation');
                    if($value == ''){continue;} ?>
               <p class="no-mbot">
                  <span class="bold"><?php echo $field['name']; ?>: </span>
                  <?php echo $value; ?>
               </p>
               <?php } ?>
            </div>
         </div>
         <div class="row">
            <div class="col-md-12">
               <div class="table-responsive">
                  <?php
                     $items = get_items_table_data($quotation, 'quotation');
                     echo $items->table();
                     ?>
               </div>
            </div>
            <div class="col-md-6 col-md-offset-6">
               <table class="table text-right">
                  <tbody>
                     <tr id="subtotal">
                        <td><span class="bold"><?php echo _l('quotation_subtotal'); ?></span>
                        </td>
                        <td class="subtotal">
                           <?php echo app_format_money($quotation->subtotal, $quotation->currency_name); ?>
                        </td>
                     </tr>
                     <?php if(is_sale_discount_applied($quotation)){ ?>
                     <tr>
                        <td>
                           <span class="bold"><?php echo _l('quotation_discount'); ?>
                           <?php if(is_sale_discount($quotation,'percent')){ ?>
                           (<?php echo app_format_number($quotation->discount_percent,true); ?>%)
                           <?php } ?></span>
                        </td>
                        <td class="discount">
                           <?php echo '-' . app_format_money($quotation->discount_total, $quotation->currency_name); ?>
                        </td>
                     </tr>
                     <?php } ?>
                     <?php
                        foreach($items->taxes() as $tax){
                         echo '<tr class="tax-area"><td class="bold">'.$tax['taxname'].' ('.app_format_number($tax['taxrate']).'%)</td><td>'.app_format_money($tax['total_tax'], $quotation->currency_name).'</td></tr>';
                        }
                        ?>
                     <?php if((int)$quotation->adjustment != 0){ ?>
                     <tr>
                        <td>
                           <span class="bold"><?php echo _l('quotation_adjustment'); ?></span>
                        </td>
                        <td class="adjustment">
                           <?php echo app_format_money($quotation->adjustment, $quotation->currency_name); ?>
                        </td>
                     </tr>
                     <?php } ?>
                     <tr>
                        <td><span class="bold"><?php echo _l('quotation_total'); ?></span>
                        </td>
                        <td class="total">
                           <?php echo app_format_money($quotation->total, $quotation->currency_name); ?>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>
            <?php
               if(get_option('total_to_words_enabled') == 1){ ?>
            <div class="col-md-12 text-center quotation-html-total-to-words">
               <p class="bold"><?php echo  _l('num_word').': '.$this->numberword->convert($quotation->total,$quotation->currency_name); ?></p>
            </div>
            <?php } ?>
            <?php if(count($quotation->attachments) > 0 && $quotation->visible_attachments_to_customer_found == true){ ?>
            <div class="clearfix"></div>
            <div class="quotation-html-files">
               <div class="col-md-12">
                  <hr />
                  <p class="bold mbot15 font-medium"><?php echo _l('quotation_files'); ?></p>
               </div>
               <?php foreach($quotation->attachments as $attachment){
                  // Do not show hidden attachments to customer
                  if($attachment['visible_to_customer'] == 0){continue;}
                  $attachment_url = site_url('download/file/sales_attachment/'.$attachment['attachment_key']);
                  if(!empty($attachment['external'])){
                  $attachment_url = $attachment['external_link'];
                  }
                  ?>
               <div class="col-md-12 mbot15">
                  <div class="pull-left"><i class="<?php echo get_mime_class($attachment['filetype']); ?>"></i></div>
                  <a href="<?php echo $attachment_url; ?>"><?php echo $attachment['file_name']; ?></a>
               </div>
               <?php } ?>
            </div>
            <?php } ?>
            <?php if(!empty($quotation->client_note)){ ?>
            <div class="col-md-12 quotation-html-note">
               <b><?php echo _l('quotation_note'); ?></b><br />
               <?php
                  $notes = explode('--', $quotation->client_note);
                  $note_text = '<ul>';
                  foreach ($notes as $note) {
                     if($note !== ''){
                        $note_text .='<li>' . $note . '</li>'; 
                     }               }
                  $note_text .= '</ul>';
                  echo($note_text); 
               ?>
            </div>
            <?php } ?>
            <?php if(!empty($quotation->terms)){ ?>
            <div class="col-md-12 quotation-html-terms-and-conditions">
               <hr />
               <b><?php echo _l('terms_and_conditions'); ?>:</b><br />
               <?php
                  $terms = explode('==', $quotation->terms);
                  $term_text = '<ol>';
                  foreach ($terms as $term) {
                     if($term !== ''){
                        $term_text .='<li>' . $term . '</li>'; 
                     }               }
                  $term_text .= '</ol>';
                  echo($term_text); 
               ?>
            </div>
            <?php } ?>
         </div>
      </div>
   </div>
</div>
<?php
   if($identity_confirmation_enabled == '1' && $can_be_accepted){
    get_template_part('identity_confirmation_form',array('formData'=>form_hidden('quotation_action',4)));
   }
   ?>
<script>
   $(function(){
     new Sticky('[data-sticky]');
   })
</script>
