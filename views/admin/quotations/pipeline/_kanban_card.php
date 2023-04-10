<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if ($quotation['status'] == $status) { ?>
<li data-quotation-id="<?php echo $quotation['id']; ?>" class="<?php if($quotation['invoice_id'] != NULL || $quotation['quotation_id'] != NULL){echo 'not-sortable';} ?>">
   <div class="panel-body">
      <div class="row">
         <div class="col-md-12">
            <h4 class="bold pipeline-heading">
               <a href="<?php echo admin_url('quotations/list_quotations/'.$quotation['id']); ?>" data-toggle="tooltip" data-title="<?php echo $quotation['subject']; ?>" onclick="quotation_pipeline_open(<?php echo $quotation['id']; ?>); return false;"><?php echo format_quotation_number($quotation['id']); ?></a>
               <?php if(has_permission('quotations','','edit')){ ?>
               <a href="<?php echo admin_url('quotations/quotation/'.$quotation['id']); ?>" target="_blank" class="pull-right"><small><i class="fa fa-pencil-square-o" aria-hidden="true"></i></small></a>
               <?php } ?>
            </h4>
            <span class="mbot10 inline-block full-width">
            <?php
               if($quotation['rel_type'] == 'lead'){
                 echo '<a href="'.admin_url('leads/index/'.$quotation['rel_id']).'" onclick="init_lead('.$quotation['rel_id'].'); return false;" data-toggle="tooltip" data-title="'._l('lead').'">' .$quotation['quotation_to'].'</a><br />';
               } else if($quotation['rel_type'] == 'customer'){
                 echo '<a href="'.admin_url('clients/client/'.$quotation['rel_id']).'" data-toggle="tooltip" data-title="'._l('client').'">' .$quotation['quotation_to'].'</a><br />';
               }
               ?>
            </span>
         </div>
         <div class="col-md-12">
            <div class="row">
               <div class="col-md-8">
                  <?php if($quotation['total'] != 0){
                     ?>
                  <span class="bold"><?php echo _l('quotation_total'); ?>:
                     <?php echo app_format_money($quotation['total'], get_currency($quotation['currency'])); ?>
                  </span>
                  <br />
                  <?php } ?>
                  <?php echo _l('quotation_date'); ?>: <?php echo _d($quotation['date']); ?>
                  <?php if(is_date($quotation['open_till'])){ ?>
                  <br />
                  <?php echo _l('quotation_open_till'); ?>: <?php echo _d($quotation['open_till']); ?>
                  <?php } ?>
                  <br />
               </div>
               <div class="col-md-4 text-right">
                  <small><i class="fa fa-comments" aria-hidden="true"></i> <?php echo _l('quotation_comments'); ?>: <?php echo total_rows(db_prefix().'quotation_comments', array(
                     'quotationid' => $quotation['id']
                     )); ?></small>
               </div>
               <?php $tags = get_tags_in($quotation['id'],'quotation');
                  if(count($tags) > 0){ ?>
               <div class="col-md-12">
                  <div class="mtop5 kanban-tags">
                     <?php echo render_tags($tags); ?>
                  </div>
               </div>
               <?php } ?>
            </div>
         </div>
      </div>
   </div>
</li>
<?php } ?>
