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

function createWireguardConfigs($email, $intranetonly, $configurationid) {
    $poczatekdnia = strtotime("today", time());
    $usertunel = getUserTunelNode($email, $configurationid);
    $user = getUserLoginByEmail($email);
    $operator = in_array($user, LMS_USER_OPERATORS);
    $intranet = !empty($intranetonly);

    if (empty($usertunel)) {
        die('ERROR: coś złego z LMS');
    }

    $wg_client_keypair = generate_wireguard_keypair();
    $wg_client_comment = $email . '-ID' . $configurationid;
    $podstawienia = [
	'%%poczatekdnia%%' => $poczatekdnia,
        '%%wg_client_configurationid%%' => $configurationid,
        '%%wg_client_ip%%' => $usertunel['ipa'],
        '%%wg_client_privkey%%' => $wg_client_keypair['private'],
        '%%wg_client_pubkey%%' => $wg_client_keypair['public'],
        '%%wg_client_mail%%' => $email,
        '%%wg_client_comment%%' => $wg_client_comment,
        '%%wg_srv_ip%%' => WGSRV_IP,
        '%%wg_srv_port%%' => WGSRV_PORT_WG,
        '%%wg_srv_pubkey%%' => WGSRV_PUBKEY,
        '%%wg_srv_allowedips%%' => $intranet ? INTRANET_IPS : '0.0.0.0/0',
        '%%wg_srv_ifacename%%' => WGSRV_IFACENAME,
        '%%wg_srv_operator_rem%%' => (empty($operator) ? '' : (empty($usertunel) ? '' : '/ip firewall address-list remove numbers=[find comment="' . $wg_client_comment . '"];')),
        '%%wg_srv_operator_add%%' => (empty($operator) ? '' : ' /ip firewall address-list add list=vlan997_acl address="' . $usertunel['ipa'] . '" comment="' . $wg_client_comment . '";'),
        '%%wg_srv_duplicate%%' => (empty($usertunel) ? '' : ' /interface/wireguard/peers/remove numbers=[find name="' . $wg_client_comment . '"];'),
    ];

    $wg_client_config = file_get_contents(TEMPLATE_CLIENT);
    $wg_srv_config = file_get_contents(TEMPLATE_SRV);

    foreach ($podstawienia as $idx => $pd) {
        $wg_client_config = str_replace($idx, $pd, $wg_client_config);
        $wg_srv_config = str_replace($idx, $pd, $wg_srv_config);
    }

    $filename_client = SCRIPT_DIR . '/tunnels/' . 'client-vpn-config-' . $user . '-' . $configurationid . '.config';
    $filename_srv = SCRIPT_DIR . '/tunnels/' . 'mt-vpn-config-' . $user . '-' . $configurationid . '.rsc';
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

function show_config($user, $configurationid) {
    $fullpath = SCRIPT_DIR . '/tunnels/'
        . 'client-vpn-config-' . $user . '-' . $configurationid . '.config';
    $output = file_get_contents($fullpath);

    if (empty($output)) {
        return;
    }

    $options = new QROptions([
        'eccLevel' => QRCode::ECC_Q,
        'margin' => 15,
    ]);

    $qr_html = '<img id="qrcode" src="'
        . (new QRCode($options))->render($output) . '" alt="QRCode"/>';

    return [
        'file' => $output,
        'fullpath' => $fullpath,
        'qr' => empty($output) ? null : $qr_html,
    ];
}

//LMSDEPS
function lms_create_wireguard($useremail, $configurationid) {
    global $DB, $AUTH, $SYSLOG, $poczatekdnia;
    $LMS = new LMS($DB, $AUTH, $SYSLOG);

    $wg_client_ip = $LMS->GetFirstFreeAddress(LMS_NETID_VPN);
    $octets = explode('.', $wg_client_ip);

    $params = [
        'name' => LMS_NODE_VPN_REGEXP . $octets[3] . '-ID' . $configurationid,
        'ipaddr' => $wg_client_ip,
        'netid' => LMS_NETID_VPN,
        'ipaddr_pub' => '0.0.0.0',
        'info' => $useremail . '-ID' . $configurationid,
        'ownerid' => LMS_CUSTOMERID_VPN,
        'passwd' => '',
        'access' => 1,
        'warning' => 0,
        'authtype' => 0,
        'chkmac' => 0,
        'halfduplex' => 0,
        'linktype' => 2,
	'linkspeed' => 1000000,
	'macs' => [ '00:00:00:00:00:00' ],
    ];

    //Dodaje kompa w LMS
    $nodeid = $LMS->NodeAdd($params);
    ///$argv = [
    ///     'customerid' => LMS_CUSTOMERID_VPN,
    ///     'tariffid' => LMS_TARIFFID_VPN,
    ///     'datefrom' => $poczatekdnia,
    ///     'period' => 3,
    ///     'at' => 1,
    ///     'note' => $params['info'],
    ///     'nodes' => $nodeid,
    ///  ];
    ///$LMS->AddAssignment($argv);
    $DB->Execute(
        'INSERT INTO assignments (customerid, tariffid, datefrom, period, at, note) VALUES (?, ?, ?, ?, ?, ?)',
        array(
            LMS_CUSTOMERID_VPN,
            LMS_TARIFFID_VPN,
            $poczatekdnia,
            3,
            1,
            $params['info']
        )
    );
    $assignmentid = $DB->getLastInsertId('assignments');

    //Dodaje taryfę dla konta VPN
    $DB->Execute(
        'INSERT INTO nodeassignments (nodeid, assignmentid) VALUES (?, ?)',
        array(
            $nodeid,
            $assignmentid
        )
    );
    //$LMS->insertNodeAssignment();
    return null;
}

function getUserEmail($login) {
    global $DB;
    return $DB->GetOne(
        'SELECT email FROM users WHERE login = ?',
        array($login)
    );
}

function getUserLoginByEmail($email) {
    global $DB;
    return $DB->GetOne(
        'SELECT login FROM users WHERE email = ?',
        array($email)
    );
}

function getUserTunelNode($email, $configurationid) {
    global $DB;
    $nodename = LMS_NODE_VPN_REGEXP . '%-ID' . $configurationid;
    $nodeinfo = $email . '-ID' . $configurationid;

    return $DB->GetRow('
        SELECT id, name, info, INET_NTOA(ipaddr) AS ipa
            FROM nodes
        WHERE
            name LIKE ?
            AND info LIKE ?',
        array(
            $nodename,
            $nodeinfo,
        )
    );
}
