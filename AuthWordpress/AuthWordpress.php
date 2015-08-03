<?php
# AuthWordpress.php
# Authenticate MediaWiki users against WordPress

# To use this
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
# http://www.gnu.org/copyleft/gpl.html

require_once('class-phpass.php');  // Import the WordPress hashing class - Added by Jon Davis

if (!class_exists('AuthPlugin'))
{
  require_once "{$GLOBALS['IP']}/includes/AuthPlugin.php";
}

class AuthWordpress extends AuthPlugin {
	
	var $_AuthWordpressTablePrefix="wp_";
	var	$_AuthWordpressDBServer;
	var	$_AuthWordpressDBName;
	var	$_AuthWordpressUser;
	var	$_UseSeparateAuthWordpressDB = false;
	var	$_AuthWordpressPassword;
	var $_AuthWordpressDBconn = -1;
	
	function AuthWordpress () {
		global $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword;

		$this->_AuthWordpressDBServer=$wgDBserver;
		$this->_AuthWordpressDBName=$wgDBname;
		$this->_AuthWordpressUser=$wgDBuser;
		$this->_AuthWordpressPassword=$wgDBpassword;
	}

	function setAuthWordpressTablePrefix ( $prefix ) {
		$this->_AuthWordpressTablePrefix=$prefix;
	}

	function getAuthWordpressUserTableName () {
		
		return $this->_AuthWordpressTablePrefix."users";
	}

	function setAuthWordpressDBServer ($server) {
		$this->_UseSeparateAuthWordpressDB=true;
		$this->_AuthWordpressDBServer=$server;
	}

	function setAuthWordpressDBName ($dbname) {
		$this->_UseSeparateAuthWordpressDB=true;
		$this->_AuthWordpressDBName=$dbname;
	}

	function setAuthWordpressUser ($user) {
		$this->_UseSeparateAuthWordpressDB=true;
		$this->_AuthWordpressUser=$user;
	}

	function setAuthWordpressPassword ($password) {
		$this->_UseSeparateAuthWordpressDB=true;
		$this->_AuthWordpressPassword=$password;
	}

	function getAuthWordpressDB () {
		$params = array(
		    "host" => $this->_AuthWordpressDBServer,
		    "user" => $this->_AuthWordpressUser,
			"password" => $this->_AuthWordpressPassword,
			"dbname" => $this->_AuthWordpressDBName,
		);
		
		Return $this->getAuthWordpressDB = new DatabaseMysql($params); 
	}

	/* Interface documentation copied in from AuthPlugin */
	/**
	 * Check whether there exists a user account with the given name.
	 * The name will be normalized to MediaWiki's requirements, so
	 * you might need to munge it (for instance, for lowercase initial
	 * letters).
	 *
	 * @param string $username
	 * @return bool
	 * @access public
	 */
	function userExists( $username ) {
		$dbr =& $this->getAuthWordpressDB();

		/* I had to use query instead of selectrow because of prefix */
		$sql = "SELECT * FROM " . $this->getAuthWordpressUserTableName() . " WHERE user_login=\"" . $username . "\"";

		$res = $this->query($sql, "AuthWordpress::userExists" );

		if($res) {

			return true;
		} else {
			return false;
		}
	}
	

	function query($sql, $fname) {
		$dbr =& $this->getAuthWordpressDB();
		$res = $dbr->query( $sql, $fname );
		$obj = $dbr->fetchObject( $res );

		$dbr->freeResult( $res );
		return $obj;
	}
	/**
	 * Check if a username+password pair is a valid login.
	 * The name will be normalized to MediaWiki's requirements, so
	 * you might need to munge it (for instance, for lowercase initial
	 * letters).
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 * @access public
	 */
	function authenticate( $username, $password ) {
		$dbr =& $this->getAuthWordpressDB();

		$sql = "SELECT user_pass FROM " . $this->getAuthWordpressUserTableName() . 
				       " WHERE user_login=".$dbr->addQuotes($username);
		$res = $this->query($sql,
				       "AuthWordpress::authenticate" );
		
		// Create a hash object using the WordPress password hash class - Added by Jon Davis
		$wp_hasher = new PasswordHash(8, TRUE);
		
		// Original login code for older versions of WordPress (pre 2.5)
		// if( $res && ( $res->user_pass == MD5( $password ))) {
			
		// New authentication test using the PasswordHash object $wp_hasher - Added by Jon Davis
		if( $res && $wp_hasher->CheckPassword($password, $res->user_pass)) {
			return true;
		} else {
			return false;
		}
	}
		    
	
	/**
	 * Modify options in the login template.
	 *
	 * @param UserLoginTemplate $template
	 * @access public
	 */
	function modifyUITemplate( &$template ) {
		$template->set( 'usedomain', false );
		$template->set( 'useemail', false );
		$template->set( 'create', false );
	}

	/**
	 * Set the domain this plugin is supposed to use when authenticating.
	 *
	 * @param string $domain
	 * @access public
	 */
	function setDomain( $domain ) {
		$this->domain = $domain;
	}

	/**
	 * Check to see if the specific domain is a valid domain.
	 *
	 * @param string $domain
	 * @return bool
	 * @access public
	 */
	function validDomain( $domain ) {
		# Override this!
		return true;
	}

	/**
	 * When a user logs in, optionally fill in preferences and such.
	 * For instance, you might pull the email address or real name from the
	 * external user database.
	 *
	 * The User object is passed by reference so it can be modified; don't
	 * forget the & on your function declaration.
	 *
	 * @param User $user
	 * @access public
	 */
	function updateUser( &$user ) {

		$dbr =& $this->getAuthWordpressDB();
		
		$res = $this->query("SELECT user_nicename, user_email FROM " . $this->getAuthWordpressUserTableName() . 
				       " WHERE user_login=".
				         $dbr->addQuotes($user->mName),
				       "AuthWordpress::updateUser" );
		
		if($res) {
			$user->setEmail( $res->user_email );
			$user->setRealName( $res->user_nicename );
		}

		return true;
	}


	/**
	 * Return true if the wiki should create a new local account automatically
	 * when asked to login a user who doesn't exist locally but does in the
	 * external auth database.
	 *
	 * If you don't automatically create accounts, you must still create
	 * accounts in some way. It's not possible to authenticate without
	 * a local account.
	 *
	 * This is just a question, and shouldn't perform any actions.
	 *
	 * @return bool
	 * @access public
	 */
	function autoCreate() {
		return true;
	}
	
	/**
	 * Set the given password in the authentication database.
	 * Return true if successful.
	 *
	 * @param string $password
	 * @return bool
	 * @access public
	 */
	function setPassword( $password ) {
		# we probably don't want users using MW to change password
		
		// Changed to return true so that WordPress changes MediaWiki passwords
		// This is confusing because the AuthPlugin documentation here
		// makes it sound like it will be changing the external authentication
		// database (WordPress) rather than the MediaWiki authentication db
		// Added by Jon Davis
		
		return true;
	}

	/**
	 * Update user information in the external authentication database.
	 * Return true if successful.
	 *
	 * @param User $user
	 * @return bool
	 * @access public
	 */
	function updateExternalDB( $user ) {
		# we probably don't want users using MW to change other stuff
		return false;
	}

	/**
	 * Add a user to the external authentication database.
	 * Return true if successful.
	 *
	 * @param User $user
	 * @param string $password
	 * @return bool
	 * @access public
	 */
	function addUser( $user, $password ) {
		# disabling
		return false;
	}


	/**
	 * Return true to prevent logins that don't authenticate here from being
	 * checked against the local database's password fields.
	 *
	 * This is just a question, and shouldn't perform any actions.
	 *
	 * @return bool
	 * @access public
	 */
	function strict() {
		return true;
	}

	/**
	 * Check to see if external accounts can be created.
	 * Return true if external accounts can be created.
	 * @return bool
	 * @access public
	 */
	function canCreateAccounts() {
		return false;
	}

	
	/**
	 * When creating a user account, optionally fill in preferences and such.
	 * For instance, you might pull the email address or real name from the
	 * external user database.
	 *
	 * The User object is passed by reference so it can be modified; don't
	 * forget the & on your function declaration.
	 *
	 * @param User $user
	 * @access public
	 */
	function initUser( &$user ) {
		/* User's email is already authenticated, because:
		 * A.  They have valid bbPress account
		 * B.  bbPress emailed them the password
		 * C.  They are logged in (presumably using that password
		 * If something changes about the bbPress email verification,
		 * then this function might need changing, too
		 */
		$user->mEmailAuthenticated = wfTimestampNow();

		/* Everything else is in updateUser */
		$this->updateUser( $user );
	}

	/**
	 * If you want to munge the case of an account name before the final
	 * check, now is your chance.
	 */


	function getCanonicalName ( $username ) {

		// connecting to MediaWiki database for this check 		
		$dbr =& wfGetDB( DB_SLAVE );
		
		$res = $dbr->selectRow('user',
				       array("user_name"),
				       "lower(user_name)=lower(".
				         $dbr->addQuotes($username).")",
				       "AuthWordpress::getCanonicalName" );
		
		if($res) {
			return $res->user_name;
		} else {
			return $username;
		}
	}
}
?>
