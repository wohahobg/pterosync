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
    $url = PteroSyncInstance::get()->cssPath;
    return '<link rel="stylesheet" href="' . $url . '">' . PHP_EOL;
});

add_hook('AdminAreaFooterOutput', 1, function ($params) {

    $url = PteroSyncInstance::get()->jsPath;
    $urls = '<script src="' . $url . '"></script>' . PHP_EOL;
    $urls .= '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>';
    return $urls;
});

add_hook('ClientAreaHeadOutput', 1, function ($params) {
    if ($params['modulename'] !== 'pterosync') return;
    $cssUrl = PteroSyncInstance::get()->cssPath;
    $jsUrl = PteroSyncInstance::get()->jsPath;
    $urls = '<link rel="stylesheet" href="' . $cssUrl . '">' . PHP_EOL;
    $urls .= '<script src="' . $jsUrl . '"></script>' . PHP_EOL;
    return $urls;
});

add_hook('ClientAreaPrimarySidebar', 1, function(\WHMCS\View\Menu\Item $primarySidebar)
{
    $ActionDetails = $primarySidebar->getChild("Service Details Actions");
    if (empty($ActionDetails)) {
        return;
    }
    if (PteroSyncInstance::get()->enable_client_area_password_changer !== true) return;

    $ActionDetailsChildren = $ActionDetails->getChildren();
    $kidsToIcon = ["Change Password"];
    foreach($ActionDetailsChildren as $key => $Action_details_child) {
        if (in_array($key, $kidsToIcon)) {
            $ActionDetails->removeChild($key);
        }
    }
});

add_hook('ClientAdd', 1, function ($vars) {
    if (PteroSyncInstance::get()->enable_whmcs_user_sync !== true) return;
    $data = PteroSyncInstance::get()->hooksData['UserAdd'] ?? [];
    $arr = [
        'username' => pteroSyncGenerateUsername(),
        'id' => $vars['client_id'],
        'firstname' => $vars['firstname'],
        'lastname' => $vars['lastname'],
        'email' => $vars['email'],
    ];
    if ($data) {
        $arr['password'] = $data['password'];
    }
    $params = PteroSyncInstance::get()->getServer();
    $userResult = PteroSyncInstance::get()->getPterodactylUser($params, $arr);
    if ($userResult['status_code'] !== 200 && $userResult['status_code'] !== 201) {
        throw new Exception('Failed to create user, received error code: ' . $userResult['status_code'] . '. Enable module debug log for more info.');
    }
});

add_hook('ClientEdit', 1, function ($client) {

    if (PteroSyncInstance::get()->enable_whmcs_user_sync !== true) return;

    $oldClient['id'] = $client['client_id'];
    $oldClient['email'] = $client['olddata']['email'];
    $params = PteroSyncInstance::get()->getServer();

    $userResult = PteroSyncInstance::get()->getPterodactylUser($params, $oldClient, false);
    if ($userResult['status_code'] !== 404) {
        $newData = [
            'username' => $userResult['attributes']['username'],
            'email' => $client['email'],
            'first_name' => $client['firstname'],
            'last_name' => $client['lastname'],
        ];
        PteroSyncInstance::get()->updatePterodactylUserData($userResult, $params, $newData);
    }
    if ($userResult['status_code'] === 404) {
        throw new Exception('Failed to edit user, received error code: ' . $userResult['status_code'] . '. Enable module debug log for more info.');
    }

});

add_hook('PreDeleteClient', 1, function ($vars) {
    if (PteroSyncInstance::get()->enable_whmcs_user_sync !== true) return;
    $clientId = $vars['userid'];
    $params = PteroSyncInstance::get()->getServer();
    $userResult = PteroSyncInstance::get()->getPterodactylUser($params, [
        'id' => $clientId
    ], false);

    if ($userResult !== 404) {
        //pteroSyncApplicationApi($params, 'users/' . $userResult['attributes']['id'], [], 'DELETE');
    }
});

add_hook('ClientClose', 1, function ($vars) {
    if (PteroSyncInstance::get()->enable_whmcs_user_sync !== true) return;
    $clientId = $vars['userid'];
    $params = PteroSyncInstance::get()->getServer();
    $userResult = PteroSyncInstance::get()->getPterodactylUser($params, [
        'id' => $clientId
    ], false);

    if ($userResult !== 404) {
     //   pteroSyncApplicationApi($params, 'users/' . $userResult['attributes']['id'], [], 'DELETE');
    }
});

add_hook('UserAdd', 1, function ($vars) {
    if (PteroSyncInstance::get()->enable_whmcs_user_sync !== true) return;
    PteroSyncInstance::get()->hooksData['UserAdd'] = $vars;
});

add_hook('UserChangePassword', 1, function ($vars) {
    if (PteroSyncInstance::get()->enable_whmcs_user_sync !== true) return;
    $client = PteroSyncInstance::get()->getClient($vars['userid']);
    if ($client) {
        $params = PteroSyncInstance::get()->getServer();
        $userResult = PteroSyncInstance::get()->getPterodactylUser($params, [
            'username' => pteroSyncGenerateUsername(),
            'id' => $client['id'],
            'email' => $client['email'],
            'firstname' => $client['firstname'],
            'lastname' => $client['lastname'],
        ]);
        if ($userResult['status_code'] === 200 || $userResult['status_code'] === 201) {
            PteroSyncInstance::get()
                ->changePterodactylPassword($userResult, $params, $vars['password']);
        } else {
            logModuleCall("PteroSync-WHMCS", 'Hook UserChangePassword: Could not change User Password', $params, '', '');
        }
    }
});
