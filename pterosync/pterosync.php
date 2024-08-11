<?php
//TODO handle when egg_id and nest_id is changed
//TODO make it optional to reinstall the server when egg_id or nest_id is changed or both.

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use Illuminate\Database\Capsule\Manager as Capsule;

global $_LANG;

$language = $_SESSION['Language'] ?? 'english';
// Load language file based on the client's language preference
if (file_exists(dirname(__FILE__) . '/lang/' . $language . '.php')) {
    include dirname(__FILE__) . '/lang/' . $language . '.php';
} else {
    include dirname(__FILE__) . '/lang/english.php'; // Fallback to English
}
$_LANG = array_merge($keys, $_LANG);

include_once dirname(__FILE__) . '/helper.php';


/*
 * Module PART
 */

function pterosync_MetaData()
{
    return [
        "DisplayName" => "Ptero Sync",
        "APIVersion" => "1.1",
        "RequiresServer" => true,
    ];
}

function pterosync_loadLocations($params): array
{
    $data = pteroSyncApplicationApi($params, 'locations');
    $list = [];
    if ($data['status_code'] == 200) {
        $locations = $data['data'];
        foreach ($locations as $location) {
            $attr = $location['attributes'];
            $list[$attr['id']] = ucfirst($attr['short']);
        }
    }
    return $list;
}

function pterosync_loadEggs($params)
{
    $eggs = [];
    if (isset($_SESSION['nets'])) {
        $nests = $_SESSION['nets'];
        foreach ($nests as $nest) {
            $attr = $nest['attributes'];
            $nestId = $attr['id'];
            foreach ($attr['relationships']['eggs']['data'] as $egg) {
                $attr = $egg['attributes'];
                $eggs[$attr['id']] = $attr['name'] . ' (' . $nestId . ')';
            }
        }
    }
    return $eggs;
}

function pterosync_loadNets($params)
{
    $data = pteroSyncApplicationApi($params, 'nests?include=eggs');
    $list = [];
    if ($data['status_code'] == 200) {
        $nests = $data['data'];
        foreach ($nests as $nest) {
            $attr = $nest['attributes'];
            $nestId = $attr['id'];
            $list[$nestId] = $attr['name'];
        }
        $_SESSION['nets'] = $nests;
    }

    return $list;
}

function pterosyncAddHelpTooltip($message, $link = '#')
{
    if ($link != '#') {
        $link = 'https://github.com/wohahobg/pterosync/wiki';
    }
    if ($link == 'port') {
        $link = 'https://github.com/wohahobg/pterosync/wiki/Ports-Ranges';
    }
    // Use htmlspecialchars to encode special characters
    $encodedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    return sprintf('<a href="%s" target="_blank" data-toggle="tooltip" data-html="true" title="%s">Help</a>', $link, $encodedMessage);
}

function pterosync_ConfigKeys()
{
    $diskConfig = PteroSyncInstance::get()->disk_as_gb;
    $diskTitle = $diskConfig ? "Disk Space (GB)" : "Disk Space (MB)";
    $memoryConfig = PteroSyncInstance::get()->memory_as_gb;
    $memoryTitle = $memoryConfig ? "Memory (GB)" : "Memory (MB)";
    $swapConfig = PteroSyncInstance::get()->swap_as_gb;
    $swapTitle = $swapConfig ? "Swap (GB)" : "Swap (MB)";
    return [
        "cpu" => "CPU Limit (%)",
        "disk" => $diskTitle,
        "memory" => $memoryTitle,
        "swap" => $swapTitle,
        "location_id" => "Location ID",
        "dedicated_ip" => "Dedicated IP",
        "nest_id" => "Nest ID",
        "io" => "Block IO Weight",
        "egg_id" => "Egg ID",
        "startup" => "Startup",
        "image" => "Image",
        "databases" => "Databases",
        "server_name" => "Server Name",
        "oom_disabled" => "Disable OOM Killer",
        "backups" => "Backups",
        "allocations" => "Allocations",
        "ports_ranges" => "Ports Ranges",
        "default_variables" => "Default Variables",
        'server_port_offset' => "Server Port Offset",
        "feature_limits" => "Feature Limits",
        "hide_server_status" => "Server Status Type",
        'threads' => "Enter the specific CPU cores that this process can run on, or leave blank to allow all cores. This can be a single number, or a comma seperated list. Example: 0, 0-1,3, or 0,1,3,4."
    ];

}

function pterosync_ConfigOptions()
{
    $diskConfig = PteroSyncInstance::get()->disk_as_gb;
    $diskTitle = $diskConfig ? "Disk Space (GB)" : "Disk Space (MB)";
    $diskDescription = "Enter the amount of disk space to assign to the server. The value will be interpreted as " . ($diskConfig ? "gigabytes (GB)." : "megabytes (MB).");
    $diskDefault = $diskConfig ? 10 : 10240; // 10 GB or 10240 MB

    $memoryConfig = PteroSyncInstance::get()->memory_as_gb;
    $memoryTitle = $memoryConfig ? "Memory (GB)" : "Memory (MB)";
    $memoryDescription = "Enter the amount of memory to assign to the server. The value will be interpreted as " . ($memoryConfig ? "gigabytes (GB)." : "megabytes (MB).");
    $memoryDefault = $memoryConfig ? 1 : 1024; // 1 GB or 1024 MB

    $swapConfig = PteroSyncInstance::get()->swap_as_gb;
    $swapTitle = $swapConfig ? "Swap (GB)" : "Swap (MB)";
    $swapDescription = "Enter the amount of swap space to assign to the server. The value will be interpreted as " . ($swapConfig ? "gigabytes (GB)." : "megabytes (MB).");
    $swapDefault = $swapConfig ? 0.5 : 512; // 1 GB or 1024 MB

    $portDescription = "Specify port ranges for various server functions. The system will automatically search for available ports within these ranges under the same IP address. Ensure the ranges do not overlap. Note: 'SERVER_PORT' is required. Format: {\"SERVER_PORT\": \"start-end\", \"QUERY_PORT\": \"start-end\", \"RCON_PORT\": \"start-end\"}. Example: {\"SERVER_PORT\": \"7777-7780\", \"QUERY_PORT\": \"27015-27020\", \"RCON_PORT\": \"27020-27030\"}.";

    return [
        "cpu" => [
            "FriendlyName" => "<style></style> CPU Limit (%)",
            "Description" => pterosyncAddHelpTooltip('Amount of CPU to assign to the created server.', 'cpu'),
            "Type" => "text",
            "Size" => 25,
            "Default" => 100,
            'SimpleMode' => true,
        ],
        "disk" => [
            "FriendlyName" => $diskTitle,
            "Description" => pterosyncAddHelpTooltip($diskDescription, 'disk'),
            "Type" => "text",
            "Size" => 25,
            "Default" => $diskDefault,
            'SimpleMode' => true,
        ],
        "memory" => [
            "FriendlyName" => $memoryTitle,
            "Description" => pterosyncAddHelpTooltip($memoryDescription, 'memory'),
            "Type" => "text",
            "Size" => 25,
            "Default" => $memoryDefault,
            'SimpleMode' => true,
        ],
        "swap" => [
            "FriendlyName" => $swapTitle,
            "Description" => pterosyncAddHelpTooltip($swapDescription, 'swap'),
            "Type" => "text",
            "Default" => $swapDefault,
            "Size" => 25,
            'SimpleMode' => true,
        ],
        "location_id" => [
            "FriendlyName" => "Location ID",
            "Description" => pterosyncAddHelpTooltip("Select the location where the server will be deployed. Each location ID corresponds to a specific geographical data center.", 'location-id'),
            "Type" => "text",
            "Size" => 25,
            'SimpleMode' => true,
            'Loader' => 'pterosync_loadLocations',
        ],
        "dedicated_ip" => [
            "FriendlyName" => "Dedicated IP",
            "Description" => pterosyncAddHelpTooltip("Assign dedicated ip to the server (optional)", 'dedicated-ip'),
            "Type" => "yesno",
            "Size" => 25,
            'SimpleMode' => true,
        ],
        "nest_id" => [
            "FriendlyName" => "<span id='cNestId'></span> Nest ID",
            "Description" => pterosyncAddHelpTooltip("Choose a Nest ID that categorizes the type of server you wish to deploy. Nests are used to group similar servers.", 'nest-id'),
            "Type" => "text",
            "Size" => 25,
            'SimpleMode' => true,
            'Loader' => 'pterosync_loadNets',
        ],
        "io" => [
            "FriendlyName" => "Block IO Weight",
            "Description" => pterosyncAddHelpTooltip("Block IO Adjustment number (10-1000)", 'io'),
            "Type" => "text",
            "Size" => 25,
            "Default" => "500",
            'SimpleMode' => true,
        ],
        "egg_id" => [
            "FriendlyName" => "<span id='cEggId'></span> Egg ID",
            "Description" => pterosyncAddHelpTooltip("Select the Egg ID to specify the software environment and settings for your server. Eggs define the application running on the server.", 'egg-id'),
            "Type" => "text",
            "Size" => 10,
            'SimpleMode' => true,
            'Loader' => 'pterosync_loadEggs',
        ],
        "startup" => [
            "FriendlyName" => "Startup",
            "Description" => pterosyncAddHelpTooltip("Custom startup command to assign to the created server (optional)", 'startup'),
            "Type" => "text",
            "Size" => 25,
            'SimpleMode' => true,
        ],
        "image" => [
            "FriendlyName" => "Image",
            "Description" => pterosyncAddHelpTooltip("Custom Docker image to assign to the created server (optional)", 'image'),
            "Type" => "text",
            "Size" => 25,
            'SimpleMode' => true,
        ],
        "databases" => [
            "FriendlyName" => "Databases",
            "Description" => pterosyncAddHelpTooltip("Client will be able to create this amount of databases for their server (optional)", 'databases'),
            "Type" => "text",
            "Size" => 25,
            "Default" => 1,
            'SimpleMode' => true,
        ],
        "server_name" => [
            "FriendlyName" => "Server Name",
            "Description" => pterosyncAddHelpTooltip("The name of the server as shown on the panel (optional)", 'server-name'),
            "Type" => "text",
            "Size" => 25,
            "Default" => 'Ptero Sync Server',
            'SimpleMode' => true,
        ],
        "oom_disabled" => [
            "FriendlyName" => "Disable OOM Killer",
            "Description" => pterosyncAddHelpTooltip("Should the Out Of Memory Killer be disabled (optional)", 'oom-disabled'),
            "Type" => "yesno",
            "Size" => 25,
            'SimpleMode' => true,
        ],
        "backups" => [
            "FriendlyName" => "Backups",
            "Description" => pterosyncAddHelpTooltip("Client will be able to create this amount of backups for their server (optional)", 'backups'),
            "Type" => "text",
            "Size" => 25,
            'SimpleMode' => true,
        ],
        "allocations" => [
            "FriendlyName" => "Allocations",
            "Description" => pterosyncAddHelpTooltip("Client will be able to create this amount of allocations for their server (optional)", 'allocations'),
            "Type" => "text",
            "Size" => 25,
            'SimpleMode' => true,
        ],
        "ports_ranges" => [
            "FriendlyName" => "Ports Ranges",
            "Description" => pterosyncAddHelpTooltip($portDescription, 'ports-ranges'),
            "Type" => "textarea",
            "Size" => 10,
            "default" => '{"SERVER_PORT": "25565-25669"}',
            'SimpleMode' => true,
        ],
        "default_variables" => [
            "FriendlyName" => "Default Variables",
            "Description" => pterosyncAddHelpTooltip("Define default values for server variables in JSON format. For instance, set MAX_PLAYERS to 30 with {\"MAX_PLAYERS\": 30}. This is useful for consistent server settings and quick configuration.", 'default-variables'),
            "Type" => "textarea",
            "default" => '{"MAX_PLAYERS": 30}',
            "Size" => 25,
            'SimpleMode' => true,
        ],
        'server_port_offset' => [
            'FriendlyName' => "Server Port Offset",
            "Description" => pterosyncAddHelpTooltip("Specify an offset for the Server Port, used for games requiring a specific increment above the SERVER_PORT. Enter '1' for games like ARK: Survival Evolved that need SERVER_PORT +1, or '123' for games like MTA requiring a larger increment. To disable this feature, simply input '0'", 'server-port-offset'),
            "Type" => "text",
            "default" => 0,
            "Size" => 25,
            'SimpleMode' => true,
        ],
        "feature_limits" => [
            'FriendlyName' => "Feature Limits",
            "Description" => pterosyncAddHelpTooltip("Feature limits are ideal for overriding add-ons that are integrated into your Pterodactyl panel. Ensure that the input is valid JSON. For more information, please refer to our Wiki page.", 'feature_limits'),
            "Type" => "text",
            "default" => '0',
            "Size" => 25,
            'SimpleMode' => true,
        ],
        //We must keep this as this name
        //since a lot of people already have set `off` or so.
        //and if we change the key name we are going to fuck up them.
        "hide_server_status" => [
            'FriendlyName' => "Server Status Type",
            "Description" => pterosyncAddHelpTooltip("Select the name to be used for Server Status. Ensure the name/egg is correctly spelled in English, such as Minecraft or Source.", 'server_'),
            "Type" => "dropdown",
            "Default" => "Nest",
            "Options" => [
                'nest' => 'Nest Name',
                'egg' => 'Egg Name',
                'off' => 'Do not show server status',
            ],
            "Size" => 25,
            'SimpleMode' => true,
        ],
        //        'threads' => "Enter the specific CPU cores that this process can run on, or leave blank to allow all cores. This can be a single number, or a comma seperated list. Example: 0, 0-1,3, or 0,1,3,4."
        "threads" => [
            'FriendlyName' => "CPU Pinning",
            "Description" => pterosyncAddHelpTooltip("Enter the specific CPU cores that this process can run on, or leave blank to allow all cores. This can be a single number, or a comma seperated list. Example: 0, 0-1,3, or 0,1,3,4.", 'threads'),
            "Type" => "text",
            "Size" => 25,
            "default" => null,
            'SimpleMode' => true,
        ]
    ];
}

function pteroSyncGetOption(array $params, $id, $default = NULL)
{
    $options = pterosync_ConfigKeys();

    $friendlyName = $options[$id];
    if (isset($params['A'][$friendlyName]) && $params['configoptions'][$friendlyName] !== '') {
        return $params['configoptions'][$friendlyName];
    } else if (isset($params['configoptions'][$id]) && $params['configoptions'][$id] !== '') {
        return $params['configoptions'][$id];
    } else if (isset($params['customfields'][$friendlyName]) && $params['customfields'][$friendlyName] !== '') {
        return $params['customfields'][$friendlyName];
    } else if (isset($params['customfields'][$id]) && $params['customfields'][$id] !== '') {
        return $params['customfields'][$id];
    }

    $found = false;
    $i = 0;
    foreach ($options as $key => $value) {
        $i++;
        if ($key === $id) {
            $found = true;
            break;
        }
    }
    if ($found && isset($params['configoption' . $i]) && $params['configoption' . $i] !== '') {
        return $params['configoption' . $i];
    }
    return $default;
}

function pterosync_TestConnection(array $params)
{
    $solutions = [
        0 => "Check module debug log for more detailed error.",
        401 => "Authorization header either missing or not provided.",
        403 => "Double check the password (which should be the Application Key).",
        404 => "Result not found.",
        422 => "Validation error.",
        500 => "Panel errored, check panel logs.",
    ];

    $err = "";
    try {
        $response = pteroSyncApplicationApi($params, 'nodes');

        if ($response['status_code'] !== 200) {
            $status_code = $response['status_code'];
            $err = "Invalid status_code received: " . $status_code . ". Possible solutions: " . $solutions[$status_code] ?? "None.";
        } else {
            if ($response['meta']['pagination']['count'] === 0) {
                $err = "Authentication successful, but no nodes are available.";
            }
        }
    } catch (Exception $e) {
        logModuleCall("PteroSync-WHMCS", 'TEST CONNECTION', $params, $e->getMessage(), $e->getTraceAsString());
        $err = $e->getMessage();
    }

    return [
        "success" => $err === "",
        "error" => $err,
    ];
}


function pterosync_CreateAccount(array $params)
{

    try {
        PteroSyncInstance::get()->service_id = $params['serviceid'];
        $portsJson = pteroSyncGetOption($params, 'ports_ranges');
        $portsJson = trim($portsJson);
        $portsArray = [];
        if ($portsJson !== '') {
            $pattern = '/^(\d+-\d+)(,\d+-\d+)*$/';
            if (!preg_match_all($pattern, $portsJson, $matches)) {
                $portsArray = json_decode($portsJson, true);
                if (!is_array($portsArray)) {
                    throw new Exception('Failed to create server because ports is not in valid JSON format.');
                }
            }
        }

        $serverId = pteroSyncGetServer($params);
        if ($serverId) throw new Exception('Failed to create server because it is already created.');
        $customFieldId = pteroSyncGetCustomFiledId($params);

        $userResult = PteroSyncInstance::get()->getPterodactylUser($params, [
            'username' => pteroSyncGetOption($params, 'username', pteroSyncGenerateUsername()),
            'id' => $params['clientsdetails']['client_id'],
            'email' => $params['clientsdetails']['email'],
            'firstname' => $params['clientsdetails']['firstname'],
            'lastname' => $params['clientsdetails']['lastname'],
        ]);

        if ($userResult['status_code'] === 200 || $userResult['status_code'] === 201) {
            if (!isset($userResult['attributes']['id'])) {
                throw new Exception("Failed to get the client pterodactyl's account.Enable module debug log for more info.");
            }
            $userId = $userResult['attributes']['id'];
        } else {
            throw new Exception('Failed to create user, received error code: ' . $userResult['status_code'] . '. Enable module debug log for more info.');
        }

        $nestId = pteroSyncGetOption($params, 'nest_id');
        $eggId = pteroSyncGetOption($params, 'egg_id');

        $eggData = pteroSyncApplicationApi($params, 'nests/' . $nestId . '/eggs/' . $eggId . '?include=variables');
        if ($eggData['status_code'] !== 200) throw new Exception('Failed to get egg data, received error code: ' . $eggData['status_code'] . '. Enable module debug log for more info.');

        $environment = [];
        $default_variables = pteroSyncGetOption($params, 'default_variables');
        $default_variables = json_decode($default_variables, true);
        foreach ($eggData['attributes']['relationships']['variables']['data'] as $key => $val) {
            $attr = $val['attributes'];
            $var = $attr['env_variable'];
            $default = $attr['default_value'];
            $friendlyName = pteroSyncGetOption($params, $attr['name']);
            $envName = pteroSyncGetOption($params, $attr['env_variable']);

            if (isset($friendlyName)) {
                $environment[$var] = $friendlyName;
            } elseif (isset($envName)) {
                $environment[$var] = $envName;
            } elseif (isset($default_variables[$var]) && !in_array($default_variables[$var], PteroSyncInstance::get()->dynamic_variables)) {
                $environment[$var] = $default_variables[$var];
            } else {
                $environment[$var] = $default;
            }
        }

        if ($default_variables) {
            foreach ($default_variables as $default_variable => $default_variable_value) {
                if (in_array($default_variable_value, PteroSyncInstance::get()->dynamic_variables)) {
                    PteroSyncInstance::get()->dynamic_environment_array[$default_variable] = $default_variable_value;
                }
            }
        }

        $name = pteroSyncGetOption($params, 'server_name', pteroSyncGenerateUsername() . '_' . $params['serviceid']);
        [$memory, $swap, $disk] = pteroSyncGetMemorySwapAndDisk($params);

        $io = pteroSyncGetOption($params, 'io');
        $cpu = pteroSyncGetOption($params, 'cpu');

        $location_id = pteroSyncGetOption($params, 'location_id');
        $dedicated_ip = (bool)pteroSyncGetOption($params, 'dedicated_ip');
        PteroSyncInstance::get()->dedicated_ip = $dedicated_ip;

        PteroSyncInstance::get()->server_port_offset = pteroSyncGetOption($params, 'server_port_offset');

        if ($portsArray) {
            $port_range = isset($portsArray['SERVER_PORT']) ? explode(',', $portsArray['SERVER_PORT']) : [];
        } else {
            $port_range = !empty($portsJson) ? explode(',', $portsJson) : [];
        }

        $image = pteroSyncGetOption($params, 'image', $eggData['attributes']['docker_image']);
        $startup = pteroSyncGetOption($params, 'startup', $eggData['attributes']['startup']);
        $databases = pteroSyncGetOption($params, 'databases');
        $maximumAllocations = pteroSyncGetOption($params, 'allocations');
        $backups = pteroSyncGetOption($params, 'backups');
        $oom_disabled = (bool)pteroSyncGetOption($params, 'oom_disabled');

        $threads = pteroSyncGetOption($params, 'threads');
        $serverData = [
            'name' => $name,
            'user' => (int)$userId,
            'nest' => (int)$nestId,
            'egg' => (int)$eggId,
            'docker_image' => $image,
            'startup' => $startup,
            'oom_disabled' => $oom_disabled,
            'limits' => [
                'memory' => is_numeric($memory) ? (int)$memory : $memory,
                'swap' => is_numeric($swap) ? (int)$swap : $swap,
                'io' => is_numeric($io) ? (int)$io : $io,
                'cpu' => is_numeric($cpu) ? (int)$cpu : $cpu,
                'disk' => is_numeric($disk) ? (int)$disk : $disk,
                'threads' => (string)$threads,
            ],
            'feature_limits' => [
                'databases' => $databases ? (int)$databases : null,
                'allocations' => (int)$maximumAllocations,
                'backups' => (int)$backups,
            ],
            'deploy' => [
                'locations' => [(int)$location_id],
                'dedicated_ip' => $dedicated_ip,
                'port_range' => $port_range,
            ],
            'environment' => $environment,
            'start_on_completion' => true,
            'external_id' => (string)$params['serviceid'],
        ];

        $feature_limits = pteroSyncGetOption($params, 'feature_limits');
        $feature_limits = json_decode($feature_limits, true);
        if ($feature_limits) {
            foreach ($feature_limits as $featureName => $default) {
                $value = pteroSyncGetOption($params, $featureName, $default);
                $feature_limits[$featureName] = $value;
            }
            $serverData['feature_limits'] = array_merge($serverData['feature_limits'], $feature_limits);
        }

        $server = pteroSyncApplicationApi($params, 'servers?include=allocations', $serverData, 'POST');

        if ($server['status_code'] === 400) throw new Exception('Couldn\'t find any nodes satisfying the request.');
        if ($server['status_code'] !== 201) throw new Exception('Failed to create the server, received the error code: ' . $server['status_code'] . '. Enable module debug log for more info.');

        $serverId = $server['attributes']['id'];
        $_SERVER_ID = $server['attributes']['uuid'];


        $serverAllocations = $server['attributes']['relationships']['allocations']['data'];
        $allocation = $server['attributes']['allocation'];
        pteroSync_getServerIPAndPort($serverAllocations, $allocation);

        $serverNode = $server['attributes']['node'];
        $node_path = 'nodes/' . $serverNode . '/allocations';
        $foundPorts = [];

        if ($portsArray) {
            pteroSyncGetNodeAllocations($params, $serverNode);
            pteroSyncProcessAllocations($eggData, $portsArray);
        }

        if (!PteroSyncInstance::get()->variables) {
            pteroSyncLog('VARIABLES', 'No variables founds.', $portsArray);
        }

        if (!PteroSyncInstance::get()->node_allocations) {
            pteroSyncLog('NODE ALLOCATIONS', 'Node allocations not found.', [$node_path]);
        }

        if (PteroSyncInstance::get()->variables && PteroSyncInstance::get()->node_allocations) {
            $ips = pteroSyncMakeIParray();
            $foundPorts = pteroSyncfindPorts($portsArray, $ips);
        }

        if (!$foundPorts && PteroSyncInstance::get()->variables && PteroSyncInstance::get()->node_allocations) {
            pteroSyncLog('Ports not founds', 'Ports not founds.', [
                'results' => PteroSyncInstance::get()->fetchedResults,
                'variables' => PteroSyncInstance::get()->variables
            ]);
        }

        if ($foundPorts) {
            $_SERVER_PORT_ID = $serverAllocations[0]['attributes']['id'];

            $allocationArray['allocation'] = $_SERVER_PORT_ID;
            if (isset($foundPorts['SERVER_PORT'])) {
                $allocationArray['allocation'] = $foundPorts['SERVER_PORT']['id'];
                $allocationArray['remove_allocations'] = [$_SERVER_PORT_ID];
            }

            $environment = [];
            $additional = [];
            foreach ($foundPorts as $key => $var) {
                $environment[$key] = "" . $var['port'] . "";
                $additional[] = $var['id'];
                $maximumAllocations++;
            }

            if (PteroSyncInstance::get()->getDynamicEnvironmentArray()) {
                PteroSyncInstance::get()->addFileLog(PteroSyncInstance::get()->getDynamicEnvironmentArray(), 'Setting Dynamic Environment');
                foreach (PteroSyncInstance::get()->getDynamicEnvironmentArray() as $environmentName => $variableName) {
                    if (isset($environment[$variableName])) {
                        $environment[$environmentName] = $environment[$variableName];
                    }
                }
            }
            if (isset($environment['SERVER_PORT'])) {
                unset($environment['SERVER_PORT']);
            }
            $allocationArray['add_allocations'] = $additional;

            $updateResult = pteroSyncApplicationApi(
                $params,
                'servers/' . $serverId . '/build?include=allocations',
                array_merge([
                    'memory' => is_numeric($memory) ? (int)$memory : $memory,
                    'swap' => is_numeric($swap) ? (int)$swap : $swap,
                    'io' => is_numeric($io) ? (int)$io : $io,
                    'cpu' => is_numeric($cpu) ? (int)$cpu : $cpu,
                    'disk' => is_numeric($disk) ? (int)$disk : $disk,
                    'oom_disabled' => $oom_disabled,
                    'feature_limits' => [
                        'databases' => is_numeric($databases) ? (int)$databases : $databases,
                        'allocations' => is_numeric($maximumAllocations) ? (int)$maximumAllocations : $maximumAllocations,
                        'backups' => is_numeric($backups) ? (int)$backups : $backups,
                    ],
                ], $allocationArray),
                'PATCH'
            );

            if ($updateResult['status_code'] !== 200) throw new Exception('Failed to update build of the server, received error code: ' . $updateResult['status_code'] . '. Enable module debug log for more info.');


            $allocation = $updateResult['attributes']['allocation'];
            $serverAllocations = $updateResult['attributes']['relationships']['allocations']['data'];
            pteroSync_getServerIPAndPort($serverAllocations, $allocation);
            pteroSyncApplicationApi($params, 'servers/' . $serverId . '/startup', [
                'startup' => $server['attributes']['container']['environment']['STARTUP'],
                'egg' => $server['attributes']['egg'],
                'image' => $server['attributes']['container']['image'],
                'environment' => array_merge($serverData['environment'], $environment),
                'skip_scripts' => false,
            ], 'PATCH');

        }


        unset($params['password']);
        pteroSync_updateServerDomain($params);
        pteroSyncUpdateCustomFiled($params, $customFieldId, $_SERVER_ID);
        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update([
            'username' => '',
            'password' => '',
        ]);

    } catch (Exception $err) {
        return $err->getMessage();
    }
    return 'success';
}

function pterosync_SuspendAccount(array $params)
{
    try {
        $serverId = pteroSyncGetServer($params);
        if (!$serverId) throw new Exception('Failed to suspend server because it doesn\'t exist.');

        $suspendResult = pteroSyncApplicationApi($params, 'servers/' . $serverId . '/suspend', [], 'POST');
        if ($suspendResult['status_code'] !== 204) throw new Exception('Failed to suspend the server, received error code: ' . $suspendResult['status_code'] . '. Enable module debug log for more info.');
    } catch (Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}

function pterosync_UnsuspendAccount(array $params)
{
    try {
        $serverId = pteroSyncGetServer($params);
        if (!$serverId) throw new Exception('Failed to unsuspend server because it doesn\'t exist.');

        $suspendResult = pteroSyncApplicationApi($params, 'servers/' . $serverId . '/unsuspend', [], 'POST');
        if ($suspendResult['status_code'] !== 204) throw new Exception('Failed to unsuspend the server, received error code: ' . $suspendResult['status_code'] . '. Enable module debug log for more info.');
    } catch (Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}

function pterosync_TerminateAccount(array $params)
{
    try {
        $serverId = pteroSyncGetServer($params);
        if (!$serverId) throw new Exception('Failed to terminate server because it doesn\'t exist.');

        $deleteResult = pteroSyncApplicationApi($params, 'servers/' . $serverId, [], 'DELETE');
        if ($deleteResult['status_code'] !== 204) throw new Exception('Failed to terminate the server, received error code: ' . $deleteResult['status_code'] . '. Enable module debug log for more info.');
    } catch (Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}

function pterosync_ChangePassword(array $params)
{

    try {
        if (PteroSyncInstance::get()->enable_client_area_password_changer !== true) {
            throw new Exception ("Password Change Unavailable: The option to change passwords directly from the product page is currently disabled. For password updates, please proceed to the 'Change Password' tab.");
        }
        if ($params['password'] === '') throw new Exception('The password cannot be empty.');

        $serverData = pteroSyncGetServer($params, true);
        if (!$serverData) throw new Exception('Failed to change password because linked server doesn\'t exist.');

        $userId = $serverData['user'];
        $userResult = pteroSyncApplicationApi($params, 'users/' . $userId);
        if ($userResult['status_code'] !== 200) throw new Exception('Failed to retrieve user, received error code: ' . $userResult['status_code'] . '.');

        $updateResult = pteroSyncApplicationApi($params, 'users/' . $serverData['user'], [
            'username' => $userResult['attributes']['username'],
            'email' => $userResult['attributes']['email'],
            'first_name' => $userResult['attributes']['first_name'],
            'last_name' => $userResult['attributes']['last_name'],

            'password' => $params['password'],
        ], 'PATCH');
        if ($updateResult['status_code'] !== 200) throw new Exception('Failed to change password, received error code: ' . $updateResult['status_code'] . '.');

        unset($params['password']);
        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update([
            'username' => '',
            'password' => '',
        ]);
    } catch (Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}

function pterosync_ChangePackage(array $params)
{
    try {
        $serverData = pteroSyncGetServer($params, true);
        if (!$serverData) throw new Exception('Failed to change package of server because it doesn\'t exist.');
        $serverId = $serverData['id'];

        [$memory, $swap, $disk] = pteroSyncGetMemorySwapAndDisk($params);

        // Ensure IO and CPU are converted to integers if they are numeric
        $io = pteroSyncGetOption($params, 'io');
        $cpu = pteroSyncGetOption($params, 'cpu');
        $databases = pteroSyncGetOption($params, 'databases');
        $allocations = pteroSyncGetOption($params, 'allocations');
        $backups = pteroSyncGetOption($params, 'backups');
        $oom_disabled = (bool)pteroSyncGetOption($params, 'oom_disabled');

        $threads = pteroSyncGetOption($params, 'threads');

        // Convert to integers where necessary
        $updateData = [
            'allocation' => $serverData['allocation'],
            'memory' => is_numeric($memory) ? (int)$memory : $memory,
            'swap' => is_numeric($swap) ? (int)$swap : $swap,
            'io' => is_numeric($io) ? (int)$io : $io,
            'cpu' => is_numeric($cpu) ? (int)$cpu : $cpu,
            'disk' => is_numeric($disk) ? (int)$disk : $disk,
            'threads' => (string)$threads,
            'oom_disabled' => $oom_disabled,
            'feature_limits' => [
                'databases' => is_numeric($databases) ? (int)$databases : $databases,
                'allocations' => is_numeric($allocations) ? (int)$allocations : $allocations,
                'backups' => is_numeric($backups) ? (int)$backups : $backups,
            ],
        ];

        $feature_limits = pteroSyncGetOption($params, 'feature_limits');
        $feature_limits = json_decode($feature_limits, true);
        if ($feature_limits) {
            $updateData['feature_limits'] = array_merge($updateData['feature_limits'], $feature_limits);
        }

        $updateResult = pteroSyncApplicationApi($params, 'servers/' . $serverId . '/build?include=allocations', $updateData, 'PATCH');
        if ($updateResult['status_code'] !== 200) throw new Exception('Failed to update build of the server, received error code: ' . $updateResult['status_code'] . '. Enable module debug log for more info.');
        $allocation = $updateResult['attributes']['allocation'];
        $serverAllocations = $updateResult['attributes']['relationships']['allocations']['data'];

        pteroSync_getServerIPAndPort($serverAllocations, $allocation);

        $nestId = pteroSyncGetOption($params, 'nest_id');
        $eggId = pteroSyncGetOption($params, 'egg_id');
        $eggData = pteroSyncApplicationApi($params, 'nests/' . $nestId . '/eggs/' . $eggId . '?include=variables');
        if ($eggData['status_code'] !== 200) throw new Exception('Failed to get egg data, received error code: ' . $eggData['status_code'] . '. Enable module debug log for more info.');

        $environment = [];
        foreach ($eggData['attributes']['relationships']['variables']['data'] as $key => $val) {
            $attr = $val['attributes'];
            $var = $attr['env_variable'];
            $friendlyName = pteroSyncGetOption($params, $attr['name']);
            $envName = pteroSyncGetOption($params, $attr['env_variable']);

            if (isset($friendlyName)) $environment[$var] = $friendlyName;
            elseif (isset($envName)) $environment[$var] = $envName;
            elseif (isset($serverData['container']['environment'][$var])) $environment[$var] = $serverData['container']['environment'][$var];
            elseif (isset($attr['default_value'])) $environment[$var] = $attr['default_value'];
        }

        $image = pteroSyncGetOption($params, 'image', $eggData['attributes']['docker_image']);
        $startup = pteroSyncGetOption($params, 'startup', $eggData['attributes']['startup']);
        $updateData = [
            'environment' => $environment,
            'startup' => $startup,
            'egg' => (int)$eggId,
            'image' => $image,
            'skip_scripts' => false,
        ];
        $updateResult = pteroSyncApplicationApi($params, 'servers/' . $serverId . '/startup', $updateData, 'PATCH');
        if ($updateResult['status_code'] !== 200) throw new Exception('Failed to update startup of the server, received error code: ' . $updateResult['status_code'] . '. Enable module debug log for more info.');

        if ($eggId !== $serverData['egg']) {
            //TODO Option to reinstall the egg
            //TODO what if the egg id is not the same?
            //TODO should we look for new ports?
            //TODO what should we do?
            //pteroSyncApplicationApi($params, 'servers/' . $serverId . '/reinstall', [], 'POST');
        }

        $_SERVER_ID = $serverData['uuid'];
        $customFieldId = pteroSyncGetCustomFiledId($params);

        pteroSync_updateServerDomain($params);
        pteroSyncUpdateCustomFiled($params, $customFieldId, $_SERVER_ID);
    } catch (Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}

function pterosync_AdminCustomButtonArray()
{
//    return array(
//        "Button 1 Display Value" => "buttonOneFunction",
//        "Button 2 Display Value" => "buttonTwoFunction",
//    );
}

function pterosync_LoginLink(array $params)
{
    if ($params['moduletype'] !== 'pterosync') return;
    try {
        $server = pteroSyncGetServer($params, true);
        if (!$server) return;

        $hostname = pteroSyncGetHostname($params);
        $button1 = '<a class="btn btn-info text-uppercase"  
                    href="' . $hostname . '/server/' . $server['identifier'] . '" target="_blank">
                 <i class="fas fa-eye fa-fw"></i>
                View Server
              </a>';
        $button2 = '<a class="btn btn-primary text-uppercase"  
                 href="' . $hostname . '/admin/servers/view/' . $server['id'] . '" 
                 target="_blank">
                 <i class="fas fa-sign-in fa-fw"></i>
                Admin View
                </a>';
        // JavaScript to add buttons and hide the existing button
        echo '<script>
            const link1 = `' . $button1 . '`;
            const link2 = `' . $button2 . '`;
          </script>';

        echo '<script>
            jQuery(document).ready(function($) {
                // Hide the existing button
                $("#btnLoginLinkTrigger").hide();
               
                var buttonGroup = $("#btnLoginLinkTrigger").parent(); // Assuming buttons are in the same group

                // Add new buttons
                $(buttonGroup).prepend(link1);
                $(buttonGroup).prepend(link2);

            });
          </script>';
        return $button2;
    } catch (Exception $err) {

    }
}

function pterosync_AdminServicesTabFields($params)
{

}


function pterosync_ClientArea(array $params)
{
    if ($params['moduletype'] !== 'pterosync') return;

    global $_LANG;

    try {
        $isAdmin = $_SESSION['adminid'] ?? 0;
        $hostname = pteroSyncGetHostname($params);
        $serverId = $params['customfields']['UUID (Server ID)'];

        $serverStatusType = pteroSyncGetOption($params, 'hide_server_status');
        $serverData = pteroSyncGetServer($params, true, 'user,node,allocations,nest,egg');
        if (!$serverData) {
            return [
                'templatefile' => 'clientarea',
                'vars' => [
                    'serverFound' => false
                ],
            ];
        }
        //[$variables, $meta] = pteroSyncGetServerVariables($params,$serverData['uuid']);

        [$game, $address, $queryPort] = pteroSyncGenerateServerStatusArray($serverData, $serverStatusType);

        // Update server UUID if empty
        if ($serverId == '') {
            $serverId = $serverData['uuid'];
            $customFieldId = pteroSyncGetCustomFiledId($params);
            pteroSyncUpdateCustomFiled($params, $customFieldId, $serverData['uuid']);
        }

        // Update server IP if empty
        if ($params['domain'] == '') {
            pteroSync_updateServerDomain($params);
        }

        $serverIp = $params['domain'];
        if ($address !== false) {
            $parts = explode(':', $params['domain']);
            $serverIp = $address;
            if (isset($parts[1])) {
                $serverIp = $address . ':' . $parts[1];
            }
        }

        $endpoint = 'servers/' . $serverData['identifier'] . '/resources';
        $serverState = pteroSyncClientApi($params, $endpoint);

        if (isset($_GET['modop']) && $_GET['modop'] == 'custom' && isset($_GET['a'])) {
            if ($serverState['status_code'] === 404) {
                pteroSyncreturnJsonMessage($_LANG['SERVER_NOT_FOUND'], 404);
            }

            $action = match ($_GET['a']) {
                'startServer' => 'pteroSyncStartServer',
                'restartServer' => 'pteroSyncRestartServer',
                'stopServer' => 'pteroSyncStopServer',
                'killServer' => 'pteroSyncKillServer',
                'getState', 'getFtpDetails' => 'pteroSyncServerState',
                default => false,
            };

            if ($action !== false) {
                $action($params, $serverState['attributes']['current_state'], $serverData['identifier']);
            }

            pteroSyncreturnJsonMessage('ACTION_NOT_FOUND');
        }

        $actionUrl = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $userAttributes = $serverData['relationships']['user']['attributes'];
        $nodeAttributes = $serverData['relationships']['node']['attributes'];

        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'serverData' => $serverData,
                'serviceUrl' => $hostname . '/server/' . $serverData['identifier'],
                'currentState' => $serverState['attributes']['current_state'],
                'getStateUrl' => $actionUrl . '&modop=custom&a=getState',
                'startUrl' => $actionUrl . '&modop=custom&a=startServer',
                'rebootUrl' => $actionUrl . '&modop=custom&a=restartServer',
                'stopUrl' => $actionUrl . '&modop=custom&a=stopServer',
                'killUrl' => $actionUrl . '&modop=custom&a=killServer',
                'serverIp' => $serverIp,
                'serverId' => $serverId,
                'ftpDetails' => [
                    'username' => $userAttributes['username'] . '.' . $serverData['identifier'],
                    'host' => 'sftp://' . $nodeAttributes['fqdn'] . ':' . $nodeAttributes['daemon_sftp']
                ],
                'serverFound' => true,
                'serviceId' => $params['serviceid'],
                'isAdmin' => $isAdmin,
                'gameQueryData' => [
                    'game' => $game,
                    'address' => $address,
                    'port' => $queryPort,
                ]
            ],
        ];
    } catch (Exception $err) {
        // Ignore
    }
}
