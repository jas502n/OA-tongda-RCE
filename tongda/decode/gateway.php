<?php

ob_start();
include_once 'inc/session.php';
include_once 'inc/conn.php';
include_once 'inc/utility_org.php';
if ($P != '') {
    if (preg_match('/[^a-z0-9;]+/i', $P)) {
        echo _('非法参数');
        exit;
    }
    session_id($P);
    session_start();
    session_write_close();
    if ($_SESSION['LOGIN_USER_ID'] == '' || $_SESSION['LOGIN_UID'] == '') {
        echo _('RELOGIN');
        exit;
    }
}
if ($json) {
    $json = stripcslashes($json);
    $json = (array) json_decode($json);
    foreach ($json as $key => $val) {
        if ($key == 'data') {
            $val = (array) $val;
            foreach ($val as $keys => $value) {
                ${$keys} = $value;
            }
        }
        if ($key == 'url') {
            $url = $val;
        }
    }
    if ($url != '') {
        if (substr($url, 0, 1) == '/') {
            $url = substr($url, 1);
        }
        if (strpos($url, 'general/') !== false || strpos($url, 'ispirit/') !== false || strpos($url, 'module/') !== false) {
            include_once $url;
        }
    }
    exit;
}