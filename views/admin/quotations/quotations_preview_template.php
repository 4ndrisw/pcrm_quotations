<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_hidden('_attachment_sale_id',$quotation->id); ?>
<?php echo form_hidden('_attachment_sale_type','quotation'); ?>
<div class="panel_s">
   <div class="panel-body">
      <div class="horizontal-scrollable-tabs preview-tabs-top">
         <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
         <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
         <div class="horizontal-tabs">
            <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
               <li role="presentation" class="active">
                  <a href="#tab_quotation" aria-controls="tab_quotation" role="tab" data-toggle="tab">
                  <?php echo _l('quotation'); ?>
                  </a>
               </li>
               <?php if(isset($quotation)){ ?>
               <li role="presentation">
                  <a href="#tab_comments" onclick="get_quotation_comments(); return false;" aria-controls="tab_comments" role="tab" data-toggle="tab">
                  <?php
                  echo _l('quotation_comments');
                  $total_comments = total_rows(db_prefix() . 'quotation_comments', [
                      'quotationid' => $quotation->id,
                    ]
                  );
                  ?>
                      <span class="badge total_comments <?php echo $total_comments === 0 ? 'hide' : ''; ?>"><?php echo $total_comments ?></span>
                  </a>
               </li>
               <li role="presentation">
                  <a href="#tab_reminders" onclick="initDataTable('.table-reminders', admin_url + 'misc/get_reminders/' + <?php echo $quotation->id ;?> + '/' + 'quotation', undefined, undefined, undefined,[1,'asc']); return false;" aria-controls="tab_reminders" role="tab" data-toggle="tab">
                  <?php echo _l('quotation_reminders'); ?>
                  <?php
                     $total_reminders = total_rows(db_prefix().'reminders',
                      array(
                       'isnotified'=>0,
                       'staff'=>get_staff_user_id(),
                       'rel_type'=>'quotation',
                       'rel_id'=>$quotation->id
                       )
                      );
                     if($total_reminders > 0){
                      echo '<span class="badge">'.$total_reminders.'</span>';
                     }
                     ?>
                  </a>
               </li>
               <?php if(is_admin()) { ?>
               <li role="presentation" class="tab-separator">
                  <a href="#tab_tasks" onclick="init_rel_tasks_table(<?php echo $quotation->id; ?>,'quotation'); return false;" aria-controls="tab_tasks" role="tab" data-toggle="tab">
                  <?php echo _l('tasks'); ?>
                  </a>
               </li>
               <?php } ?>

               <li role="presentation" class="tab-separator">
                  <a href="#tab_notes" onclick="get_quotation_notes(); return false;" aria-controls="tab_notes" role="tab" data-toggle="tab">
                  <?php
                  echo _l('quotation_notes');
                  $total_notes = total_rows(db_prefix() . 'quotation_notes', [
                      'quotationid' => $quotation->id,
                    ]
                  );
                  ?>
                      <span class="badge total_notes <?php echo $total_notes === 0 ? 'hide' : ''; ?>"><?php echo $total_notes ?></span>
                  </a>
               </li>
               <li role="presentation" data-toggle="tooltip" title="<?php echo _l('emails_tracking'); ?>" class="tab-separator">
                  <a href="#tab_emails_tracking" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab">
                    <?php if(!is_mobile()){ ?>
                     <i class="fa fa-envelope-open" aria-hidden="true"></i>
                     <?php } else { ?>
                     <?php echo _l('emails_tracking'); ?>
                     <?php } ?>
                  </a>
               </li>
               <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('view_tracking'); ?>" class="tab-separator">
                  <a href="#tab_views" aria-controls="tab_views" role="tab" data-toggle="tab">
                    <?php if(!is_mobile()){ ?>
                     <i class="fa fa-eye"></i>
                     <?php } else { ?>
                     <?php echo _l('view_tracking'); ?>
                     <?php } ?>
                  </a>
               </li>
               <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('toggle_full_view'); ?>" class="tab-separator toggle_view">
                  <a href="#" onclick="small_table_full_view(); return false;">
                  <i class="fa fa-expand"></i></a>
               </li>
               <?php } ?>
            </ul>
         </div>
      </div>
      <div class="row mtop10">
         <div class="col-md-3">
            <?php echo format_quotation_status($quotation->status,'pull-left mright5 mtop5'); ?>
         </div>
         <div class="col-md-9 text-right _buttons quotation_buttons">
            <?php if(has_permission('quotations','','edit')){ ?>
            <a href="<?php echo admin_url('quotations/quotation/'.$quotation->id); ?>" data-placement="left" data-toggle="tooltip" title="<?php echo _l('quotation_edit'); ?>" class="btn btn-default btn-with-tooltip" data-placement="bottom"><i class="fa fa-pencil-square"></i></a>
            <?php } ?>
            <div class="btn-group">
               <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-file-pdf-o"></i><?php if(is_mobile()){echo ' PDF';} ?> <span class="caret"></span></a>
               <ul class="dropdown-menu dropdown-menu-right">
                  <li class="hidden-xs"><a href="<?php echo site_url('quotations/pdf/'.$quotation->id.'?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a></li>
                  <li class="hidden-xs"><a href="<?php echo site_url('quotations/pdf/'.$quotation->id.'?output_type=I'); ?>" target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
                  <li><a href="<?php echo site_url('quotations/pdf/'.$quotation->id); ?>"><?php echo _l('download'); ?></a></li>
                  <li>
                     <a href="<?php echo site_url('quotations/pdf/'.$quotation->id.'?print=true'); ?>" target="_blank">
                     <?php echo _l('print'); ?>
                     </a>
                  </li>
               </ul>
            </div>
            <a href="#" class="btn btn-default btn-with-tooltip" data-target="#quotation_send_to_customer" data-toggle="modal"><span data-toggle="tooltip" class="btn-with-tooltip" data-title="<?php echo _l('quotation_send_to_email'); ?>" data-placement="bottom"><i class="fa fa-envelope"></i></span></a>
            <div class="btn-group ">
               <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
               <?php echo _l('more'); ?> <span class="caret"></span>
               </button>
               <ul class="dropdown-menu dropdown-menu-right">
                  <li>
                     <a href="<?php echo site_url('quotations/show/'.$quotation->id .'/'.$quotation->hash); ?>" target="_blank"><?php echo _l('quotation_view'); ?></a>
                  </li>
                  <?php hooks()->do_action('after_quotation_view_as_client_link', $quotation); ?>
                  <?php if(!empty($quotation->open_till) && date('Y-m-d') < $quotation->open_till && ($quotation->status == 4 || $quotation->status == 1) && is_quotations_expiry_reminders_enabled()) { ?>
                  <li>
                     <a href="<?php echo admin_url('quotations/send_expiry_reminder/'.$quotation->id); ?>"><?php echo _l('send_expiry_reminder'); ?></a>
                  </li>
                  <?php } ?>
                  <li>
                     <a href="#" data-toggle="modal" data-target="#quotations_attach_file"><?php echo _l('invoice_attach_file'); ?></a>
                  </li>
                  <?php if(staff_can('edit', 'quotations')){
                    foreach($quotation_statuses as $status){
                      if($quotation->status != $status){ ?>
                        <li>
                          <a href="#" onclick="quotation_status_mark_as( <?php echo $status ?> , <?php echo $quotation->id; ?> ); return false;">
                           <?php echo _l('quotation_mark_as',format_quotation_status($status,'',false)); ?></a>
                        </li>
                     <?php }
                    }
                    ?>
                  <?php } ?>
                  <?php if(has_permission('quotations','','create')){ ?>
                  <li>
                     <a href="<?php echo admin_url() . 'quotations/copy/'.$quotation->id; ?>"><?php echo _l('quotation_copy'); ?></a>
                  </li>
                  <?php } ?>
                  <?php if($quotation->id == NULL && $quotation->invoice_id == NULL){ ?>
                  <?php foreach($quotation_statuses as $status){
                     if(has_permission('quotations','','edit')){
                      if($quotation->status != $status){ ?>
                  <li>
                     <a href="<?php echo admin_url() . 'quotations/mark_action_status/'.$status.'/'.$quotation->id; ?>"><?php echo _l('quotation_mark_as',format_quotation_status($status,'',false)); ?></a>
                  </li>
                  <?php
                     } } } ?>
                  <?php } ?>
                  <?php if(!empty($quotation->signature) && has_permission('quotations','','delete')){ ?>
                  <li>
                     <a href="<?php echo admin_url('quotations/clear_signature/'.$quotation->id); ?>" class="_delete">
                     <?php echo _l('clear_signature'); ?>
                     </a>
                  </li>
                  <?php } ?>
                  <?php if(has_permission('quotations','','delete')){ ?>
                  <li>
                     <a href="<?php echo admin_url() . 'quotations/delete/'.$quotation->id; ?>" class="text-danger delete-text _delete"><?php echo _l('quotation_delete'); ?></a>
                  </li>
                  <?php } ?>
               </ul>
            </div>
            <?php if($quotation->id == NULL && $quotation->invoice_id == NULL){ ?>
            <?php if(has_permission('quotations','','create') || has_permission('invoices','','create')){ ?>
            <div class="btn-group">
               <button type="button" class="btn btn-success dropdown-toggle<?php if($quotation->rel_type == 'customer' && total_rows(db_prefix().'clients',array('active'=>0,'userid'=>$quotation->rel_id)) > 0){echo ' disabled';} ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
               <?php echo _l('quotation_convert'); ?> <span class="caret"></span>
               </button>
               <ul class="dropdown-menu dropdown-menu-right">
                  <?php
                     $disable_convert = false;
                     $not_related = false;

                     if($quotation->rel_type == 'lead'){
                      if(total_rows(db_prefix().'clients',array('leadid'=>$quotation->rel_id)) == 0){
                       $disable_convert = true;
                       $help_text = 'quotation_convert_to_lead_disabled_help';
                     }
                     } else if(empty($quotation->rel_type)){
                     $disable_convert = true;
                     $help_text = 'quotation_convert_not_related_help';
                     }
                     ?>
                  <?php if(has_permission('quotations','','create')){ ?>
                  <li <?php if($disable_convert){ echo 'data-toggle="tooltip" title="'._l($help_text,_l('quotation_convert_quotation')).'"';} ?>><a href="#" <?php if($disable_convert){ echo 'style="cursor:not-allowed;" onclick="return false;"';} else {echo 'data-template="quotation" onclick="quotation_convert_template(this); return false;"';} ?>><?php echo _l('quotation_convert_quotation'); ?></a></li>
                  <?php } ?>
                  <?php if(has_permission('invoices','','create')){ ?>
                  <li <?php if($disable_convert){ echo 'data-toggle="tooltip" title="'._l($help_text,_l('quotation_convert_invoice')).'"';} ?>><a href="#" <?php if($disable_convert){ echo 'style="cursor:not-allowed;" onclick="return false;"';} else {echo 'data-template="invoice" onclick="quotation_convert_template(this); return false;"';} ?>><?php echo _l('quotation_convert_invoice'); ?></a></li>
                  <?php } ?>
               </ul>
            </div>
            <?php } ?>
            <?php } else {
               if($quotation->id != NULL){
                echo '<a href="'.admin_url('quotations/list_quotations/'.$quotation->id).'" class="btn btn-info">'.format_quotation_number($quotation->id).'</a>';
               } else {
                echo '<a href="'.admin_url('invoices/list_invoices/'.$quotation->invoice_id).'" class="btn btn-info">'.format_invoice_number($quotation->invoice_id).'</a>';
               }
               } ?>
         </div>
      </div>
      <div class="clearfix"></div>
      <hr class="hr-panel-heading" />
      <div class="row">
         <div class="col-md-12">
            <div class="tab-content">
               <div role="tabpanel" class="tab-pane active" id="tab_quotation">
                  <div class="row mtop10">
                     <?php if($quotation->status == 3 && !empty($quotation->acceptance_firstname) && !empty($quotation->acceptance_lastname) && !empty($quotation->acceptance_email)){ ?>
                     <div class="col-md-12">
                        <div class="alert alert-info">
                           <?php echo _l('accepted_identity_info',array(
                              _l('quotation_lowercase'),
                              '<b>'.$quotation->acceptance_firstname . ' ' . $quotation->acceptance_lastname . '</b> (<a href="mailto:'.$quotation->acceptance_email.'">'.$quotation->acceptance_email.'</a>)',
                              '<b>'. _dt($quotation->acceptance_date).'</b>',
                              '<b>'.$quotation->acceptance_ip.'</b>'.(is_admin() ? '&nbsp;<a href="'.admin_url('quotations/clear_acceptance_info/'.$quotation->id).'" class="_delete text-muted" data-toggle="tooltip" data-title="'._l('clear_this_information').'"><i class="fa fa-remove"></i></a>' : '')
                              )); ?>
                        </div>
                     </div>
                     <?php } ?>
                     <div class="col-md-6">
                        <h4 class="bold">
                           <?php
                              $tags = get_tags_in($quotation->id,'quotation');
                              if(count($tags) > 0){
                               echo '<i class="fa fa-tag" aria-hidden="true" data-toggle="tooltip" data-title="'.html_escape(implode(', ',$tags)).'"></i>';
                              }
                              ?>
                           <a href="<?php echo admin_url('quotations/quotation/'.$quotation->id); ?>">
                           <span id="quotation-number">
                           <?php echo format_quotation_number($quotation->id); ?>
                           </span>
                           </a>
                        </h4>
                        <h5 class="bold mbot15 font-medium"><a href="<?php echo site_url('quotations/show/'.$quotation->id.'/'.$quotation->hash); ?>"><?php echo $quotation->subject; ?></a></h5>
                        <address>
                           <?php echo format_organization_info(); ?>
                        </address>
                     </div>
                     <div class="col-md-6 text-right">
                        <address>
                           <span class="bold"><?php echo _l('quotation_to'); ?>:</span><br />
                           <?php echo format_quotation_info($quotation,'admin'); ?>
                        </address>
                     </div>
                  </div>
                  <hr class="hr-panel-heading" />
                  <?php
                     if(count($quotation->attachments) > 0){ ?>
                  <p class="bold"><?php echo _l('quotation_files'); ?></p>
                  <?php foreach($quotation->attachments as $attachment){
                     $attachment_url = site_url('download/file/sales_attachment/'.$attachment['attachment_key']);
                     if(!empty($attachment['external'])){
                        $attachment_url = $attachment['external_link'];
                     }
                     ?>
                  <div class="mbot15 row" data-attachment-id="<?php echo $attachment['id']; ?>">
                     <div class="col-md-8">
                        <div class="pull-left"><i class="<?php echo get_mime_class($attachment['filetype']); ?>"></i></div>
                        <a href="<?php echo $attachment_url; ?>" target="_blank"><?php echo $attachment['file_name']; ?></a>
                        <br />
                        <small class="text-muted"> <?php echo $attachment['filetype']; ?></small>
                     </div>
                     <div class="col-md-4 text-right">
                        <?php if($attachment['visible_to_customer'] == 0){
                           $icon = 'fa-toggle-off';
                           $tooltip = _l('show_to_customer');
                           } else {
                           $icon = 'fa-toggle-on';
                           $tooltip = _l('hide_from_customer');
                           }
                           ?>
                        <a href="#" data-toggle="tooltip" onclick="toggle_file_visibility(<?php echo $attachment['id']; ?>,<?php echo $quotation->id; ?>,this); return false;" data-title="<?php echo $tooltip; ?>"><i class="fa <?php echo $icon; ?>" aria-hidden="true"></i></a>
                        <?php if($attachment['staffid'] == get_staff_user_id() || is_admin()){ ?>
                        <a href="#" class="text-danger" onclick="delete_quotation_attachment(<?php echo $attachment['id']; ?>); return false;"><i class="fa fa-times"></i></a>
                        <?php } ?>
                     </div>
                  </div>
                  <?php } ?>
                  <?php } ?>
                  <div class="clearfix"></div>

                  <div class="row">
                     <div class="col-md-12">
                        <div class="table-responsive">
                              <?php
                                 $items = get_items_table_data($quotation, 'quotation', 'html', true);
                                 echo $items->table();
                              ?>
                        </div>
                     </div>
                     <div class="col-md-5 col-md-offset-7">
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
                     <?php if(count($quotation->attachments) > 0){ ?>
                     <div class="clearfix"></div>
                     <hr />
                     <div class="col-md-12">
                        <p class="bold text-muted"><?php echo _l('quotation_files'); ?></p>
                     </div>
                     <?php foreach($quotation->attachments as $attachment){
                        $attachment_url = site_url('download/file/sales_attachment/'.$attachment['attachment_key']);
                        if(!empty($attachment['external'])){
                          $attachment_url = $attachment['external_link'];
                        }
                        ?>
                     <div class="mbot15 row col-md-12" data-attachment-id="<?php echo $attachment['id']; ?>">
                        <div class="col-md-8">
                           <div class="pull-left"><i class="<?php echo get_mime_class($attachment['filetype']); ?>"></i></div>
                           <a href="<?php echo $attachment_url; ?>" target="_blank"><?php echo $attachment['file_name']; ?></a>
                           <br />
                           <small class="text-muted"> <?php echo $attachment['filetype']; ?></small>
                        </div>
                        <div class="col-md-4 text-right">
                           <?php if($attachment['visible_to_customer'] == 0){
                              $icon = 'fa fa-toggle-off';
                              $tooltip = _l('show_to_customer');
                              } else {
                              $icon = 'fa fa-toggle-on';
                              $tooltip = _l('hide_from_customer');
                              }
                              ?>
                           <a href="#" data-toggle="tooltip" onclick="toggle_file_visibility(<?php echo $attachment['id']; ?>,<?php echo $quotation->id; ?>,this); return false;" data-title="<?php echo $tooltip; ?>"><i class="<?php echo $icon; ?>" aria-hidden="true"></i></a>
                           <?php if($attachment['staffid'] == get_staff_user_id() || is_admin()){ ?>
                           <a href="#" class="text-danger" onclick="delete_quotation_attachment(<?php echo $attachment['id']; ?>); return false;"><i class="fa fa-times"></i></a>
                           <?php } ?>
                        </div>
                     </div>
                     <?php } ?>
                     <?php } ?>
                     <?php if($quotation->client_note != ''){ ?>
                     <div class="col-md-12 mtop15">
                        <p class="bold text-muted"><?php echo _l('quotation_note'); ?></p>
                        <p>
                        <?php
                           $notes = explode('--', $quotation->client_note);
                           $note_text = '<ul class="unordered-list">';
                           foreach ($notes as $note) {
                              if($note !== ''){
                                 $note_text .='<li>' . $note . '</li>'; 
                              }               }
                           $note_text .= '</ul>';
                           echo($note_text); 
                        ?>
                        </p>
                     </div>
                     <?php } ?>
                     <?php if($quotation->terms != ''){ ?>
                     <div class="col-md-12 mtop15">
                        <p class="bold text-muted"><?php echo _l('terms_and_conditions'); ?></p>
                        <p>
                        <?php
                           $terms = explode('==', $quotation->terms);
                           $term_text = '<ol class="ordered-list">';
                           foreach ($terms as $term) {
                              if($term !== ''){
                                 $term_text .='<li>' . $term . '</li>'; 
                              }               }
                           $term_text .= '</ol>';
                           echo($term_text); 
                        ?>
                        </p>
                     </div>
                     <?php } ?>
                  </div>

                      <?php if(!empty($quotation->signature)) { ?>
                        <div class="row mtop25">
                           <div class="col-md-6 col-md-offset-6 text-right">
                              <div class="bold">
                                 <p class="no-mbot"><?php echo _l('contract_signed_by') . ": {$quotation->acceptance_firstname} {$quotation->acceptance_lastname}"?></p>
                                 <p class="no-mbot"><?php echo _l('quotation_signed_date') . ': ' . _dt($quotation->acceptance_date) ?></p>
                                 <p class="no-mbot"><?php echo _l('quotation_signed_ip') . ": {$quotation->acceptance_ip}"?></p>
                              </div>
                              <p class="bold"><?php echo _l('document_customer_signature_text'); ?>
                                 <?php if(has_permission('quotations','','delete')){ ?>
                                 <a href="<?php echo admin_url('quotations/clear_signature/'.$quotation->id); ?>" data-toggle="tooltip" title="<?php echo _l('clear_signature'); ?>" class="_delete text-danger">
                                 <i class="fa fa-remove"></i>
                                 </a>
                                 <?php } ?>
                              </p>
                              <div class="pull-right">
                                 <img src="<?php echo site_url('download/preview_image?path='.protected_file_url_by_path(get_upload_path_by_type('quotation').$quotation->id.'/'.$quotation->signature)); ?>" class="img-responsive" alt="">
                              </div>
                           </div>
                        </div>
                        <?php } ?>
               </div>
               <div role="tabpanel" class="tab-pane" id="tab_comments">
                  <div class="row quotation-comments mtop15">
                     <div class="col-md-12">
                        <div id="quotation-comments"></div>
                        <div class="clearfix"></div>
                        <textarea name="content" id="comment" rows="4" class="form-control mtop15 quotation-comment"></textarea>
                        <button type="button" class="btn btn-info mtop10 pull-right" onclick="add_quotation_comment();"><?php echo _l('quotation_add_comment'); ?></button>
                     </div>
                  </div>
               </div>
               <div role="tabpanel" class="tab-pane" id="tab_notes">


                  <div class="row quotation-notes mtop15">
                     <div class="col-md-12">
                        <div class="clearfix"></div>
                        <textarea name="content" id="note" rows="4" class="form-control mtop15 quotation-note"></textarea>
                        <button type="button" class="btn btn-info mtop10 pull-right" onclick="add_quotation_note();"><?php echo _l('quotation_add_note'); ?></button>
                     </div>
                  </div>

                  <?php //echo form_open(admin_url('quotations/add_note/'.$quotation->id),array('id'=>'sales-notes','class'=>'quotation-notes-form')); ?>
                  <?php //echo render_textarea('description'); ?>
                  <!--
                  <div class="text-right">
                     <button type="submit" class="btn btn-info mtop15 mbot15"><?php //echo _l('quotation_add_note'); ?></button>
                  </div>
                  -->
                  <?php //echo form_close(); ?>

                  <hr />
                  <div id="quotation-notes"></div>
                  <!-- <div class="panel_s mtop20 no-shadow" id="sales_notes_area">
                  </div>-->
               </div>
               <div role="tabpanel" class="tab-pane" id="tab_emails_tracking">
                  <?php
                     $this->load->view('admin/includes/emails_tracking',array(
                       'tracked_emails'=>
                       get_tracked_emails($quotation->id, 'quotation'))
                       );
                     ?>
               </div>
               <div role="tabpanel" class="tab-pane" id="tab_tasks">
                  <?php init_relation_tasks_table(array( 'data-new-rel-id'=>$quotation->id,'data-new-rel-type'=>'quotation')); ?>
               </div>
               <div role="tabpanel" class="tab-pane" id="tab_reminders">
                  <a href="#" data-toggle="modal" class="btn btn-info" data-target=".reminder-modal-quotation-<?php echo $quotation->id; ?>"><i class="fa fa-bell-o"></i> <?php echo _l('quotation_set_reminder_title'); ?></a>
                  <hr />
                  <?php render_datatable(array( _l( 'reminder_description'), _l( 'reminder_date'), _l( 'reminder_staff'), _l( 'reminder_is_notified')), 'reminders'); ?>
                  <?php $this->load->view('admin/includes/modals/reminder',array('id'=>$quotation->id,'name'=>'quotation','members'=>$members,'reminder_title'=>_l('quotation_set_reminder_title'))); ?>
               </div>
               <div role="tabpanel" class="tab-pane ptop10" id="tab_views">
                  <?php
                     $views_activity = get_views_tracking('quotation',$quotation->id);
                       if(count($views_activity) === 0) {
                     echo '<h4 class="no-margin">'._l('not_viewed_yet',_l('quotation_lowercase')).'</h4>';
                     }
                     foreach($views_activity as $activity){ ?>
                  <p class="text-success no-margin">
                     <?php echo _l('view_date') . ': ' . _dt($activity['date']); ?>
                  </p>
                  <p class="text-muted">
                     <?php echo _l('view_ip') . ': ' . $activity['view_ip']; ?>
                  </p>
                  <hr />
                  <?php } ?>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
<div id="modal-wrapper"></div>
<?php // $this->load->view('admin/quotations/send_quotation_to_email_template'); ?>
<script>
   init_btn_with_tooltips();
   init_datepicker();
   init_selectpicker();
   init_form_reminder();
   init_tabs_scrollable();
   init_quotations_attach_file();
     // defined in manage quotations
     quotation_id = '<?php echo $quotation->id; ?>';
     //init_quotation_editor();
</script>
