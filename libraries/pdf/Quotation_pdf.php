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


        # Don't remove these lines - important for the PDF layout
        $this->quotation->content = $this->fix_editor_html($this->quotation->content);
        $this->quotation_status_color = quotation_status_color_pdf($this->quotation->status);
        $this->quotation_status = format_quotation_status($this->quotation->status);

        $this->quotation_number = format_quotation_number($this->quotation->id);

        $this->SetTitle($this->quotation_number .'-'. $this->quotation->quotation_to);
        $this->SetDisplayMode('default', 'OneColumn');
    }

    //Page header
    public function Header() {

        $dimensions = $this->getPageDimensions();

        $quotation                = hooks()->apply_filters('quotation_html_pdf_data', $this->quotation);
        if(isset($quotation)){
            $quotation_pdf = $quotation;
        }

        $right = pdf_right_logo_url();
        
        // Add logo
        $left = pdf_logo_url();
        $this->ln(5);

        $page_start = $this->getPage();
        $y_start    = $this->GetY();
        $left_width = 40;
        // Write top left logo and right column info/text

        // write the left cell
        $this->MultiCell($left_width, 0, $left, 0, 'L', 0, 2, '', '', true, 0, true);

        $page_end_1 = $this->getPage();
        $y_end_1    = $this->GetY();

        $this->setPage($page_start);

        // write the right cell
        $this->MultiCell(185, 0, $right, 0, 'R', 0, 1, 0, $y_start, true, 0, true);

        //pdf_multi_row($info_right_column, '', $this, ($dimensions['wk'] / 1) - $dimensions['lm']);
        //pdf_multi_row($info_left_column, $info_right_column, $this, ($dimensions['wk'] / 1) - $dimensions['lm']);

        //$this->ln(5);
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

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-25);
        // Set font
        $this->SetFont('helvetica', 'B', 10);
        

        $tbl = <<<EOD
        <table cellspacing="0" cellpadding="5" border="0">
            <tr>
                <td width ="75%" align="center" style="line-height: 200%; vertical-align:middle; background-color:#00008B;color:#FFF;">
                    Jl. Raya Taktakan No.9, Lontarbaru, Kec. Serang Kota Serang, Banten <BR />
                    Web : www.ciptamasjaya.co.id - Email : info@ciptamasjaya.co.id 
                </td>
                <td width ="25%"  align="center" style="font-size:20px; line-height: 100%; vertical-align:middle; background-color:#FF0000; color:#FFF;">TAMASYA <BR />TOTAL SOLUTION FOR SAFETY</td>
            </tr>
        </table>
        EOD;

        $this->writeHTML($tbl, true, false, false, false, '');

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
