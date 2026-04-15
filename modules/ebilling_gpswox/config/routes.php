<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Routes publiques / client (selon ta politique d'accès Perfex)
$route['ebilling/create/(:num)']   = 'ebilling_gpswox/ebilling/create/$1';    // $1 = invoice_id Perfex
$route['ebilling/redirect/(:any)'] = 'ebilling_gpswox/ebilling/redirect/$1';  // $1 = bill_id
$route['ebilling/ussd/(:any)']     = 'ebilling_gpswox/ebilling/ussd/$1';      // $1 = bill_id

// Webhook Ebilling (POST)
$route['ebilling/webhook']         = 'ebilling_gpswox/webhook/listen';
