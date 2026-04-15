<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Custom routes for modules
 * This file is loaded by application/config/routes.php
 */

// Ebilling module routes
$route['ebilling/webhook']              = 'ebilling_gpswox/webhook/listen';
$route['ebilling/create/(:num)']        = 'ebilling_gpswox/ebilling/create/$1';
$route['ebilling/return/(:num)/(:any)'] = 'ebilling_gpswox/ebilling/return_from_payment/$1/$2';
$route['ebilling/redirect/(:any)']      = 'ebilling_gpswox/ebilling/redirect/$1';
$route['ebilling/ussd/(:any)']          = 'ebilling_gpswox/ebilling/ussd/$1';
