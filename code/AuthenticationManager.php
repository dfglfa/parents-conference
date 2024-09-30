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
			error_log("User " . $userName . " not found in conference database");
			return false;
		}

		global $LDAP_ENABLED;
		$authenticated = false;

		// The admin user is not looked up via LDAP
		if ($userName != "admin" && $LDAP_ENABLED) {
			$hashedPw = self::_getSSHAFromLDAP($user->getUserName());
			$authenticated = $hashedPw != null && self::ldap_verify_ssha($password, $hashedPw);
		} else {
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

	private static function _getSSHAFromLDAP($userName)
	{
		global $LDAP_HOST, $LDAP_DN, $LDAP_PASSWORD, $LDAP_BASE_DN;
		$ldap_conn = ldap_connect($LDAP_HOST);

		if (!$ldap_conn) {
			die("Could not connect to LDAP server.");
		}

		ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);  // Using LDAPv3
		ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);         // Disable referrals

		if (@ldap_bind($ldap_conn, $LDAP_DN, $LDAP_PASSWORD)) {
			//echo "LDAP bind successful.\n";

			$filter = "(uid=" . $userName . ")";
			$result = ldap_search($ldap_conn, $LDAP_BASE_DN, $filter);

			if ($result) {
				$entries = ldap_get_entries($ldap_conn, $result);
				if (count($entries) > 0) {
					return $entries[0]["userpassword"][0];
				} else {
					error_log("Password not found");
				}
			} else {
				error_log("User " . $userName . " not found in LDAP");
				return null;
			}
		} else {
			echo "LDAP bind failed.";
		}

		ldap_close($ldap_conn);
		return null;
	}

	private static function ldap_verify_ssha($plain_password, $ldap_ssha_hash)
	{
		$hash_base64 = substr($ldap_ssha_hash, 6);
		$hash_with_salt = base64_decode($hash_base64);

		// The actual SHA1 hash is the first 20 bytes, and the salt is the remaining bytes
		$hash = substr($hash_with_salt, 0, 20);
		$salt = substr($hash_with_salt, 20);

		// Recreate the hash by hashing the password + salt
		$computed_hash = sha1($plain_password . $salt, true);

		// Compare the computed hash with the stored hash
		return $computed_hash === $hash;
	}

}
