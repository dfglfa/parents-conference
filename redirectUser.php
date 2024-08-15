<?php
require_once('code/AuthenticationManager.php');
require_once('code/Util.php');

if (isset($_SESSION['userId']) != '') {
    header('Location: index.php');
}

$role = AuthenticationManager::getAuthenticatedUser()->getRole();

if ($role == "admin") {
    redirect("admin.php");
} else {
    redirect("home.php");
}
