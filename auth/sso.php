<?php

use Jumbojett\OpenIDConnectClient;

ob_start();

if ($KEYCLOAK_ENABLED) {
  $oidc = new OpenIDConnectClient(
    $KEYCLOAK_REALM_BASE_URL,
    $KEYCLOAK_CLIENT_ID,
    $KEYCLOAK_CLIENT_SECRET
  );
  $oidc->setRedirectURL($KEYCLOAK_REDIRECT_URL);
  $oidc->authenticate();
}
