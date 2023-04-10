<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(LIBSPATH . 'pdf/App_pdf.php');

class Quotation_pdf extends App_pdf
{
    protected $quotation;

    private $quotation_number;

    public function __construct($quotation, $tag = '')
    {
        if ($quotation->rel_id != null && $quotation->rel_type == 'customer') {
            $this->load_language($quotation->rel_id);
        } else if ($quotation->rel_id != null && $quotation->rel_type == 'lead') {
            $CI = &get_instance();

            $this->load_language($quotation->rel_id);
            $CI->db->select('default_language')->where('id', $quotation->rel_id);
            $language = $CI->db->get('leads')->row()->default_language;

            load_pdf_language($language);
        }

        $quotation                = hooks()->apply_filters('quotation_html_pdf_data', $quotation);
        $GLOBALS['quotation_pdf'] = $quotation;

        parent::__construct();

        $this->tag      = $tag;
        $this->quotation = $quotation;

        $this->quotation_number = format_quotation_number($this->quotation->id);

        $this->SetTitle($this->quotation_number);
        $this->SetDisplayMode('default', 'OneColumn');

        # Don't remove these lines - important for the PDF layout
        $this->quotation->content = $this->fix_editor_html($this->quotation->content);
    }

    public function prepare()
    {
        $number_word_lang_rel_id = 'unknown';

        if ($this->quotation->rel_type == 'customer') {
            $number_word_lang_rel_id = $this->quotation->rel_id;
        }

        $this->with_number_to_word($number_word_lang_rel_id);

        $total = '';
        if ($this->quotation->total != 0) {
            $total = app_format_money($this->quotation->total, get_currency($this->quotation->currency));
            $total = _l('quotation_total') . ': ' . $total;
        }

        $this->set_view_vars([
            'number'       => $this->quotation_number,
            'quotation'     => $this->quotation,
            'total'        => $total,
            'quotation_url' => site_url('quotation/' . $this->quotation->id . '/' . $this->quotation->hash),
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'quotation';
    }

    protected function file_path()
    {
        $filePath = 'my_quotationpdf.php';
        $customPath = module_views_path('quotations','themes/' . active_clients_theme() . '/views/quotations/' . $filePath);
        $actualPath = module_views_path('quotations','themes/' . active_clients_theme() . '/views/quotations/quotationpdf.php');

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
