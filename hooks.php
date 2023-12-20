<?php

/**
 * @class hook
 * @created 18/11/2023
 *
 * @author Wohaho
 * @email support@w-store.org
 * @discord Wohaho#5542
 *
 */

add_hook('ClientAreaProductDetailsPreModuleTemplate', 1, function ($params) {
    if ($params['modulename'] !== 'pterosync') return;
    //$params['service']['domain'] = '';
    $params['domain'] = '';
    return $params;
});
add_hook('ClientAreaProductDetails', 1, function ($params) {
    if ($params['modulename'] !== 'pterosync') return;
    $params['dedicatedip'] = $params['domain'];
    //$params['service']['domain'] = '';
    $params['domain'] = '';
    return $params;
});