<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['quotations/quotation/(:num)/(:any)'] = 'quotation/index/$1/$2';

/**
 * @since 2.0.0
 */
$route['quotations/list'] = 'myquotation/list';
$route['quotations/show/(:num)/(:any)'] = 'myquotation/show/$1/$2';
$route['quotations/office/(:num)/(:any)'] = 'myquotation/office/$1/$2';
$route['quotations/pdf/(:num)'] = 'myquotation/pdf/$1';
