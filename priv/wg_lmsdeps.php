<?php
require_once('/var/www/html/lms/bin/script-options.php');

function lms_create_wireguard() {
    global $LMS, $DB, $wireguard_lms_netid, $vpnregexp, $wireguard_lms_customerid, $useremail;
    $wg_client_ip = $LMS->GetFirstFreeAddress($wireguard_lms_netid);
    $octets = explode('.', $wg_client_ip);
    $params = [
        'name' => $vpnregexp . $octets[3],
        'ipaddr' => $wg_client_ip,
        'netid' => $wireguard_lms_netid,
        'ipaddr_pub' => '0.0.0.0',
        'info' => $useremail,
        'ownerid' => $wireguard_lms_customerid,
        'passwd' => '',
        'access' => 1,
        'warning' => 0,
        'authtype' => 0,
        'chkmac' => 0,
        'halfduplex' => 0,
    ];

    //Dodaje kompa w LMS
    $nodeid = $LMS->NodeAdd($params);
    ///$LMS->AddAssignment($params);
    $DB->Execute(
        'INSERT INTO assignments (customerid, tariffid, datefrom, period, at) VALUES (?, ?, ?, ?, ?)',
        array($wireguard_lms_customerid, $wireguard_lms_tariffid, $poczatekdnia, 3, 1)
    );
    ///$assignmentid = $LMS->CustomerassignmentAdd($params);
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

function getUserTunelNode($email) {
    global $DB;
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
