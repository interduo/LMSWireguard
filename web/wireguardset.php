<?php
//ini_set('error_reporting', E_ALL & ~E_NOTICE);
//ini_set('display_errors', true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("It's working.");
}

require_once('/skrypty/wireguard/priv/wg_config.php');
require_once(SCRIPT_DIR . '/priv/wg_common.php');

$poczatekdnia = strtotime("today", time());

$user = filter_var($_POST['user'], FILTER_SANITIZE_STRING);
$pass = filter_var($_POST['pass'], FILTER_SANITIZE_STRING);

$regenerate = isset($_POST['regenerate']);
$download = isset($_POST['download']);
$intranetonly = isset($_POST['intranetonly']);
$configurationid = empty($_POST['configurationid']) ? false : intval($_POST['configurationid']);

$loggedin = loginRadius($user, $pass, RADIUS_IP, RADIUS_PORT, RADIUS_SECRET);

if (empty($loggedin['state'])) {
    die('No access. Have a nice day from here! ' . $loggedin['error']);
}

require(SCRIPT_DIR . '/priv/wg_lmsdeps.php');
$useremail = getUserEmail($user);

if (empty($useremail)) {
    die('ERROR: did not find LMS user email from radius login or user doesnt exists.');
}

$exists = getUserTunelNode($useremail);
$action = $regenerate ? (empty($exists) ? 'new' : 'replace') : 'show';

if (!$regenerate && !$exists) {
    die('ERROR: brak wygenerowanego tunelu');
}

switch($action) {
    case 'new':
        lms_create_wireguard();
    case 'replace':
        $configs = createWireguardConfigs($useremail, $intranetonly);
        $conn = ssh2_connect(WGSRV_IP, WGSRV_PORT_SSH);
        ssh2_auth_password($conn, WGSRV_LOGIN, WGSRV_PASS);
        $cmd = explode(';', file_get_contents($configs['filename_srv']));
        foreach ($cmd as $c) {
            if (!empty($c)) {
                ssh2_exec($conn, $c);
                sleep(3);
            }
        }
    ssh2_disconnect($conn);
    default:
        $cfg = show_config($user);
        if (empty($download)) {
            $podstawienia = array(
                '%%cfgfile%%' => $cfg['file'],
                '%%cfgqr%%' => $cfg['qr'],
                '%%user%%' => $user,
                '%%srvconfig%%' => '', //file_get_contents($configs['filename_srv']),
            );
            $html = file_get_contents(TEMPLATE_HTML_RESULT);

            foreach ($podstawienia as $idx => $pd) {
                $html = str_replace($idx, $pd, $html);
            }
            print $html;
        } else {
            header('Content-disposition: attachment; filename="WG.conf";');
            header('Content-Type: application/octet-stream');
            readfile($cfg['file']);
        }
}

shell_exec('/skrypty/przeladuj_node.sh ' . $exists['ipa'] . ' 2>&1');
