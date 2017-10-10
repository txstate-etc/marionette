<?php
/**
 * dblayer.php
 * @package phpmanage
 */

/**
 * Database Layer
 *
 * This class will be responsible for all SQL in the application.
 *
 * It requires the database class provided with phpWebObjects.
 * @package phpmanage
 */
class db_layer {
	private static $db;
	private static $settings; // settings cache for system configuration variables
	private static $cmp;
	private static $cmptype;
	private static $sdata; // session data cache for current user
	private static $cache;
	public static $foundrows;

	/**
	 * Open the database connection
	 *
	 * @return void
	 * @access private
	 */
	public static function init() {
		self::$db = new db();
		if (self::$db->error()) exit;
	}

	/**
	 * Check for needed database upgrades / initialization
	 *
	 * This function is solely responsible for making structural changes
	 * to the database to support new features and such.  It's called at
	 * the beginning of every page load.
	 *
	 * Every time you need to make a structural change to the databse
	 * for a commit, you must create a new block of code here with
	 * a new version number.  When you're done with your SQL commands,
	 * make sure you upgrade the "db_version" setting to your new
	 * version number.
	 *
	 * @return bool
	 */
	public static function maintain_db() {
		$db = self::$db;
		$success = TRUE;
		if (self::setting('db_version') < 1) {
			// initialize the database
			if ($success) $success =
			$db->execute("CREATE TABLE attachments (
							id int(10) unsigned NOT NULL auto_increment,
							uploaded datetime NOT NULL,
							uploadedby mediumint(8) unsigned NOT NULL,
							filename tinytext,
							`data` longblob,
							PRIMARY KEY  (id)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE classification (
							id smallint(5) unsigned NOT NULL auto_increment,
							`name` tinytext,
							disporder tinyint(3) unsigned NOT NULL DEFAULT 0,
							deleted tinyint(3) unsigned NOT NULL DEFAULT 0,
							PRIMARY KEY  (id),
							KEY disporder (disporder),
							KEY deleted (deleted)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE comments (
							id int(10) unsigned NOT NULL auto_increment,
							projectid mediumint(8) unsigned NOT NULL,
							commenter mediumint(8) unsigned NOT NULL,
							`comment` text,
							PRIMARY KEY  (id)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE flexibility (
							id smallint(5) unsigned NOT NULL auto_increment,
							`name` tinytext,
							disporder tinyint(3) unsigned NOT NULL DEFAULT 0,
							deleted tinyint(3) unsigned NOT NULL DEFAULT 0,
							PRIMARY KEY  (id),
							KEY disporder (disporder),
							KEY deleted (deleted)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE groups (
							id smallint(5) unsigned NOT NULL auto_increment,
							`name` tinytext,
							PRIMARY KEY  (id)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE groups_users (
							groupid smallint(5) unsigned NOT NULL,
							userid mediumint(8) unsigned NOT NULL,
							PRIMARY KEY  (groupid,userid),
							KEY userid (userid)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE permissions (
							entityid mediumint(8) unsigned NOT NULL,
							entitytype enum('user','group') NOT NULL,
							projectid int(10) unsigned NOT NULL DEFAULT 0,
							sysadmin tinyint(3) unsigned NOT NULL DEFAULT 0,
							viewpublished tinyint(3) unsigned NOT NULL DEFAULT 0,
							viewcurrent tinyint(3) unsigned NOT NULL DEFAULT 0,
							editcurrent tinyint(3) unsigned NOT NULL DEFAULT 0,
							publish tinyint(3) unsigned NOT NULL DEFAULT 0,
							PRIMARY KEY  (entitytype,entityid),
							KEY projectid (projectid)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE phases (
							id tinyint(3) unsigned NOT NULL auto_increment,
							`name` tinytext,
							disporder tinyint(3) unsigned NOT NULL DEFAULT 0,
							deleted tinyint(3) unsigned NOT NULL DEFAULT 0,
							PRIMARY KEY  (id),
							KEY disporder (disporder),
							KEY deleted (deleted)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE projects (
							id mediumint(8) unsigned NOT NULL auto_increment,
							deleted tinyint(3) unsigned NOT NULL DEFAULT 0,
							publishof mediumint(8) unsigned NOT NULL,
							createdby mediumint(8) unsigned NOT NULL,
							created datetime NOT NULL,
							modifiedby mediumint(8) unsigned NOT NULL,
							modified datetime NOT NULL,
							completedby mediumint(8) unsigned NOT NULL,
							completed datetime NOT NULL,
							manager mediumint(8) unsigned NOT NULL,
							identify tinytext,
							`name` tinytext,
							goal text,
							classification smallint(5) unsigned NOT NULL,
							unit smallint(5) unsigned NOT NULL,
							`start` date NOT NULL,
							target date NOT NULL,
							priority char(1) NOT NULL,
							phaseid tinyint(3) unsigned NOT NULL DEFAULT 0,
							phasefree tinytext,
							activity tinytext,
							scopetraits mediumint(8) unsigned NOT NULL,
							scheduletraits mediumint(8) unsigned NOT NULL,
							resourcetraits mediumint(8) unsigned NOT NULL,
							qualitytraits mediumint(8) unsigned NOT NULL,
							overalltraits mediumint(8) unsigned NOT NULL,
							`comment` text,
							PRIMARY KEY (id),
							KEY manager (manager),
							KEY ispublish (publishof),
							KEY created (created),
							KEY identify (identify(15)),
							KEY deleted (deleted)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE projects_attachments (
							projectid int(10) unsigned NOT NULL,
							attachid int(10) unsigned NOT NULL,
							PRIMARY KEY (projectid,attachid)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE sessions (
							sessid int(10) unsigned NOT NULL auto_increment,
							sesskey tinytext,
							securekey tinytext,
							ipaddr int(10) unsigned NOT NULL,
							userid mediumint(8) unsigned NOT NULL DEFAULT 0,
							created datetime NOT NULL,
							accessed datetime NOT NULL,
							PRIMARY KEY (sessid),
							UNIQUE KEY sesskey (sesskey(12)),
							UNIQUE KEY securekey (securekey(12))
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE settings (
							`name` tinytext,
							`value` tinytext,
							PRIMARY KEY (`name`(15))
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE `status` (
							id smallint(5) unsigned NOT NULL auto_increment,
							`name` tinytext,
							disporder tinyint(3) unsigned NOT NULL DEFAULT 0,
							deleted tinyint(3) unsigned NOT NULL DEFAULT 0,
							PRIMARY KEY (id),
							KEY disporder (disporder),
							KEY deleted (deleted)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE traits (
							id int(10) unsigned NOT NULL auto_increment,
							flexibility tinyint(3) unsigned NOT NULL DEFAULT 0,
							`status` tinyint(3) unsigned NOT NULL DEFAULT 0,
							trend tinyint(3) unsigned NOT NULL DEFAULT 0,
							risk tinytext,
							mitigation tinytext,
							PRIMARY KEY (id)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE trend (
							id smallint(5) unsigned NOT NULL auto_increment,
							`name` tinytext,
							disporder tinyint(3) unsigned NOT NULL DEFAULT 0,
							deleted tinyint(3) unsigned NOT NULL DEFAULT 0,
							PRIMARY KEY (id),
							KEY disporder (disporder),
							KEY deleted (deleted)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE units (
							id tinyint(3) unsigned NOT NULL auto_increment,
							parent tinyint(3) unsigned NOT NULL DEFAULT 0,
							disporder tinyint(3) unsigned NOT NULL DEFAULT 0,
							`name` tinytext,
							abbrev tinytext,
							deleted tinyint(3) unsigned NOT NULL DEFAULT 0,
							PRIMARY KEY  (id),
							KEY parent (parent,disporder),
							KEY deleted (deleted)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			if ($success) $success =
			$db->execute("CREATE TABLE users (
							userid mediumint(8) unsigned NOT NULL auto_increment,
							username tinytext,
							passwd tinytext,
							lastname tinytext,
							firstname tinytext,
							manager tinyint(3) unsigned NOT NULL DEFAULT 0,
							PRIMARY KEY  (userid),
							UNIQUE KEY netid (username(15)),
							KEY manager (manager)
						  ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 DEFAULT COLLATE utf8_general_ci");
			// create the guest and initial admin users
			if ($success) $success = $db->execute("INSERT INTO users (username, passwd, lastname) VALUES (?,MD5(?),?),(?,MD5(?),?)",
				'guest', 'guest', 'Guest User',
				'admin', 'admin', 'Admin User');
			if ($success) $success = $db->execute("INSERT INTO permissions (entityid, entitytype, sysadmin) VALUES (?,?,?), (?,?,?)",
			  1, 'user', 0,
			  2, 'user', 1);
			// flag the database as initialized
			if ($success) self::setting_set('db_version', 1);
		}
		if ($success && self::setting('db_version') == 1) {
			$success = $db->execute("ALTER TABLE `units` ADD INDEX (`abbrev` (12))");
			if ($success) self::setting_set('db_version', 2);
		}
		if ($success && self::setting('db_version') == 2) {
			$success = $db->execute("ALTER TABLE `sessions` ADD `caslogin` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `userid` ");
			if ($success) self::setting_set('db_version', 3);
		}
		if ($success && self::setting('db_version') == 3) {
			$success = $db->execute("ALTER TABLE `users` ADD `deleted` TINYINT UNSIGNED NOT NULL DEFAULT 0");
			if ($success) self::setting_set('db_version', 4);
		}
		if ($success && self::setting('db_version') == 4) {
			// support for saving name/value pairs to the user session
			$success = $db->execute("CREATE TABLE sessions_data (
												`sessid` INT UNSIGNED NOT NULL ,
												`name` VARCHAR( 50 ) NOT NULL DEFAULT '',
												`value` tinytext,
												PRIMARY KEY ( `sessid` , `name` )
												) ENGINE = MyISAM DEFAULT CHARSET=latin1");
			if ($success) self::setting_set('db_version', 5);
		}
		if ($success && self::setting('db_version') == 5) {
			// filtered project list support
			$success = $db->execute("CREATE TABLE filters_data (
											`filterid` INT UNSIGNED NOT NULL ,
											`type` tinytext ,
											`field` tinytext ,
											`control` tinytext ,
											`val` tinytext ,
											`disporder` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
											KEY `filterid` ( `filterid`, `disporder` )
											) ENGINE = MyISAM DEFAULT CHARSET=latin1");
			if ($success) $success = $db->execute("CREATE TABLE filters (
											`id` INT UNSIGNED NOT NULL auto_increment,
											`wherecache` text,
											PRIMARY KEY ( `id` )
											) ENGINE = MyISAM DEFAULT CHARSET=latin1");
			if ($success) $success = $db->execute("ALTER TABLE `users` ADD `currentfilter` INT UNSIGNED NOT NULL AFTER `manager`");
			if ($success) self::setting_set('db_version', 6);
		}
		if ($success && self::setting('db_version') == 6) {
			// program manager support
			$success = $db->execute("ALTER TABLE `units` ADD `manager` MEDIUMINT UNSIGNED NOT NULL AFTER `abbrev`");
			if ($success) $success = $db->execute("ALTER TABLE `units` ADD INDEX ( `manager` )");
			if ($success) $success = $db->execute("ALTER TABLE `users` ADD `progman` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `manager`");
			if ($success) $success = $db->execute("ALTER TABLE `users` ADD INDEX ( `progman` )");
			if ($success) self::setting_set('db_version', 7);
		}
		if ($success && self::setting('db_version') == 7) {
			// project completion support
			$success = $db->execute("ALTER TABLE `phases` ADD `complete` ENUM('pending','complete') NOT NULL AFTER `disporder`");
			if ($success) $success = $db->execute("ALTER TABLE `phases` ADD INDEX ( `complete` )");
			if ($success) $success = $db->execute("UPDATE phases SET complete=''");
			$disp = $db->get("SELECT MAX(disporder) FROM phases WHERE NOT deleted") ?: 0;
			if ($success) $success = $db->execute("INSERT INTO `phases` (name, disporder, complete) VALUES ('Completion Request',?,'pending'),('Completed',?,'complete')", $disp, $disp+1);
			if ($success) self::setting_set('db_version', 8);
		}
		if ($success && self::setting('db_version') == 8) {
			// support for collection of links associated with each project
			$success = $db->execute("CREATE TABLE links (
											`id` INT UNSIGNED NOT NULL auto_increment,
											`href` tinytext ,
											`title` tinytext ,
											`added` DATETIME NOT NULL ,
											`addedby` MEDIUMINT UNSIGNED NOT NULL ,
											PRIMARY KEY ( `id` )
											) ENGINE = MyISAM DEFAULT CHARSET=latin1");
			if ($success) $success = $db->execute("CREATE TABLE projects_links (
											`linkid` INT UNSIGNED NOT NULL ,
											`projectid` INT UNSIGNED NOT NULL ,
											PRIMARY KEY ( `linkid`,`projectid` )
											) ENGINE = MyISAM DEFAULT CHARSET=latin1");
			if ($success) self::setting_set('db_version', 9);
		}
		if ($success && self::setting('db_version') == 9) {
			$success = $db->execute("ALTER TABLE projects ADD commentdate DATETIME NOT NULL AFTER comment");
			if ($success) $success = $db->execute("ALTER TABLE projects ADD activitydate DATETIME NOT NULL AFTER activity");
			if ($success) self::setting_set('db_version', 10);
		}
		if ($success && self::setting('db_version') == 10) {
			$success = $db->execute("ALTER TABLE projects ADD master INT UNSIGNED NOT NULL AFTER completed");
			if ($success) $success = $db->execute("CREATE TABLE masters (
											`id` INT UNSIGNED NOT NULL auto_increment,
											`name` tinytext,
											`disporder` TINYINT UNSIGNED NOT NULL,
											`deleted` TINYINT UNSIGNED NOT NULL,
											PRIMARY KEY ( `id` )
											) ENGINE = MyISAM DEFAULT CHARSET=latin1");
			if ($success) self::setting_set('db_version', 11);
		}
		if ($success && self::setting('db_version') == 11) {
			$success = $db->execute("CREATE TABLE project_subscribe (
											`projectid` MEDIUMINT UNSIGNED NOT NULL,
											`userid` MEDIUMINT UNSIGNED NOT NULL,
											`flag` TINYINT NOT NULL,
											PRIMARY KEY ( `projectid`, `userid` )
											) ENGINE = MyISAM DEFAULT CHARSET=latin1");
			if ($success) self::setting_set('db_version', 12);
		}
		if ($success && self::setting('db_version') == 12) {
			$success = $db->execute("ALTER TABLE `phases` CHANGE `complete` `complete` ENUM( '', 'pending', 'complete' ) NOT NULL");
			if ($success) self::setting_set('db_version', 13);
		}
		if ($success && self::setting('db_version') == 13) {
			$success = $db->execute("CREATE TABLE help (
											id SMALLINT UNSIGNED NOT NULL auto_increment,
											helpkey tinytext,
											helptext text,
											PRIMARY KEY ( id ),
											KEY helpkey (helpkey(15))
											) ENGINE = MyISAM DEFAULT CHARSET=latin1");
			if ($success) self::setting_set('db_version', 14);
		}
		if ($success && self::setting('db_version') == 14) {
			$success = $db->execute("ALTER TABLE comments ADD created DATETIME NOT NULL AFTER projectid");
			if ($success) self::setting_set('db_version', 15);
		}
		if ($success && self::setting('db_version') == 15) {
			$success = $db->execute("ALTER TABLE users ADD unitid TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER firstname");
			if ($success) self::setting_set('db_version', 16);
		}
		if ($success && self::setting('db_version') == 16) {
			$success = $db->execute("ALTER TABLE permissions ADD createproject TINYINT UNSIGNED NOT NULL AFTER sysadmin");
			if ($success) self::setting_set('db_version', 17);
		}
		if ($success && self::setting('db_version') == 17) {
			$success = $db->execute("ALTER TABLE permissions ADD addcomment TINYINT UNSIGNED NOT NULL AFTER publish");
			if ($success) self::setting_set('db_version', 18);
		}
		return $success;
	}

	public static function db_upkeep() {
		$db = self::$db;
		$db->execute("OPTIMIZE TABLE filters_data");
	}

	/**
	 * Check a session key to find information about the user
	 *
	 * Use this method to check a session key obtained from the client browser
	 * and match it up with a current session.  It will also tag the session and set
	 * the last access time to now.
	 *
	 * It can be configured by sending <b>optional</b> parameters in the $opt array with this spec:
	 * <pre>array(
	 *   'secure'   => boolean check the secure key instead of the normal key
	 *   'created'  => integer number of hours from creation until this session expires
	 *   'accessed' => integer number of minutes from creation until this session expires
	 *   'ipaddr'   => string IP address that must match the one that created the session
	 * )</pre>
	 *
	 * It will return an array with the following spec:
	 * <pre>array(
	 *   'sessid'    => integer identifier for the session, used internally for database efficiency
	 *   'sid'       => string identifier that we use to communicate with the client
	 *   'securesid' => string identifier that we use for secure communications with the client
	 *   'userid'    => integer identifier for the user
	 *   'login'     => string identifier for the user, they type this to log into the system
	 * )</pre>
	 * @param string $sid
	 * @param array $opt
	 * @return array
	 */
	public static function session_check($sid, $opt = array()) {
		$db = self::$db;

		// deal with secure/nonsecure
		$column = $opt['secure'] ? 's.securekey' : 's.sesskey';

		// deal with the extra options
		$extrabind = array();
		if ($opt['accessed']) $extra = ' AND NOW() - INTERVAL '.$opt['accessed'].' MINUTE > accessed';
		if ($opt['created']) $extra .= ' AND NOW() - INTERVAL '.$opt['created'].' HOUR > created';
		if ($opt['ipaddr']) {
			$extra .= ' AND ipaddr=INET_ATON(?)';
			$extrabind[] = $opt['ipaddr'];
		}

		// make the query
		$row = $db->getrow("SELECT s.sessid, s.sesskey AS sid, s.securekey AS securesid, s.userid, s.caslogin, u.username AS login
			FROM sessions s LEFT JOIN users u USING (userid) WHERE $column = ? $extra", $sid, $extrabind);

		// if it succeeded, update the last access time on the session
		if (!empty($row)) $db->execute("UPDATE sessions SET accessed=NOW() WHERE sessid=?", $row['sessid']);
		return $row;
	}

	/**
	 * Create a new session
	 *
	 * This will return the same array as {@link session_check()} will.  Requires
	 * an IP address to record with the session.
	 *
	 * @param string $ipaddr
	 * @return array
	 */
	public static function session_create($ipaddr) {
		$db = self::$db;
		$ret = array(
			'sid' => generatestring(12),
			'securesid' => generatestring(12),
			'userid' => 0,
			'login' => ''
		);
		$db->execute("INSERT INTO sessions (sesskey, securekey, ipaddr, created, accessed)
			VALUES (?,?,INET_ATON(?), NOW(), NOW())", $ret['sid'], $ret['securesid'], $ipaddr);
		$ret['sessid'] = $db->insertid();
		return $ret;
	}

	/**
	 * Destroy a user session
	 *
	 * Completely deletes a session from the database, requires the string key
	 *
	 * @param string $sesskey
	 * @return bool
	 */
	public static function session_destroy($sesskey) {
		$db = self::$db;
		$sessid = $db->get("SELECT sessid FROM sessions WHERE sesskey=?", $sesskey);
		$success = $db->execute("DELETE FROM sessions_data WHERE sessid=?", $sessid);
		return $success && $db->execute("DELETE FROM sessions WHERE sesskey=?", $sesskey);
	}

	/**
	 * Store a name/value pair to a user's session
	 *
	 * Note that $name should be limited to 50 chars
	 *
	 * @param int $sessid
	 * @param string $name
	 * @param string $val
	 * @return bool
	 */
	public static function session_store($sessid, $name, $value) {
		$db = self::$db;
		$exists = $db->get("SELECT 1 FROM sessions_data WHERE sessid=? AND name=?", $sessid, $name);
		if ($exists) {
			$success = $db->execute("UPDATE sessions_data SET value=? WHERE sessid=? AND name=?", $value, $sessid, $name);
			if ($success) $success = (bool) $db->rows();
		} else {
			$success = $db->execute("INSERT INTO sessions_data (sessid, name, value) VALUES (?,?,?)", $sessid, $name, $value);
		}
		if ($success) self::$sdata[$name] = $value;
		return $success;
	}

	/**
	 * Retrieve the value of a name/value pair from a user's session
	 *
	 * Note that $name should be limited to 50 chars
	 *
	 * @param int $sessid
	 * @param string $name
	 * @return string
	 */
	public static function session_grab($sessid, $name) {
		$db = self::$db;
		if (self::$sdata[$name]) return self::$sdata[$name];
		$value = $db->get("SELECT value FROM sessions_data WHERE sessid=? AND name=?", $sessid, $name);
		self::$sdata[$name] = $value;
		return $value;
	}


	/**
	 * Retrieve a system setting
	 *
	 * System settings are in name/value pairs in the database.
	 *
	 * Subsequent calls for the same setting will not access the database
	 * again.  They'll be pulled from local memory.  As long as you use
	 * db_layer::setting_set(), this should stay in synch.
	 *
	 * @param string $name
	 * @return string
	 */
	public static function setting ($name = 'ALL') {
		$db = self::$db;

		if ($name == 'ALL') {
			$ret = $db->getall("SELECT * FROM settings");
			foreach ($ret as $row) self::$settings[$row['name']] = $row['value'];
			return self::$settings;
		}
		if ($name != 'nextident' && isset(self::$settings[$name])) $ret = self::$settings[$name];
		else {
		  // if this is the first run of the software, it's going to ask for the
		  // db_version setting before the settings table has been created - there
		  // shouldn't be any harm in returning an empty string after an error anyway
		  $printerror = $db->print_error(FALSE);
			$raiseerror = $db->raise_error(FALSE);
			$ret = $db->get("SELECT value FROM settings WHERE name=? LIMIT 1", $name);
			$db->print_error($printerror);
			$db->raise_error($raiseerror);
			if ($db->error()) return '';
			self::$settings[$name] = $ret;
		}
		if ($name == 'nextident') {
			if (!$ret) $db->execute("INSERT INTO settings (name, value) VALUES ('nextident', 0)");
			$db->execute("UPDATE settings SET value=value+1 WHERE name=?", $name);
		}

		// here we can define defaults for settings that have never been set
		if ($name == 'pl_perpage' && !$ret) $ret = 20;
		return $ret;
	}

	/**
	 * Set a system setting
	 *
	 * Use this method to assign a name/value pair in the settings table.
	 *
	 * It will create an entry if none exists, or overwrite as appropriate.
	 *
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public static function setting_set ($name, $value) {
		$db = self::$db;
		$exists = $db->get("SELECT 1 FROM settings WHERE name=?", $name);
		if ($exists) $db->execute("UPDATE settings SET value=? WHERE name=?", $value, $name);
		else $db->execute("INSERT INTO settings (name, value) VALUES (?, ?)", $name, $value);
		if (!$db->error()) self::$settings[$name] = $value;
		return;
	}

	/**
	 * Check User Credentials
	 *
	 * Checks user credentials from the login form against the database.
	 *
	 * Can also check an LDAP server, if there are system settings for
	 * 'ldap_server' and optionally 'ldap_port' and 'ldap_userstring'.
	 *
	 * 'ldap_userstring' should have '%netid%' somewhere inside it, where the
	 * login will be placed.
	 *
	 * @param string $login
	 * @param string $password
	 * @return bool
	 */
	public static function user_checklogin($login, $password) {
		$db = self::$db;
		if ($password && $db->get("SELECT userid FROM users WHERE username=? and passwd=MD5(?) AND NOT deleted", $login, $password))
			return 1;
		if ($server = self::setting('ldap_server')) {
			$level = error_reporting(1);
			$port = self::setting('ldap_port');
			if (!$port) $port = 389;
			$userstring = self::setting('ldap_userstring');
			if (!$userstring) $userstring = $login;
			else $userstring = preg_replace('/%netid%/', $login, $userstring);
			$ldap = ldap_connect($server, $port);
			if (ldap_bind($ldap, $userstring, $password)) {
				ldap_unbind($ldap);
				return 1;
			}
			error_reporting($level);
		}
		return 0;
	}

	/**
	 * Return all the attachments on a given project (or publish)
	 *
	 * @param int $projectid
	 * @return array
	 */
	public static function project_attach($projectid) {
		$db = self::$db;
		return $db->getall("SELECT a.id, a.filename FROM attachments a, projects_attachments p
							WHERE a.id=p.attachid AND p.projectid=? ORDER BY a.id", $projectid);
	}

	/**
	 * Get detailed info and binary data for an attachment
	 *
	 * @param int $attid
	 * @return array
	 */
	public static function attach_get($attid) {
		$db = self::$db;
		return $db->getrow("SELECT * FROM attachments WHERE id=?", $attid);
	}

	/**
	 * Add an attachment to a project (or publish)
	 *
	 * If $info['id'] is set, it'll be linked to the project, otherwise
	 * a new attachment will be inserted and then linked to the project.
	 *
	 * @param int $projectid
	 * @param array $info
	 * @return bool
	 */
	public static function attach_add($projectid, $info = array()) {
		$db = self::$db;
		$success = TRUE;
		if (!$info['id']) {
			if (!$info['userid']) {
				$user = doc::getuser();
				if ($user instanceof user) $info['userid'] = $user->userid();
			}
			$success = $db->execute("INSERT INTO attachments (filename, data, uploaded, uploadedby) VALUES (?,?,NOW(),?)",
				$info['filename'], $info['data'], $info['userid']);
			$info['id'] = $db->insertid();
		}
		if ($success) $success = $db->execute("INSERT INTO projects_attachments (projectid, attachid) VALUES (?,?)", $projectid, $info['id']);
		return $success;
	}

	/**
	 * Remove an attachment from a project
	 *
	 * Simply removes the link to the attachment from the current version of the project, the
	 * attachment will remain in order to preserve the integrity of past publishes.
	 *
	 * If no publishes are attached to the image, it is fully deleted.
	 *
	 * @param int $attid
	 * @param int $projid
	 * @return void
	 */
	public static function attach_del($attid, $projid) {
		$db = self::$db;

		// let's make double-secret-sure that this is NOT a publish
		$projid = self::project_master_id($projid);

		// unlink attachment from project
		$db->execute("DELETE FROM projects_attachments WHERE attachid=? AND projectid=?", $attid, $projid);

		// check to see if the attachment has any remaining references, if not, purge it
		if (!$db->get("SELECT COUNT(*) FROM projects_attachments WHERE attachid=?", $attid)) {
			$db->execute("DELETE FROM attachments WHERE id=?", $attid);
		}
	}

	/**
	 * Return all the links associated with a given project (or publish)
	 *
	 * @param int $projectid
	 * @return array
	 */
	public static function project_links($projectid) {
		$db = self::$db;
		return $db->getall("SELECT l.id, l.href, l.title FROM links l, projects_links p
							WHERE l.id=p.linkid AND p.projectid=?", $projectid);
	}

	/**
	 * Get detailed info for a link
	 *
	 * @param int $linkid
	 * @return array
	 */
	public static function link_get($linkid) {
		$db = self::$db;
		return $db->getrow("SELECT * FROM links WHERE id=?", $attid);
	}

	/**
	 * Add a link to a project (or publish)
	 *
	 * If $info['id'] is set, it'll be linked to the project, otherwise
	 * a new link will be inserted and then associated with the project.
	 *
	 * @param int $projectid
	 * @param array $info
	 * @return bool
	 */
	public static function link_add($projectid, $info = array()) {
		$db = self::$db;
		$success = TRUE;
		if (!$info['id']) {
			if (!$info['userid']) {
				$user = doc::getuser();
				if ($user instanceof user) $info['userid'] = $user->userid();
			}
			$success = $db->execute("INSERT INTO links (href, title, added, addedby) VALUES (?,?,NOW(),?)",
				$info['href'], $info['title'], $info['userid']);
			$info['id'] = $db->insertid();
		}
		if ($success) $success = $db->execute("INSERT INTO projects_links (projectid, linkid) VALUES (?,?)", $projectid, $info['id']);
		return $success;
	}

/**
	 * Add a link to a project, or update existing one if it exists
	 * This function primarily used for Static Link Fields
	 *
	 * If $info['id'] is set, it'll be linked to the project, otherwise
	 * a new link will be inserted and then associated with the project.
	 *
	 * @param int $projectid
	 * @param array $info
	 * @return bool
	 */
	public static function link_insertupdate($projectid, $info = array())
	{
		$db = self::$db;

		if (!$info['id']){
			$existingId = $db->get("SELECT id 
				FROM links 
				INNER JOIN projects_links ON links.id = projects_links.linkid 
				WHERE projectid=? AND title=?", $projectid, $info['tite']);

			if(!$existingId){
				return self::link_add($projectid, $info);
			}

			$info['id'] = $existingId;
		}
		$success = $db->execute("UPDATE links SET href=? WHERE id=?", $info['href'], $info['id']);
		return $success;
	}

	/**
	 * Remove a link from a project
	 *
	 * Simply removes the link from the current version of the project, the
	 * actual link record will remain in order to preserve the integrity of past publishes.
	 *
	 * If no publishes are associated with the link, it is fully deleted.
	 *
	 * @param int $linkid
	 * @param int $projid
	 * @return void
	 */
	public static function link_del($linkid, $projid) {
		$db = self::$db;

		// let's make double-secret-sure that this is NOT a publish
		$publishof = $db->get("SELECT publishof FROM projects WHERE id=?", $projid);
		if ($publishof) $projid = $publishof;

		// unlink attachment from project
		$db->execute("DELETE FROM projects_links WHERE linkid=? AND projectid=?", $linkid, $projid);

		// check to see if the attachment has any remaining references, if not, purge it
		if (!$db->get("SELECT COUNT(*) FROM projects_links WHERE linkid=?", $linkid)) {
			$db->execute("DELETE FROM links WHERE id=?", $linkid);
		}
	}

	/**
	 * Retrieve an array of projects
	 *
	 * This is the master project retrieval function. project_get() depends on this
	 * for results.
	 *
	 * There are a number of filters available:
	 * <pre>array(
	 *   'id' => id of the project to retrieve
	 *   'latestpublish' => only retrieve the latest publish of each project
	 *     'manager_show_current' => userid of current user, so their projects
	 *                               can be retrieved in unpublished form
	 *   'publishof' => retrieve publishes of this project id
	 *   'manager' => userid of a manager, retrieves all their projects (not publishes)
	 *   'sort' => array of columns to sort, each column should be an array containing
	 *            the column name and 'asc' or 'desc', so example input here
	 *            would be: array(array('columnname','asc'));
	 * )</pre>
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function project_getmany($filters = array()) {
		$db = self::$db;
		$extrabind = array();

		// logic for fetching a single project's latest publish
		if ($filters['id'] && !$filters['latestpublish']) { $extra .= ' AND p.id=?'; $extrabind[] = $filters['id']; }
		elseif ($filters['id'] && $filters['latestpublish']) {
			if ($parent = $db->get("SELECT publishof FROM projects WHERE id=?", $filters['id'])) $filters['publishof'] = $parent;
			elseif (self::project_haspublishes($filters['id'])) $filters['publishof'] = $filters['id'];
			else { $extra .= ' AND p.id=?'; $extrabind[] = $filters['id']; }
		}

		// use a set of filtering rules identified by an ID
		if ($filters['filterid']) {
			list($filtquery, $filtbind) = self::filter_query($filters['filterid']);
			if ($filtquery) {
				$filtjoin = 'INNER JOIN ( '.$filtquery.' ) f ON f.id=p.id';
				$extrabind += $filtbind;
			}
		}
		else if ($filters['filter_rules']) {
			list($filtquery, $filtbind) = self::filter_query_from_rules($filters['filter_rules']);
			if ($filtquery) 
			{
				$filtjoin = 'INNER JOIN ( '.$filtquery.' ) f ON f.id=p.id';
				$extrabind += $filtbind;
			}
		}

		if ($filters['publishof']) { $extra .= ' AND p.publishof=?'; $extrabind[] = $filters['publishof']; }
		if ($filters['manager']) { $extra .= ' AND p.manager=?'; $extrabind[] = $filters['manager']; }
		if ($filters['mine']) {
			$extra .= ' AND (p.manager=?';
			$extrabind[] = $filters['mine'];
			$units = self::user_progman_units($filters['mine']);
			if (count($units)) {
				$extra .= ' OR p.unit IN (?{'.count($units).'})';
				$extrabind = array_merge($extrabind, $units);
			}
			$extra .= ')';
		}
		if ($filters['latestpublish']) {
			if ($filters['manager_show_current']) {
				$publishjoin = 'LEFT JOIN (
								SELECT publishof, MAX(id) as id FROM projects WHERE publishof>0 GROUP BY publishof
								UNION
								SELECT p1.id as publishof, p1.id FROM projects p1 LEFT JOIN projects p2 ON p1.id=p2.publishof WHERE p1.publishof=0 AND p2.id IS NULL
								) lf ON p.id=lf.id
								LEFT JOIN projects cp ON lf.publishof=cp.id';
				$publishwhere = ' AND ((lf.publishof IS NOT NULL AND cp.manager != ?) OR (p.manager=? AND p.publishof=0))';
				$extrabind[] = $filters['manager_show_current'];
				$extrabind[] = $filters['manager_show_current'];
			} else {
				$publishjoin = 'INNER JOIN (
								SELECT publishof, MAX(id) as id FROM projects WHERE publishof>0 GROUP BY publishof
								UNION
								SELECT p1.id as publishof, p1.id FROM projects p1 LEFT JOIN projects p2 ON p1.id=p2.publishof WHERE p1.publishof=0 AND p2.id IS NULL
								) lf ON p.id=lf.id';
			}
		}

		if (!$filters['id'] && !$filters['publishof'] && !$filters['latestpublish']) { $extra .= ' AND p.publishof=0'; }

		if ($filters['complete'] == -1) {
			$completewhere = ' AND (ph.complete IS NULL OR ph.complete != "complete")';
		} elseif ($filters['complete'] == 1) {
			$completewhere = ' AND ph.complete = "complete"';
		}

		if ($filters['sort']) {
			$sortcols = array();
			$pcols = $db->getcolumn("SHOW COLUMNS FROM projects");
			foreach ($filters['sort'] as $col) {
				if (in_array($col[0], array('scope', 'schedule', 'resource', 'quality', 'overall'))) continue;
				if ($col[0] == 'phase') {
					$sortcols[] = 'ph.id IS NULL ASC';
					$sortcols[] = 'ph.disporder '.$col[1];
					$sortcols[] = 'p.phasefree ASC';
				} else {
					if (in_array($col[0], $pcols)) {
						$col[0] = 'p.`'.$col[0].'`';
					}
					$sortcols[] = implode(' ', $col);
				}
			}
			if (!empty($sortcols)) $orderby .= 'ORDER BY '.implode(',',$sortcols);
		}

		if ($filters['perpage']) {
			$page = $filters['page'] ? $filters['page'] : 1;
			$limit = 'LIMIT '.(($page-1)*$filters['perpage']).','.$filters['perpage'];
			$calcfound = 'SQL_CALC_FOUND_ROWS';
		}
		$ret = $db->getall("SELECT $calcfound p.*, IF(p.phaseid, ph.name, p.phasefree) AS phase, ph.complete AS complete, u.name AS unit_name,
															 t.name AS classification_name, u.abbrev AS unit_abbr, ma.name AS master_name,
							CONCAT(m.lastname, IF(m.firstname!='', ', ', ''), m.firstname) AS manager_name,
							CONCAT(c.lastname, IF(c.firstname!='',', ',''), c.firstname) AS createdby_name,
							CONCAT(md.lastname, IF(md.firstname!='',', ', ''), md.firstname) AS modifiedby_name,
							CONCAT(cd.lastname, IF(cd.firstname!='',', ', ''), cd.firstname) AS completedby_name,
							IF (currm.userid IS NULL, m.userid, currm.userid) AS current_manager_id,
							IF (currm.userid IS NULL, CONCAT(m.lastname, IF(m.firstname!='', ', ', ''), m.firstname), CONCAT(currm.lastname, IF(currm.firstname!='', ', ', ''), currm.firstname)) AS current_manager
							FROM projects p
							LEFT JOIN units u ON u.id=p.unit
							LEFT JOIN phases ph ON ph.id=p.phaseid
							LEFT JOIN users m ON m.userid=p.manager
							LEFT JOIN users c ON c.userid=p.createdby
							LEFT JOIN users md ON md.userid=p.modifiedby
							LEFT JOIN users cd ON cd.userid=p.completedby
							LEFT JOIN classification t ON t.id=p.classification
							LEFT JOIN masters ma ON ma.id=p.master
							LEFT JOIN projects curr ON p.publishof=curr.id
							LEFT JOIN users currm ON curr.manager=currm.userid
							$filtjoin
							$publishjoin
							WHERE p.deleted=0 $extra
							$publishwhere
							$completewhere
							$orderby
							$limit
							", $extrabind);
		if ($filters['perpage']) {
			self::$foundrows = $db->get("SELECT FOUND_ROWS()");
		}
		foreach ($ret as $k => $row) {
			$ret[$k]['overall'] = self::traits_get($row['overalltraits']);
			$ret[$k]['attach'] = self::project_attach($row['id']);
			$ret[$k]['links'] = self::project_links($row['id']);
		}

		if ($filters['sort']) {
			foreach ($filters['sort'] as $col)
				if (in_array($col[0], array('scope', 'schedule', 'resource', 'quality', 'overall'))) {
					self::$cmp = $col[0];
					self::$cmptype = $col[1];
					usort($ret, array('db_layer', 'compare_projects'));
				}
		}

		return $ret;
	}

	/**
	 * @access private
	 */
	public static function compare_projects($a, $b) {
		if (strtolower(self::$cmptype) != 'desc') {
			$c = $a;
			$a = $b;
			$b = $c;
		}
		if ($a[self::$cmp]['status'] == $b[self::$cmp]['status']) {
			$adate = strtotime($a['target']);
			$bdate = strtotime($b['target']);
			if ($adate == $bdate) return 0;
			elseif ($adate > $bdate) return 1;
			else return -1;
		} else return strcmp($a[self::$cmp]['status_disporder'], $b[self::$cmp]['status_disporder']);
	}

	/**
	 * Get a single project
	 *
	 * Can potentially accept any of the filters {@link project_getmany()} can,
	 * though most commonly you'll just want to set $filters['id'].
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function project_get($filters = array()) {
		$db = self::$db;
		if (!$filters['id']) return array();
		$ret = self::project_getmany($filters);
		return $ret[0];
	}

	/**
	 * Check if given project id is an outdated publish
	 *
	 * If a newer publish of the same project exists, the ID of the newer
	 * publish will be returned.  Otherwise returns 0.
	 *
	 * @param int $id
	 * @return int
	 */
	public static function project_oldpublish($id) {
		$db = self::$db;
		$publishof = $db->get("SELECT publishof FROM projects WHERE id=?", $id);
		if (!$publishof) return 0;
		$latest = $db->get("SELECT p.id FROM projects p
							INNER JOIN (
								SELECT publishof, MAX(created) as created FROM projects WHERE publishof=? GROUP BY publishof
							) f ON f.created=p.created AND f.publishof=p.publishof", $publishof);
		if ($latest == $id) return 0;
		return $latest;
	}

	/**
	 * Does given project have any published versions?
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function project_haspublishes($id) {
		$db = self::$db;
		return $db->get("SELECT 1 FROM projects WHERE publishof=? LIMIT 1", $id);
	}

	/**
	 * Create a publish of given project ID
	 *
	 * Immediately takes a snapshot of the project and creates a published
	 * version.
	 *
	 * $currentuser should be the ID of the user who clicked the publish
	 * button, so we can log the action.
	 *
	 * @param int $id
	 * @param int $currentuser
	 * @return void
	 */
	public static function project_publish($id, $currentuser = 0) {
		$db = self::$db;
		$p = self::project_get(array('id'=>$id));
		unset($p['id']);
		unset($p['scope']['id']);
		unset($p['schedule']['id']);
		unset($p['resource']['id']);
		unset($p['quality']['id']);
		unset($p['overall']['id']);
		$p['publishof'] = $id;
		$pid = self::project_update($p, $currentuser);
		foreach ($p['attach'] as $att) {
			self::attach_add($pid, $att);
		}
		foreach ($p['links'] as $ln) {
			self::link_add($pid, $ln);
		}
	}

	/**
	 * Update or create a project
	 *
	 * If $data['id'] is set, updates the project with the new data.
	 * Otherwise creates a new project.
	 *
	 * $actor should be the ID of the user who is taking this action, for
	 * logging purposes.
	 *
	 * Returns the ID of the project so you have access to the insertid if
	 * a new one was created.
	 *
	 * @param array $data
	 * @param int $actor
	 * @return int
	 */
	public static function project_update($data = array(), $actor = 0) {
		$db = self::$db;

		// If no id is provided, we'll create a new project
		if (!$data['id']) {
			if (!$data['identify']) { // preserve project ID for publishes
				$now = new DateTime();
				$next = self::setting('nextident');
				$data['identify'] = $now->format('Y').'-'.sprintf("%04d",$next);
			}
			$db->execute("INSERT INTO projects (identify, created, createdby) VALUES (?, NOW(), ?)", $data['identify'], $actor);
			$data['id'] = $db->insertid();
		}

		// update the different aspects of the project
		// these records will also be created on demand
		$scope = self::traits_update($data['scope']);
		$schedule = self::traits_update($data['schedule']);
		$resource = self::traits_update($data['resource']);
		$quality = self::traits_update($data['quality']);
		$overall = self::traits_update($data['overall']);

		// update the project itself
		$db->execute("UPDATE projects SET publishof=?, modified=NOW(), modifiedby=?, manager=?, name=?, master=?, goal=?, classification=?, unit=?,
					  start=?, target=?, priority=?, phaseid=?, phasefree=?, activity=?, activitydate=?, scopetraits=?,
					  scheduletraits=?, resourcetraits=?, qualitytraits=?, overalltraits=?, comment=?, commentdate=?
					  WHERE id=?", $data['publishof'], $actor, $data['manager'], $data['name'], $data['master'], $data['goal'], $data['classification'],
					  $data['unit'], $data['start'], $data['target'], $data['priority'], $data['phaseid'], $data['phasefree'],
					  $data['activity'], $data['activitydate'], $scope, $schedule, $resource, $quality, $overall, $data['comment'],
					  $data['commentdate'], $data['id']);
		return $data['id'];
	}

	/**
	 * Delete a project
	 *
	 * Doesn't actually delete any data, just sets a flag that eliminates
	 * it from ever being retrieved by one of this class' methods.
	 *
	 * @param int $pid
	 * @return bool
	 */
	public static function project_delete($pid) {
		$db = self::$db;
		return $db->execute("UPDATE projects SET deleted=1 WHERE id=? OR publishof=?", $pid, $pid);
	}

	/**
	 * Complete a project
	 *
	 * Moves a project to the "complete" phase, which has various interface
	 * implications.
	 *
	 * The $options parameter may be used to ask for a publish before and/or
	 * after the project is moved to completion.
	 *
	 * <pre>$options = array(
	 *   'request' => boolean (complete project or merely put in a request)
	 *   'currentuser' => int (user ID to be associated with the completion)
	 * )</pre>
	 *
	 * @param int $pid
	 * @return bool
	 */
	public static function project_complete($pid, $options = array()) {
		$db = self::$db;

		if (!$options['currentuser']) $options['currentuser'] = doc::getuser()->userid();

		if ($options['request']) {
			$pphase = $db->get("SELECT id FROM phases WHERE complete='pending'");
			if ($pphase) $success = $db->execute("UPDATE projects SET phaseid=? WHERE id=?", $pphase, $pid);
		} else {
			$cphase = $db->get("SELECT id FROM phases WHERE complete='complete'");
			if ($cphase) $success = $db->execute("UPDATE projects SET phaseid=?, completed=NOW(), ".
					"completedby=? WHERE id=?", $cphase, $options['currentuser'], $pid);
		}

		// publish after
		if ($success) self::project_publish($pid, $options['currentuser']);

		return $success;
	}

	/**
	 * List of project's subscribers
	 *
	 * This returns a list of userids of users who should be notified when a project is updated.
	 *
	 * People may subscribe/unsubscribe to projects manually, or they may be notified automatically
	 * in some cases (no such cases are implemented yet).
	 *
	 * $options is for future implementations where there are multiple types of subscriptions.
	 *
	 * @param int $pid
	 * @param array $options
	 * @return array
	 */
	public static function project_subscribers($pid, $options = array()) {
		$db = self::$db;
		return $db->getcolumn("SELECT userid FROM project_subscribe WHERE projectid=? AND flag > 0", $pid);
	}

	/**
	 * Subscribe to a project
	 *
	 * Use this method to subscribe to or unsubscribe from a project.  Subscribers will be
	 * notified when a project changes.
	 *
	 * @param int $pid
	 * @param int $userid
	 * @param bool $subscribe
	 * @return void
	 */
	public static function project_subscribe($pid, $userid, $subscribe = true) {
		$db = self::$db;
		$newflag = ($subscribe ? 1 : 0);
		$publishof = $db->get("SELECT publishof FROM projects WHERE id=?", $pid);
		if ($publishof) $pid = $publishof;
		$curr = $db->get("SELECT flag FROM project_subscribe WHERE projectid=? AND userid=?", $pid, $userid);
		if ($curr == '')
			$db->execute("INSERT INTO project_subscribe (projectid, userid, flag) VALUES (?,?,?)", $pid, $userid, $newflag);
		elseif ($curr != $newflag)
			$db->execute("UPDATE project_subscribe SET flag=? WHERE projectid=? AND userid=?", $newflag, $pid, $userid);
	}

	/**
	 * Grab the traits of a particular aspect of a project
	 *
	 * Projects have 5 aspects (scope, schedule, resource, quality, overall), each
	 * has all the same traits.  So we try to re-use code when we deal with any
	 * of them.
	 *
	 * @param int $id
	 * @return array
	 */
	public static function traits_get ($id) {
		$db = self::$db;
		return $db->getrow("SELECT t.*, s.name AS status_name, s.disporder as status_disporder, f.name AS flexibility_name, n.name as trend_name
							FROM traits t
							LEFT JOIN status s ON s.id=t.status
							LEFT JOIN flexibility f ON f.id=t.flexibility
							LEFT JOIN trend n ON n.id=t.trend
							WHERE t.id=?", $id);
	}

	/**
	 * Retrieve the lists of possible trait values
	 *
	 * This is used on the system.php page where we manage the lists of
	 * potential popup values during project editing.
	 *
	 * Projects have 5 aspects (scope, schedule, resource, quality, overall), each
	 * has all the same traits.  So we try to re-use code when we deal with any
	 * of them.
	 *
	 * Return array looks like:
	 * <pre>array(
	 *   'trend' => array(array('id'=>int, 'name'=>string), ...)
	 *   'status' => array(array('id'=>int, 'name'=>string), ...)
	 *   'flexibility' => array(array('id'=>int, 'name'=>string), ...)
	 * )</pre>
	 *
	 * @return array
	 */
	public static function traits_lists() {
		$db = self::$db;
		$ret['trend'] = $db->getall("SELECT id, name FROM trend WHERE deleted=0 ORDER BY disporder");
		$ret['status'] = $db->getall("SELECT id, name FROM status WHERE deleted=0 ORDER BY disporder");
		$ret['flexibility'] = $db->getall("SELECT id, name FROM flexibility WHERE deleted=0 ORDER BY disporder");
		return $ret;
	}

	/**
	 * Update a set of traits
	 *
	 * Projects have 5 aspects (scope, schedule, resource, quality, overall), each
	 * has all the same traits.  So we try to re-use code when we deal with any
	 * of them.  This function deals with a set of traits without knowledge of which
	 * aspect of which project we're dealing with.
	 *
	 * Creates a new entry if !$data['id'].  Returns ID of the entry so you have access
	 * to the insertid.
	 *
	 * @param array $data
	 * @return int
	 */
	public static function traits_update($data = array()) {
		$db = self::$db;
		if (!$data['id']) {
			$db->execute("INSERT INTO traits (flexibility) VALUES (?)", $data['flexibility']);
			$data['id'] = $db->insertid();
		}
		$db->execute("UPDATE traits SET flexibility=?, status=?, trend=?, risk=?, mitigation=? WHERE id=?",
			$data['flexibility'], $data['status'], $data['trend'], $data['risk'], $data['mitigation'], $data['id']);
		return $data['id'];
	}

	/**
	 * Re-sequence the disporder of the possible values for a trait
	 *
	 * $type is the name of the trait list you're trying to re-sequence:
	 * 'flexibility', 'status', etc...  More specifically, it is the table name
	 * that is getting re-sequenced.
	 *
	 * @param string $type
	 * @return void
	 */
	public static function trait_repair($type) {
		$db = self::$db;
		$list = $db->getcolumn("SELECT id FROM $type WHERE deleted=0 ORDER BY disporder");
		foreach ($list as $i => $id) $db->execute("UPDATE $type SET disporder=? WHERE id=?", $i, $id);
	}

	/**
	 * Move one of the possible values for a trait higher in the list
	 *
	 * $type is the name of the trait list you're trying to edit:
	 * 'flexibility', 'status', etc...  More specifically, it is the table name
	 * that is getting edited.
	 *
	 * @param string $type
	 * @param int $id
	 * @return void
	 */
	public static function trait_move($type, $id) {
		$db = self::$db;
		self::trait_repair($type);
		$myorder = $db->get("SELECT disporder FROM $type WHERE id=?", $id);
		if (!$myorder) return;
		$db->execute("UPDATE $type SET disporder=? WHERE disporder=?", $myorder, $myorder-1);
		$db->execute("UPDATE $type SET disporder=? WHERE id=?", $myorder-1, $id);
	}

	/**
	 * Delete one of the possible values for a trait
	 *
	 * Doesn't actually delete - it stays in the database and is used as
	 * a legacy entry so that all the projects that've used it in the past
	 * are not left with a broken link.
	 *
	 * However, it will no longer show up in the list during project edit
	 * or creation.
	 *
	 * $type is the name of the trait list you're trying to edit:
	 * 'flexibility', 'status', etc...  More specifically, it is the table name
	 * that is getting edited.
	 *
	 * @param string $type
	 * @param int $id
	 * @return void
	 */
	public static function trait_delete($type, $id) {
		$db = self::$db;

		switch ($type)
		{
			case 'phases':
				$used = $db->get("SELECT id FROM projects WHERE phaseid=?", $id);
				break;

			case 'classification':
				$used = $db->get("SELECT id FROM projects WHERE classification=?", $id);
				break;

			default:
				$used = $db->get("SELECT id FROM traits WHERE $type=?", $id);
				break;
		}

		// if ($type == 'phases') { 
			
		// } elseif ($type == 'classification') {
			
		// } else {
			
		// }

		if ($used) {
			$db->execute("UPDATE $type SET deleted=1 WHERE id=?", $id);
		} else {
			$db->execute("DELETE FROM $type WHERE id=?", $id);
		}
	}

	/**
	 * Edit or create a possible value for a trait
	 *
	 * $type is the name of the trait list you're trying to edit:
	 * 'flexibility', 'status', etc...  More specifically, it is the table name
	 * that is getting edited.
	 *
	 * Returns the ID so you have access to the insertid.
	 *
	 * @param string $type
	 * @param int $id
	 * @param string $name
	 * @return int
	 */
	public static function trait_save($type, $id, $name, $more = array()) {
		$db = self::$db;
		if (!$id) {
			$ord = $db->get("SELECT MAX(disporder) FROM $type WHERE deleted=0");
			$db->execute("INSERT INTO $type (disporder) VALUES (?)", $ord+1);
			$id = $db->insertid();
		}
		$cols = array('name=?');
		$bind = array($name);
		foreach ((array) $more as $key => $val) {
			$cols[] = '`'.addslashes($key).'`=?';
			$bind[] = $val;
		}
		$db->execute("UPDATE $type SET ".implode(',', $cols)." WHERE id=?", $bind, $id);
		return $id;
	}

	/**
	 * Change the parent of a Unit or Department
	 *
	 * The department list is organized hierarchically, so use this
	 * method to alter the parentage of a particular department.
	 *
	 * @param int $child
	 * @param int $parent
	 * @return void
	 */
	public static function unit_change_parent($child, $parent) {
		if (!$child) return;
		$db = self::$db;
		$maxord = $db->get("SELECT MAX(disporder) FROM units WHERE parent=?", $parent);
		$db->execute("UPDATE units SET parent=? WHERE id=?", $parent, $child);
	}

	/**
	 * Move a department to appear before another in the list
	 *
	 * This method will also alter the parent of the department to match
	 * that of the department you're inserting in front of.
	 *
	 * @param int $insert
	 * @param int $before
	 * @return void
	 */
	public static function unit_insertbefore($insert, $before) {
		$db = self::$db;
		$newparent = $db->get("SELECT parent FROM units WHERE id=?", $before);
		self::unit_change_parent($insert, $newparent);
		$units = $db->getcolumn("SELECT id FROM units WHERE parent=? ORDER BY disporder", $newparent);
		foreach ($units as $id) {
			if ($id == $before) $sorted[] = $insert;
			if ($id != $insert) $sorted[] = $id;
		}
		foreach ($sorted as $i => $id)
			$union[] = "(SELECT $id AS id, $i AS disporder)";
		$db->execute("UPDATE units u INNER JOIN (".
					  join(" UNION ", $union).
					 ") f ON f.id=u.id
					  SET u.disporder=f.disporder");
	}

	/**
	 * Update a Department
	 *
	 * Creates a new record unless $info['id'] is set.  Returns the
	 * ID of the department so you have access to the insertid.
	 *
	 * <pre>$info = array(
	 *   'id' => id of the dept to edit (zero to create new one)
	 *   'name' => full name
	 *   'abbrev' => abbreviated name
	 * );</pre>
	 *
	 * @param array $info
	 * @return int
	 */
	public static function unit_update($info) {
		$db = self::$db;
		if (!$info['id']) {
			$db->execute("INSERT INTO units (parent, disporder) VALUES (0, 99)");
			$info['id'] = $db->insertid();
		}
		$db->execute("UPDATE units SET name=?, abbrev=?, manager=? WHERE id=?", $info['name'], $info['abbrev'], $info['manager'], $info['id']);
		return $info['id'];
	}

	/**
	 * Delete a department
	 *
	 * Only deletes it if it's never been used.  Otherwise it keeps it
	 * for legacy purposes and sets a deletion flag.
	 *
	 * @param int $id
	 * @return void
	 */
	public static function unit_delete($id) {
		$db = self::$db;
		if ($db->get("SELECT 1 FROM projects WHERE unit=? LIMIT 1", $id)) {
			$db->execute("UPDATE units SET deleted=1 WHERE id=?", $id);
		} else {
			$db->execute("DELETE FROM units WHERE id=?", $id);
		}
	}

	/**
	 * Get an array with the IDs of all parent units
	 *
	 * Recursive method to traverse its way up the hierarchy and get IDs for all
	 * parents of given unit ID.
	 *
	 * @param int $id
	 * @return array
	 */
	public static function unit_parents($id) {
		$db = self::$db;
		$par = $db->get("SELECT parent FROM units WHERE id=?", $id);
		if (!$par) return array();
		return array_merge(array($par), self::unit_parents($par));
	}

	/**
	 * Get a list of the departments
	 *
	 * This list is not hierarchical, so may be of limited use
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function units_getmany($filters = array()) {
		$db = self::$db;
		$binds = array();
		if (isset($filters['parent'])) { $parwhere = 'AND a.parent=? '; $binds[] = $filters['parent']; }
		return $db->getall("SELECT a.*, CONCAT(u.firstname,' ',u.lastname) AS manager_name ".
											 "FROM units a ".
											 "LEFT JOIN users u ON u.userid=a.manager ".
											 "WHERE a.deleted=0 ".
											 $parwhere.
											 "ORDER BY a.parent, a.disporder", $binds);
	}

	/**
	 * Get a hierarchical list of departments
	 *
	 * This retrieves all the departments in the system (modified by
	 * the optional filters) as an array tree.  The key for descendants is
	 * 'children'.
	 *
	 * Currently there are no filters.
	 *
	 * <pre>$return = array(
	 *   array(...dept data..., 'children'=>array(
	 *   	array(...dept data..., 'children'=>array(
	 *        array(...dept data...),
	 *        ...etc...
	 *      ),
	 *      ...etc...
	 *   ),
	 *   ...etc...
	 * )</pre>
	 * @param array $filters
	 * @param int $parent
	 * @return array
	 */
	public static function units_gethierarchy($filters = array(), $parent = 0) {
		$db = self::$db;
		$ret = self::units_getmany(array('parent'=>$parent));
		foreach ($ret as $k => $row) {
			$ret[$k]['children'] = self::units_gethierarchy($filters, $row['id']);
		}
		return $ret;
	}

	/**
	 * Get a flat list of departments beneath a given parent department
	 *
	 * @param int $parent
	 * @return array
	 */
	public static function units_flattenhierarchy($parent = 0) {
		$db = self::$db;
		$list = $db->getcolumn("SELECT id FROM units WHERE parent=? AND NOT deleted",$parent);
		$ret = $list;
		foreach ($list as $i => $id) {
			$ret = array_merge($ret,self::units_flattenhierarchy($id));
		}
		return $ret;
	}

	/**
	 * Get all the project phases
	 *
	 * Currently no filters are implemented.
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function phase_getmany($filters = array()) {
		$db = self::$db;
		return $db->getall("SELECT * FROM phases WHERE deleted=0 ORDER BY disporder");
	}

	/**
	 * Get all the project types
	 *
	 * Currently no filters are implemented.
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function type_getmany($filters = array()) {
		$db = self::$db;
		return $db->getall("SELECT * FROM classification WHERE deleted=0 ORDER BY disporder");
	}

	/**
	 * Get all the master projects
	 *
	 * Currently no filters are implemented.
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function masters_getmany($filters = array()) {
		$db = self::$db;
		return $db->getall("SELECT * FROM masters WHERE deleted=0 ORDER BY disporder");
	}

	/**
	 * Get permissions for a user (or group)
	 *
	 * $type is either "user" or "group"
	 *
	 * @param string $type
	 * @param int $id
	 * @return array
	 */
	public static function permission_get($type, $id) {
		$db = self::$db;
		return $db->getrow("SELECT * FROM permissions WHERE entitytype=? AND entityid=?", $type, $id);
	}

	/**
	 * Edit / Create permissions for a user (or group)
	 *
	 * <pre>$info = array(
	 *   'entityid' => id of the user or group
	 *   'entitytype' => either 'user' or 'group'
	 *   'projectid' => id of project these permissions apply to, or zero
	 *   'sysadmin' => user is a system administrator, full privs
	 *   'viewpub' => able to view published versions of project(s)
	 *   'viewcurr' => able to view most recent unpublished version of project(s)
	 *   'editcurr' => able to edit project(s)
	 *   'publish' => able to create a published version
	 * );</pre>
	 *
	 * @param array $info
	 * @return bool
	 */
	public static function permission_update($info) {
		$db = self::$db;
		$success = TRUE;
		if (!$db->get("SELECT 1 FROM permissions WHERE entityid=? AND entitytype=?", $info['entityid'], $info['entitytype']))
			$success = $db->execute("INSERT INTO permissions (entityid, entitytype) VALUES (?,?)", $info['entityid'], $info['entitytype']);
		if ($success) $success = $db->execute("UPDATE permissions SET projectid=?, sysadmin=?, createproject=?, viewpublished=?, viewcurrent=?,
					  editcurrent=?, publish=?, addcomment=? WHERE entityid=? AND entitytype=?", $info['projectid'],
			$info['sysadmin'], $info['createproject'], $info['viewpub'], $info['viewcurr'], $info['editcurr'], $info['publish'], $info['addcomment'],
			$info['entityid'], $info['entitytype']);
		return $success;
	}

	/**
	 * Create some easier views for people's names
	 *
	 * Takes a database row and adds two keys: 'lastfirst' and 'fullname'
	 *
	 * 'lastfirst' is "Wing, Nickolaus" form, while 'fullname' is
	 * "Nickolaus Wing" form.
	 *
	 * @param array $row
	 * @return array
	 */
	public static function user_fillnames($row = array()) {
		$row['lastfirst'] = $row['lastname'].($row['firstname'] ? ', ' : '').$row['firstname'];
		$row['fullname'] = $row['firstname'].' '.$row['lastname'];
		return $row;
	}

	/**
	 * Get a user's details
	 *
	 * $filters can take either 'userid' or 'username', depending on which
	 * you have available.
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function user_get($filters = array()) {
		$db = self::$db;
		if (!($filters['userid'] || $filters['username'])) return array();
		$bind = array();
		if ($filters['userid']) { $extra .= ' AND userid=?'; $bind[] = $filters['userid']; }
		if ($filters['username']) { $extra .= ' AND username=?'; $bind[] = $filters['username']; }
		$ret = $db->getrow("SELECT u.*, a.name as areaname FROM users u LEFT JOIN units a ON u.unitid=a.id AND NOT a.deleted WHERE NOT u.deleted".$extra, $bind);
		$ret['permissions'] = self::permission_get('user', $ret['userid']);
		return self::user_fillnames($ret);
	}

	/**
	 * Edit or Create a User
	 *
	 * <pre>$info = array(
	 *   'userid' => ID of user if editing, or zero for creation
	 *   'username' => netID of user
	 *   'passwd' => local password to use, overridden by CAS, unnecessary with LDAP
	 *   'lastname' => user's last name
	 *   'firstname' => user's first name
	 *   'manager' => whether user should be available in the list of project managers
	 *   'permissions' => array(...see array for {@link permission_update()}...)
	 * );</pre>
	 *
	 * @param array $info
	 * @return bool
	 */
	public static function user_update($info = array()) {
		$db = self::$db;
		$success = TRUE;
		if (!$info['userid']) {

			// is this a previously deleted user? if so we'll just reactivate
			if ($prev_id = $db->get("SELECT userid FROM users WHERE username=?", $info['username'])) {
				$success = $db->execute("UPDATE users SET deleted=0 WHERE userid=?", $prev_id);
				$info['userid'] = $prev_id;
			} else {
				$success = $db->execute("INSERT INTO users (username) VALUES (?)", $info['username']);
				$info['userid'] = $db->insertid();
				if ($success) $success = $db->execute("INSERT INTO permissions (entityid, entitytype) VALUES (?, 'user')", $info['userid']);
			}
		}
		if ($success) $success = $db->execute("UPDATE users u SET username=?, passwd=MD5(?), lastname=?, firstname=?, unitid=?, manager=?, progman=? WHERE userid=?",
			$info['username'], $info['passwd'], $info['lastname'], $info['firstname'], $info['unitid'], $info['manager'], $info['progman'], $info['userid']);
		$perm = $info['permissions'];
		$perm['entitytype'] = 'user';
		$perm['entityid'] = $info['userid'];
		if ($success) $success = self::permission_update($perm);
		return $success;
	}

	/**
	 * Delete a User
	 *
	 * <pre>$info = array(
	 *   'userid' => ID of user
	 *   'replacewith' => ID of user (manager) who should inherit the deleted user's open projects
	 *   'publishfirst' => boolean; publish open projects before modifying them
	 *   'publishafter' => boolean; publish open projects after modifying them
	 *   'currentuser' => ID of user who is performing the deletion
	 * );</pre>
	 *
	 * @param array $info
	 * @return bool
	 */
	public static function user_delete($info = array()) {
		$db = self::$db;
		$projects = self::project_getmany(array('manager'=>$info['userid'], 'complete'=>-1));
		$replace = self::user_get(array('userid'=>$info['replacewith']));
		if (!$replace['userid']) $info['replacewith'] = 0;
		foreach ($projects as $p) {
			if ($info['publishfirst']) self::project_publish($p['id'], $info['currentuser']);
			$p['manager'] = $info['replacewith'];
			$db->execute("UPDATE projects SET manager=? WHERE id=?", $info['replacewith'], $p['id']);
			if ($info['publishafter']) self::project_publish($p['id'], $info['currentuser']);
		}
		return $db->execute("UPDATE users SET deleted=1 WHERE userid=?", $info['userid']);
	}

	/**
	 * Get list of project managers
	 *
	 * This is a list of all the users in the system who should appear
	 * in the dropdown for "Project Manager" for a project.
	 *
	 * No filters currently implemented.
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function user_managers($filters = array()) {
		$db = self::$db;
		$ret = $db->getall("SELECT * FROM users WHERE manager=1 AND NOT deleted ORDER BY lastname, firstname");
		foreach ($ret as $i => $row) {
			$ret[$i] = self::user_fillnames($row);
		}
		return $ret;
	}

	/**
	 * Get list of areas over which a user is a program manager
	 *
	 * @param int $userid
	 * @return array
	 */
	public static function user_progman_units($userid) {
		$db = self::$db;
	  $unitids = db_layer::units_flattenhierarchy();
	  $list = $db->getcolumn("SELECT id FROM units WHERE manager=? AND id IN (?*)", $userid, $unitids);
		$ret = $list;
		foreach ($ret as $i => $id) {
			$ret = array_merge($ret,self::units_flattenhierarchy($id));
		}
		return array_unique($ret);
	}

	/**
	 * Get list of potential program managers in the system
	 *
	 * This is a list of all the users in the system who should appear
	 * in the dropdown for "Program Manager" for an Area.
	 *
	 * No filters currently implemented.
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function user_progmans($filters = array()) {
		$db = self::$db;
		$ret = $db->getall("SELECT * FROM users WHERE progman=1 AND NOT deleted ORDER BY lastname, firstname");
		foreach ($ret as $i => $row) {
			$ret[$i] = self::user_fillnames($row);
		}
		return $ret;
	}

	/**
	 * Get list of program managers with authority over given project
	 *
	 * Give it a project id and out pops a list of program managers who have domain over
	 * that project.  This is a hierarchical area/unit search.
	 *
	 * Returns full rows from the users table.
	 *
	 * @param int $pid
	 * @return array
	 */
	public static function project_progmans($pid) {
		$db = self::$db;
		$area = $db->get("SELECT unit FROM projects WHERE id=?", $pid);
		$areas = self::unit_parents($area);
		$areas[] = $area;
		if (!empty($areas)) {
			$ret = $db->getall("SELECT DISTINCT u.* FROM users u, units a WHERE u.userid=a.manager AND a.id IN (?*)", $areas);
			foreach ($ret as $i => $row) {
				$ret[$i] = self::user_fillnames($row);
			}
		}
		return (array) $ret;
	}

	/**
	 * Determine whether user is an active program manager
	 *
	 * Checks the areas list and finds whether the given userid is listed as the
	 * program manager for any of them.
	 *
	 * @param int $userid
	 * @return bool
	 */
	public static function active_progman($userid) {
	  $db = self::$db;
	  $unitids = db_layer::units_flattenhierarchy();
	  return $db->get("SELECT COUNT(*) FROM units WHERE manager=? AND id IN (?*)", $userid, $unitids);
	}

	/**
	 * Associate a user with a session
	 *
	 * When someone logs in, we simply add their user ID to their session.  That's
	 * how we know they're logged in.
	 *
	 * <pre>$filters = array(
	 *   'sessid' => auto_increment ID for the session
	 *   'userid' => auto_increment ID of the user
	 *   'username' => netID of the user
	 * );</pre>
	 *
	 * Either 'userid' or 'username' is required. 'sessid' is required.
	 *
	 * Returns user ID, in case you made the request with 'username' and you
	 * need the ID for further work.
	 *
	 * @param array $filters
	 * @return int
	 */
	public static function usertosession($filters = array()) {
		if (!$filters['sessid'] || (!$filters['userid'] && !$filters['username'])) return 0;
		$db = self::$db;
		$sessid = $filters['sessid'];
		unset($filters['sessid']);
		$user = self::user_get($filters);
		if ($user['userid'])
			$db->execute("UPDATE sessions SET userid=?, caslogin=? WHERE sessid=?", $user['userid'], $filters['caslogin'], $sessid);
		elseif (self::setting('use_cas')) $db->execute("UPDATE sessions SET caslogin=1 WHERE sessid=?", $sessid);
		return $user['userid'];
	}

	/**
	 * Get all users
	 *
	 * No filters currently implemented.  Sorts by lastname, firstname.
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function user_getmany($filters = array()) {
		$db = self::$db;
		$ret = $db->getall("SELECT u.*, a.name as areaname FROM users u LEFT JOIN units a ON u.unitid=a.id AND NOT a.deleted WHERE NOT u.deleted ORDER BY lastname, firstname");
		foreach ($ret as $i => $row) {
			$ret[$i] = self::user_fillnames($row);
			$ret[$i]['permissions'] = self::permission_get('user', $row['userid']);
		}
		return $ret;
	}

	/**
	 * Get all groups
	 *
	 * Not currently implemented.
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function group_getmany($filters = array()) {
		$db = self::$db;
		return $db->getall("SELECT * FROM groups");
	}

	/**
	 * Check if a project's displayed ID is unique
	 *
	 * For a time we allowed users to specify their own project ID, so we had
	 * to use this to make sure they picked something unique.  No longer in
	 * use.
	 *
	 * @param string $val
	 * @return bool
	 */
	public static function unique_projectid($val) {
		$db = self::$db;
		if ($db->get("SELECT 1 FROM projects WHERE identify=?", $val)) return 0;
		return 1;
	}

	/**
	 * Check if a username is already in the database
	 *
	 * When creating a new user or editing someone's netID, we have to be sure
	 * the name is unique.  To prevent false positives when editing a user and NOT
	 * changing their username, we ask for a userid to exclude from the search.
	 *
	 * @param int $userid
	 * @param string $username
	 * @return bool
	 */
	public static function unique_username($userid, $username) {
		$db = self::$db;
		if ($db->get("SELECT 1 FROM users WHERE username=? AND userid!=? AND NOT deleted", $username, $userid)) return 0;
		return 1;
	}

	/**
	 * Create a filter set for a user
	 *
	 * Use this method to create or update a given user's preferred list of filters.  It will remove any
	 * filters they had saved, and replace them with these.
	 *
	 * $data should be a 2-D array with column names 'type', 'field', 'control', and 'val'.
	 *
	 * Returns the number of filter lines successfully saved.
	 *
	 * @param int $userid
	 * @param array $data
	 * @return int
	 */
	public static function filter_create($userid, $data = array()) {
		$db = self::$db;

		// find out if the user already has a current filterid
		$filterid = self::filter_current($userid);

		if ($filterid) {
			// user has a filter, let's delete all current data on that filter
			$db->execute("DELETE FROM filters_data WHERE filterid=?", $filterid);
			// let's make sure the ID really exists in `filters`
			if (!$db->get("SELECT id FROM filters WHERE id=?", $filterid)) $db->execute("INSERT INTO filters (id) VALUES (?)", $filterid);
			// let's clear the query cache for this filter
			$db->execute("UPDATE filters SET wherecache = '' WHERE id=?", $filterid);
		}	else {
			// user didn't have a filter yet
			$db->execute("INSERT INTO filters (id) VALUES ('')");
			$filterid = $db->insertid();
			$db->execute("UPDATE users SET currentfilter=? WHERE userid=?", $filterid, $userid);
			self::$cache['filter_current'][$userid] = $filterid; // update the local cache
		}

		// now we can add the data as received, if $data is empty we'll just have cleared their
		// filters, which is expected behavior
		foreach ((array) $data as $i => $f) {
			$ins[] = array('filterid'=>$filterid, 'type'=>$f['type'], 'field'=>$f['field'], 'control'=>$f['control'], 'val'=>$f['val'], 'disporder'=>$i);
		}
		if (!empty($ins)) {
			unset(self::$cache['filter_data'][$filterid]); // reset the local cache
			return $db->massinsert('filters_data', $ins);
		}
	}

	/**
	 * Fetch current filter id for a given user id
	 *
	 * @param int $userid
	 * @return int
	 */
	public static function filter_current($userid) {
		$db = self::$db;
		$cache =& self::$cache['filter_current'];
		if (isset($cache[$userid])) return $cache[$userid];
		return $cache[$userid] = $db->get("SELECT currentfilter FROM users WHERE userid=?", $userid);
	}

	/**
	 * Fetch current filter lines for a given user id
	 *
	 * Returns all the filters for that user in a 2-D array
	 *
	 * @param int $userid
	 * @return array
	 */
	public static function filter_currentdata($userid) {
		$filt = self::filter_current($userid);
		return self::filter_data($filt);
	}

	/**
	 * Fetch filter lines for a given filter id
	 *
	 * Returns all the filter lines in a 2-D array.
	 *
	 * @param int $filtid
	 * @return array
	 */
	public static function filter_data($filtid) {
		$db = self::$db;
		$cache =& self::$cache['filter_data'];
		if (isset($cache[$filtid])) return $cache[$filtid];
		return $cache[$filtid] = $db->getall("SELECT type, field, control, val FROM filters_data WHERE filterid=? ORDER BY disporder", $filtid);
	}

	/**
	 * Generate a subquery
	 *
	 * Should generate a full MySQL query that can select IDs for a filtered list of projects
	 * according to the rules for the specified filter.
	 *
	 * @param int $filtid
	 * @return string
	 */
	public static function filter_query($filtid) {
		$db = self::$db;

		// let's see if we have a cached version so we don't have to do all this work
		$cached = $db->get("SELECT wherecache FROM filters WHERE id=?", $filtid);
		if ($cached) return $cached;

		// let's create a list for each field that's included, later we can AND them together
		$rules = $db->getall("SELECT * FROM filters_data WHERE filterid=? ORDER BY field", $filtid);

		return self::filter_query_from_rules($rules);

	}

	public static function filter_query_from_rules($rules) {
		foreach ($rules as $r) $mast[$r['field']][] = $r;

		if (empty($rules)) return array(0,0);

		// here we go
		$allrules = array();
		$allbinds = array();
		foreach ($mast as $fld => $rules) {
			// traits need some advanced support
			if ($fld == 'scope') {
				$fld = 'sp.status';
			} elseif ($fld == 'schedule') {
				$fld = 'sd.status';
			} elseif ($fld == 'resource') {
				$fld = 'r.status';
			} elseif ($fld == 'quality') {
				$fld = 'q.status';
			} elseif ($fld == 'overall') {
				$fld = 'o.status';
			} elseif ($fld == 'anyhealth') {
				$fld = array('sp.status', 'sd.status', 'r.status', 'q.status', 'o.status');
			} else {
				$fld = 'p.`'.$fld.'`';
			}

			// here is the standard routine for ANDing and ORing
			$orrules = array();
			$andrules = array();
			$orbinds = array();
			$andbinds = array();
			$listin = FALSE;
			$listnotin = FALSE;
			foreach ($rules as $r) {
				if ($r['type'] == 'list') {
				// this field is the list type of filter, user selected value from a popup
					if ($r['control'] == 'equal') {
						$listin = TRUE;
						$orbinds[] = $r['val'];
					} else {
						$listnotin = TRUE;
						$andbinds[] = $r['val'];
					}
				} elseif ($r['type'] == 'date') {
				// this field is a calendar date type of filter
					$gtlt = ($r['control'] == 'gt' ? '>=' : '<=');
					$andrules[] = $fld.$gtlt.'?';
					$dt = new DateTime($r['val']);
					$andbinds[] = $dt->format('Ymd');
				} elseif ($r['type'] == 'search') {
					if ($r['control'] == 'maycont') {
						$orrules[] = $fld.' LIKE ?';
						$orbinds[] = '%'.$r['val'].'%';
					} elseif ($r['control'] == 'mustcont') {
						$andrules[] = $fld.' LIKE ?';
						$andbinds[] = '%'.$r['val'].'%';
					} else {
						$andrules[] = $fld.' NOT LIKE ?';
						$andbinds[] = '%'.$r['val'].'%';
					}
				} elseif ($r['type'] == 'char') {
					if ($r['control'] == 'gt') $andrules[] = $fld.' >= ?';
					else $andrules[] = $fld.' <= ?';
					$andbinds[] = $r['val'];
				} elseif ($r['type'] == 'pri') {
					if ($r['control'] == 'lt') $andrules[] = $fld.' >= ?';
					else $andrules[] = $fld.' <= ?';
					$andbinds[] = $r['val'];
				}
			}

			if (is_array($fld)) {
				$mybinds = array();
				foreach ($fld as $f) {
					if ($listin) $orrules[] = $f.' IN (?{'.count($orbinds).'})';
					if ($listnotin) $andrules[] = $f.' NOT IN (?{'.count($andbinds).'})';
					$mybinds = array_merge($mybinds, $orbinds, $andbinds);
				}
				$allbinds = array_merge($allbinds, $mybinds);
			} else {
				if ($listin) $allrules[] = $fld.' IN (?{'.count($orbinds).'})';
				if ($listnotin) $allrules[] = $fld.' NOT IN (?{'.count($andbinds).'})';
				$allbinds = array_merge($allbinds, $orbinds, $andbinds);
			}
			if (!empty($orrules)) $allrules[] = '('.implode(' OR ', $orrules).')';
			if (!empty($andrules)) $allrules[] = implode(' AND ', $andrules);
		}

		if (empty($allrules)) return array(0,0);

		// create the final query
		$query = 'SELECT p.id FROM projects p '.
						 'LEFT JOIN traits sp ON sp.id=p.scopetraits '.
						 'LEFT JOIN traits sd ON sd.id=p.scheduletraits '.
						 'LEFT JOIN traits r ON r.id=p.resourcetraits '.
						 'LEFT JOIN traits q ON q.id=p.qualitytraits '.
						 'LEFT JOIN traits o ON o.id=p.overalltraits '.
						 'WHERE '.implode(' AND ', $allrules);
		return array($query, $allbinds);
	}

	/**
	 * Get help text for a contexthelp
	 *
	 * Used by the contexthelp widget.
	 *
	 * @param string $id
	 * @return string
	 */
	public static function help_get($id) {
		$db = self::$db;
		return $db->get("SELECT helptext FROM help WHERE helpkey=?", $id);
	}

	/**
	 * Set help text for a contexthelp
	 *
	 * Used by the contexthelp widget.
	 *
	 * @param string $id
	 * @param string $text
	 * @return bool
	 */
	public static function help_set($id, $text) {
		$db = self::$db;
		$exists = $db->get("SELECT id FROM help WHERE helpkey=?", $id);
		if ($exists) {
			$success = $db->execute("UPDATE help SET helptext=? WHERE id=?", $text, $exists);
		} else {
			$success = $db->execute("INSERT INTO help (helpkey, helptext) VALUES (?,?)", $id, $text);
		}
		return $success;
	}

	public static function comments_add($pid, $text, $user = 0) {
		$db = self::$db;
		$pid = self::project_master_id($pid);
		if (!$user) $user = doc::getuser()->userid();
		$ts = new timestamp();
		$db->execute("INSERT INTO comments (projectid, created, commenter, comment) VALUES (?, ?, ?, ?)", $pid, $ts->todb(), $user, $text);
	}

	public static function comments_getall($pid, $page = 1, $perpage = 20) {
		$db = self::$db;
		$pid = self::project_master_id($pid);
		$ret = $db->getall("SELECT SQL_CALC_FOUND_ROWS CONCAT(u.firstname, ' ', u.lastname) as author, ".
											 "comment, created ".
											 "FROM comments c ".
											 "LEFT JOIN users u ON u.userid=c.commenter ".
											 "WHERE c.projectid=? ".
											 "ORDER BY c.created DESC ".
											 "LIMIT ".(($page-1)*$perpage).",".$perpage, $pid);
		self::$foundrows = $db->get("SELECT FOUND_ROWS()");
		return $ret;
	}

	public static function project_master_id($pid) {
		$db = self::$db;
		$publishof = $db->get("SELECT publishof FROM projects WHERE id=?", $pid);
		if ($publishof) $pid = $publishof;
		return $pid;
	}

}

// make sure the database gets initialized
db_layer::init();

?>
