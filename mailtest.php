<?php
require_once('code/AuthenticationManager.php');
AuthenticationManager::checkPrivilege('admin');
require_once('code/Notification.php');

$email = $_REQUEST['email'];
sendMail($email, "Hello from the parents-conference", "It works!");
?>