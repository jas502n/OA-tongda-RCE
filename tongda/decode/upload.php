<?php

set_time_limit(0);
$P = $_POST['P'];
if (isset($P) || $P != '') {
    ob_start();
    include_once 'inc/session.php';
    session_id($P);
    session_start();
    session_write_close();
} else {
    include_once './auth.php';
}
include_once 'inc/utility_file.php';
include_once 'inc/utility_msg.php';
include_once 'mobile/inc/funcs.php';
ob_end_clean();
$TYPE = $_POST['TYPE'];
$DEST_UID = $_POST['DEST_UID'];
$dataBack = array();
if ($DEST_UID != '' && !td_verify_ids($ids)) {
    $dataBack = array('status' => 0, 'content' => '-ERR ' . _('接收方ID无效'));
    echo json_encode(data2utf8($dataBack));
    exit;
}
if (strpos($DEST_UID, ',') !== false) {
} else {
    $DEST_UID = intval($DEST_UID);
}
if ($DEST_UID == 0) {
    if ($UPLOAD_MODE != 2) {
        $dataBack = array('status' => 0, 'content' => '-ERR ' . _('接收方ID无效'));
        echo json_encode(data2utf8($dataBack));
        exit;
    }
}
$MODULE = 'im';
if (1 <= count($_FILES)) {
    if ($UPLOAD_MODE == '1') {
        if (strlen(urldecode($_FILES['ATTACHMENT']['name'])) != strlen($_FILES['ATTACHMENT']['name'])) {
            $_FILES['ATTACHMENT']['name'] = urldecode($_FILES['ATTACHMENT']['name']);
        }
    }
    $ATTACHMENTS = upload('ATTACHMENT', $MODULE, false);
    if (!is_array($ATTACHMENTS)) {
        $dataBack = array('status' => 0, 'content' => '-ERR ' . $ATTACHMENTS);
        echo json_encode(data2utf8($dataBack));
        exit;
    }
    ob_end_clean();
    $ATTACHMENT_ID = substr($ATTACHMENTS['ID'], 0, -1);
    $ATTACHMENT_NAME = substr($ATTACHMENTS['NAME'], 0, -1);
    if ($TYPE == 'mobile') {
        $ATTACHMENT_NAME = td_iconv(urldecode($ATTACHMENT_NAME), 'utf-8', MYOA_CHARSET);
    }
} else {
    $dataBack = array('status' => 0, 'content' => '-ERR ' . _('无文件上传'));
    echo json_encode(data2utf8($dataBack));
    exit;
}
$FILE_SIZE = attach_size($ATTACHMENT_ID, $ATTACHMENT_NAME, $MODULE);
if (!$FILE_SIZE) {
    $dataBack = array('status' => 0, 'content' => '-ERR ' . _('文件上传失败'));
    echo json_encode(data2utf8($dataBack));
    exit;
}
if ($UPLOAD_MODE == '1') {
    if (is_thumbable($ATTACHMENT_NAME)) {
        $FILE_PATH = attach_real_path($ATTACHMENT_ID, $ATTACHMENT_NAME, $MODULE);
        $THUMB_FILE_PATH = substr($FILE_PATH, 0, strlen($FILE_PATH) - strlen($ATTACHMENT_NAME)) . 'thumb_' . $ATTACHMENT_NAME;
        CreateThumb($FILE_PATH, 320, 240, $THUMB_FILE_PATH);
    }
    $P_VER = is_numeric($P_VER) ? intval($P_VER) : 0;
    $MSG_CATE = $_POST['MSG_CATE'];
    if ($MSG_CATE == 'file') {
        $CONTENT = '[fm]' . $ATTACHMENT_ID . '|' . $ATTACHMENT_NAME . '|' . $FILE_SIZE . '[/fm]';
    } else {
        if ($MSG_CATE == 'image') {
            $CONTENT = '[im]' . $ATTACHMENT_ID . '|' . $ATTACHMENT_NAME . '|' . $FILE_SIZE . '[/im]';
        } else {
            $DURATION = intval($DURATION);
            $CONTENT = '[vm]' . $ATTACHMENT_ID . '|' . $ATTACHMENT_NAME . '|' . $DURATION . '[/vm]';
        }
    }
    $AID = 0;
    $POS = strpos($ATTACHMENT_ID, '@');
    if ($POS !== false) {
        $AID = intval(substr($ATTACHMENT_ID, 0, $POS));
    }
    $query = 'INSERT INTO im_offline_file (TIME,SRC_UID,DEST_UID,FILE_NAME,FILE_SIZE,FLAG,AID) values (\'' . date('Y-m-d H:i:s') . '\',\'' . $_SESSION['LOGIN_UID'] . '\',\'' . $DEST_UID . '\',\'*' . $ATTACHMENT_ID . '.' . $ATTACHMENT_NAME . '\',\'' . $FILE_SIZE . '\',\'0\',\'' . $AID . '\')';
    $cursor = exequery(TD::conn(), $query);
    $FILE_ID = mysql_insert_id();
    if ($cursor === false) {
        $dataBack = array('status' => 0, 'content' => '-ERR ' . _('数据库操作失败'));
        echo json_encode(data2utf8($dataBack));
        exit;
    }
    $dataBack = array('status' => 1, 'content' => $CONTENT, 'file_id' => $FILE_ID);
    echo json_encode(data2utf8($dataBack));
    exit;
} else {
    if ($UPLOAD_MODE == '2') {
        $DURATION = intval($_POST['DURATION']);
        $CONTENT = '[vm]' . $ATTACHMENT_ID . '|' . $ATTACHMENT_NAME . '|' . $DURATION . '[/vm]';
        $query = 'INSERT INTO WEIXUN_SHARE (UID, CONTENT, ADDTIME) VALUES (\'' . $_SESSION['LOGIN_UID'] . '\', \'' . $CONTENT . '\', \'' . time() . '\')';
        $cursor = exequery(TD::conn(), $query);
        echo '+OK ' . $CONTENT;
    } else {
        if ($UPLOAD_MODE == '3') {
            if (is_thumbable($ATTACHMENT_NAME)) {
                $FILE_PATH = attach_real_path($ATTACHMENT_ID, $ATTACHMENT_NAME, $MODULE);
                $THUMB_FILE_PATH = substr($FILE_PATH, 0, strlen($FILE_PATH) - strlen($ATTACHMENT_NAME)) . 'thumb_' . $ATTACHMENT_NAME;
                CreateThumb($FILE_PATH, 320, 240, $THUMB_FILE_PATH);
            }
            echo '+OK ' . $ATTACHMENT_ID;
        } else {
            $CONTENT = '[fm]' . $ATTACHMENT_ID . '|' . $ATTACHMENT_NAME . '|' . $FILE_SIZE . '[/fm]';
            $msg_id = send_msg($_SESSION['LOGIN_UID'], $DEST_UID, 1, $CONTENT, '', 2);
            $query = 'insert into IM_OFFLINE_FILE (TIME,SRC_UID,DEST_UID,FILE_NAME,FILE_SIZE,FLAG) values (\'' . date('Y-m-d H:i:s') . '\',\'' . $_SESSION['LOGIN_UID'] . '\',\'' . $DEST_UID . '\',\'*' . $ATTACHMENT_ID . '.' . $ATTACHMENT_NAME . '\',\'' . $FILE_SIZE . '\',\'0\')';
            $cursor = exequery(TD::conn(), $query);
            $FILE_ID = mysql_insert_id();
            if ($cursor === false) {
                echo '-ERR ' . _('数据库操作失败');
                exit;
            }
            if ($FILE_ID == 0) {
                echo '-ERR ' . _('数据库操作失败2');
                exit;
            }
            echo '+OK ,' . $FILE_ID . ',' . $msg_id;
            exit;
        }
    }
}