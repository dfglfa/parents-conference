<?php

require_once('code/config.php');
require_once('code/Util.php');
require_once('code/dao/AbstractDAO.php');
require_once('code/AuthenticationManager.php');

error_reporting(E_ALL);

SessionContext::create();

if (!isset($_SESSION['userId'])) {
    header('Location: index.php');
    exit();
}

$user = AuthenticationManager::getAuthenticatedUser();

?>
<!DOCTYPE html
    PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>

<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
    <meta name="viewport" content="width=device-width, initial-scale=0.7">
    <title>Elternsprechtag</title>

    <link href='libs/bootstrap/css/bootstrap.min.css' rel='stylesheet'>
    <link href='libs/bootstrap/css/bootstrap-theme.min.css' rel='stylesheet'>
    <link href='css/style.css' rel='stylesheet'>

    <script type='text/javascript' src='js/jquery-1.11.3-jquery.min.js'></script>
</head>

<body>

    <?php include_once 'inc/navBar.php'; ?>

    <div class='body-container'>