<?

//lp 2012/11/29 1:26:01 兼容客户端提交数据时无session的情况
if(isset($P) || $P!="")
{
   ob_start();
   include_once("inc/session.php");
   session_id($P);
   session_start();
   session_write_close();
}

include_once("./auth.php");
include_once("inc/utility_file.php");
ob_end_clean();


$DEST_UID = urldecode($DEST_UID);

if(strpos($DEST_UID, ",") !== false){
   //如果找到了则不处理，为微讯群发使用     
}else{
   $DEST_UID = intval($DEST_UID); 
}

if($DEST_UID == 0)
{
   echo "-ERR "._("接收方ID无效");
   exit;
}

if($UPLOAD_MODE == "1")
{
   $MODULE = 'voicemsg';
}else{
   $MODULE = 'im';
}

if(count($_FILES) >= 1)
{
   $ATTACHMENTS=upload("ATTACHMENT", $MODULE, FALSE);
   if(!is_array($ATTACHMENTS))
   {
      echo "-ERR ".$ATTACHMENTS;
      exit;
   }
   
   ob_end_clean();

   $ATTACHMENT_ID = substr($ATTACHMENTS["ID"], 0, -1);
   $ATTACHMENT_NAME = substr($ATTACHMENTS["NAME"], 0, -1);
}
else
{
   echo "-ERR "._("无文件上传");
   exit;
}

$FILE_SIZE = attach_size($ATTACHMENT_ID, $ATTACHMENT_NAME, $MODULE);
if(!$FILE_SIZE)
{
   echo "-ERR "._("文件上传失败");
   exit;
}

if($UPLOAD_MODE == "1")
{
   include_once("inc/utility_msg.php");
   $P_VER = is_numeric($P_VER) ? intval($P_VER) : 0;   //增加MSG_TYPE的数据
   $DURATION = intval($DURATION);
   $CONTENT = "[vm]".$ATTACHMENT_ID."|".$ATTACHMENT_NAME."|".$DURATION."[/vm]";
   send_voice_msg($LOGIN_UID, $DEST_UID, $P_VER, $CONTENT);
   echo "+OK ".$CONTENT;
   exit;
}
else if($UPLOAD_MODE == "3")
{
    if(is_thumbable($ATTACHMENT_NAME))
   {
      $FILE_PATH = attach_real_path($ATTACHMENT_ID, $ATTACHMENT_NAME, $MODULE);
      $THUMB_FILE_PATH = substr($FILE_PATH, 0, strlen($FILE_PATH)-strlen($ATTACHMENT_NAME))."thumb_".$ATTACHMENT_NAME;
      CreateThumb($FILE_PATH, 320, 240, $THUMB_FILE_PATH);
   }
    echo "+OK ".$ATTACHMENT_ID;
}
else
{
   $query="insert into IM_OFFLINE_FILE (TIME,SRC_UID,DEST_UID,FILE_NAME,FILE_SIZE,FLAG) values ('".date('Y-m-d H:i:s')."','$LOGIN_UID','$DEST_UID','*".($ATTACHMENT_ID.".".$ATTACHMENT_NAME)."','$FILE_SIZE','0')";
   $cursor = exequery($connection,$query);
   $FILE_ID=mysql_insert_id();
   if($cursor === FALSE)
   {
      echo "-ERR "._("数据库操作失败");
      exit;
   }

   if($FILE_ID == 0)
   {
      echo "-ERR "._("数据库操作失败2");
      exit;
   }
   
   echo "+OK ".$FILE_ID;
   exit;
}

?>