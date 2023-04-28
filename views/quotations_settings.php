<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_hidden('quotations_settings'); ?>
<div class="horizontal-scrollable-tabs mbot15">
   <div role="tabpanel" class="tab-pane" id="quotations">

   <div class="row">
      <div class="col-md-12">
         <?php $iso_logo = get_option('iso_logo'); ?>
         <?php $iso_logo_dark = get_option('iso_logo_dark'); ?>

         <?php if($iso_logo != ''){ ?>
            <div class="row">
               <div class="col-md-9">
                  <img src="<?php echo base_url('uploads/iso/'.$iso_logo); ?>" class="img img-responsive">
               </div>
               <?php if(has_permission('settings','','delete')){ ?>
                  <div class="col-md-3 text-right">
                     <a href="<?php echo admin_url('settings/remove_iso_logo'); ?>" data-toggle="tooltip" title="<?php echo _l('settings_general_iso_remove_logo_tooltip'); ?>" class="_delete text-danger"><i class="fa fa-remove"></i></a>
                  </div>
               <?php } ?>
            </div>
            <div class="clearfix"></div>
         <?php } else { ?>
            <div class="form-group">
               <label for="iso_logo" class="control-label"><?php echo _l('settings_general_iso_logo'); ?></label>
               <input type="file" name="iso_logo" class="form-control" value="" data-toggle="tooltip" title="<?php echo _l('settings_general_iso_logo_tooltip'); ?>">
            </div>
         <?php } ?>
         <hr />
         <?php if($iso_logo_dark != ''){ ?>
            <div class="row">
               <div class="col-md-9">
                  <img src="<?php echo base_url('uploads/iso/'.$iso_logo_dark); ?>" class="img img-responsive">
               </div>
               <?php if(has_permission('settings','','delete')){ ?>
                  <div class="col-md-3 text-right">
                     <a href="<?php echo admin_url('settings/remove_iso_logo/dark'); ?>" data-toggle="tooltip" title="<?php echo _l('settings_general_iso_remove_logo_tooltip'); ?>" class="_delete text-danger"><i class="fa fa-remove"></i></a>
                  </div>
               <?php } ?>
            </div>
            <div class="clearfix"></div>
         <?php } else { ?>
            <div class="form-group">
               <label for="iso_logo_dark" class="control-label"><?php echo _l('iso_logo_dark'); ?></label>
               <input type="file" name="iso_logo_dark" class="form-control" value="" data-toggle="tooltip" title="<?php echo _l('settings_general_iso_logo_tooltip'); ?>">
            </div>
         <?php } ?>
         <hr />
      </div>
   </div>

      <div class="form-group">
         <label class="control-label" for="quotation_prefix"><?php echo _l('quotation_prefix'); ?></label>
         <input type="text" name="settings[quotation_prefix]" class="form-control" value="<?php echo get_option('quotation_prefix'); ?>">
      </div>
      <hr />
      <i class="fa fa-question-circle pull-left" data-toggle="tooltip" data-title="<?php echo _l('next_quotation_number_tooltip'); ?>"></i>
      <?php echo render_input('settings[next_quotation_number]','next_quotation_number',get_option('next_quotation_number'), 'number', ['min'=>1]); ?>
      <hr />
      <i class="fa fa-question-circle pull-left" data-toggle="tooltip" data-title="<?php echo _l('due_after_help'); ?>"></i>
      <?php echo render_input('settings[quotation_qrcode_size]', 'quotation_qrcode_size', get_option('quotation_qrcode_size')); ?>
      <hr />
      <i class="fa fa-question-circle pull-left" data-toggle="tooltip" data-title="<?php echo _l('due_after_help'); ?>"></i>
      <?php echo render_input('settings[quotation_due_after]','quotation_due_after',get_option('quotation_due_after')); ?>
      <hr />
      <i class="fa fa-question-circle pull-left" data-toggle="tooltip" data-title="<?php echo _l('quotation_number_of_date_tooltip'); ?>"></i>
      <?php echo render_input('settings[quotation_number_of_date]','quotation_number_of_date',get_option('quotation_number_of_date'), 'number', ['min'=>0]); ?>
      <hr />
      <?php render_yes_no_option('quotation_send_telegram_message','quotation_send_telegram_message'); ?>
      <hr />
      <?php render_yes_no_option('delete_only_on_last_quotation','delete_only_on_last_quotation'); ?>
      <hr />
      <?php render_yes_no_option('quotation_number_decrement_on_delete','decrement_quotation_number_on_delete','decrement_quotation_number_on_delete_tooltip'); ?>
      <hr />
      <?php echo render_yes_no_option('allow_staff_view_quotations_assigned','allow_staff_view_quotations_assigned'); ?>
      <hr />
      <?php render_yes_no_option('view_quotation_only_logged_in','require_client_logged_in_to_view_quotation'); ?>
      <hr />
      <?php render_yes_no_option('show_assigned_on_quotations','show_assigned_on_quotations'); ?>
      <hr />
      <?php render_yes_no_option('show_project_on_quotation','show_project_on_quotation'); ?>
      <hr />

      <?php
      $staff = $this->staff_model->get('', ['active' => 1]);
      $selected = get_option('default_quotation_assigned');
      foreach($staff as $member){
       
         if($selected == $member['staffid']) {
           $selected = $member['staffid'];
         
       }
      }
      echo render_select('settings[default_quotation_assigned]',$staff,array('staffid',array('firstname','lastname')),'default_quotation_assigned_string',$selected);
      ?>
      <hr />
      <?php render_yes_no_option('exclude_quotation_from_client_area_with_draft_status','exclude_quotation_from_client_area_with_draft_status'); ?>
      <hr />   
      <?php render_yes_no_option('quotation_accept_identity_confirmation','quotation_accept_identity_confirmation'); ?>
      <hr />
      <?php echo render_input('settings[quotation_year]','quotation_year',get_option('quotation_year'), 'number', ['min'=>2020]); ?>
      <hr />
      
      <div class="form-group">
         <label for="quotation_number_format" class="control-label clearfix"><?php echo _l('quotation_number_format'); ?></label>
         <div class="radio radio-primary radio-inline">
            <input type="radio" name="settings[quotation_number_format]" value="1" id="e_number_based" <?php if(get_option('quotation_number_format') == '1'){echo 'checked';} ?>>
            <label for="e_number_based"><?php echo _l('quotation_number_format_number_based'); ?></label>
         </div>
         <div class="radio radio-primary radio-inline">
            <input type="radio" name="settings[quotation_number_format]" value="2" id="e_year_based" <?php if(get_option('quotation_number_format') == '2'){echo 'checked';} ?>>
            <label for="e_year_based"><?php echo _l('quotation_number_format_year_based'); ?> (YYYY.000001)</label>
         </div>
         <div class="radio radio-primary radio-inline">
            <input type="radio" name="settings[quotation_number_format]" value="3" id="e_short_year_based" <?php if(get_option('quotation_number_format') == '3'){echo 'checked';} ?>>
            <label for="e_short_year_based">000001-YY</label>
         </div>
         <div class="radio radio-primary radio-inline">
            <input type="radio" name="settings[quotation_number_format]" value="4" id="e_year_month_based" <?php if(get_option('quotation_number_format') == '4'){echo 'checked';} ?>>
            <label for="e_year_month_based">000001.MM.YYYY</label>
         </div>
         <hr />
      </div>
      <div class="row">
         <div class="col-md-12">
            <?php echo render_input('settings[quotations_pipeline_limit]','pipeline_limit_status',get_option('quotations_pipeline_limit')); ?>
         </div>
         <div class="col-md-7">
            <label for="default_proposals_pipeline_sort" class="control-label"><?php echo _l('default_pipeline_sort'); ?></label>
            <select name="settings[default_quotations_pipeline_sort]" id="default_quotations_pipeline_sort" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
               <option value="datecreated" <?php if(get_option('default_quotations_pipeline_sort') == 'datecreated'){echo 'selected'; }?>><?php echo _l('quotations_sort_datecreated'); ?></option>
               <option value="date" <?php if(get_option('default_quotations_pipeline_sort') == 'date'){echo 'selected'; }?>><?php echo _l('quotations_sort_quotation_date'); ?></option>
               <option value="pipeline_order" <?php if(get_option('default_quotations_pipeline_sort') == 'pipeline_order'){echo 'selected'; }?>><?php echo _l('quotations_sort_pipeline'); ?></option>
               <option value="expirydate" <?php if(get_option('default_quotations_pipeline_sort') == 'expirydate'){echo 'selected'; }?>><?php echo _l('quotations_sort_expiry_date'); ?></option>
            </select>
         </div>
         <div class="col-md-5">
            <div class="mtop30 text-right">
               <div class="radio radio-inline radio-primary">
                  <input type="radio" id="k_desc_quotation" name="settings[default_quotations_pipeline_sort_type]" value="asc" <?php if(get_option('default_quotations_pipeline_sort_type') == 'asc'){echo 'checked';} ?>>
                  <label for="k_desc_quotation"><?php echo _l('order_ascending'); ?></label>
               </div>
               <div class="radio radio-inline radio-primary">
                  <input type="radio" id="k_asc_quotation" name="settings[default_quotations_pipeline_sort_type]" value="desc" <?php if(get_option('default_quotations_pipeline_sort_type') == 'desc'){echo 'checked';} ?>>
                  <label for="k_asc_quotation"><?php echo _l('order_descending'); ?></label>
               </div>
            </div>
         </div>
         <div class="clearfix"></div>
      </div>
      <hr  />
      <?php echo render_textarea('settings[predefined_client_note_quotation]','predefined_clientnote',get_option('predefined_client_note_quotation'),array('rows'=>6)); ?>
      <?php echo render_textarea('settings[predefined_terms_quotation]','predefined_terms',get_option('predefined_terms_quotation'),array('rows'=>6)); ?>
   </div>
 <?php hooks()->do_action('after_quotations_tabs_content'); ?>
</div>
