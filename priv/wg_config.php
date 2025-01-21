<?php
$script_dir = '/skrypty/wireguard';
$template_client = $script_dir . '/priv/client-tunel-template.config';
$template_srv = $script_dir . '/priv/mikrotik-tunel-template.rsc';
$template_html_result = $script_dir . '/priv/output.html';
$intranetips = '10.0.0.0/8, 172.16.0.0/12 46.151.184.0/21';
$lms_config = '/etc/lms/lms.ini';

//lms
$vpnregexp = 'WIREGUARD-VPN-';
$wireguard_lms_customerid = 1384;
$wireguard_lms_netid = 911;
$wireguard_lms_tariffid = 96;

//mikrotik konfig
$wg_srv_ip = 'wg.interduo.pl';
$wg_srv_port = '34715';
$wg_srv_port_ssh = '5455';
$wg_srv_login = 'superuser';
$wg_srv_pass = '7FyJEQP6wNdLZjJp';
$wg_srv_pubkey = 'MLFY0aTH9piMhnEKQdGflk7Gp5ftR2rB0tJxRIymWHo=';
$wg_srv_ifacename = 'wg-vpn';

//radius konfig
$radius_ip = 'radius.interduo.pl';
$radius_port = 1812;
$radius_secret = 'A7rg4qYPu4ez';

$operators = array(
    'yarii',
    'booyas',
    'qbas',
    'nabi',
    'p.panas',
    'm.wagrodny',
    't.karczewski',
    'm.kobylka',
);
