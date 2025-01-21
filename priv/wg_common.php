<?php
require 'vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

function generate_wireguard_keypair() {
    $keyPair = sodium_crypto_box_keypair();

    return [
        'private' => base64_encode(sodium_crypto_box_secretkey($keyPair)),
        'public' => base64_encode(sodium_crypto_box_publickey($keyPair)),
    ];
}

function createWireguardConfigs($email, $intranetonly) {
    require('/skrypty/wireguard/priv/wg_config.php');
    $usertunel = getUserTunelNode($email);
    $user = getUserLoginByEmail($email);
    $operator = in_array($user, $operators);
    $intranet = !empty($intranetonly);

    if (empty($usertunel)) {
        die('Something wrong with LMS');
    }

    $wg_client_keypair = generate_wireguard_keypair();

    $podstawienia = [
        '%%wg_client_ip%%' => $usertunel['ipa'],
        '%%wg_client_privkey%%' => $wg_client_keypair['private'],
        '%%wg_client_pubkey%%' => $wg_client_keypair['public'],
        '%%wg_client_mail%%' => $email,
        '%%wg_srv_ip%%' => WGSRV_IP,
        '%%wg_srv_port%%' => WGSRV_PORT_WG,
        '%%wg_srv_pubkey%%' => WGSRV_PUBKEY,
        '%%wg_srv_allowedips%%' => $intranet ? INTRANET_IPS : '0.0.0.0/0',
        '%%wg_srv_ifacename%%' => WGSRV_IFACENAME,
        '%%wg_srv_operator_rem%%' => (empty($operator) ? '' : (empty($usertunel) ? '' : '/ip firewall address-list remove numbers=[find comment="' . $email . '"];')),
        '%%wg_srv_operator_add%%' => (empty($operator) ? '' : '/ip firewall address-list add list=vlan997_acl address="' . $usertunel['ipa'] . '" comment="' . $email . '";'),
        '%%wg_srv_duplicate%%' => (empty($usertunel) ? '' : '/interface/wireguard/peers/remove numbers=[find comment="' . $email . '"];'),
    ];

    $wg_client_config = file_get_contents(TEMPLATE_CLIENT);
    $wg_srv_config = file_get_contents(TEMPLATE_SRV);

    foreach ($podstawienia as $idx => $pd) {
        $wg_client_config = str_replace($idx, $pd, $wg_client_config);
        $wg_srv_config = str_replace($idx, $pd, $wg_srv_config);
    }

    $filename_client = SCRIPT_DIR . '/tunnels/' . 'client-vpn-config-' . $user . '.config';
    $filename_srv = SCRIPT_DIR . '/tunnels/' . 'mt-vpn-config-' . $user . '.rsc';
    file_put_contents($filename_client, $wg_client_config);
    file_put_contents($filename_srv, $wg_srv_config);

    return [
        'filename_client' => $filename_client,
        'filename_srv' => $filename_srv,
    ];
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

    return [
        'state' => $result == RADIUS_ACCESS_ACCEPT,
        'error' => radius_strerror($radius),
    ];
}

function show_config($user) {
    $output = file_get_contents(SCRIPT_DIR . '/tunnels/' . 'client-vpn-config-' . $user . '.config');

    $options = new QROptions([
        'eccLevel' => QRCode::EccLevel::L,
        'size'     => 3,
    ]);

    new QRCode($options);

    if (!empty($output)) {
        $qr_html = '<img src="' . (new QRCode)->render($output) . '" alt="QRCode"/>';
    }
 
    return [
        'file' => $output,
        'qr' => empty($output) ? null : $qr_html,
    ];
}
