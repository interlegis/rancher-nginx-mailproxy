<?php
/*
NGINX sends headers as
Auth-User: somuser
Auth-Pass: somepass
On my php app server these are seen as
HTTP_AUTH_USER and HTTP_AUTH_PASS
*/

if (!isset($_SERVER["HTTP_AUTH_USER"] ) || !isset($_SERVER["HTTP_AUTH_PASS"] )){
  exit;
}

$username=$_SERVER["HTTP_AUTH_USER"];
$userpass=$_SERVER["HTTP_AUTH_PASS"];
$protocol=$_SERVER["HTTP_AUTH_PROTOCOL"];
$userip=$_SERVER["HTTP_CLIENT_IP"];

// default backend port
$backend_port=110;
$server=getmailserver($username);
$relay_host = getenv("RELAY_HOST");

// Authenticate the user or fail
if (!authuser($username,$userpass)){
  fail($username, $userip, $protocol, $server);
  exit;
}

if ($protocol=="imap") {
  $backend_port=143;
  $serverip=gethostbyname($server);
  // Pass!
  pass($serverip, $backend_port, $username, $userip, $protocol);
}

if ($protocol=="smtp") {
  $backend_port=25;
  if ($relay_host != '') {
    // Send email via relay host
    $serverip=gethostbyname(getenv("RELAY_HOST"));
  } else {
    // Send email through default mail server
    $userdomain=substr(strrchr($user, "@"), 1);
    $server=("smtp.".str_replace(".","-",$userdomain).".rancher.internal");
    $serverip=gethostbyname($server);
  }
  // Pass!
  pass($serverip, $backend_port, $username, $userip, $protocol);
}

//END

function authuser($user,$pass){
  // To connect to an SSL IMAP or POP3 server with a self-signed certificate,
  // add /ssl/novalidate-cert after the protocol specification:

  $userserver=getmailserver($user);
  $mbox = imap_open ('{'.$userserver.':143/imap}', $user, $pass );

  if ($mbox) {
    imap_close($mbox);
    return true;
  } else {
    return false;
  }
}

function getmailserver($user){

  $userdomain=substr(strrchr($user, "@"), 1);
  $server=("imap.".str_replace(".","-",$userdomain).".rancher.internal");
 
  return $server;
}

function fail($username, $userip, $protocol, $server){
  flog("Auth ".strtoupper($protocol)." FAIL: ".$username." IP: ". $userip." Server: ".$server);
  header("Auth-Status: Invalid login or password");
  exit;
}

function pass($server,$port, $username, $userip, $protocol){
  flog("Auth ".strtoupper($protocol)." OK: ".$username." IP: ".$userip." Server: ".$server);
  header("Auth-Status: OK");
  header("Auth-Server: $server");
  header("Auth-Port: $port");
  exit;
}

function flog($message){
  error_log($message);
}

?>
