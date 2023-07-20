<?php

function generate_wireguard_privkey() {
    require('/opt/LMSWireguard/priv/wg_config.php');
    return rtrim(shell_exec($script_dir . '/priv/wg genkey'));
}

function generate_wireguard_pubkey($privkey) {
    require('/opt/LMSWireguard/priv/wg_config.php');
    return rtrim(shell_exec('echo -n "' . $privkey . '" | ' . $script_dir . '/priv/wg pubkey'));
}

function createWireguardConfigs($email, $intranetonly) {
    require('/opt/LMSWireguard/priv/wg_config.php');
    $usertunel = getUserTunelNode($email);
    $user = getUserLoginByEmail($email);
    $operator = in_array($user, $operators) ? true : false;
    $intranet = empty($intranetonly) ? false : true;

    if (empty($usertunel)) {
        die('Something wrong with LMS');
    }

    $wg_client_privkey = generate_wireguard_privkey();
    $wg_client_pubkey = generate_wireguard_pubkey($wg_client_privkey);

    $podstawienia = array(
        '%%wg_client_ip%%' => $usertunel['ipa'],
        '%%wg_client_privkey%%' => $wg_client_privkey,
        '%%wg_client_pubkey%%' => $wg_client_pubkey,
        '%%wg_client_mail%%' => $email,
        '%%dns%%' => $dns,
        '%%wg_srv_ip%%' => $wg_srv_ip,
        '%%wg_srv_port%%' => $wg_srv_port,
        '%%wg_srv_pubkey%%' => $wg_srv_pubkey,
        '%%wg_srv_allowedips%%' => $intranetonly ? $intranetips : '0.0.0.0/0',
        '%%wg_srv_ifacename%%' => $wg_srv_ifacename,
        '%%wg_srv_operator_rem%%' => (empty($operator) ? '' : (empty($usertunel) ? '' : '/ip firewall address-list remove numbers=[find comment="' . $email . '"]; ')),
	'%%wg_srv_operator_add%%' => (empty($operator) ? '' : '/ip firewall address-list add list=vlan997_acl address=' . $usertunel['ipa'] . ' comment="' . $email . '"; '),
	'%%wg_srv_duplicate%%' => (empty($usertunel) ? '' : '/interface/wireguard/peers/remove numbers=[find comment="' . $email . '"]; '),
    );

    $wg_client_config = file_get_contents($template_client);
    $wg_srv_config = file_get_contents($template_srv);

    foreach ($podstawienia as $idx => $pd) {
        $wg_client_config = str_replace($idx, $pd, $wg_client_config);
        $wg_srv_config = str_replace($idx, $pd, $wg_srv_config);
    }

    $filename_client = $script_dir . '/tunnels/' . 'client-vpn-config-' . $user . '.config';
    $filename_srv = $script_dir . '/tunnels/' . 'mt-vpn-config-' . $user . '.rsc';
    file_put_contents($filename_client, $wg_client_config);
    file_put_contents($filename_srv, $wg_srv_config);

    return array(
        'filename_client' => $filename_client,
        'filename_srv' => $filename_srv,
    );
}

function loginRadius($user, $pass, $radius_ip, $radius_port, $radius_secret) {
    $radius = radius_auth_open();
    radius_add_server($radius, $radius_ip, $radius_port, $radius_secret, 3, 3);
    radius_create_request($radius, RADIUS_ACCESS_REQUEST);

    $ident = 1;
    $chall = mt_rand();
    $chapval = md5(pack('Ca*', $ident, $pass . $chall));
    $pass = pack('CH*', $ident, $chapval);

    radius_put_attr($radius, RADIUS_USER_NAME, $user);
    radius_put_attr($radius, RADIUS_CHAP_PASSWORD, $pass);
    radius_put_attr($radius, RADIUS_CHAP_CHALLENGE, $chall);

    $result = radius_send_request($radius);

    return array(
        'state' => $result == RADIUS_ACCESS_ACCEPT ? true : false,
        'error' => radius_strerror($radius),
    );
}

function show_config($user) {
    require('/opt/LMSWireguard/priv/wg_config.php');
    $output = file_get_contents($script_dir . '/tunnels/' . 'client-vpn-config-' . $user . '.config');
    require_once("/usr/share/phpqrcode/phpqrcode.php");

    if (!empty($output)) {
        $tmpfile = '/tmp/qrcode-tmpfile';
        $qrcode = QRcode::png($output, $tmpfile);
	$qr_html = '<img src="data:image/png;base64,' . base64_encode(file_get_contents($tmpfile)) . '" />';
    }

    return array(
        'file' => $output,
        'qr' => empty($output) ? null : $qr_html,
    );
}
