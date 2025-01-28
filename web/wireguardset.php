<?php
//ini_set('error_reporting', E_ALL & ~E_NOTICE);
//ini_set('display_errors', true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("It's working.");
}

require('/opt/LMSWireguard/priv/wg_config.php');
require(LMS_DIR . '/bin/script-options.php');
require(SCRIPT_DIR . '/priv/wg_common.php');

$poczatekdnia = strtotime("today", time());

$user = filter_var($_POST['user'], FILTER_SANITIZE_STRING);
$pass = filter_var($_POST['pass'], FILTER_SANITIZE_STRING);
$regenerate = isset($_POST['regenerate']);
$download = isset($_POST['download']);
$intranetonly = isset($_POST['intranetonly']);
$configurationid = empty($_POST['configurationid']) ? 1 : intval($_POST['configurationid']);

if ($configurationid > 2) {
    die('ERROR: limit dwóch tuneli per użytkownik!');
}

$loggedin = loginRadius($user, $pass, RADIUS_IP, RADIUS_PORT, RADIUS_SECRET);
if (empty($loggedin['state'])) {
    die('ERROR: brak dostępu. Miłego dnia stąd! ' . $loggedin['error']);
}

$useremail = getUserEmail($user);
if (empty($useremail)) {
    die('ERROR: brak znalezionego adresu email użytkownika LMS lub użytkownik nie istnieje.');
}

$exists = getUserTunelNode($useremail, $configurationid);
$action = $regenerate ? (empty($exists) ? 'new' : 'replace') : 'show';

if (!$regenerate && !$exists) {
    die('ERROR: brak wygenerowanego tunelu');
}

switch($action) {
    case 'new':
        lms_create_wireguard($useremail, $configurationid);
    case 'replace':
        $configs = createWireguardConfigs($useremail, $intranetonly, $configurationid);
        $conn = ssh2_connect(WGSRV_IP, WGSRV_PORT_SSH);
        ssh2_auth_password($conn, WGSRV_LOGIN, WGSRV_PASS);
        $cmd = explode(';', file_get_contents($configs['filename_srv']));

	// Usuń komentarze i puste znaki z komend
        $filteredCmd = [];
	foreach ($cmd as $item) {
            if ($item[0] !== '#') {
                $filteredCmd[] = trim($item);
            }
        }
	$lastElement = end($filteredCmd);
        foreach ($filteredCmd as $c) {
            if (!empty($c)) {
                ssh2_exec($conn, $c);
		if($c !== $lastElement) {
                    sleep(3);
		}
            }
        }
    	ssh2_disconnect($conn);
    default:
        $cfg = show_config($user, $configurationid);
        if (empty($download)) {
            $podstawienia = [
                '%%cfgfile%%' => $cfg['file'],
                '%%cfgqr%%' => $cfg['qr'],
                '%%user%%' => $user,
                '%%wg_client_configurationid%%' => $configurationid,
                //show server side config
                //'%%srvconfig%%' => file_get_contents($configs['filename_srv']),
                '%%srvconfig%%' => '',
            ];
            $html = file_get_contents(TEMPLATE_HTML_RESULT);

            foreach ($podstawienia as $idx => $pd) {
                $html = str_replace($idx, $pd, $html);
            }
            print $html;
        } else {
            header('Content-disposition: attachment; filename="WG-' . $configurationid . '-' . $poczatekdnia . '.conf";');
            header('Content-Type: application/octet-stream');
            print $cfg['file'];
        }
}

shell_exec('sudo /opt/LMSWireguard/reload_node.sh ' . $exists['ipa'] . ' 2>&1');
