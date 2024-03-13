<?php


use Illuminate\Database\Capsule\Manager as Capsule;

class PteroSyncInstance
{
    private static PteroSyncInstance|null $instance = null;
    public $swap_as_gb = false;
    public $disk_as_gb = false;
    public $memory_as_gb = false;
    public $show_server_information = false;
    public $enable_client_area_password_changer = true;
    public $enable_whmcs_user_sync = false;
    public $game_panel = "pterodactyl";

    public $server_port_offset = 0;

    public $dynamic_variables = [
        'SERVER_PORT_OFFSET'
    ];

    public $dynamic_environment_array = [];

    public $jsPath = '';
    public $cssPath = '';

    public array $panelCurlConfig = [
        'user' => 'PteroSync',
        'accept' => 'Application/vnd.pterodactyl.v1+json'
    ];

    public array $curlRequestSettings = [
        'pterodactyl' => [
            'user' => 'PteroSync',
            'accept' => 'Application/vnd.pterodactyl.v1+json'
        ],
        'wisp' => [
            'user' => 'WISP',
            'accept' => 'Application/vnd.wisp.v1+json',
        ]
    ];

    public $fetchingPagination = [];
    /**
     * @var true
     */
    public bool $fetching = true;
    public int $fetchingNextPage = 1;
    public array $fetchedResults = [];
    public array $hooksData = [];
    private array|false $server = false;

    public function __construct()
    {

        global $CONFIG;
        $data = file_get_contents(dirname(__FILE__) . '/config.json');
        $data = json_decode($data, true);
        if (!$data) {
            throw new Exception('PteroSync Config file not valid json format!');
        }
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        if (isset($this->curlRequestSettings[$this->game_panel])) {
            $this->panelCurlConfig = $this->curlRequestSettings[$this->game_panel];
        }

        $whmcsBaseUrl = rtrim($CONFIG['SystemURL'], '/');
        $this->jsPath = $whmcsBaseUrl . '/modules/servers/pterosync/pterosync.js?v=' . time();
        $this->cssPath = $whmcsBaseUrl . '/modules/servers/pterosync/pterosync.css?v=' . time();
    }


    public static function get(): ?PteroSyncInstance
    {
        if (self::$instance === null) {
            self::$instance = new PteroSyncInstance();
        }
        return self::$instance;
    }

    public function getServer()
    {
        if (!$this->server) {
            $server = Capsule::table('tblservers')
                ->where('type', 'pterosync')
                ->first();
            $this->server = [
                'serverhostname' => $server->hostname,
                'serverusername' => $server->username,
                'serverpassword' => decrypt($server->password),
                'serveraccesshash' => $server->accesshash,
                'serversecure' => $server->secure
            ];
        }
        return $this->server;
    }

    public function getClient($userId)
    {
        if (isset($_SESSION['uid'])) return $this->getClientById($_SESSION['uid']);
        $client = Capsule::table('tblusers_clients')
            ->where('auth_user_id', '=', $userId)
            ->orderBy('last_login', 'desc')
            ->first();
        if ($client) return $this->getClientById($client->client_id);
        logModuleCall("PteroSync-WHMCS", 'Could not find valid client for the given user id.', ['userId' => $userId], '', '');
        return false;
    }

    private function getClientById($id)
    {
        try {
            return WHMCS\User\Client::findOrFail($id);
        } catch (Exception $e) {
            logModuleCall("PteroSync-WHMCS", 'Could not find valid client data for the given client id.', ['clientId' => $id], '', '');
            return false;
        }

    }

    public function changePterodactylPassword($userResult, $params, $password)
    {
        $updateResult = pteroSyncApplicationApi($params, 'users/' . $userResult['attributes']['id'], [
            'username' => $userResult['attributes']['username'],
            'email' => $userResult['attributes']['email'],
            'first_name' => $userResult['attributes']['first_name'],
            'last_name' => $userResult['attributes']['last_name'],
            'password' => $password
        ], 'PATCH');
        if ($updateResult['status_code'] !== 200) throw new Exception('Failed to change password, received error code: ' . $updateResult['status_code'] . '.');
    }

    public function updatePterodactylUserData($userResult, $params, $column)
    {
        $defaultAttributes = [
            'username' => $userResult['attributes']['username'],
            'email' => $userResult['attributes']['email'],
            'first_name' => $userResult['attributes']['first_name'],
            'last_name' => $userResult['attributes']['last_name'],
        ];
        $arr = array_merge($defaultAttributes, $column);

        $updateResult = pteroSyncApplicationApi($params, 'users/' . $userResult['attributes']['id'], $arr, 'PATCH');
        if ($updateResult['status_code'] !== 200) throw new Exception('Failed to change ' . $column . ', received error code: ' . $updateResult['status_code'] . '.');
    }

    public function getPterodactylUser($params, array $client = [], $create = true)
    {
        $userResult = pteroSyncApplicationApi($params, 'users/external/' . $client['id']);
        if ($userResult['status_code'] === 404) {
            if (!isset($client['email'])) return $userResult;

            if (PteroSyncInstance::get()->game_panel == 'wisp') {
                $userResult = pteroSyncApplicationApi($params, 'users?search=' . urlencode($client['email']));
            } elseif (PteroSyncInstance::get()->game_panel == 'pterodactyl') {
                $userResult = pteroSyncApplicationApi($params, 'users?filter[email]=' . urlencode($client['email']));
            }

            if ($create === true && $userResult['meta']['pagination']['total'] === 0) {
                $arr = [
                    'username' => $client['username'],
                    'email' => $client['email'],
                    'first_name' => $client['firstname'],
                    'last_name' => $client['lastname'],
                    'external_id' => (string)$client['id'],
                ];
                if (isset($client['password'])) {
                    $arr['password'] = $client['password'];
                }
                $userResult = pteroSyncApplicationApi($params, 'users', $arr, 'POST');
            } else {
                foreach ($userResult['data'] as $key => $value) {
                    if ($value['attributes']['email'] == $client['email']) {
                        $userResult = $value;
                        $userResult['status_code'] = 200;
                        //Let's update the external_id to match the actual client id.
                        $arr['external_id'] = (string)$client['id'];
                        if (isset($client['password'])) {
                            $arr['password'] = $client['password'];
                        }
                        $this->updatePterodactylUserData($userResult, $params, $arr);

                        break;
                    }
                }
            }
        }
        return $userResult;
    }


    public function addFileLog($data, $title = 'Log')
    {
        $logDir = __DIR__ . '/log';
        $file = sprintf('%s/.%s.log', $logDir, date('Y-m-d'));

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
            chown($logDir, fileowner(__FILE__));
            chgrp($logDir, filegroup(__FILE__));
        }
        $content = PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'START: ' . $title . PHP_EOL;
        $content .= 'Time: ' . date('Y-m-d H:i:s');
        $content .= PHP_EOL;
        $content .= print_r($data, true);
        $content .= PHP_EOL;
        $content .= 'END: ' . $title;
        file_put_contents($file, $content, FILE_APPEND);
    }

    public function getDynamicEnvironmentArray(): array
    {
        return $this->dynamic_environment_array;
    }
}


function pteroSyncGetHostname(array $params)
{

    $hostname = $params['serverhostname'];

    if ($hostname === '') throw new Exception('Could not find the panel\'s hostname - did you configure server group for the product?');
    // For whatever reason, WHMCS converts some characters of the hostname to their literal meanings (- => dash, etc) in some cases
    foreach ([
                 'DOT' => '.',
                 'DASH' => '-',
             ] as $from => $to) {
        $hostname = str_replace($from, $to, $hostname);
    }
    if (ip2long($hostname) !== false) $hostname = 'http://' . $hostname;
    else $hostname = ($params['serversecure'] ? 'https://' : 'http://') . $hostname;
    return rtrim($hostname, '/');
}

function pteroSyncApiHandler(mixed $method, array $data, CurlHandle|false $curl, array $headers, mixed $dontLog, string $url): mixed
{
    if ($method === 'POST' || $method === 'PATCH') {
        $jsonData = json_encode($data);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        $headers[] = "Content-Type: application/json";
        $headers[] = "Content-Length: " . strlen($jsonData);
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($curl);
    $responseData = json_decode($response, true);
    $responseData['status_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($responseData['status_code'] === 0 && !$dontLog) logModuleCall("PteroSync-WHMCS", "CURL ERROR", curl_error($curl), "");

    curl_close($curl);
    if (isset($data['password'])) {
        //let's unset that ?
        unset($data['password']);
    }
    if (!$dontLog) logModuleCall("PteroSync-WHMCS", $method . " - " . $url,
        isset($data) ? json_encode($data) : "",
        print_r($responseData, true));

    return $responseData;
}

function pteroSyncApplicationApi(array $params, $endpoint, array $data = [], $method = "GET", $dontLog = false)
{
    $url = pteroSyncGetHostname($params) . '/api/application/' . $endpoint;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
    curl_setopt($curl, CURLOPT_USERAGENT, PteroSyncInstance::get()->panelCurlConfig['user'] . "-WHMCS");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_301);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $headers = [
        "Authorization: Bearer " . $params['serverpassword'],
        "Accept: " . PteroSyncInstance::get()->panelCurlConfig['accept'],
    ];

    return pteroSyncApiHandler($method, $data, $curl, $headers, $dontLog, $url);
}

function pteroSyncClientApi(array $params, $endPoint, array $data = [], $method = "GET", $dontLog = false)
{
    $url = pteroSyncGetHostname($params) . '/api/client/' . $endPoint;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
    curl_setopt($curl, CURLOPT_USERAGENT, PteroSyncInstance::get()->panelCurlConfig['user'] . "-WHMCS");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_301);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $headers = [
        "Authorization: Bearer " . $params['serveraccesshash'],
        "Accept: " . PteroSyncInstance::get()->panelCurlConfig['accept'],
    ];

    return pteroSyncApiHandler($method, $data, $curl, $headers, $dontLog, $url);
}

function pteroSyncGetMemorySwapAndDisck($params)
{
    $memory = pteroSyncGetOption($params, 'memory');
    if (PteroSyncInstance::get()->memory_as_gb) {
        $memory = pteroSyncConvertToMB($memory);
    }
    $swap = pteroSyncGetOption($params, 'swap');
    if (PteroSyncInstance::get()->swap_as_gb) {
        $swap = pteroSyncConvertToMB($swap);
    }
    $disk = pteroSyncGetOption($params, 'disk');
    if (PteroSyncInstance::get()->disk_as_gb) {
        $disk = pteroSyncConvertToMB($disk);
    }
    return [$memory, $swap, $disk];
}

function pteroSyncGetServer(array $params, $raw = false, $include = false)
{
    if ($include) {
        $include = '?include=' . $include;
    }
    $serverResult = pteroSyncApplicationApi($params, 'servers/external/' . $params['serviceid'] . $include, [], 'GET', true);

    if ($serverResult['status_code'] === 200) {
        if ($raw) return $serverResult['attributes'];
        else return $serverResult['attributes']['id'];
    } else if ($serverResult['status_code'] === 500) {
        throw new Exception('Failed to get server, panel errored. Check panel logs for more info.');
    }
    return false;
}

function pteroSyncGetClientServer($params, $serverId)
{
    $serverResult = pteroSyncClientApi($params, 'servers/' . $serverId . '?include=subusers', [], 'GET', true);

    if ($serverResult['status_code'] === 200) {
        return $serverResult['attributes'];
    } else if ($serverResult['status_code'] === 500) {
        throw new Exception('Failed to get server, panel errored. Check panel logs for more info.');
    }
    return false;
}

function pteroSyncRandom($length)
{
    if (class_exists("\Illuminate\Support\Str")) {
        return \Illuminate\Support\Str::random($length);
    } else if (function_exists("str_random")) {
        return str_random($length);
    } else {
        throw new \Exception("Unable to find a valid function for generating random strings");
    }
}

function pteroSyncGenerateUsername($length = 8)
{
    $returnable = false;
    while (!$returnable) {
        $generated = pteroSyncRandom($length);
        if (preg_match('/[A-Z]+[a-z]+[0-9]+/', $generated)) {
            $returnable = true;
        }
    }
    return $generated;
}

function pteroSyncConvertToMB($input)
{
    return $input * 1024;
}

function pteroSyncGetNodeAllocations($params, $serverNode, $nodePath)
{

    $perPage = 200;
    $PteroSyncSettings = PteroSyncInstance::get();
    $currentPage = $PteroSyncSettings->fetchingNextPage;
    $path = sprintf(
        '%s?per_page=%d&page=%d&filter[server_id]=0',
        $nodePath,
        $perPage,
        $currentPage
    );

    $nodeAllocations = pteroSyncApplicationApi($params, $path);

    if ($nodeAllocations['status_code'] == 200 && !empty($nodeAllocations['data'])) {

        $PteroSyncSettings->fetchedResults = array_merge($PteroSyncSettings->fetchedResults ?? [], $nodeAllocations['data']);

        if (!empty($nodeAllocations['meta']['pagination']['links']['next'])) {
            $PteroSyncSettings->fetchingNextPage = $currentPage + 1;
            $PteroSyncSettings->fetching = true;
        } else {
            $PteroSyncSettings->fetchingNextPage = 1;
            $PteroSyncSettings->fetching = false;
        }
        return $PteroSyncSettings->fetchedResults;
    }

    return false;
}

function pteroSyncProcessAllocations($allocations, $eggData, $ports)
{
    $variables = [];
    foreach ($eggData['attributes']['relationships']['variables']['data'] as $val) {
        $attr = $val['attributes'];
        $var = $attr['env_variable'];
        if (isset($ports[$var])) {
            $variables[$var] = $ports[$var];
        }
    }
    if (isset($ports['EXTRA_ALLOCATION'])) {
        $extra = $ports['EXTRA_ALLOCATION'];
        $parts = explode(":", $extra);

        if (count($parts) > 1) {
            $times = end($parts);
            $lastColonPos = strrpos($extra, ':');
            if ($lastColonPos !== false) {
                $modifiedString = substr($extra, 0, $lastColonPos);
            } else {
                $modifiedString = $extra;
            }
        } else {
            $times = 1;
            $modifiedString = $extra;
        }
        for ($i = 0; $i < $times; $i++) {
            $variables['EXTRA_ALLOCATION' . $i] = $modifiedString;
        }
    }

    $ips = [];
    foreach ($allocations as $allocation) {
        $attr = $allocation['attributes'];
        $ip = $attr['ip'];
        $ips[$ip][] = [
            'port' => $attr['port'],
            'id' => $attr['id']
        ];
    }

    return [$variables, $ips];
}

function pteroSyncfindFreePortsForVariables($ips_data, &$variables)
{
    foreach ($ips_data as $ip => $ports) {
        $allocatedPorts = []; // Track allocated ports per IP
        $freePorts = [];
        $foundAll = true;

        foreach ($variables as $var => $range) {
            list($start, $end) = explode('-', $range);
            $foundPort = false;

            foreach ($ports as $port) {
                if (!isset($allocatedPorts[$port['id']])) {
                    if ($port['port'] >= $start && $port['port'] <= $end) {
                        $port['ip'] = $ip;
                        $freePorts[$var] = $port;

                        if ($var == 'SERVER_PORT' && !isset($variables['SERVER_PORT_OFFSET']) && PteroSyncInstance::get()->server_port_offset > 0) {
                            $offset = $port['port'] + PteroSyncInstance::get()->server_port_offset;
                            PteroSyncInstance::get()->addFileLog([
                                'offset' => $offset,
                                'port' => $port['port']
                            ], 'New server port found!');
                            foreach ($ports as $offsetPort) {
                                if ($offsetPort['port'] == $offset) {
                                    $offsetPort['ip'] = $ip;
                                    $foundPort['SERVER_PORT_OFFSET'] = $offsetPort;
                                    break;
                                }
                            }
                        }
                        $allocatedPorts[$port['id']] = $port['port'];
                        $foundPort = true;
                    }
                }
            }

            if (!$foundPort) {
                $foundAll = false;
                break;
            }
        }

        if ($foundAll) {
            return $freePorts;
        }
    }

    return [];
}

function pteroSyncfindFreePortsForAllVariablesOnIP($ports, $variables, $_SERVER_IP)
{
    $freePorts = [];
    $foundAll = true;

    $allocatedPorts = []; // Store allocated ports

    foreach ($variables as $var => $range) {
        list($start, $end) = explode('-', $range);
        $foundPort = false;

        foreach ($ports as $port) {
            if (!isset($allocatedPorts[$port['id']])) {
                if ($port['port'] >= $start && $port['port'] <= $end) {
                    $port['ip'] = $_SERVER_IP;
                    $freePorts[$var] = $port;
                    $allocatedPorts[$port['id']] = $port['port']; // Store allocated port to prevent reuse
                    $foundPort = true;
                    break;
                }
            }


        }

        if (!$foundPort) {
            $foundAll = false;
            break;
        }
    }

    if ($foundAll) {
        return $freePorts;
    } else {
        return [];
    }
}

function pteroSyncSetServerPortVariables(&$variables, $serverPort, $ips, $isRange = false)
{
    $serverPortValue = $isRange ? $serverPort : $serverPort . '-' . $serverPort;
    $serverPortArray = ['SERVER_PORT' => $serverPortValue];
    $serverPortOffsetArray = [];
    if (isset($variables['SERVER_PORT_OFFSET'])) {
        unset($variables['SERVER_PORT_OFFSET']);
        if (!$isRange) {
            $offset = $serverPort + PteroSyncInstance::get()->server_port_offset;
            $serverPortOffsetArray = ['SERVER_PORT_OFFSET' => $offset . '-' . $offset];
        }
    }
    $variables = array_merge($serverPortArray, $serverPortOffsetArray, $variables);
    return pteroSyncfindFreePortsForVariables($ips, $variables);
}

function pteroSyncfindPorts($ports, $_SERVER_PORT, $_SERVER_IP, $variables, $ips)
{
    //check if we need server port offset
    //if so we add it here
    if (PteroSyncInstance::get()->server_port_offset > 0) {
        $offset = $_SERVER_PORT + PteroSyncInstance::get()->server_port_offset;
        $variables = array_merge(['SERVER_PORT_OFFSET' => $offset . '-' . $offset], $variables);
    }
    //first we are checking for possible ips for the given IP.
    $foundPorts = pteroSyncfindFreePortsForAllVariablesOnIP($ips[$_SERVER_IP], $variables, $_SERVER_IP);
    //if we can't find that we are trying to find ip with the same port that is given by pterodactyl panel.
    if (!$foundPorts) {
        $foundPorts = pteroSyncSetServerPortVariables($variables, $_SERVER_PORT, $ips);
    }
    //if not we are trying to find any ip in the server port range.
    if (!$foundPorts) {
        $foundPorts = pteroSyncSetServerPortVariables($variables, $ports['SERVER_PORT'], $ips, true);
    }

    PteroSyncInstance::get()->addFileLog([
        'foundedPorts' => json_encode($foundPorts),
        'variables' => json_encode($variables),
        'ports' => json_encode($ports),
        'ips' => json_encode($ips),
        'server_port' => $_SERVER_PORT,
        'server_ip' => $_SERVER_IP,
        'server_ip_ports' => json_encode($ips[$_SERVER_IP] ?? [])
    ], 'Founding ports finish');
    return $foundPorts;
}

function pteroSyncGetCustomFiledId($params)
{
    $customFieldExists = Capsule::table('tblcustomfields')
        ->where('relid', $params['packageid'])
        ->where('fieldname', 'UUID (Server ID)')
        ->where('type', 'product')
        ->first();
    if ($customFieldExists) {
        $customFieldId = $customFieldExists->id;
    }
    if (!$customFieldExists) {
        Capsule::table('tblcustomfields')->insert([
            'type' => 'product',
            'relid' => $params['packageid'],
            'fieldname' => 'UUID (Server ID)',
            'fieldtype' => 'text',
            'adminonly' => 'on'
        ]);
        $customFieldId = Capsule::getPdo()->lastInsertId();
    }
    return $customFieldId;
}

function pteroSyncUpdateCustomFiled($params, $customFieldId, $serverId)
{
    Capsule::table('tblcustomfieldsvalues')
        ->updateOrInsert(
            ['fieldid' => $customFieldId, 'relid' => $params['serviceid']],
            ['value' => $serverId]
        );
}

function pteroSyncReturnJson($data, $code = 200)
{
    header(sprintf('HTTP/%s %s', '1.1', $code), true, $code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function pteroSyncreturnJsonMessage($message, $code = 200)
{
    global $_LANG;
    pteroSyncReturnJson([
        'message' => $_LANG[$message] ?? $message
    ], $code);
}

function pteroSyncStartServer($params, $serverState, $serverId)
{
    if ($serverState == 'running' || $serverState == 'starting') {
        pteroSyncreturnJsonMessage('SERVER_RESTARTED', 200);
    }
    $endpoint = 'servers/' . $serverId . '/power';
    $power = pteroSyncClientApi($params, $endpoint, [
        'signal' => 'start'
    ], 'POST');

    if ($power['status_code'] == 204) {
        pteroSyncReturnJson([
            'state' => 'starting'
        ], 200);
    }
    pteroSyncreturnJsonMessage('SERVER_COULD_NOT_START', 400);
    die();
}

function pteroSyncRestartServer($params, $serverState, $serverId)
{
    if ($serverState == 'starting') {
        pteroSyncreturnJsonMessage('SERVER_RESTARTED', 200);
    }
    $endpoint = 'servers/' . $serverId . '/power';
    $power = pteroSyncClientApi($params, $endpoint, [
        'signal' => 'restart'
    ], 'POST');
    if ($power['status_code'] == 204) {
        pteroSyncReturnJson([
            'state' => 'starting'
        ], 200);
    }
    pteroSyncreturnJsonMessage('SERVER_COULD_NOT_RESTART', 400);
    die();
}

function pteroSyncStopServer($params, $serverState, $serverId)
{
    if ($serverState == 'offline' || $serverState == 'stopping') {
        pteroSyncreturnJsonMessage('SERVER_STOPPED', 200);
    }
    $endpoint = 'servers/' . $serverId . '/power';
    $power = pteroSyncClientApi($params, $endpoint, [
        'signal' => 'stop'
    ], 'POST');
    if ($power['status_code'] == 204) {
        pteroSyncReturnJson([
            'state' => 'stopping'
        ], 200);
    }
    pteroSyncreturnJsonMessage('SERVER_COULD_NOT_STOP', 400);
    die();
}

function pteroSyncKillServer($params, $serverState, $serverId)
{
    if ($serverState == 'offline' || $serverState == 'stopping') {
        pteroSyncreturnJsonMessage('SERVER_STOPPED', 200);
    }
    $endpoint = 'servers/' . $serverId . '/power';
    $power = pteroSyncClientApi($params, $endpoint, [
        'signal' => 'kill'
    ], 'POST');
    if ($power['status_code'] == 204) {
        pteroSyncReturnJson([
            'state' => 'stopping'
        ], 200);
    }
    pteroSyncreturnJsonMessage('SERVER_COULD_NOT_STOP', 400);
    die();
}

function pteroSyncServerState($params, $serverState, $serverId)
{
    pteroSyncReturnJson([
        'state' => $serverState
    ], 200);
}

function pteroSyncGenerateServerStatusArray($server, $hide_server_status)
{
    if (!PteroSyncInstance::get()->show_server_information || $hide_server_status === "on") {
        return [false, false, false];
    }

    $address = '';
    $environment = $server['container']['environment'];
    $useQueryPort = isset($environment['QUERY_PORT']);
    if ($useQueryPort) {
        $port = $environment['QUERY_PORT'];
    }
    $allocations = $server['relationships']['allocations']['data'];
    foreach ($allocations as $allocation) {
        if ($allocation['attributes']['id'] == $server['allocation']) {
            $address = $allocation['attributes']['ip'];
            if (!$useQueryPort) {
                $port = $allocation['attributes']['port'];
            }
            break;
        }
    }
    $nestName = strtolower($server['relationships']['nest']['attributes']['name']);
    $nestName = explode(' ', $nestName);
    $game = $nestName[0] ?? false;
    if (isset($nestName[1]) && $nestName[1] != 'servers') {
        $game .= ' ' . $nestName[1];
    }
    $gameEngine = match ($game) {
        'minecraft' => 'minecraft',
        'samp' => 'samp',
        'mta' => 'mta',
        'fivem' => 'fivem',
        default => 'source'
    };
    return [$gameEngine, $address, $port];
}