<?php defined('BASEPATH') or exit('No direct script access allowed');
$i = 0;
foreach ($statuses as $status) {
  $kanBan = new modules\quotations\services\quotations\QuotationsPipeline($status);
  $kanBan->search($this->input->get('search'))
    ->sortBy($this->input->get('sort_by'),$this->input->get('sort'));
    if($this->input->get('refresh')) {
        $kanBan->refresh($this->input->get('refresh')[$status] ?? null);
    }
  $quotations = $kanBan->get();
  $total_quotations = count($quotations);
  $total_pages = $kanBan->totalPages();
  ?>
 <ul class="kan-ban-col" data-col-status-id="<?php echo $status; ?>" data-total-pages="<?php echo $total_pages; ?>" data-total="<?php echo $total_quotations; ?>">
  <li class="kan-ban-col-wrapper">
    <div class="border-right panel_s no-mbot">
      <div class="panel-heading-bg <?php echo quotation_status_color_class($status); ?>-bg">
       <div class="kan-ban-step-indicator<?php if($i == count($statuses) -1){ echo ' kan-ban-step-indicator-full'; } ?>"></div>
       <?php echo format_quotation_status($status,'',false); ?> - <?php echo $kanBan->countAll() . ' ' . _l('quotations') ?>
     </div>
     <div class="kan-ban-content-wrapper">
      <div class="kan-ban-content">
        <ul class="sortable<?php if(has_permission('quotations','','edit')){echo ' status pipeline-status'; } ?>" data-status-id="<?php echo $status; ?>">
          <?php
          foreach ($quotations as $quotation) {
              $this->load->view('admin/quotations/pipeline/_kanban_card',array('quotation'=>$quotation,'status'=>$status));
          }
          ?>
          <?php if($total_quotations > 0 ){ ?>
          <li class="text-center not-sortable kanban-load-more" data-load-status="<?php echo $status; ?>">
            <a href="#" class="btn btn-default btn-block<?php if($total_pages <= 1 || $kanBan->getPage() === $total_pages){echo ' disabled';} ?>" data-page="<?php echo $kanBan->getPage(); ?>" onclick="kanban_load_more(<?php echo $status; ?>,this,'quotations/pipeline_load_more',347,360); return false;";><?php echo _l('load_more'); ?></a>
          </li>
          <?php } ?>
          <li class="text-center not-sortable mtop30 kanban-empty<?php if($total_quotations > 0){echo ' hide';} ?>">
            <h4>
              <i class="fa fa-circle-o-notch" aria-hidden="true"></i><br /><br />
              <?php echo _l('no_quotations_found'); ?></h4>
            </li>
          </ul>
        </div>
      </div>
    </li>
  </ul>
  <?php $i++;} ?>
