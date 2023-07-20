<?php
if(empty($_POST)) {
    die("It's working.");
}

$script_dir = '/opt/LMSWireguard';
require($script_dir . '/priv/wg_config.php');
require($script_dir . '/priv/wg_common.php');

$user = filter_var($_POST['user'], FILTER_SANITIZE_STRING);
$pass = filter_var($_POST['pass'], FILTER_SANITIZE_STRING);
$regenerate = empty($_POST['regenerate']) ? false : true;
$download = empty($_POST['download']) ? false : true;
$intranetonly = empty($_POST['intranetonly']) ? false : true;

$loggedin = loginRadius($user, $pass, $radius_ip, $radius_port, $radius_secret);

if (empty($loggedin['state'])) {
    die('No access. Have a nice day from here! ' . $loggedin['error']);
} else {
    require($script_dir . '/priv/wg_lmsdeps.php');
    $useremail = getUserEmail($user);

    if (empty($useremail)) {
        die('ERROR: did not find LMS user email from radius login or user doesnt exists.');
    }

    $exists = getUserTunelNode($useremail);
    $action = empty($regenerate) ? 'show' : (empty($exists) ? 'new' : 'replace');

    switch($action) {
        case 'new':
            lms_create_wireguard();
        case 'replace':
            $configs = createWireguardConfigs($useremail, $intranetonly);
    	    $exists = getUserTunelNode($useremail);
            $conn = ssh2_connect($wg_srv_ip, $wg_srv_port_ssh);
            ssh2_auth_password($conn, $wg_srv_login, $wg_srv_pass);
	    $cmd = explode(';', file_get_contents($configs['filename_srv']));
	    foreach ($cmd as $c) {
	        ssh2_exec($conn, $c);
	    }
            ssh2_disconnect($conn);
        default:
            $cfg = show_config($user);
            if (empty($download)) {
                $podstawienia = array(
                    '%%cfgfile%%' => $cfg['file'],
                    '%%cfgqr%%' => $cfg['qr'],
                    '%%user%%' => $user,
		    '%%srvconfig%%' => '',
                );
                $html = file_get_contents($template_html_result);

                foreach ($podstawienia as $idx => $pd) {
                    $html = str_replace($idx, $pd, $html);
                }
                print $html;
            } else {
                header('Content-disposition: attachment; filename="IDUO.conf"');
                header('Content-Type: application/octet-stream');
                print $cfg['file'];
            }
    }

    if(!empty($shellcmd_at_end)) {
        shell_exec($shellcmd_at_end);
    }
}
