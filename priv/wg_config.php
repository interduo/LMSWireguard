<?php
$script_dir = getcwd();

//templatka tunelu wireguard
$template_client = $script_dir . '/priv/client-tunel-template.config';

//templatka komend dla serwera wireguard
$template_srv = $script_dir . '/priv/mikrotik-tunel-template.rsc';

//templatka prezentacji konfiga użytkownika po wygenerowaniu tunelu
$template_html_result = $script_dir . '/priv/output.html';

//dostep za pomocą tuneli tylko do podsieci (zamiast puszczac cały ruch przez tunel)
$intranetips = '10.0.0.0/8, 172.16.0.0/12 46.151.184.0/21';

//adresy serwerów DNS w konfigach
$dns = '8.8.8.8, 8.8.4.4';
$lms_config = '/etc/lms/lms.ini';

//komenda uruchomiona po utworzeniu tunelu
//$shellcmd_at_end = '/var/www/html/lms/bin/reload_node.sh' . $exists['ipa'] . ' 2>&1';

$vpnregexp = 'WIREGUARD-VPN-';

//LMS: numer klienta w którym utworzymy komputer dla każdego tworzonego tunelu wireguard
$wireguard_lms_customerid = 1111;
//LMS: numer sieci w której utworzymy komputer dla każdego tworzonego tunelu wireguard
$wireguard_lms_netid = 222;
//LMS: numer przypisanej taryfy na podstawie której zostanie utworzone zobowiązanie
$wireguard_lms_tariffid = 11;

//dane dostępowe mikrotika z koncentratorem wireguard
$wg_srv_ip = 'jakistammikrotik.interduo.pl';
$wg_srv_port = '34777';
$wg_srv_port_ssh = '22';
$wg_srv_login = 'admin';
$wg_srv_pass = 'admin123';

//klucz publiczny (wartość public-key z /interface/wireguard print)
$wg_srv_pubkey = 'MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM';
$wg_srv_ifacename = 'wg-vpn';

//dane dostępowe serwera radius
$radius_ip = 'radius.pl';
$radius_port = 1812;
$radius_secret = 'secretradius';

//lista loginów radius traktowanych jako operatorzy (tak wiem że da się lepiej:)
$operators = array(
    'login1',
    'login2',
    'login3',
);
