<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * CSRF Exclusions pour le module Ebilling + GPSWOX
 *
 * Liste des URIs à exclure de la vérification CSRF
 * Utilisé principalement pour le webhook Ebilling
 */

return [
    'ebilling/webhook',           // Route directe du webhook
    '.*/ebilling/webhook.*',      // Pattern flexible pour différentes configurations
];
