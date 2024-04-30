<?php

use Ext\Models\Customer;
use Ext\Models\SettingGroup;

// Global variables
$GLOBALS['my_time_zone'] = loadEnv('MY_TIME_ZONE', 'Europe/Oslo');
$GLOBALS['db_time_zone'] = loadEnv('DB_TIME_ZONE', 'UTC');
$GLOBALS['db_time_format'] = loadEnv('DB_TIME_FORMAT', 'Y-m-d H:i:s');

$GLOBALS['test_customer_id'] = 1552;
$GLOBALS['test_passed'] = '{dgreen}';
$GLOBALS['test_failed'] = '{dred}';

$GLOBALS['menu'] = '{dyellow}';
$GLOBALS['title'] = '{lblack}';
$GLOBALS['item'] = '{lblack}';
$GLOBALS['model'] = '{dgreen}';
$GLOBALS['id'] = '{lblack}';
$GLOBALS['prompt'] = '{dcyan}';
$GLOBALS['get'] = '{lyellow}';
$GLOBALS['list'] = '{dyellow}';
$GLOBALS['warn'] = '{lred}';
$GLOBALS['error'] = '{dred}';
$GLOBALS['success'] = '{dgreen}';
$GLOBALS['exists'] = '{dyellow}';
$GLOBALS['fail'] = '{dred}';
$GLOBALS['done'] = '{dgreen}';
$GLOBALS['bold'] = '{lyellow}';
$GLOBALS['column'] = '{dyellow}';
$GLOBALS['line'] = '{dyellow}';

$GLOBALS['branch_colors'] = ['{dyellow}', '{dgreen}', '{dcyan}', '{dmagenta}', '{dred}'];
$GLOBALS['input_colors'] = ['{lyellow}', '{lgreen}', '{lcyan}', '{lmagenta}', '{lred}'];

function loadEnv(string $key, string $default = null): ?string
{
    static $dotenv;

    if (!$dotenv) {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);

        $dotenv->safeLoad();
    }

    return getenv(strtoupper($key)) ? getenv(strtoupper($key)) : $default;
}

function getScreenWidth(): int
{
    $columns = defined('COLUMNS') ? (int)COLUMNS : (int)@getenv('COLUMNS');
    if (empty($columns)) {
        $process = proc_open('tput cols', [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        $columns = (int)stream_get_contents($pipes[1]);
        proc_close($process);
    }

    return !empty($columns) ? $columns: 90;
}

/**
 * Appends one slash at the end, and removes any extra slashes
 * https://stackoverflow.com/a/9339669/812973
 *
 * @return string $path with the slash appended
 */
function addTrailingSlash($path): string
{
    return rtrim($path, '/') . '/';
}

function getApiVersion(): ?string
{
        return eaw()
            ->request('GET', '/status', null, null, null, [ 'raw' => true ])
            ->getHeader('X-Version')[0]
            ?? null;
}

function startScript(string $title, string $filename, string $prefix = 'Running Script: '): void
{
    $GLOBALS['log_file_name'] = $filename;
    $GLOBALS['log_file'] = fopen(getcwd().'\logs\\'.$GLOBALS['log_file_name'], "w") ?: null;
    $GLOBALS['log_file_indent'] = 1;

    $me = eaw()->read('/me')['user'];

    addToLog(
        "Script [$title] started by " . $me['id'] . ': ' . $me['name'],
        $GLOBALS['log_file_indent']);

    echo str_repeat('-', mb_strlen($prefix) + mb_strlen($title)) . PHP_EOL;
    logg()->info("{lblack}$prefix{lgreen}$title\n");
    echo str_repeat('-', mb_strlen($prefix) + mb_strlen($title)) . PHP_EOL . PHP_EOL;
}

function endScript(bool $withoutAsking = false): void
{
    $stream = $GLOBALS['log_file'];

    if (!is_null($stream))
    {
        fclose($stream);

        inform(0, $GLOBALS['item']."Log saved to [".$GLOBALS['bold'].$GLOBALS['log_file_name'].$GLOBALS['item']."]", true);

        $GLOBALS['log_file'] = null;
        $GLOBALS['log_file_name'] = null;
    }

    if ($withoutAsking || isYes(0, 'Exit?', 'y', false, true)) exit;
}

// Add message to Log File
function addToLog(string $message, int $indent = 1): void
{
    $tab = "\t";
    $stream = $GLOBALS['log_file'];
    if (!is_null($stream)) fwrite($stream,
        (new \DateTime('now',
            new \DateTimeZone('Europe/Oslo')))->format('Y-m-d H:i:s') . str_repeat($tab, $indent) . $message . PHP_EOL);
}

function logg(string $name = null): \Monolog\Logger|\Psr\Log\LoggerInterface
{
    return \Ext\Logger::getInstance()->getLogger($name);
}

/**
 * For getting SheetIDs stored in the .env file
 * @return array|false|string|null
 */
function sheet(string $variable, string $default = null)
{
    return loadEnv($variable, $default);
}

/**
 * This will execute $cmd in the background (no cmd window) without PHP waiting for it to finish, on both Windows and Unix.
 * @param $cmd
 * @param $output
 * @param $root
 * @return int|null
 */
function execInBackground($cmd, $output, $root): ?int
{
    // Get Operating System
    $OperatingSystem = php_uname('s');

    if ($OperatingSystem == "Windows NT")
    {
        $spec = array (0 => array("pipe", "r"), 1 => array("pipe", "w"));

        if (is_resource($process = proc_open("start /b ".$cmd, $spec, $pipes, $root, null)))
        {
            // Get Parent Process ID
            $pid = proc_get_status($process)['pid'];
        }
        else
        {
            echo("Failed to execute!");
            exit();
        }

        // Get PID Tree
        $output = array_filter(explode(" ", shell_exec("wmic process get parentprocessid,processid | find \"$pid\"")));
        array_pop($output);

        // Return Process ID
        return end($output);
    }
    else if ($OperatingSystem == "Linux")
    {
        $spec = array (0 => array("pipe", "r"), 1 => array("pipe", "w"));

        if (is_resource($process = proc_open("nohup " . $cmd, $spec, $pipes, $root, null)))
        {
            // Get Parent Process ID
            $pid = proc_get_status($process)['pid'];

            // Return Process ID
            return $pid + 1;
        }
    }
    return null;
}

function killProcess($pid): void
{
    // Get Operating System
    $OperatingSystem = php_uname('s');

    if ($OperatingSystem == "Windows NT") {
        exec("taskkill /pid $pid /F");
    }
    else if ($OperatingSystem == "Linux") {
        exec("kill -9 $pid");
    }
}

/**
 * Returns an authorized API client.
 * @return Google\Client the authorized client object
 * @throws \Google\Exception
 */
function getGoogleClient(): ?\Google\Client
{
    $pid = null;
    $configPath = addTrailingSlash(getcwd());
    $secretPath = $configPath.'.secret.json';
    $credentialsPath = $configPath . '.auth.json';

    if (!file_exists($secretPath)) die("File not found: [$secretPath]\n");

    $client = new Google\Client();

    $client->setApplicationName('php-eaw-client-scripts');
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
    $client->setAuthConfig($secretPath);
    $client->setAccessType('offline');

    // We have a stored credentials file, try using the data from there first
    if (file_exists($credentialsPath))
    {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    }
    else // No stored credentials found, we'll need to request them with OAuth
    {
        // Start Web Server
        $host = 'localhost';
        $port = '8000';

        // Command that starts the built-in web server
        $command = sprintf('php -S %s:%d -t %s > nul 2>&1 & echo $!', $host, $port, __DIR__);

        // Execute the command and store the process ID
        $output = array();
        $pid = execInBackground($command, $output, __DIR__);
        echo sprintf('%s - Web server started on %s:%d with PID %d', date('r'), $host, $port, $pid) . PHP_EOL;

        // Request authorization from the user
        $authUrl = $client->createAuthUrl();

        // User needs to authenticate
        logg()->info($GLOBALS['warn'] . "Google Authentication required.\nOpen the following link in your browser:\n" . $GLOBALS['bold'] . "$authUrl\n");
        $authCode = prompt('Enter verification code:', 'code');

        // Fetch Access Token based on the verification code
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
    }

    // Provide client with API access token
    try {
        $client->setAccessToken($accessToken);
        if (!is_null($pid)) killProcess($pid);
    } catch (Exception $e) {
        logg()->info($GLOBALS['fail']."Google Authentication Failed\n");
        if (!is_null($pid)) killProcess($pid);
        return null;
    }

    // Create credentials.json if it doesn't already exist (first run)
    if (!file_exists(dirname($credentialsPath))) {
        mkdir(dirname($credentialsPath), 0700, true);
    }

    // Save the $accessToken object to the credentials.json file for re-use
    file_put_contents($credentialsPath, json_encode($accessToken));

    // If the $accessToken is expired then we'll need to refresh it
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }

    return $client;
}

// ---------------------------------------------------------------------------------------------------------------------

// Show Menu
function showMenu(string $title, array $options): string
{
    // What to update?
    logg()->info($GLOBALS['menu'].$title.PHP_EOL);

    $prefix = ' ►';
    $text = '';

    foreach ($options as $key => $value) {
        $text .= "$prefix $key = $value\n";
    }
    $text = rtrim($text);

    $response = promptWithArray(array_keys($options), $text, '#',
        function ($keys, $input)
        {
            if (in_array($input, $keys)) return $input;
            throw new Exception("Invalid response");
        }, true // Otherwise, '0' will not work to exit
    ); echo PHP_EOL;

    return $response;
}

function menu(string $title, array $options, string $get = '#', int $indent = 0, bool $first = true): string
{
    // Space
    if (!$first) {
        $trail = '';
        for ($i = 0; $i < $indent + 1; $i++) {
            $trail .= $GLOBALS['branch_colors'][$i] . ' │';
        }
        logg()->info($trail . PHP_EOL);
    }

    // Title
    $trail = '';
    for ($i = 0; $i < $indent; $i++) {
        $trail .= $GLOBALS['branch_colors'][$i] . ' │';
    }
    if (!$first) {
        $trail .= $GLOBALS['branch_colors'][$indent] . ' ├─« ' . $title . PHP_EOL;
    } else {
        $trail .= $GLOBALS['branch_colors'][$indent] . ' ┌─« ' . $title . PHP_EOL;
    }
    logg()->info($trail);

    // Items
    $itemPrefix = '';
    for ($i = 0; $i < $indent + 1; $i++) {
        $itemPrefix .= $GLOBALS['branch_colors'][$i] . ' │';
    }
    $itemPrefix .= $GLOBALS['item'];

    // Text
    $text = '';
    foreach ($options as $key => $value) {
        $text .= "$itemPrefix $key = $value\n";
    }
    $text = rtrim($text);

    // Input
    $getPrefix = '';
    for ($i = 0; $i < $indent; $i++) {
        $getPrefix .= $GLOBALS['branch_colors'][$i] . ' │';
    }
    $getPrefix .= $GLOBALS['branch_colors'][$indent] . ' ├ ' . $GLOBALS['input_colors'][$indent];

    // Return
    return promptWithArray(array_keys($options), $text, $getPrefix.$get,
        function ($keys, $input)
        {
            if (in_array($input, $keys)) return $input;
            throw new Exception("Invalid response");
        }
    );
}

function choice(int $indent, ?string $text, string $get, bool $moreExpected = false, bool $skipLine = true, bool $allowEmptyInput = false, string $returnOnEmptyInput = null): ?string
{
    // Space
    if ($skipLine) {
        $trail = '';
        for ($i = 0; $i < $indent + 1; $i++) {
            $trail .= $GLOBALS['branch_colors'][$i] . ' │';
        }
        logg()->info($trail . PHP_EOL);
    }

    // Text
    $textPrefix = '';
    for ($i = 0; $i < $indent + 1; $i++) {
        $textPrefix .= $GLOBALS['branch_colors'][$i] . ' │';
    }
    $textPrefix .= ' ' . $GLOBALS['branch_colors'][$indent];

    // Input
    $getPrefix = '';
    for ($i = 0; $i < $indent; $i++) {
        $getPrefix .= $GLOBALS['branch_colors'][$i] . ' │';
    }
    $getPrefix .= $moreExpected ? $GLOBALS['branch_colors'][$indent] . ' ├ ' : $GLOBALS['branch_colors'][$indent] . ' └ ';

    // Prompt
    $promptGet = $allowEmptyInput ? '{lblack}' : $GLOBALS['input_colors'][$indent];
    $return = is_null($text)
        ? prompt(null, $getPrefix.$promptGet.$get, null, $allowEmptyInput)
        : prompt($textPrefix.$text, $getPrefix.$promptGet.$get, null, $allowEmptyInput);

    if ($return == null && $allowEmptyInput) return $returnOnEmptyInput;

    return $return;
}

function indent(int $indent): void
{
    $trail = '';
    for ($i = 0; $i < $indent + 1; $i++) {
        $trail .= $GLOBALS['branch_colors'][$i] . ' │';
    }
    logg()->info($trail . PHP_EOL);
}

function inform(int $indent, string $text, bool $textPreceding = false, bool $moreExpected = true, bool $skipLine = false, string $startPoint = '─« ', bool $newLine = true): void
{
    // Space
    if ($skipLine) {
        $trail = '';

        if (!$textPreceding) {
            for ($i = 0; $i < $indent; $i++) {
                $trail .= $GLOBALS['branch_colors'][$i] . ' │';
            }
            $trail .= '  ';
        } else {
            for ($i = 0; $i < $indent + 1; $i++) {
                $trail .= $GLOBALS['branch_colors'][$i] . ' │';
            }
        }
        logg()->info($trail . PHP_EOL);
    }

    // Print result
    if ($textPreceding && $moreExpected) {
        $char = '├';
    } else if ($textPreceding && !$moreExpected) {
        $char = '└';
    } else if (!$textPreceding && $moreExpected) {
        $char = '┌';
    } else { // both $textPreceding and $moreExpected == false
        $char = '';
    }
    $result = ' ';
    for ($i = 0; $i < $indent; $i++) {
        $result .= $GLOBALS['branch_colors'][$i] . '│ ';
    }
    logg()->info($result . $GLOBALS['branch_colors'][$indent] . $char . $startPoint . $text);

    // New Line
    if ($newLine) echo PHP_EOL;
}

function feedback(int $indent, string $text, bool $moreExpected = false, bool $skipLine = true, string $endPoint = '─» ', bool $newLine = true): void
{
    // Space
    if ($skipLine) {
        $trail = '';
        for ($i = 0; $i < $indent + 1; $i++) {
            $trail .= $GLOBALS['branch_colors'][$i] . ' │';
        }
        logg()->info($trail . PHP_EOL);
    }

    // Print result
    if ($moreExpected) {
        $char = '├';
    } else {
        $char = '└';
    }
    $result = ' ';
    for ($i = 0; $i < $indent; $i++) {
        $result .= $GLOBALS['branch_colors'][$i] . '│ ';
    }
    logg()->info($result . $GLOBALS['branch_colors'][$indent] . $char . $endPoint . $text);

    // New Line
    if ($newLine) echo PHP_EOL;
}

// Display Items in Array
function listItems(array $list, string $owner, string $description, string $nameField = 'name', string $idField = 'id'): array
{
    $itemIds = [];

    logg()->info("{dyellow}$owner: {lblack}$description\n");

    // List all items found
    $numItems = count($list);
    $i = 0;
    foreach ($list as $item)
    {
        if (++$i !== $numItems) logg()->info("{dyellow} ├─» "); else logg()->info("{dyellow} └─» ");
        logg()->info('{dgreen}'.$item->{$nameField}.' '."{lblack}(".$item->{$idField}.")\n");
        $itemIds[] = $item->{$idField};
    }
    echo PHP_EOL;

    return $itemIds;
}

// Prompts for input and returns a valid Model from ID -----------------------------------------------------------------
function getCustomer(string $msg = 'Customer ID:', string $get = 'id'): Customer
{
    $customer = prompt($msg, $get, function ($input) { return Customer::get($input); });
    addToLog("$msg $customer->id found", $GLOBALS['log_file_indent']);
    return $customer;
}
function getSettingGroup(string $prompt = 'Enter SettingGroup ID:', string $get = 'id'): SettingGroup
{
    return prompt($prompt, $get, function ($input) { return SettingGroup::get($input); });
}

// Prompts for input and uses a callback function to validate input
function prompt(?string $msg, string $get = '', callable $cb = null, bool $allow_empty = false)
{
    if (!is_null($msg)) logg()->info($GLOBALS['prompt'].$msg.PHP_EOL);

    do {
        do {
            logg()->info($GLOBALS['get'].$get.'{reset}'.'> ');
            $input = readline();
        } while (!$allow_empty && empty($input));

        if ($cb) {
            try {
                return call_user_func($cb, $input);
            } catch (Exception $exception) {
                logg()->info($GLOBALS['error'].preg_replace("/\r|\n/", "", $exception->getMessage()).PHP_EOL);
                continue;
            }
        }
        break;
    } while (true);

    if (!$allow_empty) return $input;

    switch ($input) {
        case '':
            return '';
        case 'null':
            return null;
        default:
            return $input;
    }
}

function periodSequence(
    string $localFrom,
    string $localTo,
    string $timeZone = 'Europe/Oslo',
    array $days = [0,1,2,3,4,5,6], // 0=Sunday, 1=Monday, etc.
    array $hours = ['from' => '00:00', 'to' => '24:00'], bool $chart = false): \League\Period\Sequence
{
    $includeMonths = $include['months'] ?? [1,2,3,4,5,6,7,8,9,10,11,12];
    $includeDays = $include['days'] ?? [0,1,2,3,4,5,6]; // 0=Sunday, 1=Monday, etc.
    $includeHours = $include['hours'] ?? ['from' => '00:00', 'to' => '24:00'];

    $requestedPeriod = \Carbon\CarbonPeriod::create(\Carbon\Carbon::parse($localFrom, $timeZone), \Carbon\Carbon::parse($localTo, $timeZone));

    $sequence = new \League\Period\Sequence();

    foreach ($requestedPeriod as $date) {
        if ($date->format('Y-m-d H:i:s') == $requestedPeriod->getEndDate()->format('Y-m-d H:i:s')) continue;

        if (in_array($date->dayOfWeek, $days))
        {
            $dayPeriod = \League\Period\Period::fromDatepoint(
                \Carbon\Carbon::parse($date->format('Y-m-d') . ' ' . $hours['from'], $timeZone),
                \Carbon\Carbon::parse($date->format('Y-m-d') . ' ' . $hours['to'], $timeZone)
            );

            $sequence->push($dayPeriod);
        }
    }

    if ($chart)
    {
        $dataSet = new \League\Period\Chart\Dataset();
        $dataSet->append('periodSequence', $sequence);
        $config = \League\Period\Chart\GanttChartConfig::createFromRainbow()->withWidth(getScreenWidth() - ($dataSet->labelMaxLength() + 2));
        (new \League\Period\Chart\GanttChart($config))->stroke($dataSet);
    }

    return $sequence;
}

function periodHolidays(
    string $localFrom,
    string $localTo,
    array $holidays,
    array $hours = ['from' => '00:00', 'to' => '24:00'],
    string $timeZone = 'Europe/Oslo', bool $chart = false): \League\Period\Sequence
{
    $sequence = new \League\Period\Sequence();

    foreach ($holidays as $holiday) {
        $dayPeriod = \League\Period\Period::fromDatepoint(
            \Carbon\Carbon::parse(substr($holiday['date'], 0, 10) . ' ' . $hours['from'], $timeZone),
            \Carbon\Carbon::parse(substr($holiday['date'], 0, 10) . ' ' . $hours['to'], $timeZone)
        );

        $sequence->push($dayPeriod);
    }

    if ($chart)
    {
        $dataSet = new \League\Period\Chart\Dataset();
        $dataSet->append('periodHolidays', $sequence);
        $config = \League\Period\Chart\GanttChartConfig::createFromRainbow()->withWidth(getScreenWidth() - ($dataSet->labelMaxLength() + 2));
        (new \League\Period\Chart\GanttChart($config))->stroke($dataSet);
    }

    return $sequence;
}

// Prompts for input and uses a callback function with ID to validate input
function promptWithId(int $id, $msg, string $get, callable $cb = null) {
    logg()->info('{lblack}'.$msg.PHP_EOL);

    do {
        do {
            logg()->info($GLOBALS['get'].$get.'{reset}'.'> ');
            $input = readline();
        } while (empty($input));

        if ($cb) {
            try {
                return call_user_func($cb, $input, $id);
            } catch (Exception $exception) {
                logg()->info('{dred}'.$exception->getMessage().PHP_EOL);
                continue;
            }
        }
        break;
    } while (true);

    return $input;
}

// Prompts for input and uses a callback function with Array to validate input
function promptWithArray(array $array, $msg, string $get, callable $cb = null, bool $allow_empty = false) {
    logg()->info('{lblack}'.$msg.PHP_EOL);

    do {
        do {
            logg()->info($GLOBALS['get'].$get.'{reset}'.'> ');
            $input = readline();
        } while (!$allow_empty && empty($input));

        if ($cb) {
            try {
                return call_user_func($cb, $array, $input);
            } catch (Exception $exception) {
                logg()->info('{dred}'.$exception->getMessage().PHP_EOL);
                continue;
            }
        }
        break;
    } while (true);

    if (!$allow_empty) return $input;

    switch ($input) {
        case '':
            return '';
        case 'null':
            return null;
        default:
            return $input;
    }
}

// Returns True if To-date is later than Now
function isActive(?string $to): bool
{
    if (!is_null($to)) {
        $to_date = new DateTime($to);
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        if ($to_date > $now)
        {
            return false;
        }
    }
    return true;
}

// TODO: This does not work properly. If "default" is 'y', but you type 'n', it will return true
// Prompts for Yes/No response and returns True if input validates to Yes
function isYes(int $indent, string $prompt = 'Continue?', ?string $default = 'y', bool $moreExpected = true, bool $skipLine = false): bool
{
    // y/n-string
    $getString = is_null($default)
        ? $GLOBALS['input_colors'][$indent] . 'y{reset}/' . $GLOBALS['input_colors'][$indent] . 'n'
        : ($default == 'y'
            ? $GLOBALS['input_colors'][$indent] . 'y{reset}/{lblack}n'
            : '{lblack}y{reset}/' . $GLOBALS['input_colors'][$indent] . 'n');

    // Space
    if ($skipLine) {
        $trail = '';
        for ($i = 0; $i < $indent + 1; $i++) {
            $trail .= $GLOBALS['branch_colors'][$i] . ' │';
        }
        logg()->info($trail . PHP_EOL);
    }

    // Prompt
    $promptPrefix = '';
    for ($i = 0; $i < $indent + 1; $i++) {
        $promptPrefix .= $GLOBALS['branch_colors'][$i] . ' │';
    }
    $promptPrefix .= ' ' . $GLOBALS['branch_colors'][$indent];

    // Input
    if ($moreExpected) {
        $char = '├';
    } else {
        $char = '└';
    }
    $inputPrefix = '';
    for ($i = 0; $i < $indent; $i++) {
        $inputPrefix .= $GLOBALS['branch_colors'][$i] . ' │';
    }
    $inputPrefix .= ' ' . $GLOBALS['branch_colors'][$indent] . $char . ' ';

    // Return
    return prompt(
        $promptPrefix . $GLOBALS['branch_colors'][$indent] . $prompt,
        $inputPrefix . $getString,
        function (string $input) use ($default, $promptPrefix) {
            switch ($input === '' ? $default : $input) {
                case 'y': return true;
                case 'n': return false;
                default: throw new Exception($promptPrefix . $GLOBALS['error'] . 'Invalid input');
            }
        },
        $default !== null
    );
}

// ---------------------------------------------------------------------------------------------------------------------

// Returns a DateTime. If response is 1, null is returned
function makeActiveFrom(string $prompt = 'Make active from? (1=Original, 2=Now, 3=Specify)', string $get = '1/2/3', array $validInput = array('1', '2', '3'))
{
    // Ask when the PTLs should be active from
    $active_from = null;
    $active_from_response = promptWithArray($validInput, $prompt, $get, function ($valid_input, $input) {
        if (in_array($input, $valid_input)) return $input;
        throw new Exception("Invalid response"); });

    switch ($active_from_response) {
        case '2': // Now
            $active_from = (new \DateTime("now", new \DateTimeZone("Europe/Oslo")))->format('Y-m-d H:i:s');
            break;
        case '3': // Specific Date
            $active_from = prompt('Specify DateTime', 'YYYY-MM-DD hh:mm:ss', function ($input) {
                $date = new DateTime($input, new \DateTimeZone("Europe/Oslo"));
                //$date->setTimezone(new DateTimeZone("UTC"));
                return $date->format('Y-m-d H:i:s'); });
            break;
        default:
            break;
    }

    return $active_from;
}


function createChecklistItems(int $customerId, $source, $destination, $parent_id) {

    // Traverse each Item
    foreach ($source['items'] as $item) {

        // Try creating new Item
        try
        {
            eaw()->create('/customers/' . $customerId . '/checklists/' . $destination['id'] . '/items', null, array_filter([
                'parent_id' => $parent_id,
                'title' => $item['title'],
                'description' => $item['description'],
                'type' => $item['type'],
                'weight' => $item['weight'],
                'options' => $item['options']
            ], static function($var) {return $var !== null;} ));
            logg()->info('{dgreen}'.'.');
        } catch (Exception $e) {
            logg()->info('{dred}'.'|');
            echo $e->getMessage();
        }
    }

    // Traverse each Category
    foreach ($source['categories'] as $category) {

        // Try creating new Category
        $new_category = null;
        try {
            $new_category = eaw()->create('/customers/' . $customerId . '/checklists/' . $destination['id'] . '/categories', null, [
                'parent_id' => $parent_id,
                'title' => $category['title'],
                'weight' => $category['weight']]);
            logg()->info('{dgreen}'.':');
        } catch (Exception $e) {
            logg()->info('{dred}'.'|');
            echo $e->getMessage();
        }

        // Create Checklist Items with the new category as parent
        createChecklistItems($customerId, $category, $destination, $new_category['id']);
    }
}

function arrays_are_equal($array1, $array2, bool $type_specific = false): bool
{
    array_multisort($array1);
    array_multisort($array2);

    return $type_specific ? serialize($array1) == serialize($array2) : $array1 == $array2;
}

function array_contains(array $needles, array $haystack, bool $match_all = true): bool
{
    if ($match_all) {
        foreach ($needles as $item)
        {
            if (!in_array($item, $haystack))
            {
                return false;
            }
        }
        return true;
    } else {
        foreach ($needles as $item)
        {
            if (in_array($item, $haystack))
            {
                return true;
            }
        }
        return false;
    }
}

function bdBeforeOrEqual(string $businessDateString, string $utcCompareWith, string $localTimeZone = 'Europe/Oslo'): bool
{
    $businessDate = date_create($businessDateString, new DateTimeZone($localTimeZone));
    $compareBd = date_create($utcCompareWith, new DateTimeZone($localTimeZone));

    return $businessDate <= $compareBd;
}
function bdBefore(string $businessDateString, string $utcCompareWith, string $localTimeZone = 'Europe/Oslo'): bool
{
    $businessDate = date_create($businessDateString, new DateTimeZone($localTimeZone));
    $compareBd = date_create($utcCompareWith, new DateTimeZone($localTimeZone));

    return $businessDate < $compareBd;
}
function bdWithin(string $businessDateString, ?string $from, ?string $to = null): bool
{
    // Include everything
    if (is_null($from)) return true;

    $dateTime = new DateTime($businessDateString);
    $start = new DateTime($from);

    if (is_null($to)) // Open to-date -- only compare with start
    {
        return $dateTime >= $start;
    }
    else
    {
        $end = new DateTime($to);
        return $dateTime >= $start && $dateTime <= $end;
    }
}
function matchesBusinessDate(string $utcDateTimeString, string $bdCompareWith, string $localTimeZone = 'Europe/Oslo'): bool
{
    $dateTime = date_create($utcDateTimeString, new DateTimeZone($localTimeZone));
    $compareBd = date_create($bdCompareWith, new DateTimeZone($localTimeZone));

    return $dateTime->format('Y-m-d') == $compareBd->format('Y-m-d');
}
function dateTimeWithin(?string $utcDateTimeString, ?string $utcFrom, ?string $utcTo = null, bool $overlapStart = true, bool $overlapEnd = true): bool
{
    // Include everything
    if (is_null($utcDateTimeString) || is_null($utcFrom)) return true;

    $dateTime = new DateTime($utcDateTimeString ?? 'now');
    $start = new DateTime($utcFrom);

    if (is_null($utcTo)) // Open to-date -- only compare with start
    {
        return $dateTime >= $start;
    }
    else
    {
        $end = new DateTime($utcTo);

        $startWithin = $overlapStart ? $dateTime >= $start : $dateTime > $start;
        $endWithin = $overlapEnd ? $dateTime <= $end : $dateTime < $end;

        return $startWithin && $endWithin;
    }
}
function intervalWithin(?string $utcIntervalFrom, ?string $utcIntervalTo, ?string $utcStart, ?string $utcEnd, bool $completelyWithin = true, bool $includeTangent = false): bool
{
    // Include everything
    if (is_null($utcIntervalFrom) ||is_null($utcStart)) return true;

    // Open to-date -- only compare with start
    if (is_null($utcIntervalTo)) return dateTimeWithin($utcIntervalFrom, $utcStart, $utcEnd);

    // Returns true only if both $from and $to are within the provided interval
    if ($completelyWithin) return dateTimeWithin($utcIntervalFrom, $utcStart, $utcEnd, true, true) && dateTimeWithin($utcIntervalTo, $utcStart, $utcEnd, true, true);

    // Returns true if either $from or $to is within the provided interval
    return dateTimeWithin($utcIntervalFrom, $utcStart, $utcEnd, true, false) || dateTimeWithin($utcIntervalTo, $utcStart, $utcEnd, false, true);
}

/**
 * @deprecated Use dbTime instead
 */
function utcDateTimeString(?string $localDateTime, string $local_time_zone = 'Europe/Oslo'): string
{
    $date = new DateTime($localDateTime ?? 'now', new \DateTimeZone($local_time_zone));
    $date->setTimezone(new \DateTimeZone('UTC'));
    return $date->format('Y-m-d H:i:s');
}

/**
 * @throws Exception
 */
function dbTime(?string $myDateTime = 'now'): ?string
{
    if (is_null($myDateTime)) return null;

    return (new \DateTime($myDateTime, new \DateTimeZone($GLOBALS['my_time_zone'])))->setTimezone(new \DateTimeZone($GLOBALS['db_time_zone']))->format($GLOBALS['db_time_format']);
}

function toUtcDateTime(?string $utcDateTimeString): DateTime
{
    $date = new DateTime($utcDateTimeString ?? 'now', new \DateTimeZone("UTC"));
    return $date->setTimezone(new DateTimeZone("UTC"));
}

function localDateTimeString(?string $utcDateTime, string $localTimeZone = 'Europe/Oslo'): string
{
    $date = new DateTime($utcDateTime ?? 'now', new \DateTimeZone("UTC"));
    $date->setTimezone(new DateTimeZone($localTimeZone));
    return $date->format('Y-m-d H:i:s');
}

function localDateTime(?string $utcDateTime, string $localTimeZone = 'Europe/Oslo'): DateTime
{
    $date = new DateTime($utcDateTime ?? 'now', new \DateTimeZone("UTC"));
    $date->setTimezone(new DateTimeZone($localTimeZone));
    return $date;
}

function utcNow(string $format = 'Y-m-d H:i:s'): ?string
{
    try {
        return (new \DateTime('now', new \DateTimeZone('UTC')))->format($format);
    } catch (Exception $e) {
        return null;
    }

}

function toNowOrFuture(?string $localDateTimeString = null, $format = 'Y-m-d H:i:s'): string
{
    // Get "Now" in UTC
    $utcNow = utcNow();

    if (is_null($localDateTimeString) || strtolower($localDateTimeString) == 'now') return $utcNow;//$utcNow->add(new DateInterval('PT1S'))->format($format);

    // Convert local dateTimeString to UTC
    $localDateTimeInUTC = (new \DateTime($localDateTimeString, new \DateTimeZone($GLOBALS['my_time_zone'])))->setTimezone(new \DateTimeZone('UTC'));

//    // TODO: Check if swap date is in the past, if so use 'now' ???
//    if ($localDateTimeInUTC < $utcNow) {
//        echo "ADD ONE SECOND\n";
//        return $utcNow->add(new DateInterval('PT1S'))->format($format);
//    } else {
//        return $localDateTimeInUTC->format($format);
//    }

    return $localDateTimeInUTC->format($format);
}

function localDateString(?string $date_time_in_utc, string $local_time_zone = 'Europe/Oslo'): string
{
    $date = toUtcDateTime($date_time_in_utc);
    $date->setTimezone(new DateTimeZone($local_time_zone));
    return $date->format('Y-m-d');
}

function localTimeString(?string $date_time_in_utc, string $local_time_zone = 'Europe/Oslo'): string
{
    $date = toUtcDateTime($date_time_in_utc);
    $date->setTimezone(new DateTimeZone($local_time_zone));
    return $date->format('H:i:s');
}

function hoursBetween(string $from_string, string $to_string, string $localTimeZone = 'Europe/Oslo'): float // TODO: Super hacky. Use lib to account for DST, etc.
{
    if (strlen($from_string) != strlen($to_string)) return 0;

    if (strlen($from_string) == strlen('YYYY-MM-DD HH:MM:SS'))
    {
        $from_date = substr($from_string, 0, 10);
        $to_date = substr($to_string, 0, 10);

        // Check if date is same
        if ($from_date == $to_date)
        {
            $parts_from = explode(':', substr($from_string, 11));
            $from_seconds = ((int)$parts_from[0] * 3600) + ((int)$parts_from[1] * 60) + (int)$parts_from[2];

            $parts_to = explode(':', substr($to_string, 11));
            $to_seconds = ((int)$parts_to[0] * 3600) + ((int)$parts_to[1] * 60) + (int)$parts_to[2];

            $diff = $to_seconds - $from_seconds;
        }
        else // Date is different
        {
            $days = date_diff(date_create($from_date), date_create($to_date))->days;

            $parts_from = explode(':', substr($from_string, 11));
            $from_seconds = ((int)$parts_from[0] * 3600) + ((int)$parts_from[1] * 60) + (int)$parts_from[2];

            $parts_to = explode(':', substr($to_string, 11));
            $to_seconds = ((int)$parts_to[0] * 3600) + ((int)$parts_to[1] * 60) + (int)$parts_to[2];

            $diff = $to_seconds - $from_seconds + ($days * 86400);
        }

        return $diff / 3600;
    }
    elseif (strlen($from_string) == strlen('HH:MM:SS'))
    {
        $parts_from = explode(':', $from_string);
        $from_seconds = ((int)$parts_from[0] * 3600) + ((int)$parts_from[1] * 60) + (int)$parts_from[2];

        $parts_to = explode(':', $to_string);
        $to_seconds = ((int)$parts_to[0] * 3600) + ((int)$parts_to[1] * 60) + (int)$parts_to[2];

        $diff = $to_seconds - $from_seconds;

        return $diff / 3600;
    }

    return 0;
}

function convertEmployeeWorkedHoursToArray(array $data): array
{
    $values = [];

    foreach ($data as $day => $range)
    {
        foreach ($range as $number => $interval)
        {
            $row = [$day, $number, $interval['start'], $interval['end'], hoursBetween($interval['start'], $interval['end'])];
            $values[] = $row;
        }
    }
    return $values;
}

function convertEmployeeWorkedHoursPerDayToArray(array $data, string $first_day, string $last_day): array
{
    $day_values = [];

    // Loop through all calendar days, creating 0 hours for the days there is no work
    for ($i = 0; date_create(localDateString($first_day))->modify('+'.$i.' days') <= date_create(localDateString($last_day)); $i++)
    {
        $current_day = date_create(localDateString($first_day))->modify('+'.$i.' days')->format('Y-m-d');
        $current_day_sum = 0;

        if (key_exists($current_day, $data))
        {
            $values_for_day = $data[$current_day];

            foreach ($values_for_day as $number => $interval)
            {
                $current_day_sum += hoursBetween($interval['start'], $interval['end']);
            }
        }
        $row = [$current_day_sum];
        $day_values[] = $row;
    }
    return $day_values;
}

function convertEmployeeWorkedHoursPerWeekToArray(array $data, string $first_day, string $last_day): array
{
    $week_values = [];
    $row = [];
    $current_week_sum = 0;

    // Loop through all calendar days, creating 0 hours for the days there is no work
    for ($i = 1; date_create($first_day)->modify('+'.$i.' days') <= date_create($last_day); $i++)
    {
        $current_day = date_create($first_day)->modify('+'.$i.' days')->format('Y-m-d');
        $current_day_sum = 0;

        if (key_exists($current_day, $data))
        {
            $values_for_day = $data[$current_day];

            foreach ($values_for_day as $number => $interval)
            {
                $current_day_sum += hoursBetween($interval['start'], $interval['end']);
            }
        }
        // Add to weekly sum
        $current_week_sum += $current_day_sum;

        // Start of new week?
        if (!fmod($i, 7))
        {
            // Store weekly sum and reset counter
            $row[] = $current_week_sum;
            $current_week_sum = 0;
        }
    }
    $week_values[] = $row;

    return $week_values;
}

function updateSheet(string $sheet_id, string $cell, array $values, $options = ['valueInputOption' => 'RAW']): \Google\Service\Sheets\UpdateValuesResponse
{
    // Get the API client and construct the service object.
    $client = getGoogleClient();
    $service = new Google_Service_Sheets($client);

    // Print Sheet Info
//    $sheetInfo = $service->spreadsheets->get($sheet_id)->getProperties();
//    echo "$sheetInfo->title\n";

    // Update and return results
    return $service->spreadsheets_values->update($sheet_id, $cell, new Google_Service_Sheets_ValueRange(['values' => $values]), $options);
}

function modelList(array $list, string $display, string $key, bool $sort = true): array
{
    $array = [];
    foreach ($list as $item)
    {
        if ($item[$display] ?? false)
        {
            $array[$item[$display]] = $item[$key];
        }
    }

    if ($sort) ksort($array);
    return $array;
}

// Validate input based on a set of valid options. Use default value if input is empty.
// TODO: $returnValue
function select(int $indent, string $message, array $allowed, string $default = null, bool $byIndex = true, string $get = "#", bool $returnValue = false, bool $moreExpected = false, bool $skipLine = false, bool $allowEmptyInput = true, bool $displayValue = false, bool $allowExit = false): ?string
{
    // Space
    if ($skipLine) {
        $trail = '';
        for ($i = 0; $i < $indent + 1; $i++) {
            $trail .= $GLOBALS['branch_colors'][$i] . ' │';
        }
        logg()->info($trail . PHP_EOL);
    }

    // Message
    if ($message != '') {
        $title = ' ';
        for ($i = 0; $i < $indent + 1; $i++) {
            $title .= $GLOBALS['branch_colors'][$i] . '│ ';
        }
        logg()->info($title . $GLOBALS['branch_colors'][$indent] . $message . PHP_EOL);
    }

    // Create an Index Map if Selecting by Index
    $indexMap = [];
    if ($byIndex)
    {
        $index = 1;
        foreach ($allowed as $key => $value) {
            $indexMap[$key] = $index;
            $index++;
        }
    }

    // Prompt Prefix
    $promptPrefix = ' ';
    for ($i = 0; $i < $indent + 1; $i++) {
        $promptPrefix .= $GLOBALS['branch_colors'][$i] . '│ ';
    }

    // Input Prefix
    $inputPrefix = ' ';
    for ($i = 0; $i < $indent; $i++) {
        $inputPrefix .= $GLOBALS['branch_colors'][$i] . '│ ';
    }
    $inputPrefix .= $moreExpected ? $GLOBALS['branch_colors'][$indent] . '├ ' : $GLOBALS['branch_colors'][$indent] . '└ ';

    // Layout Configuration
    $c_text = "{lblack}"; // [ = ]
    $c_input_message = "{dcyan}"; // Select ...
    $c_allowed_option = "{lblack}"; // Allowed option that is not default
    $c_default_option = "{lcyan}";
    $c_index = "{reset}";
    $separator = " | ";
    $indentSpace = "  ";

    // Create the "Allowed Options" string
    $allowed_string = $promptPrefix . $c_allowed_option . "[ ";
    foreach ($allowed as $key => $value)
    {
        if ($byIndex) { $allowed_string .= $c_index . $indexMap[$key] . $c_text . " = "; }

        if (!is_null($default) && $key == $default) {
            $allowed_string .= $c_default_option . $key . $c_allowed_option . $c_text . $separator;
        } else {
            $allowed_string .= $c_allowed_option . $key . $c_text . $separator;
        }
    }
    $allowed_string = mb_substr($allowed_string, 0, mb_strlen($allowed_string) - mb_strlen($separator)) . " ]";

    // Check if length of "Allowed Options" string exceeds the screen width
    if (mb_strlen(preg_replace("/\{[^}]+\}/", '', $allowed_string)) > getScreenWidth())
    {
        $allowed_string = ' ' . str_replace($separator, "\n$promptPrefix$indentSpace", "{" . trim($allowed_string, $c_text."[ ]")) . ' ]';
    }

    // Display Input Message and the "Allowed Options" string
//    logg()->info($promptPrefix.$c_input_message.$message.PHP_EOL);
    logg()->info($allowed_string.PHP_EOL);

    // Validate Input
    do
    {
        $valid = false;

        // Get Input
        $promptGet = (is_null($default) && $allowEmptyInput) ? '{lblack}' : $GLOBALS['input_colors'][$indent];
        logg()->info($inputPrefix.$promptGet.$get.'{reset}'.'> ');
        $input = readline();

        // Return null on empty input if empty input is allowed, and no default value is set
        if (is_null($default) && $allowEmptyInput && !$input) return null;

        // Return 'exit' if input is 'x' and allowExit is true
        if ($allowExit && $input == 'x') return 'exit';

        // Return default value on empty input
        if (!is_null($default) && !$input) {
            return $allowed[$default] ?? $default;
        }

        // Validate depending on Selection by Index or not
        if ($byIndex)
        {
            if (in_array($input, $indexMap)) $valid = true;
        }
        else
        {
            if (key_exists($input, $allowed)) $valid = true;
        }

    } while (!$valid);

    // Return Value instead of Index or Input?
    if ($returnValue) return array_flip($indexMap)[$input];

    // Return either Index or valid Input
    return $byIndex ? $allowed[array_search($input, $indexMap)] : $allowed[$input] ?? $input;
}

function correctDays(?array $days): ?array
{
    if (is_null($days)) return [0,1,2,3,4,5,6];

    $correctDays = [];
    foreach ($days ?? [] as $day) {
        if ($day === 7) {
            $correctDays[] = 0;
        } else {
            $correctDays[] = $day;
        }
    }
    return $correctDays;
}

function columnizeText(array $text, int $offset, int $screen_width)
{
    $color = '{dgreen}';
    $reset = '{reset}';
    $divider = '  |  ';
    $columns = count($text);
    $column_width = floor((($screen_width - $offset) / $columns) - (mb_strlen($divider) * ($columns - 1)));
    $rows = 0;
    $split_array = [];

    foreach ($text as $column => $value) {
        $split_array[$column] = mb_str_split($value, $column_width);
    }

    foreach ($split_array as $value)
    {
        if (count($value) > $rows) $rows = count($value);
    }

    for ($row = 0; $row < $rows; $row++)
    {
        if ($row == 0) {
            $line = '';
        } else {
            $line = str_repeat(" ", $offset);
        }

        for ($col = 0; $col < $columns; $col++)
        {
            $length = mb_strlen($split_array[$col][$row] ?? '');
            $cell = key_exists($row, $split_array[$col]) ? $split_array[$col][$row] : '';

            if ($col < $columns -1) {
                $line = $line . $color . $cell . str_repeat(" ", $column_width - $length) . $reset . $divider;
            } else {
                $line = $line . $color . $cell . "\n";
            }
        }

        logg()->info($line);
    }
}

function gmdate24(int $seconds): string
{
    if ($seconds = 24 * 3600) return '24:00';
    return gmdate('H:i', $seconds);
}

function secondsToHours(int $seconds, bool $returnString = false): float|string
{
    $format = '%2d hours, %02d minutes, %02d seconds';
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;

    return $returnString
        ? sprintf($format, $h, $m, $s)
        : $seconds / 3600;
}

function calculate(\Ext\Models\Employee $employee, string $localFrom,
    string $localTo,
    array $includedDefinitions,
    array $excludedDefinitions,
    array $limits,
    bool $useBusinessDates = true): array
{
    $result = [];
    $includedHours = 0;
    $excludedHours = 0;

    // Get PaidTime Periods
    $paidTimes = $employee->paidTimeSequence($localFrom, $localTo, $useBusinessDates);

    // Calculate All Included Time
    $inclCount = 1;
    foreach ($includedDefinitions as $definition) {
        if ($definition::class !== \Ext\PeriodDefinition::class) {
            $result['incl.' . $inclCount] = 'N/A';
            $inclCount += 1;
            continue;
        }

        // Get Included Periods
        $included = $definition->sequence();

        // Get PaidTime Intersections with Included
        foreach ($paidTimes as $interval) {
            $included->push($interval);
        }

        $includedHours += $included->intersections()->totalTimeDuration() / 3600;
        $result['incl.' . $inclCount] = $included->intersections()->totalTimeDuration() / 3600;

        $inclCount += 1;
    }

    // Calculate All Excluded Time
    $exclCountCount = 1;
    foreach ($excludedDefinitions as $definition) {
        if ($definition::class !== \Ext\PeriodDefinition::class) {
            $result['excl.' . $exclCountCount] = 'N/A';
            $exclCountCount += 1;
            continue;
        }

        // Get Excluded Periods
        $excluded = $definition->sequence();

        // Get PaidTime Intersections with Included and Excluded
        foreach ($paidTimes as $interval) {
            $excluded->push($interval);
        }

        $excludedHours += $excluded->intersections()->totalTimeDuration() / 3600;
        $result['excl.' . $exclCountCount] = $excluded->intersections()->totalTimeDuration() / 3600;

        $exclCountCount += 1;
    }

    $result['result'] = $includedHours - $excludedHours;

    return $result;
}

function actAs(int $userId): array
{
    return eaw()->create('/me/act_as', ['user_id' => $userId]);
}

function actAsMe(): array
{
    return actAs(eaw()->read('/me')['authed_as']);
}

function viewChildren(int $indent, int $parentId): void
{
    $customer = Customer::get($parentId);

    if ($customer) {

        $children = $customer->children();
        $count = 0;
        foreach ($children as $childId)
        {
            // Get Child Customer
            $child = Customer::get($childId);

            // Display
            if ($count == count($children) - 1) {
                feedback($indent, "{lblack}$child->id:\t{dgreen}$child->name", false, false);
            } else {
                feedback($indent, "{lblack}$child->id:\t{dgreen}$child->name", true, false);
            }

            $count++;
        }
    }
}

function getChainStructure(int $settingGroupId): array
{
    $chainStructure = [];
    foreach (SettingGroup::get($settingGroupId)->members() as $member) {
        $customer = Customer::get($member->id);
        $chainStructure[$customer->type][] = [$customer->id => $customer->name];

        if (!$customer->hasParent()) {
            $chainStructure[$member->id] = $customer->descendants();
        }
    }
    return $chainStructure;
}

function newGetChainStructure(int $settingGroupId): array
{
    $chainStructure = [];

    // Loop through all members of the setting group
    foreach (SettingGroup::get($settingGroupId)->members() as $member)
    {
        // Get the Customer object
        $customer = Customer::get($member->id);

        // Grab Root Level Customers
        if (!$customer->hasParent()) {
            $chainStructure[$customer->id] = ['model' => $customer, 'descendants' => $customer->newDescendants()];
        }
    }
    return $chainStructure;
}

function viewChainStructure(array $branch, int $indent, int $depth = 0): void
{
    $trail = '';
    for ($i = 0; $i < $indent; $i++) {
        $trail .= $GLOBALS['branch_colors'][$i] . ' │';
    }
    logg()->info($trail . '   ');

    foreach ($branch as $parentId => $children)
    {
        // Skip if ParentId is a string
        if (is_string($parentId)) continue;

        $customer = Customer::get($parentId);
        logg()->info(str_repeat('   ', $depth) . '{reset}' . $customer->id . ': ' . $GLOBALS['branch_colors'][$i] . "$customer->name ({reset}$customer->type" . $GLOBALS['branch_colors'][$i] . ")\n");

        if ($children) {
            viewChainStructure($children, $indent, $depth + 1);
        } else {
            logg()->info($trail . '   ');
        }
    }
}

function newViewChainStructure(array $branch, int $indent, int $depth = 0, int $current = 0, int $total = 1, bool $prevLvlComplete = false): void
{
    $trail = '';
    for ($i = 0; $i < $indent; $i++) {
        $trail .= $GLOBALS['branch_colors'][$i] . ' │';
    }

    foreach ($branch as $id => $data)
    {
        // Get the Customer model
        $customer = $data['model'];
        $descendants = $data['descendants'];

//        echo "\ncust.: $customer->name\n";
//        echo "depth: $depth\n";
//        echo "count: " . count($descendants) . "\n";
//        echo "currt: $current\n";
//        echo "total: $total\n";
//        echo "prev.: $prevLvlComplete\n";

        logg()->info($trail . '   '); // Append something here

        if ($depth == 0) // Root level
        {
            logg()->info($GLOBALS['input_colors'][$i] . '▪');
        }
        else
        {
            // If there are descendants, and this is not the last one
            if ($descendants && $current < $total) {
                if ($prevLvlComplete) {
                    logg()->info($GLOBALS['input_colors'][$i] . str_repeat('  ', $depth - 1) . str_repeat('│ ', $depth - 2) . '├─◦');
                } else {
                    logg()->info($GLOBALS['input_colors'][$i] . str_repeat('│ ', $depth - 1) . '├─◦');
                }
            }

            // If there are descendants, and this is the last one
            if ($descendants && $current == $total) {
                if ($prevLvlComplete) {
                    logg()->info($GLOBALS['input_colors'][$i] . str_repeat('  ', $depth - 1) . str_repeat('│ ', $depth - 2) . '└─◦');
                } else {
                    logg()->info($GLOBALS['input_colors'][$i] . str_repeat('│ ', $depth - 1) . '└─◦');
                }
            }

            // If there are no descendants, and this is not the last one
            if (!$descendants && $current < $total) {
                if ($prevLvlComplete) {
                    logg()->info($GLOBALS['input_colors'][$i] . str_repeat('  ', $depth - 1) . str_repeat('│ ', $depth - 2) . '├─◦');
                } else {
                    logg()->info($GLOBALS['input_colors'][$i] . str_repeat('│ ', $depth - 1) . '├─◦');
                }
            }

            // If there are no descendants, and this is the last one
            if (!$descendants && $current == $total) {
                if ($prevLvlComplete) {
                    logg()->info($GLOBALS['input_colors'][$i] . str_repeat('  ', $depth - 1) . str_repeat('│ ', $depth - 2) . '└─◦');
                } else {
                    logg()->info($GLOBALS['input_colors'][$i] . str_repeat('│ ', $depth - 1) . '└─◦');
                }
            }
        }
        logg()->info('{reset} ' . $customer->id . ': ' . $GLOBALS['branch_colors'][$i] . "$customer->name ({reset}$customer->type" . $GLOBALS['branch_colors'][$i] . ")\n");

        // Process descendants
        $newCurrent = 0;
        foreach ($descendants as $descendant) {
            $newCurrent++;
            newViewChainStructure($descendant, $indent, $depth + 1, $newCurrent, count($descendants), $current == $total);
        }
    }
}

function combineIdsByParentTypes(array $chain, ?array $parentTypes): array
{
    $combinedIds = [];

    if (is_null($parentTypes)) return $combinedIds;

    foreach ($parentTypes as $parentType) {
        if (isset($chain[$parentType])) {
            $ids = $chain[$parentType];
            foreach ($ids as $item) {
                if (is_array($item)) {
                    foreach ($item as $id => $name) {
                        $combinedIds[$id] = $name;
                    }
                } else {
                    // Extract ID and name, then add to the combined IDs array
                    $combinedIds[$item] = $chain[$item][$item];
                }
            }
        }
    }

    return array_flip($combinedIds);
}

function newCombineIdsByParentTypes(array $branch, ?array $parentTypes): array
{
    $combinedIds = [];

    if (is_null($parentTypes)) return $combinedIds;

    foreach ($branch as $rootId => $data) {
        $customer = $data['model'];

        if (in_array($customer->type, $parentTypes)) {
            $combinedIds[$customer->id] = $customer->name;

            foreach ($data['descendants'] as $descendant) {
                $combinedIds += newCombineIdsByParentTypes($descendant, $parentTypes);
            }
        }
    }

    return $combinedIds;
}

function warn(int $indent, string $text, bool $textPreceding = true, bool $moreExpected = true, bool $skipLine = false, string $startPoint = ' ', bool $newLine = true): void
{
    // Space
    if ($skipLine) {
        $trail = '';

        if (!$textPreceding) {
            for ($i = 0; $i < $indent; $i++) {
                $trail .= $GLOBALS['branch_colors'][$i] . ' │';
            }
            $trail .= '  ';
        } else {
            for ($i = 0; $i < $indent + 1; $i++) {
                $trail .= $GLOBALS['branch_colors'][$i] . ' │';
            }
        }
        logg()->info($trail . PHP_EOL);
    }

    // Print result
    if ($textPreceding && $moreExpected) {
        $char = '├';
    } else if ($textPreceding && !$moreExpected) {
        $char = '└';
    } else if (!$textPreceding && $moreExpected) {
        $char = '┌';
    } else { // both $textPreceding and $moreExpected == false
        $char = '';
    }

    $result = ' ';
    for ($i = 0; $i < $indent; $i++) {
        $result .= $GLOBALS['branch_colors'][$i] . '│ ';
    }
    logg()->info($result . $GLOBALS['branch_colors'][$indent] . $char . $startPoint . $GLOBALS['warn'] . $text);

    // New Line
    if ($newLine) echo PHP_EOL;
}