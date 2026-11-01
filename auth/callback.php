<?php

ob_start();

require_once(__DIR__ . '/../code/config.php');
require_once(__DIR__ . '/../code/Util.php');
require_once(__DIR__ . '/../code/dao/UserDAO.php');

SessionContext::create();

use Jumbojett\OpenIDConnectClient;

$oidc = new OpenIDConnectClient(
  $KEYCLOAK_REALM_BASE_URL,
  $KEYCLOAK_CLIENT_ID,
  $KEYCLOAK_CLIENT_SECRET
);
$oidc->setRedirectURL($KEYCLOAK_REDIRECT_URL);
$oidc->authenticate();

$userId = $oidc->requestUserInfo("preferred_username");
$user = UserDAO::getUserForUserName($userId);

$_SESSION['userId'] = $user->getId();
$_SESSION['user'] = $user;

header("Location: /redirectUser.php");
exit;