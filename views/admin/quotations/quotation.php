<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content accounting-template quotation">
      <div class="row">
         <?php
         if(isset($quotation)){
             echo form_hidden('isedit',$quotation->id);
            }
            $rel_type = '';
            $rel_id = '';
            if(isset($quotation) || ($this->input->get('rel_id') && $this->input->get('rel_type'))){
             if($this->input->get('rel_type')){
               $rel_id = $this->input->get('rel_id');
               $rel_type = $this->input->get('rel_type');
             } else {
               $rel_id = $quotation->rel_id;
               $rel_type = $quotation->rel_type;
             }
            }
            ?>
         <?php echo form_open($this->uri->uri_string(),array('id'=>'quotation-form','class'=>'_transaction_form quotation-form')); ?>

          <div class="col-md-12">
            <div class="panel_s">
               <div class="panel-body">
                  <div class="row">
                     <?php if(isset($quotation)){ ?>
                     <div class="col-md-12">
                        <?php echo format_quotation_status($quotation->status); ?>
                     </div>
                     <div class="clearfix"></div>
                     <hr />
                     <?php } ?>
                     <div class="col-md-6 border-right">
                        <?php $value = (isset($quotation) ? $quotation->subject : ''); ?>
                        <?php $attrs = (isset($quotation) ? array() : array('autofocus'=>true)); ?>
                        <?php echo render_input('subject','quotation_subject',$value,'text',$attrs); ?>
                        <div class="form-group select-placeholder">
                           <label for="rel_type" class="control-label"><?php echo _l('quotation_related'); ?></label>
                           <select name="rel_type" id="rel_type" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                              <option value=""></option>
                              <option value="lead" <?php if((isset($quotation) && $quotation->rel_type == 'lead') || $this->input->get('rel_type')){if($rel_type == 'lead'){echo 'selected';}} ?>><?php echo _l('quotation_for_lead'); ?></option>
                              <option value="customer" <?php if((isset($quotation) &&  $quotation->rel_type == 'customer') || $this->input->get('rel_type')){if($rel_type == 'customer'){echo 'selected';}} ?>><?php echo _l('quotation_for_customer'); ?></option>
                           </select>
                        </div>
                        <div class="form-group customer-removed select-placeholder<?php if($rel_id == ''){echo ' hide';} ?> " id="rel_id_wrapper">
                           <label for="rel_id"><span class="rel_id_label"></span></label>
                           <div id="rel_id_select">
                              <select name="rel_id" id="rel_id" class="ajax-search" data-width="100%" data-live-search="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                              <?php if($rel_id != '' && $rel_type != ''){
                                 $rel_data = get_relation_data($rel_type,$rel_id);
                                 $rel_val = get_relation_values($rel_data,$rel_type);
                                    echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                                 } ?>
                              </select>
                           </div>
                        </div>


                        <div class="row">
                          <div class="col-md-12">

                              <?php
                                 $next_quotation_number = get_option('next_quotation_number');
                                 $format = get_option('quotation_number_format');
                                 
                                  if(isset($quotation)){
                                    $format = $quotation->number_format;
                                  }

                                 $prefix = get_option('quotation_prefix');

                                 if ($format == 1) {
                                   $__number = $next_quotation_number;
                                   if(isset($quotation)){
                                     $__number = $quotation->number;
                                     $prefix = '<span id="prefix">' . $quotation->prefix . '</span>';
                                   }
                                 } else if($format == 2) {
                                   if(isset($quotation)){
                                     $__number = $quotation->number;
                                     $prefix = $quotation->prefix;
                                     $prefix = '<span id="prefix">'. $prefix . '</span><span id="prefix_year">' . date('Y',strtotime($quotation->date)).'</span>/';
                                   } else {
                                     $__number = $next_quotation_number;
                                     $prefix = $prefix.'<span id="prefix_year">'.date('Y').'</span>/';
                                   }
                                 } else if($format == 3) {
                                    if(isset($quotation)){
                                     $yy = date('y',strtotime($quotation->date));
                                     $__number = $quotation->number;
                                     $prefix = '<span id="prefix">'. $quotation->prefix . '</span>';
                                   } else {
                                    $yy = date('y');
                                    $__number = $next_quotation_number;
                                  }
                                 } else if($format == 4) {
                                    if(isset($quotation)){
                                     $yyyy = date('Y',strtotime($quotation->date));
                                     $mm = date('m',strtotime($quotation->date));
                                     $__number = $quotation->number;
                                     $prefix = '<span id="prefix">'. $quotation->prefix . '</span>';
                                   } else {
                                    $yyyy = date('Y');
                                    $mm = date('m');
                                    $__number = $next_quotation_number;
                                  }
                                 }
                                 
                                 $_quotation_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
                                 $isedit = isset($quotation) ? 'true' : 'false';
                                 $data_original_number = isset($quotation) ? $quotation->number : 'false';
                                 ?>

                                 <div class="form-group">
                                    <label for="number"><?php echo _l('quotation_add_edit_number'); ?></label>
                                    <div class="input-group">
                                       <span class="input-group-addon">
                                       <?php if(isset($quotation)){ ?>
                                       <a href="#" onclick="return false;" data-toggle="popover" data-container='._transaction_form' data-html="true" data-content="<label class='control-label'><?php echo _l('settings_sales_quotation_prefix'); ?></label><div class='input-group'><input name='s_prefix' type='text' class='form-control' value='<?php echo $quotation->prefix; ?>'></div><button type='button' onclick='save_sales_number_settings(this); return false;' data-url='<?php echo admin_url('quotations/update_number_settings/'.$quotation->id); ?>' class='btn btn-info btn-block mtop15'><?php echo _l('submit'); ?></button>"><i class="fa fa-cog"></i></a>
                                        <?php }
                                         echo $prefix;
                                       ?>
                                       </span>
                                       <input type="text" name="number" class="form-control" value="<?php echo $_quotation_number; ?>" data-isedit="<?php echo $isedit; ?>" data-original-number="<?php echo $data_original_number; ?>">
                                       <?php if($format == 3) { ?>
                                       <span class="input-group-addon">
                                          <span id="prefix_year" class="format-n-yy"><?php echo $yy; ?></span>
                                       </span>
                                       <?php } else if($format == 4) { ?>
                                        <span class="input-group-addon">
                                          <span id="prefix_month" class="format-mm-yyyy"><?php echo $mm; ?></span>
                                          .
                                          <span id="prefix_year" class="format-mm-yyyy"><?php echo $yyyy; ?></span>
                                       </span>
                                       <?php } ?>
                                    </div>
                                 </div>
                           </div>
                        </div>

                        <div class="row">
                          <div class="col-md-6">
                              <?php $value = (isset($quotation) ? _d($quotation->date) : _d(date('Y-m-d'))) ?>
                              <?php echo render_date_input('date','quotation_date',$value); ?>
                          </div>
                          <div class="col-md-6">
                            <?php
                        $value = '';
                        if(isset($quotation)){
                          $value = _d($quotation->open_till);
                        } else {
                          if(get_option('quotation_due_after') != 0){
                              $value = _d(date('Y-m-d',strtotime('+'.get_option('quotation_due_after').' DAY',strtotime(date('Y-m-d')))));
                          }
                        }
                        echo render_date_input('open_till','quotation_open_till',$value); ?>
                          </div>
                        </div>
                        <?php
                           $selected = '';
                           $currency_attr = array('data-show-subtext'=>true);
                           foreach($currencies as $currency){
                            if($currency['isdefault'] == 1){
                              $currency_attr['data-base'] = $currency['id'];
                            }
                            if(isset($quotation)){
                              if($currency['id'] == $quotation->currency){
                                $selected = $currency['id'];
                              }
                              if($quotation->rel_type == 'customer'){
                                $currency_attr['disabled'] = true;
                              }
                            } else {
                              if($rel_type == 'customer'){
                                $customer_currency = $this->clients_model->get_customer_default_currency($rel_id);
                                if($customer_currency != 0){
                                  $selected = $customer_currency;
                                } else {
                                  if($currency['isdefault'] == 1){
                                    $selected = $currency['id'];
                                  }
                                }
                                $currency_attr['disabled'] = true;
                              } else {
                               if($currency['isdefault'] == 1){
                                $selected = $currency['id'];
                              }
                            }
                           }
                           }
                           $currency_attr = apply_filters_deprecated('quotation_currency_disabled', [$currency_attr], '2.3.0', 'quotation_currency_attributes');
                           $currency_attr = hooks()->apply_filters('quotation_currency_attributes', $currency_attr);
                           ?>
                           <div class="row">
                             <div class="col-md-6">
                              <?php
                              echo render_select('currency', $currencies, array('id','name','symbol'), 'quotation_currency', $selected, $currency_attr);
                              ?>
                             </div>
                             <div class="col-md-6">
                               <div class="form-group select-placeholder">
                                 <label for="discount_type" class="control-label"><?php echo _l('discount_type'); ?></label>
                                 <select name="discount_type" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                  <option value="" selected><?php echo _l('no_discount'); ?></option>
                                  <option value="before_tax" <?php
                                  if(isset($quotation)){ if($quotation->discount_type == 'before_tax'){ echo 'selected'; }}?>><?php echo _l('discount_type_before_tax'); ?></option>
                                  <option value="after_tax" <?php if(isset($quotation)){if($quotation->discount_type == 'after_tax'){echo 'selected';}} ?>><?php echo _l('discount_type_after_tax'); ?></option>
                                </select>
                              </div>
                            </div>
                           </div>
                        <?php $fc_rel_id = (isset($quotation) ? $quotation->id : false); ?>
                        <?php echo render_custom_fields('quotation',$fc_rel_id); ?>
                         <div class="form-group no-mbot">
                           <label for="tags" class="control-label"><i class="fa fa-tag" aria-hidden="true"></i> <?php echo _l('tags'); ?></label>
                           <input type="text" class="tagsinput" id="tags" name="tags" value="<?php echo (isset($quotation) ? prep_tags_input(get_tags_in($quotation->id,'quotation')) : ''); ?>" data-role="tagsinput">
                        </div>
                        <div class="form-group mtop10 no-mbot">
                            <p><?php echo _l('quotation_allow_comments'); ?></p>
                            <div class="onoffswitch">
                              <input type="checkbox" id="allow_comments" class="onoffswitch-checkbox" <?php if((isset($quotation) && $quotation->allow_comments == 1) || !isset($quotation)){echo 'checked';}; ?> value="on" name="allow_comments">
                              <label class="onoffswitch-label" for="allow_comments" data-toggle="tooltip" title="<?php echo _l('quotation_allow_comments_help'); ?>"></label>
                            </div>
                          </div>
                     </div>
                     <div class="col-md-6">
                        <div class="row">
                           <div class="col-md-6">
                              <div class="form-group select-placeholder">
                                 <label for="status" class="control-label"><?php echo _l('quotation_status'); ?></label>
                                 <?php
                                    $disabled = '';
                                    if(isset($quotation)){
                                     if($quotation->id != NULL || $quotation->invoice_id != NULL){
                                       $disabled = 'disabled';
                                     }
                                    }
                                    ?>
                                 <select name="status" class="selectpicker" data-width="100%" <?php echo $disabled; ?> data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                    <?php foreach($statuses as $status){ ?>
                                    <option value="<?php echo $status; ?>" <?php if((isset($quotation) && $quotation->status == $status) || (!isset($quotation) && $status == 0)){echo 'selected';} ?>><?php echo format_quotation_status($status,'',false); ?></option>
                                    <?php } ?>
                                 </select>
                              </div>
                           </div>
                           <div class="col-md-6">
                              <?php
                                 $i = 0;
                                 $selected = get_option('default_quotation_assigned');
                                 foreach($staff as $member){
                                  if(isset($quotation)){
                                    if($quotation->assigned == $member['staffid']) {
                                      $selected = $member['staffid'];
                                    }
                                  }
                                  $i++;
                                 }
                                 echo render_select('assigned',$staff,array('staffid',array('firstname','lastname')),'quotation_assigned',$selected);
                                 ?>
                           </div>
                        </div>
                        <?php $value = (isset($quotation) ? $quotation->quotation_to : ''); ?>
                        <?php echo render_input('quotation_to','quotation_to',$value); ?>
                        <?php $value = (isset($quotation) ? $quotation->address : ''); ?>
                        <?php echo render_textarea('address','quotation_address',$value); ?>
                        <div class="row">
                           <div class="col-md-6">
                              <?php $value = (isset($quotation) ? $quotation->city : ''); ?>
                              <?php echo render_input('city','billing_city',$value); ?>
                           </div>
                           <div class="col-md-6">
                              <?php $value = (isset($quotation) ? $quotation->state : ''); ?>
                              <?php echo render_input('state','billing_state',$value); ?>
                           </div>
                           <div class="col-md-6">
                              <?php $countries = get_all_countries(); ?>
                              <?php $selected = (isset($quotation) ? $quotation->country : ''); ?>
                              <?php echo render_select('country',$countries,array('country_id',array('short_name'),'iso2'),'billing_country',$selected); ?>
                           </div>
                           <div class="col-md-6">
                              <?php $value = (isset($quotation) ? $quotation->zip : ''); ?>
                              <?php echo render_input('zip','billing_zip',$value); ?>
                           </div>
                           <div class="col-md-6">
                              <?php $value = (isset($quotation) ? $quotation->email : ''); ?>
                              <?php echo render_input('email',_l('quotation_email'),$value); ?>
                           </div>
                           <div class="col-md-6">
                              <?php $value = (isset($quotation) ? $quotation->phone : ''); ?>
                              <?php echo render_input('phone',_l('quotation_phone'),$value); ?>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="btn-bottom-toolbar bottom-transaction text-right">
                  <p class="no-mbot pull-left mtop5 btn-toolbar-notice"><?php echo _l('include_quotation_items_merge_field_help','<b>{quotation_items}</b>'); ?></p>
                    <?php
                      $cancel = admin_url() . 'quotation';
                      if(isset($quotation->id)){
                        $cancel = admin_url() . 'quotation'.'#'.$quotation->id;
                      }
                     ?>
                    <a class="btn btn-sm btn-default" href="<?php echo $cancel; ?>"><?php echo _l('cancel'); ?></a>
                    <button class="btn btn-info mleft5 quotation-form-submit transaction-submit" type="button">
                      <?php echo _l('submit'); ?>
                    </button>
               </div>
               </div>
            </div>
         </div>
         <div class="col-md-12">
            <div class="panel_s">
               <?php $this->load->view('admin/quotations/_add_edit_items'); ?>
            </div>
         </div>


          <div class="col-md-12 mtop15">
             <div class="panel-body bottom-transaction">
               <?php $value = (isset($quotation) ? $quotation->client_note : get_option('predefined_client_note_quotation')); ?>
               <?php echo render_textarea('client_note','quotation_add_edit_client_note',$value,array(),array(),'mtop15'); ?>
               <?php $value = (isset($quotation) ? $quotation->terms : get_option('predefined_terms_quotation')); ?>
               <?php echo render_textarea('terms','terms_and_conditions',$value,array(),array(),'mtop15'); ?>
             </div>
          </div>


         <?php echo form_close(); ?>
         <?php $this->load->view('admin/invoice_items/item'); ?>
      </div>
      <div class="btn-bottom-pusher"></div>
   </div>
</div>
<?php init_tail(); ?>
<script>
   var _rel_id = $('#rel_id'),
   _rel_type = $('#rel_type'),
   _rel_id_wrapper = $('#rel_id_wrapper'),
   data = {};

   $(function(){
    init_currency();
    // Maybe items ajax search
    init_ajax_search('items','#item_select.ajax-search',undefined,admin_url+'items/search');
    validate_quotation_form();
    $('body').on('change','#rel_id', function() {
     if($(this).val() != ''){
      $.get(admin_url + 'quotations/get_relation_data_values/' + $(this).val() + '/' + _rel_type.val(), function(response) {
        $('input[name="quotation_to"]').val(response.to);
        $('textarea[name="address"]').val(response.address);
        $('input[name="email"]').val(response.email);
        $('input[name="phone"]').val(response.phone);
        $('input[name="city"]').val(response.city);
        $('input[name="state"]').val(response.state);
        $('input[name="zip"]').val(response.zip);
        $('select[name="country"]').selectpicker('val',response.country);
        var currency_selector = $('#currency');
        if(_rel_type.val() == 'customer'){
          if(typeof(currency_selector.attr('multi-currency')) == 'undefined'){
            currency_selector.attr('disabled',true);
          }

         } else {
           currency_selector.attr('disabled',false);
        }
        var quotation_to_wrapper = $('[app-field-wrapper="quotation_to"]');
        if(response.is_using_company == false && !empty(response.company)) {
          quotation_to_wrapper.find('#use_company_name').remove();
          quotation_to_wrapper.find('#use_company_help').remove();
          quotation_to_wrapper.append('<div id="use_company_help" class="hide">'+response.company+'</div>');
          quotation_to_wrapper.find('label')
          .prepend("<a href=\"#\" id=\"use_company_name\" data-toggle=\"tooltip\" data-title=\"<?php echo _l('use_company_name_instead'); ?>\" onclick='document.getElementById(\"quotation_to\").value = document.getElementById(\"use_company_help\").innerHTML.trim(); this.remove();'><i class=\"fa fa-building-o\"></i></a> ");
        } else {
          quotation_to_wrapper.find('label #use_company_name').remove();
          quotation_to_wrapper.find('label #use_company_help').remove();
        }
       /* Check if customer default currency is passed */
       if(response.currency){
         currency_selector.selectpicker('val',response.currency);
       } else {
        /* Revert back to base currency */
        currency_selector.selectpicker('val',currency_selector.data('base'));
      }
      currency_selector.selectpicker('refresh');
      currency_selector.change();
    }, 'json');
    }
   });
    $('.rel_id_label').html(_rel_type.find('option:selected').text());
    _rel_type.on('change', function() {
      var clonedSelect = _rel_id.html('').clone();
      _rel_id.selectpicker('destroy').remove();
      _rel_id = clonedSelect;
      $('#rel_id_select').append(clonedSelect);
      quotation_rel_id_select();
      if($(this).val() != ''){
        _rel_id_wrapper.removeClass('hide');
      } else {
        _rel_id_wrapper.addClass('hide');
      }
      $('.rel_id_label').html(_rel_type.find('option:selected').text());
    });
    quotation_rel_id_select();
    <?php if(!isset($quotation) && $rel_id != ''){ ?>
      _rel_id.change();
      <?php } ?>
    });
   function quotation_rel_id_select(){
      var serverData = {};
      serverData.rel_id = _rel_id.val();
      data.type = _rel_type.val();
      init_ajax_search(_rel_type.val(),_rel_id,serverData);
   }
   function validate_quotation_form(){
      appValidateForm($('#quotation-form'), {
        subject : 'required',
        quotation_to : 'required',
        rel_type: 'required',
        //rel_id : 'required',
        date : 'required',
        email: {
         email:true,
         required:true
       },
       currency : 'required',
     });
   }
</script>
</body>
</html>
