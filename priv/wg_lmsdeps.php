<?php
if (!is_readable($lms_config)) {
    die('Unable to read configuration file [' . $lms_config . ']!');
}

define('CONFIG_FILE', $lms_config);

$CONFIG = (array) parse_ini_file(CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['plugin_dir'] = (!isset($CONFIG['directories']['plugin_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'plugins' : $CONFIG['directories']['plugin_dir']);
$CONFIG['directories']['plugins_dir'] = $CONFIG['directories']['plugin_dir'];

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('PLUGIN_DIR', $CONFIG['directories']['plugin_dir']);
define('PLUGINS_DIR', $CONFIG['directories']['plugin_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
}

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$plugin_manager = new LMSPluginManager();
$LMS->setPluginManager($plugin_manager);

$divisionid = isset($options['division']) ? $LMS->getDivisionIdByShortName($options['division']) : null;

if (!empty($divisionid)) {
    ConfigHelper::setFilter($divisionid);
}

$DB = LMSDB::getInstance();

function lms_create_wireguard() {
    $DB = LMSDB::getInstance();
    $wg_client_ip = $LMS->GetFirstFreeAddress($wireguard_lms_netid);
    $octets = explode('.', $wg_client_ip);
    $nodeid = $LMS->NodeAdd(
        array(
            'name' => $vpnregexp . $octets[3],
            'ipaddr' => $wg_client_ip,
            'netid' => $wireguard_lms_netid,
            'ipaddr_pub' => '0.0.0.0',
            'info' => $argv[1],
            'ownerid' => $wireguard_lms_customerid,
            'passwd' => '',
            'access' => 1,
            'warning' => 0,
            'authtype' => 0,
            'chkmac' => 0,
            'halfduplex' => 0,
        )
    );

    $assignmentid = $LMS->insertAssignment(
        array(
            'customerid' => $wireguard_lms_customerid,
            'tariffid' => $wireguard_lms_tariffid,
            'datefrom' => strtotime("today", $timestamp),
            'period' => 3,
            'at' => 1,
        )
    );

    $LMS->insertNodeAssignments(
        array(
            'nodes' => array($nodeid),
            'assinmentid' => $assignmentid
        )
    );

    return null;
}

function getUserEmail($login) {
    $DB = LMSDB::getInstance();
    return $DB->GetOne(
        'SELECT email FROM users WHERE login = ?',
        array($login)
    );
}

function getUserLoginByEmail($email) {
    $DB = LMSDB::getInstance();
    return $DB->GetOne(
        'SELECT login FROM users WHERE email = ?',
        array($email)
    );
}

function getUserTunelNode($email) {
    $DB = LMSDB::getInstance();
    return $DB->GetRow(
        'SELECT id, name, inet_ntoa(ipaddr) AS ipa
	    FROM nodes
	    WHERE
                name LIKE ?
                AND info = ?',
        array(
            $vpnregexp . '%',
            $email
        )
    );
}
