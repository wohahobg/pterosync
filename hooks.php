<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

include_once dirname(__FILE__) . '/helper.php';

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
    $params['domain'] = '';
    return $params;
});
add_hook('ClientAreaProductDetails', 1, function ($params) {
    if ($params['modulename'] !== 'pterosync') return;
    $params['dedicatedip'] = $params['domain'];
    $params['domain'] = '';
    return $params;
});

add_hook('AdminAreaHeadOutput', 1, function ($params) {
    $url = PteroSyncSettings::get()->cssPath;
    return '<link rel="stylesheet" href="' . $url . '">' . PHP_EOL;
});

add_hook('AdminAreaFooterOutput', 1, function ($params) {
    $url = PteroSyncSettings::get()->jsPath;
    $urls = '<script src="' . $url . '"></script>' . PHP_EOL;
    $urls .= '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>';
    return $urls;
});

add_hook('ClientAreaHeadOutput', 1, function ($params) {
    if ($params['modulename'] !== 'pterosync') return;
    $cssUrl = PteroSyncSettings::get()->cssPath;
    $jsUrl = PteroSyncSettings::get()->jsPath;
    $urls = '<link rel="stylesheet" href="' . $cssUrl . '">' . PHP_EOL;
    $urls .= '<script src="' . $jsUrl . '"></script>' . PHP_EOL;
    return $urls;
});