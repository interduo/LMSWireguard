<?php
const SCRIPT_DIR = '/skrypty/wireguard';
const TEMPLATE_CLIENT = SCRIPT_DIR . '/priv/client-tunel-template.config';
const TEMPLATE_SRV = SCRIPT_DIR . '/priv/mikrotik-tunel-template.rsc';
const TEMPLATE_HTML_RESULT = SCRIPT_DIR . '/priv/output.html';
const INTRANET_IPS = '10.0.0.0/8, 172.16.0.0/12 46.151.184.0/21';

//lms
const LMS_NODE_VPN_REGEXP = 'WIREGUARD-VPN-';
const LMS_CUSTOMERID_VPN = 1384;
const LMS_NETID_VPN = 911;
const LMS_TARIFFID_VPN = 96;

//mikrotik wireguard
const WGSRV_IP = 'wg.interduo.pl';
const WGSRV_PORT_WG = '34715';
const WGSRV_PORT_SSH = '5455';
const WGSRV_LOGIN = 'superuser';
const WGSRV_PASS = '7FyJEQP6wNdLZjJp';
const WGSRV_PUBKEY = 'MLFY0aTH9piMhnEKQdGflk7Gp5ftR2rB0tJxRIymWHo=';
const WGSRV_IFACENAME = 'wg-vpn';

//radius
const RADIUS_IP = 'radius.interduo.pl';
const RADIUS_PORT = 1812;
const RADIUS_SECRET = 'A7rg4qYPu4ez';

$operators = [
    'yarii',
    'booyas',
    'qbas',
    'nabi',
    'p.panas',
    'm.wagrodny',
    't.karczewski',
    'm.kobylka',
];