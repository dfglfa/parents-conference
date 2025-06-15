<?php
require_once('Util.php');
require_once('dao/UserDAO.php');
require_once('dao/LogDAO.php');
require_once('config.php');

SessionContext::create();

class AuthenticationManager
{
  public static function authenticate($userName, $password)
  {
    $user = UserDAO::getUserForUserName($userName);
    
    if ($user != null) {
      LogDAO::log($user->getId(), LogDAO::LOG_ACTION_LOGIN, $userName);
    } else {
      error_log("User " . $userName . " not found in conference database.");
      return false;
    }

    $authenticated = false;
    global $LDAP_ENABLED;
    if ($LDAP_ENABLED) {
      $authenticated = self::authenticateLdap($userName, $password);
    }

    // Use local DB in case a) LDAP is not used or b) LDAP auth failed (fallback to local DB)
    if (!$authenticated) {
      $authenticated = password_verify($password, $user->getPasswordHash());
    }

    if ($authenticated) {
      $_SESSION['userId'] = $user->getId();
      $_SESSION['user'] = $user;
      return true;
    }

    return false;
  }

  public static function signOut()
  {
    if (self::isAuthenticated()) {
      $user = self::getAuthenticatedUser();
      LogDAO::log($user->getId(), LogDAO::LOG_ACTION_LOGOUT);
    }

    unset($_SESSION['userId']);
    unset($_SESSION['user']);
  }

  public static function isAuthenticated()
  {
    return isset($_SESSION['userId']);
  }

  public static function getAuthenticatedUser()
  {
    return self::isAuthenticated() ? $_SESSION['user'] : null;
  }

  public static function checkPrivilege($role)
  {
    if ((!self::isAuthenticated()) || (self::getAuthenticatedUser()->getRole() != $role)) {
      redirect('index.php');
    }
  }

  private static function authenticateLdap($userName, $password)
  {
    global $LDAP_HOST, $LDAP_BASE_DN, $LDAP_BIND_USER, $LDAP_BIND_USER_PWD;
    global $LDAP_USER_OBJECT_CLASS, $LDAP_USERID_REF;

    $ldap_conn = ldap_connect($LDAP_HOST);

    if (!$ldap_conn) {
      die("Could not connect to LDAP server.");
    }

    if (!ldap_bind($ldap_conn, $LDAP_BIND_USER, $LDAP_BIND_USER_PWD)) {
      return false;
    }

    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

    $username = ldap_escape($userName, "", LDAP_ESCAPE_FILTER);
    $filter = "(&(objectClass=$LDAP_USER_OBJECT_CLASS)($LDAP_USERID_REF=$username))";
    $search = @ldap_search($ldap_conn, $LDAP_BASE_DN, $filter);

    if (!$search || !ldap_count_entries($ldap_conn, $search) === 1) {
      return false;
    }

    $userEntry = ldap_first_entry($ldap_conn, $search);

    if (!$userEntry) {
      return false;
    }

    $dn = ldap_get_dn($ldap_conn, $userEntry);

    return @ldap_bind($ldap_conn, $dn, $password);
  }
}
