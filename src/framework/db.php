<?php
/**
 * DB.PHP - A Simple Database Framework for MySQL & PHP5
 *
 * PHP4 support would be nice but will require a port due to object oriented programming
 * changes in PHP5
 *
 * <pre>by Nickolaus Wing
 * March 2006</pre>
 * @package database
 */

/**
 * Database Framework Class
 *
 * The DB class is an abstraction for the MySQL functions provided by PHP.
 * It enables the developer to program in Perl/DBI style, where a
 * placeholder(?) stands in for strings, and the values are placed in the
 * query in the safest manner available. See {@link getall()} for more details
 * about placeholders.
 *
 * Currently requires PHP 5.
 *
 * This class has a lot of nifty features:
 * <ul>
 * <li>convenient single method queries</li>
 * <li>advanced data pre-processing<br/>(see {@link massupdate()} and {@link getrandomkeys()})</li>
 * <li>advanced data post-processing<br/>(see {@link getrecursed()} and {@link getallcolumns()})</li>
 * <li>supports mysqli_multi_query()<br/>(see {@link multiple()})</li>
 * <li>self-contained error handling<br/>(see {@link __construct()}, {@link error()}, and {@link errmsg()})</li>
 * <li>built in query timer<br/>(see {@link querytime()} and {@link logging_callback()})</li>
 * <li>automatically disables magic quotes<br/>(see {@link __construct()})</li>
 * <li>able to switch cleanly between mysql and mysqli</li>
 * <li>automatic prepared statement reuse under mysqli<br/>(see {@link cache_queries()})</li>
 * </ul>
 *
 * @package database
 * @author Nick Wing
 * @version 1.3.3
 */
class db {

	/************** Persistent Variables **************/

	// Server definition defaults
	private $server = "localhost";
	private $username = "root";
	private $password = "";
	private $dbname = "";
	// Option defaults
	private $compress = FALSE;
	private $settings = array(
								raise_error => FALSE,
								print_error => TRUE,
								cache_queries => FALSE,
								debug_mode => FALSE,
								undo_magic_quotes => TRUE,
								logging_callback => FALSE,
								table_prefix => '');
	private $mysqli = TRUE;
	// Status variables
	private $querytime;
	private $logging;
	private $insertid;
	private $rows;
	private $error;
	private $errormsg;
	// The query cache
	private $querycache = array();
	// Handler
	private $dblink;

	/************** Connect to a database **************/

	/**
	 * Database Interface Constructor
	 *
	 * The constructor takes two inputs, a database reference that should match
	 * a reference set up in the config file, and a settings array with descriptive
	 * keys.
	 *
	 * Currently there are 5 possible settings (defaults are displayed):
	 * <pre>$db = new db("mydb", array(
	 *    'raise_error' => FALSE,       // halt execution upon error
	 *    'print_error' => TRUE,        // echo error messages immediately
	 *    'cache_queries' => FALSE,     // see {@link cache_queries()}
	 *    'debug_mode' => FALSE,        // see {@link debug_mode()}
	 *    'undo_magic_quotes' => TRUE   // Repairs the damage if magic quotes was on
	 *    'logging_callback' => FALSE   // see {@link logging_callback()}
	 *    'timeout' => 30               // sets a connection timeout
	 * ));</pre>
	 * Note that magic quotes is evil, and therefore deactivated, by force if necessary.
	 * If you absolutely must have magic quotes, you must set the 'undo_magic_quotes' setting
	 * to FALSE.  If you run with magic quotes on, remember to stripslashes() when you feed
	 * form data to this class.
	 */
	function __construct($dbref = "0", $settings = array()) {
		global $cfg;
		foreach ($settings as $key => $val) $this->settings[$key] = $val;

		// Check for presence of mysql modules
		if (!function_exists('mysql_connect') && !function_exists('mysqli_connect')) {
			$this->handle_error('Neither mysql nor mysqli module has been loaded.  PHP was likely not compiled with --with-mysql or --with-mysqli.');
			return;
		}

		// Get connection settings from config info
		$dbname = $cfg['db'][$dbref]['dbname'];
		$server = $cfg['db'][$dbref]['server'];
		$user = $cfg['db'][$dbref]['username'];
		$this->mysqli = ($cfg['db'][$dbref]['extension'] != 'mysql' && function_exists('mysqli_connect'));
		if ($server) $this->server = $server;
		if ($user) $this->username = $user;
		if ($cfg['db'][$dbref]['password']) $this->password = $cfg['db'][$dbref]['password'];
		if ($cfg['db'][$dbref]['compress']) $this->compress = $cfg['db'][$dbref]['compress'];
		if ($dbname) $this->dbname = $dbname;

		// If a db class has already been instantiated with these stats
		// then we have a valid connection already. No need to connect again.
		if (is_object($cfg['db'][$server][$user][$dbname]['link'])) {
			$this->dblink = $cfg['db'][$server][$user][$dbname]['link'];
		} else {
			// open a connection to the database
			$this->connect($settings['timeout']);
			// store the link so we can use it when this class gets instantiated again
			$cfg['db'][$server][$user][$dbname]['link'] = $this->dblink;
		}

		// check for the ever-pernicious presence of magic quotes
		if ($this->settings['undo_magic_quotes']) db::undo_magic_quotes();

		return;
	}

	/************** Query functions **************/

	/**
	 * Get a single value
	 *
	 * For quickly retrieving a single value from the database, e.g.
	 * <pre>$name = $db->get("SELECT name FROM table WHERE id=2");</pre>
	 * @return mixed
	 * @param string $query
	 * @param [mixed $bindparam1]
	 * @param [mixed $bindparam2]
	 * @param [mixed $etc...]
	 */
	public function get($query) {
		$bind_params = $this->get_params(func_get_args());
		$result = $this->query($query, $bind_params);
		if (!$result) $result = array();
		if (!is_array($result[0])) return '';
		return reset($result[0]);
	}

	/**
	 * Get a single row
	 *
	 * For retrieving a single row from the database, e.g.
	 * <pre>$row = $db->getrow("SELECT name, age FROM table WHERE id=?", 2);
	 * echo $row['name'] . ', age ' . $row['age'];</pre>
	 * @return array
	 * @param string $query
	 * @param [mixed $bindparam1]
	 * @param [mixed $bindparam2]
	 * @param [mixed $etc...]
	 */
	public function getrow($query) {
		$bind_params = $this->get_params(func_get_args());
		$result = $this->query($query, $bind_params);
		if (!$result) $result = array();
		if (!is_array($result[0])) return array();
		return $result[0];
	}

	/**
	 * Get a column of results
	 *
	 * For retrieving all values in the selected column in a single array, e.g.
	 * <pre>$names = $db->getcolumn("SELECT name FROM table WHERE id IN (?,?,?)", 2, 14, 15);
	 * foreach ($names as $name) {
	 *    echo $name . "\n";
	 * }</pre>
	 * @return array
	 * @param string $query
	 * @param [mixed $bindparam1]
	 * @param [mixed $bindparam2]
	 * @param [mixed $etc...]
	 */
	public function getcolumn($query) {
		$bind_params = $this->get_params(func_get_args());
		$result = $this->query($query, $bind_params);
		if (!$result) $result = array();
		$return = array();
		foreach ($result as $row) {
			$return[] = reset($row);
		}
		return $return;
	}

	/**
	 * Get a full set of results
	 *
	 * For retrieving multiple rows as an array, e.g.
	 * <pre>$array = $db->getall("SELECT name, age FROM table WHERE id IN (?,?,?)", 2, 4, 15);
	 * foreach ($array as $row) {
	 *    echo $row['name'] . ', age ' . $row['age'];
	 * }</pre>
	 *
	 * <b>General Query Notes</b>
	 *
	 * Since this is the primary retrieval method, I'll include here a general discussion
	 * about writing queries for this class.
	 *
	 * <b>Placeholders</b>
	 *
	 * This class uses the question mark (?) as a placeholder for string values.  For example:
	 * <pre>$db->getall("SELECT * FROM mytable WHERE lastname=?", "O'Reilly");</pre>
	 *
	 * As you can see, this is safer than "... lastname='O'Reilly'", because the ' in O'Reilly
	 * breaks single quotes.
	 *
	 * <b>Bound Parameters</b>
	 *
	 * Once you've placed question marks in your query, you'll need to specify the string values
	 * they represent.  To do this, you supply the correct number of <i>bound parameters</i> as extra
	 * function parameters.  These will be substituted in before the query is executed.
	 *
	 * <pre>$db->getall("SELECT * FROM mytable WHERE lastname=? AND firstname=?", "Wing", "Nick");</pre>
	 *
	 * You may include arrays in the bound parameters if you wish, they will automatically be
	 * expanded in place.  For instance:
	 *
	 * <pre>$db->getall("SELECT * FROM mytable WHERE lastname IN (?,?,?)",
	 *             "O'Reilly", array("Wing", "Smith"));</pre>
	 *
	 * <b>Advanced Placeholders</b>
	 *
	 * There are also advanced placeholder syntaxes (?* and ?{4}) that can sometimes
	 * prove useful, particularly with arrays and the mysql IN operator. For example:
	 * <pre>$db->getcolumn("SELECT party FROM presidents WHERE lastname IN (?*)",
	 *                "Washington", "Lincoln", "Clinton");
	 * // equivalent alternative
	 * $db->getcolumn("SELECT party FROM presidents WHERE lastname IN (?{3})",
	 *                "Washington", "Lincoln", "Clinton");</pre>
	 *
	 * The placeholders here will be expanded to act as three comma-separated placeholders so
	 * that you don't have to do it yourself with a foreach loop.
	 *
	 * Note that, as mentioned earlier, you can optionally pass arrays of bound parameters,
	 * making the whole process quite painless.
	 *
	 * <b>Type Issues</b>
	 *
	 * mysqli prepared statements require that a type be specified for every bound parameter
	 * for extra safety.  I found this highly inconvenient and unnecessary so types are automatically
	 * detected before they're sent to mysqli.
	 *
	 * If this becomes problematic, or if you like the extra check (if you use my html framework,
	 * formelement::check_integer() or the like would be highly recommended instead), you can
	 * manually specify a type by passing the bound parameter in the same way you would for mysqli.
	 * Specifically, as an array with the first element defining the type ('s' for string, 'b'
	 * for blob, 'i' for integer, 'd' for double/float) and the second element being the data itself.
	 * For example:
	 *
	 * <pre>$db->get("SELECT lastname FROM mytable WHERE id=?", 34);
	 * becomes:
	 * $db->get("SELECT lastname FROM mytable WHERE id=?", array('i', 34));</pre>
	 *
	 * If the data does not match the type, an error will be thrown.  This may or may not
	 * interrupt processing.  Your query might go through successfully (despite the type
	 * mismatch!) unless you have RAISE_ERROR set.
	 *
	 * @return array
	 * @param string $query
	 * @param [mixed $bindparam1]
	 * @param [mixed $bindparam2]
	 * @param [mixed $etc...]
	 */
	public function getall($query) {
		$bind_params = $this->get_params(func_get_args());
		$return = $this->query($query, $bind_params);
		if (!$return) $return = array();
		return $return;
	}

	/**
	 * Get many columns of results
	 *
	 * Inverts the 2-D array returned by {@link getall()}, so that you
	 * can access data in a column oriented way.
	 *
	 * For example:<pre>$data = $db->getallcolumns("SELECT name, age FROM table WHERE id IN (2,4,15)");
	 * foreach ($data as $col => $row) {
	 *    echo $col . ":";
	 *    foreach ($row as $val) {
     *       echo " " . $val;
     *    }
     *    echo "&lt;br&gt;\n";
     * }
	 * </pre>
	 * Prints:<pre>
	 * name: Nick Kasey Aaron
	 * age: 25 24 22
	 * </pre>
	 * @return array
	 * @param string $query
	 * @param [mixed $bindparam1]
	 * @param [mixed $bindparam2]
	 * @param [mixed $etc...]
	 */
	public function getallcolumns($query) {
		$bind_params = $this->get_params(func_get_args());
		$result = $this->query($query, $bind_params);
		if (!$result) $result = array();
		$return = array();
		foreach ($result as $i => $row) {
			foreach ($row as $j => $value) {
				$return[$j][$i] = $value;
			}
		}
		return $return;
	}

	/**
	 * Get recursive data
	 *
	 * Allows you to group up several columns for easy multi-layer displays.
	 *
	 * The $control array can be a list of columns (or aliases) that belong to the
	 * first group, or many lists (array of arrays).  In the latter case, the first
	 * list defines the first grouping, the second list defines the second grouping, etc.
	 * So if you have data that requires 3 foreach() loops, you should send an array
	 * containing 2 column lists.
	 *
	 * The first column in each list is the key on which the grouping will occur.
	 * All other columns in the list should be dependent on it.  You MUST have a
	 * unique column to key on. Multi-column keys aren't supported.  You can always
	 * select an extra column that is a CONCAT of your two keys, or something like that.
	 *
	 * To loop through the recursive results, foreach() through the special key
	 * $row['recursedata'].
	 *
	 * This is an example of the single grouping syntax:
	 * <pre>$data = $db->getrecursed(array('invno', 'invdate'),
	 * "SELECT i.invno, i.invdate, l.sku, l.qty FROM invoices i, lineitems l" .
	 * "WHERE l.invoice=i.invno");
	 *
	 * foreach ($data as $invoice) {
	 *    echo 'invno: ' . $invoice['invno'] . "\n";
	 *    echo 'invdate: ' . $invoice['invdate'] . "\n";
	 *    foreach ($invoice['recursedata'] as $lineitem) {
	 *       echo '   lineitem: ' . $lineitem['qty'] . ' x ' . $lineitem['sku'] . "\n";
	 *    }
	 * }</pre>
	 *
	 * You will usually see a performance gain with this method.  It transfers
	 * redundant data that the multi-query approach would not, but requires only one
	 * communication.  The more data your query returns, the slower this becomes
	 * in comparison with a multiple-query solution.  You can always use {@link querytime()}
	 * to benchmark it.
	 * @return array
	 * @param array $control
	 * @param string $query
	 * @param [mixed $bindparam1]
	 * @param [mixed $bindparam2]
	 * @param [mixed $etc...]
	 */
	public function getrecursed($control, $query) {
		$start = microtime(TRUE);
		$args = func_get_args();
		array_shift($args);
		$bind_params = $this->get_params($args);
		$result = $this->query($query, $bind_params);
		if (!$result) $result = array();
		$return = $this->formatrecursedata($result, $control);
		$this->querytime = microtime(TRUE) - $start;
		return $return;
	}

	/**
	 * Get Single-Value-per-Depth Recursive Data
	 *
	 * This retrieves information from the database and assumes that you need
	 * it in a deep array with format
	 * <pre>$data[col1value][col2value]...[coln-1value] = array(colnvalues)</pre>
	 *
	 * For example, the return array might look like this:
	 * <pre>$data = array(
	 *    'presidents'=>array(
	 *       'democrats'=>array(
	 *          'Carter',
	 *          'Clinton'
	 *       ),
	 *       'republicans'=>array(
	 *          'Nixon',
	 *          'Reagan',
	 *          'Bush'
	 *       )
	 *    )
	 * );</pre>
	 *
	 * This is useful for many kinds of hierarchical information, particularly
	 * interface menus, which can have submenus that pop up on mouseover.  It can
	 * also be useful when generating HTML select boxes that are linked by javascript.
	 *
	 * Note that non-unique values in the same group will be combined, and will pool
	 * their children.
	 *
	 * @return array
	 * @param string $query
	 * @param mixed $bindparam1
	 * @param mixed $bindparam2
	 * @param mixed $etc
	 */
	public function getmenudata($query) {
		$start = microtime(TRUE);
		$bind_params = $this->get_params(func_get_args());
		$result = $this->query($query, $bind_params);
		if (!$result) return array();
		if (!is_array($result[0])) return array();

		foreach (array_keys($result[0]) as $col) {
			$control[] = $col;
			$cont[] = array($col);
		}
		array_pop($cont);
		$data = $this->formatrecursedata($result, $cont);

		$return = $this->recursemenu($data, $control, 0);
		$this->querytime = microtime(TRUE) - $start;
		return $return;
	}

	/**
	 * Get a random sample of primary keys
	 *
	 * Selecting random rows from a relational database table is often a sticky
	 * issue.  The standard ORDER BY RAND() LIMIT 10 is a slow table scan
	 * query, and many other solutions are not truly random, are unreliable,
	 * are still slow, or produce duplicates.
	 *
	 * Use this method instead to keep up to date with the latest and greatest
	 * routine we've thought of.
	 *
	 * <pre>$ids = $db->getrandomkeys("mytable", "id", 25, "datecol > NOW - INTERVAL 1 MONTH");
	 * $data = $db->getall("SELECT col1, col2, col3 FROM mytable WHERE id IN (?*)", $ids);</pre>
	 *
	 * @return array
	 * @param string $table
	 * @param string $key
	 * @param int $num
	 * @param string $where
	 * @param mixed $param1
	 * @param mixed $param2
	 * @param mixed $etc...
	 */
	public function getrandomkeys($table, $key, $num = 10, $where = "") {
		// start the timer
		$start = microtime(TRUE);

		// get the bound parameters for the $where clause
		$args = func_get_args();
		array_shift($args);
		array_shift($args);
		array_shift($args);
		$bind_params = $this->get_params($args);

		// we only want to include a WHERE clause if the user specified one
		if ($where) $fullwhere = " WHERE $where";

		// generate $num random numbers inside the appropriate range
		$max = $this->get("SELECT COUNT(*) FROM `$table`".$fullwhere, $bind_params) - 1;
		$min = 0;
		for ($i = 0; $i < $num; $i++) {
			$primkey = mt_rand($min,$max);
			if ($primkey && !$used[$primkey]) {
				$lims[] = $primkey;
				$used[$primkey] = TRUE;
			}
		}

		// generate queries to retrieve ids, use LIMIT to select the random row
		$qparams = array();
		foreach ($lims as $lim) {
			// we can improve speed here a little bit - if the row we want
			// is further than half way down, we'll flip the sorting and
			// bring the row closer to the top
			if ($lim > $max / 2) { $desc = "DESC"; $lim = $max - $lim; }
			else $desc = "";

			$qs[] = "(SELECT `$key` FROM `$table`".$fullwhere." ORDER BY `$key` $desc LIMIT $lim,1)";
			foreach ($bind_params as $p) $qparams[] = $p;
		}

		// UNION the queries and get them all at once
		$ids = $this->getcolumn(implode(' UNION ALL ', $qs), $qparams);

		// finish up the query and return the ids
		$this->rows = count($ids);
		$this->querytime = microtime(TRUE) - $start;
		return $ids;
	}

	/**
	 * Execute an SQL command
	 *
	 * For executing an UPDATE, DELETE, CREATE, etc. Returns TRUE if successful.
	 * @return bool
	 * @param string $query
	 * @param [mixed $bindparam1]
	 * @param [mixed $bindparam2]
	 * @param [mixed $etc...]
	 */
	public function execute($query) {
		$bind_params = $this->get_params(func_get_args());
		return ($this->query($query, $bind_params) == TRUE);
	}

	/**
	 * Perform many queries at once
	 *
	 * Under mysqli, this method will use mysqli_multi_query() to perform many queries
	 * in just one server communication. It will retrieve all the results from
	 * every query and is perfectly suitable for bundling SELECT queries as well as
	 * any other query.
	 *
	 * Under the mysql extension, this method will behave exactly like it does under
	 * mysqli, but will not gain a performance advantage.
	 *
	 * <pre>$data = $db->multiple(
	 * "SELECT ...", array('param1','param2'),
	 * "UPDATE ...", array('param1')
	 * );
	 * foreach ($data as $i => $result) {
	 *    if (is_bool($result))
	 *       echo 'Query ' . $i . ' was an execute-type and returned ' . $result . '.';
	 *    else {
	 *       echo "Query " . $i . " returned results:\n";
	 *       foreach ($result as $row) {
	 *          echo $row['col1'] . ' ' . $row['col2'] . "\n";
	 *       }
	 *    }
	 * }
	 * </pre>
	 *
	 * Returns an array, each row could be a boolean, an empty array, or a 2-d array
	 *
	 * {@link insertid()} and {@link rows()} will both return ARRAYS after this, each element
	 * will correspond to a different query, in the order they were submitted.
	 *
	 * There is an alternate parameter set for this method.  You may bundle up all the
	 * arguments in an array and send that instead.  This makes it easier to automate
	 * query generation for input to this method.
	 * @return array
	 * @param string $query1
	 * @param [array $bind_values1][
	 * @param string $query2
	 * @param [array $bind_values2]]
	 */
	public function multiple() {
		$save_level = error_reporting(0);
		$args = func_get_args();
		if (is_array($args[0])) $args = $args[0];
		$start = microtime(TRUE);
		if ($this->mysqli) { $return = $this->multi_queryi($args); }
		else { $return = $this->multi_query($args); }
		if (!$return) $return = array();
		$end = microtime(TRUE);
		$this->querytime = $end - $start;
		error_reporting($save_level);
		return $return;
	}

	/**
	 * Simple Update
	 *
	 * This method is meant to simplify updating a table if you've already got an array
	 * preloaded with the desired values.  This can be very useful with the $_POST array,
	 * if your form element names match database column names.
	 *
	 * It will simply ignore any columns in $info that don't correspond to a column in the database.
	 *
	 * <pre>$_POST = array(
	 *    'unique_id' => 23,
	 *    'firstname' => 'george',
	 *    'lastname' => 'wilson',
	 *    'dateofbirth' => '05/17/1962',
	 *    'ssn' => '534870325'
	 * );
	 *
	 * $db->update('mytable', 'unique_id', $_POST);</pre>
	 *
	 * $keys can be an array, or if there is only one key, a string.  You must specify
	 * a key column.  Un-keyed updates should not be performed with this method.
	 *
	 * $cols is an optional input you may use to specify only the columns you want
	 * updated.
	 *
	 * Returns false only if there's an error.  Check rows() to determine if any
	 * rows were affected.
	 *
	 * @return bool
	 *
	 */
	public function update($table, $keys, $info, $cols = array()) {
		$start = microtime(TRUE);
		// no need to pass array if only one key
		if (!is_array($keys)) $keys = array($keys);
		// string value for $cols?
		if (!is_array($cols) && $cols) $cols = array($cols);
		// pre-process to find applicable columns
		if (empty($cols)) {
			$show = $this->getallcolumns("SHOW COLUMNS FROM `$table`");
			if (empty($show['Field'])) return FALSE;
			foreach ($show['Field'] as $col) {
				if (isset($info[$col])) $cols[] = $col;
			}
		}
		$cols = array_diff($cols, $keys);

		// make sure we have work to do
		if (empty($cols)) { $this->handle_error('No columns to update.'); return FALSE; }
		if (empty($keys) || !$keys[0]) { $this->handle_error('No keys specified, full table update should not be performed with update() method.'); }

		// start building the query
		$q = "UPDATE `$table` SET ";

		// prepare columns to update
		foreach ($cols as $col) {
			$s[] = "`$col`".'=?';
			$b[] = $info[$col];
		}
		$q .= implode(',', $s).' ';

		// prepare keys
		foreach ($keys as $key) {
			$ks[] = "`$key`".'=?';
			$b[] = $info[$key];
		}
		$q .= 'WHERE '.implode(' AND ', $ks);

		// execute the update
		$return = $this->execute($q, $b);

		// clean up
		$this->querytime = microtime(TRUE) - $start;
		return $return;
	}

	/**
	 * Simple Insert
	 *
	 * This method is meant to simplify inserting into a table if you've already got an array
	 * preloaded with the desired values.  This can be very useful with the $_POST hash,
	 * if your form element names match database column names.
	 *
	 * It will simply ignore any columns in $info that don't correspond to a column in the database.
	 *
	 * Will not specify a value for an auto_increment column.  If that (very unusual) behavior is
	 * desired, do not use this method.
	 *
	 * <pre>$_POST = array(
	 *    'unique_id' => 23,
	 *    'firstname' => 'george',
	 *    'lastname' => 'wilson',
	 *    'dateofbirth' => '05/17/1962',
	 *    'ssn' => '534870325'
	 * );
	 *
	 * $db->insert('mytable', $_POST);</pre>
	 *
	 * $cols is an optional input you may use to specify only the columns you want
	 * inserted.
	 *
	 * Returns false only if there's an error.  Check insertid() to get any auto_increment
	 * values back.
	 *
	 * @return bool
	 *
	 */
	public function insert($table, $info, $cols = array()) {
		$start = microtime(TRUE);
		// string value for $cols?
		if (!is_array($cols) && $cols) $cols = array($cols);
		// pre-process to find applicable columns
		if (empty($cols)) {
			$show = $this->getallcolumns("SHOW COLUMNS FROM `$table`");
			if (empty($show['Field'])) return FALSE;
			foreach ($show['Field'] as $i => $col) {
				if (isset($info[$col]) && $show['Extra'][$i] != 'auto_increment') $cols[] = $col;
			}
		}

		// make sure we have work to do
		if (empty($cols)) { $this->handle_error('No columns to insert.'); return FALSE; }

		// prepare columns to insert
		foreach ($cols as $col) {
			$c[] = "`$col`";
			$v[] = '?';
			$b[] = $info[$col];
		}

		// build the query
		$q = "INSERT INTO `$table` (".implode(',', $c).') VALUES ('.implode(',',$v).')';

		// execute the update
		$return = $this->execute($q, $b);

		// clean up
		$this->querytime = microtime(TRUE) - $start;
		return $return;
	}


	/**
	 * Massive Table Insertion
	 *
	 * Insert a large set of data from an outside source.
	 *
	 * Any columns you leave out of a particular row will be submitted
	 * as an empty string ('') <b>not</b> a NULL value.  It is therefore
	 * safe to use with NOT NULL columns (which should be the standard.. NULL
	 * columns have performance issues).
	 *
	 * Could produce an error if any single row contains more than 256kB of data.
	 *
	 * If you do not want to filter out unique key duplicates, you may set the third
	 * parameter to true, and INSERT IGNORE INTO will be used to ignore any duplicate
	 * key errors.
	 *
	 * Returns the number of rows inserted.
	 *
	 * @return int
	 */
	public function massinsert($table, $data, $ignore = FALSE) {
		$start = microtime(TRUE);
		if ($ignore) $ignore = " IGNORE";
		else $ignore = "";
		$max_query_size = 768 * 1024;

		$updatecols = db::get_result_columns($data);
		$lastcol = end($updatecols);

		$paramlen = 0;
		$queries = array();
		$allparams = array();
		$whichquery = 0;
		$query =& $queries[$whichquery];
		$params =& $allparams[$whichquery];
		$i = 0;
		foreach ($data as $row) {
			$query .= '(';
			foreach ($updatecols as $col) {
				if ($row[$col]) {
					$query .= '?';
					$params[] = $row[$col];
					$paramlen += strlen($row[$col]) - 1;
				} elseif ($row[$col] == "0") {
					$query .= '0';
				} else {
					$query .= "''";
				}
				if ($col != $lastcol) $query .= ',';
			}
			$query .= ')';
			if (strlen($query) + $paramlen >= $max_query_size) {
				$whichquery += 1;
				$query =& $queries[$whichquery];
				$params =& $allparams[$whichquery];
				$paramlen = 0;
			} else {
				if ($i < (count($data) - 1)) $query .= ',';
			}
			$i++;
		}

		$collist = implode(",", $updatecols);

		$cache = $this->cache_queries(FALSE);
		foreach ($queries as $i => $query) {
			$return = $this->query("INSERT$ignore INTO $table ($collist) VALUES $query", $allparams[$i]);
		}
		$this->cache_queries($cache);

		$this->querytime = microtime(TRUE) - $start;
		return $return;
	}

	/**
	 * Massive Table Update
	 *
	 * Update a table with a large set of data from an outside source.
	 * Will only update rows that match the key(s).  Will not insert new rows.
	 *
	 * $keys is the unique key to use to match up rows.  It may be an array
	 * if you require more than one column for your unique key.  Key values should
	 * be shorter than 256 bytes each.
	 *
	 * Any columns present in $data that are not listed as a key will be updated.
	 * If you do not wish for a particular row to be updated in a particular column,
	 * simply do not include the column in that row.  This will be handled correctly.
	 * However, if you send the column, even set to empty string, the table will be
	 * updated.
	 *
	 * Note that any row which does not include a value for EVERY key will be
	 * automatically skipped. Empty values are fine, as long as $row[$key] passes
	 * an isset() check.
	 *
	 * Returns the number of rows that were successfully updated.
	 *
	 * Example use:
	 * <pre>$db->massupdate("shipments", "tracknum", array(
	 *    array('tracknum' => "1ZE782360393773035", 'cost' => 4.35, 'published' => 5.60),
	 *    array('tracknum' => "521297363841847", 'cost' => 2.87, 'published' => 3.41)
	 * ));</pre>
	 *
	 * $method is an option to control how massupdate() executes. There are three
	 * possible values:
	 * <pre>'union'     : default.  The fastest option.  Completes the operation in
	 *               one query (unless input is very large). If an error occurs and
	 *               the input data is very large it is possible that updates will
	 *               not all fail together.  Also can generate an error with rows
	 *               that are >160kB ea.
	 * 'temptable' : most stable. Only slightly slower than 'union'.  Completes the
	 *               operation in four queries (create temp table, put data in it,
	 *               update real table, drop temp table).  If an error occurs the
	 *               entire update fails and no data is altered.
	 * 'simple'    : mainly provided for reference.  This is how one would normally
	 *               execute a mass update.  Can be many times slower than the other
	 *               two options, and updates do not fail together.  It is very easy
	 *               to understand, however, so should not have code errors in it.
	 * </pre>
	 * @return int
	 * @param string $table
	 * @param mixed $keys
	 * @param array $data
	 * @param string $method
	 */
	public function massupdate($table, $keys, $data, $method = 'union') {
		if (!is_array($keys)) $keys = array($keys);
		$start = microtime(TRUE);

		foreach ($data as $row) {
			foreach ($row as $val) {
				if (!empty($val) && !is_string($val) && !is_numeric($val)) {
					$this->handle_error("Invalid input to massupdate() function.");
					return 0;
				}
			}
		}

		switch ($method) {
			case 'union' 		: $return = $this->massupdate_union($table, $keys, $data);
								  break;
			case 'simple' 		: $return = $this->massupdate_simple($table, $keys, $data);
								  break;
			case 'temptable' 	: $return = $this->massupdate_temptable($table, $keys, $data);
								  break;
		}
		$this->querytime = microtime(TRUE) - $start;
		$this->insertid = 0;
		$this->rows = $return;
		return $return;
	}

	/************** Access to status information **************/

	/**
	 * Returns number of rows affected by last query.
	 *
	 * Note: This method will return an array after a call to {@link multiple()}.
	 * @return int
	 */
	public function rows() {
		return $this->rows;
	}

	/**
	 * Return the auto_increment value if last query was an insert.
	 *
	 * Note: This method will return an array after a call to {@link multiple()}.
	 * @return int
	 */
	public function insertid() {
		return $this->insertid;
	}

	/**
	 * Query Timer
	 *
	 * Returns the execution time for the most recent query, or
	 * all queries combined in a call to {@link multiple()}.  It is not possible
	 * to get individual times for queries combined with {@link multiple()}.
	 * @return float
	 */
	public function querytime() {
		return $this->querytime;
	}

	/**
	 * Error Detection
	 *
	 * Returns TRUE if (and only if) the most recent query returned an error.
	 * Use {@link errmsg()} to print an error message.
	 * @return bool
	 */
	public function error() {
		return $this->error;
	}

	/**
	 * Error Description
	 *
	 * Returns a string describing the error indicated by {@link error()}.  It will
	 * be empty when {@link error()} is FALSE.
	 * @return string
	 */
	public function errmsg() {
		return $this->errormsg;
	}

	/************** Change behavioral settings **************/

	/**
	 * Set a table prefix
	 *
	 * All non-quoted appearances of [pre] will be replaced by this
	 * value, intended to be used as an easy way to use a dynamic
	 * table prefix so you can easily port your tables into
	 *	another*database with naming conflicts.
	 *
	 * Returns the old value in case you need it.
	 */
	public function set_table_prefix($prefix) {
	  $return = $this->settings['table_prefix'];
		$this->settings['table_prefix'] = $prefix;
		return $return;
	}

	/**
	 * Toggle the raise_error setting
	 *
	 * When this toggle is set to TRUE, the class will automatically halt the whole PHP
	 * script when there's an error.  It will obey {@link print_error()}  before it halts.
	 *
	 * Returns the old toggle setting to make it easy to toggle it momentarily.
	 * @param bool $flag
	 * @return bool
	 */
	public function raise_error($flag = TRUE) {
		$return = $this->settings['raise_error'];
		$this->settings['raise_error'] = $flag ? TRUE : FALSE;
		return $return;
	}

	/**
	 * Toggle the print_error setting
	 *
	 * When this toggle is set to TRUE, the class will automatically echo a descriptive message
	 * when there's an error.  This will allow users to gain information about your code, so
	 * it should be switched off in a production environment.
	 *
	 * Returns the old toggle setting to make it easy to toggle it momentarily.
	 * @param bool $flag
	 * @return bool
	 */
	public function print_error($flag = TRUE) {
		$return = $this->settings['print_error'];
		$this->settings['print_error'] = $flag ? TRUE : FALSE;
		return $return;
	}

	/**
	 * Toggle the cache_queries setting
	 *
	 * When this toggle is set to TRUE, the class will begin to store queries for
	 * later use.  It uses mysqli (if available) to prepare statements with the MySQL
	 * server and then reuses those prepared statements when appropriate, to gain a speed
	 * boost.
	 *
	 * This is only helpful for queries that appear inside of a loop or a frequently used
	 * function.  For queries that are only run once per page load, there will be a slight
	 * speed decrease.
	 *
	 * It is recommended that you use this method to switch caching on and off before and
	 * after you enter a loop.
	 *
	 * For example:
	 * <pre>$db->cache_queries(TRUE);
	 * for ($i = 0; $i < 1000; $i++) {
	 *    $db->execute("UPDATE users SET active=0 WHERE id=?", $i);
     * }
     * $db->cache_queries(FALSE);</pre>
	 *
	 * Returns the old toggle setting to make it easy to toggle it momentarily.
	 * @param bool $flag
	 * @return bool
	 */
	public function cache_queries($flag = TRUE) {
		$return = $this->settings['cache_queries'];
		$this->settings['cache_queries'] = $flag ? TRUE : FALSE;
		return $return;
	}

	/**
	 * Toggle debug mode
	 *
	 * When in debug mode, no queries will be relayed to MySQL.
	 * Instead, every query will produce an error message containing the query
	 * that would have been sent if it weren't in debug mode.
	 *
	 * You can turn on debug mode around a single query, simply put $db->debug_mode(TRUE)
	 * before it, and $db->debug_mode(FALSE) after it.
	 *
	 * Returns the old toggle setting to make it easy to toggle it momentarily.
	 * @param bool $flag
	 * @return bool
	 */
	public function debug_mode($flag = TRUE) {
		$return = $this->settings['debug_mode'];
		$this->settings['debug_mode'] = $flag ? TRUE : FALSE;
		return $return;
	}

	/**
	 * Set a Logging Function
	 *
	 * Use this method to designate a custom function for logging query execution times
	 *
	 * The custom function you designate will be called after each query with the following inputs:
	 * <pre>
	 *      'query'   => the full query as sent to mysql (e.g. SELECT col WHERE id=6)
	 *      'unbound' => the query before bound parameters were substituted (e.g. SELECT col WHERE id=?)
	 *      'time'    => the execution time for the query
	 *      'cached'  => TRUE if the query had been previously cached
	 * </pre>
	 * @param callback $callback
	 */
	public function logging_callback($callback) {
		$this->settings['logging_callback'] = $callback;
	}

	/**
	 * Suspend query logging
	 *
	 * If you are logging queries in a database and using the same db object
	 * to INSERT the log entries, you must suspend logging or you will create
	 * an infinite loop!
	 * @param bool $flag
	 * @return void
	 */
	public function logging_suspend($flag = TRUE) {
		$this->logging['suspend'] = $flag;
	}

	/************** Other public functions **************/

	/**
	 * Select a New Database
	 *
	 * This method may be used to switch to another default database.
	 * Of course, the active user must have privileges on the new database.
	 *
	 * Any queries cached with the old database will not be used with new
	 * database, but remain available in case you switch back again.
	 * @param string $dbname
	 * @return bool
	 */
	public function select_db($dbname) {
		if ($this->mysqli) {
			if ($this->dblink->select_db($dbname)) {
				$this->move_dblink($dbname);
				return TRUE;
			}
		} else {
			if (mysql_select_db($dbname, $this->dblink)) {
				$this->move_dblink($dbname);
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Sort a database result set
	 *
	 * This method is designed to help you sort two-dimensional database results.
	 *
	 * Input goes like ($data, 'sortcolname1', 'asc', 'sortcolname2', 'desc', ...)
	 * @param array $data
	 * @param string $col1
	 * @param string $order1
	 * @param string $col2
	 * @param string $order2
	 * @param string $col3
	 * @param string $order3
	 **/
	public static function sort(&$data) {
		$args = func_get_args();
		$data = array_shift($args);
		if (!count($args) || count($args) % 2) return $data;
		self::$sortargs = $args;
		usort($data, array('db', 'sort_helper'));
	}
	
	/**
	 * @access private
	 */
	private static $sortargs;
	/**
	 * @access private
	 */
	public static function sort_helper($a, $b) {
		for ($i=0;$i<count(self::$sortargs);$i+=2) {
			$key = self::$sortargs[$i];
			$asc = (self::$sortargs[$i+1] == 'asc');
			if ($a[$key] > $b[$key]) {
				if ($asc) return 1;
				else return -1;
			} elseif ($a[$key] < $b[$key]) {
				if ($asc) return -1;
				else return 1;
			}
		}
		return 0;
	}

	/**
	 * @access private
	 */
	protected function move_dblink($dbname) {
		$cfg['db'][$this->server][$this->username][$dbname]['link'] = $this->dblink;
		unset($cfg['db'][$this->server][$this->username][$this->dbname]['link']);
		$this->dbname = $dbname;
		return;
	}

	/************** Private helper functions **************/

	/**
	 * Connect to the database
	 *
	 * This will form a connection to the database, whether you're using mysql or mysqli.
	 * It is called by the constructor, but may be called again to automagically resolve a
	 * "server has gone away" error
	 * @access private
	 */
	protected function connect($timeout = '') {
		if (!$timeout) $timeout = 30;
		$compress = $this->compress ? MYSQLI_CLIENT_COMPRESS : 0;
		// de-activate error reporting
		$save_level = error_reporting(0);
		if ($this->mysqli) {
			// do it the mysqli way
			$this->dblink = mysqli_init();
			$this->dblink->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
			$this->dblink->real_connect($this->server, $this->username, $this->password, $this->dbname, 0, '', $compress);
			if (mysqli_connect_errno()) {
				$this->handle_error("Failed to connect to MySQL server.\n" . mysqli_connect_error());
			}
		} else {
			// do it the old mysql way
			$this->dblink = mysql_connect($this->server, $this->username, $this->password, TRUE, $compress);
			if (!is_resource($this->dblink)) {
				$this->handle_error("Failed to connect to MySQL server.\n" . mysql_error());
			}
			if (!mysql_select_db($this->dbname, $this->dblink)) {
				$this->handle_error("Could not select desired database.\n" . mysql_error());
			}
		}
		// re-activate error reporting
		error_reporting($save_level);
	}

	/**
	 * MySQL Query
	 *
	 * this is the main interface to the public query functions
	 * it will decide between mysql and mysqli, record the execution time,
	 * and make sure the only error reporting is our own
	 * @access private
	 */
	protected function query($query, $bound_params = array()) {
		// Turn PHP's error reporting off for the duration of the query
		// we'll handle errors ourselves
		$save_level = error_reporting(0);
		$this->error = FALSE;
		$this->errormsg = "";

		// Prepare the bound parameters, both for convert_bindings
		// and for mysqli's prepared statements.
		$bound_params = $this->preproc_params($bound_params);
		$query = $this->preproc_placeholders($query, count($bound_params));

		// Respect debug mode
		if ($this->settings['debug_mode']) {
			$this->handle_error($this->convert_bindings($query, $bound_params));
			return FALSE;
		}

		// Initialize rows and insertid to make sure they don't carry over
		// from the last query
		$this->rows = 0;
		$this->insertid = 0;

		// Start the query timer
		$start = microtime(TRUE);

		// Switch between mysql and mysqli
		if ($this->mysqli) {
			if ($this->settings['cache_queries']) {
				$return = $this->query_mysqli($query, $bound_params);
			} else {
				$return = $this->mysqli_nocache($query, $bound_params);
			}
		} else {
			$return = $this->query_mysql($query, $bound_params);
		}

		// End the query timer
		$end = microtime(TRUE);
		$this->querytime = $end - $start;
		// call the custom logging function, if they've set one
		$cback = $this->settings['logging_callback'];
		if (!empty($cback) && is_callable($cback) && !$this->logging['suspend'])
			call_user_func($cback, $this->logging['query'], $query, $this->querytime, $this->logging['cached']);

		// Set error reporting back to where it was
		error_reporting($save_level);

		// Return the results
		return $return;
	}

	/**
	 * Escape Quotes
	 *
	 * A generic function that will allow you to properly escape characters for
	 * strings in SQL statements, whether you are using mysql or mysqli.
	 *
	 * Better than addslashes() since it will respect the character set currently
	 * in use for the connection.
	 * @access private
	 */
	protected function escape_str($str) {
		if ($this->mysqli)
			return $this->dblink->real_escape_string($str);
		else
			return mysql_real_escape_string($str, $this->dblink);
	}

	/**
	 * MySQL Query - mysql extension
	 *
	 * This will carry out the database communication if we
	 * decide to use the mysql extension.
	 * @access private
	 */
	protected function query_mysql($query, $bound_params) {
		$query = $this->convert_bindings($query, $bound_params);
		if (!$query) return FALSE;
		$this->logging['query'] = $query;
		$result = mysql_query($query, $this->dblink);
		if (mysql_errno($this->dblink)) {
			$this->handle_error(mysql_error($this->dblink));
			return FALSE;
		}
		if (is_bool($result)) {
			$this->insertid = mysql_insert_id($this->dblink);
			$this->rows = mysql_affected_rows($this->dblink);
			$return = $result;
		} else {
			$this->rows = mysql_num_rows($result);
			$return = array();
			while ($row = mysql_fetch_assoc($result)) {
				$return[] = $row;
			}
			mysql_free_result($result);
		}
		return $return;
	}

	/**
	 *
	 * Query MySQL - mysqli extension, no query caching
	 *
	 * This sends the query through mysqli without using prepared
	 * statements.  If caching is turned off, then this is about
	 * twice as fast as using prepared statements.
	 * @access private
	 */
	protected function mysqli_nocache($query, $bound_params) {
		$db = $this->dblink;
		$query = $this->convert_bindings($query, $bound_params);
		if (!$query) return FALSE;
		$this->logging['query'] = $query;
		$result = $db->query($query);
		if ($db->errno) {
			$this->handle_error($db->error);
			return FALSE;
		}
		if (is_bool($result)) {
			$this->insertid = $db->insert_id;
			$this->rows = $db->affected_rows;
			$return = $result;
		} else {
			$this->rows = $result->num_rows;
			$return = array();
			while ($row = $result->fetch_assoc()) {
				$return[] = $row;
			}
			$result->free();
		}
		return $return;
	}

	/**
	 * Query MySQL - mysqli extension, use query caching
	 *
	 * This is the cached query handler for mysqli.  It uses mysqli's
	 * prepared statement capability to prepare statements, then saves
	 * the statement in an associative array, with the query string serving
	 * as the key.
	 *
	 * Note that the prepared statements engine has too much overhead when
	 * not getting the improved performance from caching queries. It is faster
	 * to use mysqli_nocache().
	 * @access private
	 */
	protected function query_mysqli($query, $bound_params) {

		// check the cache for a query that
		// matches the input and was created with the same default database
		$cache = $this->querycache[$this->dbname][md5($query)];
		$cachehit = is_object($cache);
		if ($cachehit) {
			$stmt = $cache;
			$this->logging['cached'] = TRUE;
		} else {
			// if we didn't get a cache match then we'll have to
			// prepare a new statement
			$stmt = $this->dblink->stmt_init();
			if (!$stmt->prepare($query)) {
				$this->handle_error("Unable to prepare statement.\n" . $stmt->error);
				return FALSE;
			}
		}

		// bind the parameters with dynamic number of params
		if (count($bound_params)) {
			foreach ($bound_params as $i => $param) {
				$params[0] .= $param[0];
				$params[$i+1] = $param[1];
			}
			call_user_func_array(array($stmt, 'bind_param'), $params);
		}

		// execute the query
		if (!$stmt->execute()) {
			$this->handle_error("Execution failed.\n" . $stmt->error);
			return FALSE;
		}

		// get information about the result, it might have just been
		// boolean if it was an INSERT or UPDATE
		$meta = $stmt->result_metadata();

		// if $meta is an object that means we got a result set
		// if not then it was an execute() so we're done
		if (is_object($meta)) {
			$stmt->store_result();
			$this->rows = $stmt->num_rows;
			$this->insertid = $this->dblink->insert_id;
		} else {
			$this->rows = $stmt->affected_rows;
			return TRUE;
		}

		/** deal with the result set **/

		// call bind result with a dynamic number of columns
		$columns = $meta->fetch_fields();
		foreach ($columns as $i => $col) {
			// prepare the parameters for call_user_func_array
			// we're going to pass references to our $return array, so that the
			// bind_result function (provided by mysqli) will fill the $return
			// array for us
			$fieldnames[] = &$return[$col->name];
		}
		call_user_func_array(array($stmt, 'bind_result'), $fieldnames);

		// fetch the results into $return, then copy $return into
		// a row of $whole, BY VALUE
		// If we don't copy by value, the references will get overwritten
		// and we'll lose our data
		$whole = array();
		while ($stmt->fetch()) {
			foreach ($return as $key => $val) $newreturn[$key] = $val;
			$whole[] = $newreturn;
		}

		// store the $stmt variable so we can use it later
		// on an identical query
		if (!$cachehit) $this->querycache[$this->dbname][md5($query)] = $stmt;

		// return results
		return $whole;
	}

	/**
	 * Multiple MySQL queries under mysqli
	 *
	 * This is the multiple query driver for the mysqli extension.
	 * It uses mysqli_query_multiple() for a speed boost (hopefully)
	 * @access private
	 */
	protected function multi_queryi($args) {
		$db = $this->dblink;
		$bind_params = array();
		$whole = array();

		/** parse the input **/
		// the strategy here is to combine all the queries into one string with a
		//    semicolon separator, then combine all the bound parameters into
		//    one array and run the whole thing through convert_bindings
		// we expect alternating query strings and bind_param arrays, but
		//    if they have a query with no bound parameters I'm not going to
		//    force them to send an empty array()
		$wasbind = TRUE;
		foreach ($args as $arg) {
			if (is_array($arg) && $wasbind) {
				$this->handle_error("Bad execute_multiple syntax.  Expected string but got array.");
				return FALSE;
			} elseif (is_array($arg)) {
				$wasbind = TRUE;
				foreach ($arg as $param) {
					$bind_params[] = $param;
				}
			} else {
				$wasbind = FALSE;
				if (substr($arg, -1) == ";") $query .= $arg;
				else $query .= $arg . ";";
			}
		}

		// pre-proc the params, pretty standard
		$bind_params = $this->preproc_params($bind_params);
		$query = $this->preproc_placeholders($query, count($bind_params));

		// mysqli_query_multiple does not have parameter binding support
		// so we'll do it ourselves
		$query = $this->convert_bindings($query, $bind_params);
		if (!$query) return FALSE; // check for a binding error

		// Respect debug mode
		if ($this->settings['debug_mode']) {
			$this->handle_error($query);
			return FALSE;
		}

		// perform the query
		// we're not worried about the return, it only reflects upon the first query
		$db->multi_query($query);

		// insertid and rows are reborn as arrays
		$this->insertid = array();
		$this->rows = array();

		// loop through and get all the results
		$i = 1;
		do {
			if ($db->errno) { $this->handle_error("Error in Query " . $i . ": " . $db->error); $waserror = TRUE; }
			else { $waserror = FALSE; }

			$result = $db->store_result();

			if (is_resource($result)) {  // query had a result set
				$this->insertid[] = 0;
				$this->rows[] = $result->num_rows;
				$return = array();
				while($row = $result->fetch_assoc()) {
					$return[] = $row;
				}
				$result->close;
			} else {  // query had no result set => $result is false even with no error
				if ($waserror) {
					$this->insertid[] = 0;
					$this->rows[] = 0;
					$return = FALSE;
				} else {
					$this->insertid[] = $db->insert_id;
					$this->rows[] = $db->affected_rows;
					$return = TRUE;
				}
			}
			// store this query's result in master array
			$whole[] = $return;
			$i++;
		} while ($db->next_result());

		// done
		return $whole;
	}

	/**
	 * Multiple MySQL queries under mysqli
	 *
	 * This is the multiple query driver for the mysql extension.  It basically just
	 * uses the class' public methods to carry out many queries in the format expected by
	 * {@link multiple()}.  There is no speed bonus for using this function, it's just here
	 * for compatibility.
	 *
	 * It will work with mysqli too if we don't want to use mysqli_query_multiple().  Of course,
	 * if we do that, we will lose the speed boost.
	 * @access private
	 */
	protected function multi_query($args) {
		$bind_params = array();
		$wasbind = TRUE;
		foreach ($args as $arg) {
			if (is_array($arg) && $wasbind) {
				$this->handle_error("Bad execute_multiple syntax.  Expected string but got array.");
				return FALSE;
			} elseif (is_array($arg)) {
				$wasbind = TRUE;
				$bind_params[] = $arg;
			} elseif ($wasbind) {
				$wasbind = FALSE;
				$query[] = $arg;
			} else {
				$wasbind = FALSE;
				$bind_params[] = array();
				$query[] = $arg;
			}
		}
		$return = array();
		$insertids = array();
		$rows = array();
		foreach ($query as $i => $q) {
			$p = $bind_params[$i];
			$result = $this->query($q, $p);
			if ($this->error) $this->handle_error("Error in Query " . $i + 1 . ": " . $this->errmsg());
			$rows[] = $this->rows;
			if (is_bool($result)) {
				$insertids[] = $this->insertid;
			} else {
				$insertids[] = 0;
			}
			$return[] = $result;
		}
		$this->rows = $rows;
		$this->insertid = $insertids;
		return $return;
	}

	/**
	 * Simple Mass Update Implementation
	 *
	 * Carries out the mass update using many single-row update
	 * queries.
	 * @access private
	 */
	protected function massupdate_simple($table, $keys, $data) {
		foreach ($keys as $key) $used[$key] = TRUE;
		foreach ($data as $row) {
			unset($bindparams);
			unset($keyparams);
			unset($keyparts);
			unset($updateparts);
			foreach ($row as $key => $val) {
				if ($used[$key]) {
					$keyparts[] = $key . '=?';
					$keyparams[] = $val;
				} else {
					$updateparts[] = $key . '=?';
					$bindparams[] = $val;
				}
			}

			foreach ($keyparams as $val) {
				$bindparams[] = $val;
			}
			$updatepart = implode(',', $updateparts);
			$keypart = implode(' AND ', $keyparts);

			$query = "UPDATE $table SET $updatepart WHERE $keypart";

			$this->query($query, $bindparams);
			$return += $this->rows();
		}
		return $return;
	}

	/**
	 * Temp Table Mass Update Implementation
	 *
	 * Carries out a mass update by creating a temp table, filling
	 * it with data, then performing a joined update query.
	 * @access private
	 */
	protected function massupdate_temptable($table, $keys, $data) {
		$maxquerylen = 768 * 1024;

		$updatecols = array_diff(db::get_result_columns($data), $keys);
		$lastcol = end($updatecols);

		$entropy = mt_rand(1,1000);
		$tempname = 'massupdate_' . $table . '_' . $entropy;
		$createquery = 'CREATE TEMPORARY TABLE ' . $tempname . ' (';
		foreach ($keys as $key) {
			$cols[] = $key . ' TINYBLOB NULL DEFAULT NULL';
		}
		foreach ($updatecols as $key) {
			$cols[] = $key . ' MEDIUMBLOB NULL DEFAULT NULL';
		}
		$createquery .= implode(',', $cols) . ') TYPE=MyISAM';

		$this->query($createquery);
		if ($this->error()) return 0;

		$insertquery = 'INSERT INTO ' . $tempname . ' VALUES ';

		foreach ($data as $i => $row) {
			$droprow = FALSE;
			unset($thisrow);
			foreach ($keys as $key) {
				if (!isset($key)) $droprow = TRUE;
				else {
					$thisrow .= '?,';
					$bindparams[] = $row[$key];
					$fakesize += 2 + strlen($row[$key]);
				}
			}
			if ($droprow) continue;

			foreach ($updatecols as $key) {
				if (isset($row[$key])) {
					$thisrow .= '?';
					$bindparams[] = $row[$key];
					$fakesize += 2 + strlen($row[$key]);
				} else {
					$thisrow .= 'NULL';
				}

				if ($key != $lastcol) $thisrow .= ',';
			}

			$insertquery .= '(' . $thisrow . ')';

			if ($fakesize + strlen($insertquery) > $maxquerylen) {
				$this->query($insertquery, $bindparams);
				if ($this->error()) return 0;
				$return += $this->rows();

				$insertquery = 'INSERT INTO ' . $tempname . ' VALUES ';
				$fakesize = 0;
				unset($bindparams);
			} else {
				$insertquery .= ',';
			}

		}
		$insertquery = substr($insertquery, 0, -1);
		$this->query($insertquery, $bindparams);
		if ($this->error()) return 0;
		$return += $this->rows();

		foreach ($updatecols as $col) {
			$stuff .= 't.' . $col . '=IF(f.' . $col . ' IS NULL, t.' . $col . ', f.' . $col . '), ';
		}
		$stuff = substr($stuff, 0, -2);
		$keystr = implode(',', $keys);

		$updatequery = "UPDATE $table t INNER JOIN $tempname f USING ($keystr) SET $stuff";
		$this->query($updatequery);
		if ($this->error()) {
			$save_error = TRUE;
			$save_errormsg = $this->errmsg();
		}
		$return = $this->rows();

		$this->query("DROP TABLE $tempname");

		$this->error = $save_error;
		$this->errormsg = $save_errormsg;

		return $return;
	}

	/**
	 * @access private
	 */
	protected function massupdate_union($table, $keys, $data) {
		$maxquerylen = 160 * 1024;

		$updatecols = array_diff(db::get_result_columns($data), $keys);
		$lastcol = end($updatecols);

		foreach ($updatecols as $col) {
			$stuff .= 't.' . $col . '=IF(f.' . $col . ' IS NULL, t.' . $col . ', f.' . $col . '), ';
		}
		$stuff = substr($stuff, 0, -2);
		$keystr = implode(',', $keys);

		$faketable = '(';
		$firstrow = TRUE;
		foreach ($data as $i => $row) {
			$droprow = FALSE;
			unset($keyvals);
			foreach ($keys as $key) {
				if (!isset($key)) $droprow = TRUE;
				else {
					$keyvals .= '?';
					if ($firstrow) {
						$keyvals .= ' as ' . $key;
					}
					$keyvals .= ',';
					$bindparams[] = $row[$key];
					$fakesize += 2 + strlen($row[$key]);
				}
			}
			if ($droprow) continue;

			$faketable .= 'SELECT ' . $keyvals;
			foreach ($updatecols as $key) {
				if (isset($row[$key])) {
					$faketable .= '?';
					$bindparams[] = $row[$key];
					$fakesize += 2 + strlen($row[$key]);
				} else {
					$faketable .= 'NULL';
				}

				if ($firstrow) {
					$faketable .= ' as ' . $key;
				}
				if ($key != $lastcol) $faketable .= ',';
			}
			$firstrow = FALSE;

			if ($fakesize + strlen($faketable) > $maxquerylen) {
				$faketable .= ')';
				$query = 'UPDATE ' . $table . ' t INNER JOIN ' . $faketable . ' f USING (' . $keystr . ') SET ' . $stuff;

				$this->query($query, $bindparams);
				$return += $this->rows();

				$faketable = '(';
				$fakesize = 0;
				unset($bindparams);
				$firstrow = TRUE;
			} else {
				$faketable .= ' UNION ';
			}

		}
		$faketable = substr($faketable, 0, -7) . ')';
		$query = 'UPDATE ' . $table . ' t INNER JOIN ' . $faketable . ' f USING (' . $keystr . ') SET ' . $stuff;

		$this->query($query, $bindparams);
		$return += $this->rows();

		return $return;
	}

	/**
	 * @access private
	 */
	private function formatrecursedata($result, $control) {
		if (empty($result)) return array();
		if (!is_array($control[0])) $control = array($control);

		// flag the key columns
		foreach ($control as $cont) {
			foreach ($cont as $col) {
				$usedcol[$col] = TRUE;
			}
		}

		// add the final-depth columns as the last row of $control
		foreach ($result[0] as $key => $val) {
			if (!$usedcol[$key]) $newcont[] = $key;
		}
		$control[] = $newcont;

		// start chunking through the results
		$maxdepth = count($control) - 1;
		$return = array();
		$done = array();
		foreach ($result as $row) {
			$current = &$return;
			$currdone = &$done;

			// This is recursive but uses pointers instead of
			// function calls (for speed)
			for($depth = 0; $depth < $maxdepth; $depth++) {
				// get the key value at this depth
				$val = $row[$control[$depth][0]];
				// have we seen this key before?
				$myidx = $currdone[$val]['myridx'];
				if ($myidx == "" && $myidx != "0") {
					// if $myidx is empty that means this key value has not happened before
					// So we'll make a row for it and save the index in the $currdone hash
					$myidx = count($current);
					foreach ($control[$depth] as $col) {
						$current[$myidx][$col] = $row[$col];
					}
					$currdone[$val]['myridx'] = $myidx;
				}
				// recurse down the tree
				$current = &$current[$myidx]['recursedata'];
				$currdone = &$currdone[$val];
			}

			// we're down at the leaf at the bottom of the tree - so record the final-depth data
			// from this row
			$myidx = count($current);
			foreach ($control[$maxdepth] as $col) {
				$current[$myidx][$col] = $row[$col];
			}
		}
		return $return;
	}

	/**
	 * @access private
	 */
	private function recursemenu($data, $control, $depth) {
		foreach ($data as $next) {
			$v = $next[$control[$depth]];
			if (is_array($next['recursedata']) && !empty($next['recursedata'])) {
				$newdata[$v] = $this->recursemenu($next['recursedata'], $control, $depth+1);
			} else {
				$newdata[] = $v;
			}
		}
		return $newdata;
	}

	/**
	 * @access private
	 */
	private static function get_result_columns($data) {
		foreach ($data as $row) {
			foreach ($row as $key => $val) {
				if (!$used[$key]) {
					$updatecols[] = $key;
					$used[$key] = TRUE;
				}
			}
		}
		return $updatecols;
	}

	/**
	 * Process the input parameters
	 *
	 * This cleans up the input to most of the public functions so that
	 * the user can just list params in DBI style.  He can even mix in arrays if
	 * desired - the arrays will be stretched out automatically.
	 * @access private
	 */
	protected function get_params($params) {
		$return = array();
		foreach ($params as $i => $value) {
			if ($i > 0) {
				if (is_array($value)) {
					if (is_array($value[0])) $this->handle_error('Improper array included in bound parameters.');
					elseif (strlen($value[0]) == 1 && count($value) == 2 && strstr("ibds", $value[0])) {
						// if $value[0] equals "i", "b", "d", or "s" then it is a data type definition and
						// we want to maintain the array as-is
						$return[] = $value;
					} else {
						foreach ($value as $val) {
							$return[] = $val;
						}
					}
				} else {
					$return[] = $value;
				}
			}
		}
		return $return;
	}

	/**
	 * Preprocess Bound Parameters
	 *
	 * This will automatically detect & set the data type that mysqli
	 * requires - it's a pain in the ass to have to define it manually.
	 *
	 * Input to this function is a list of parameters, output is a list of
	 * arrays where the first element is code for the data type.  For example:
	 * <pre>Input: array("hello", "world", "you are number", 1)
	 * Output: array(
	 *               array('s',"hello"), array('s', "world"),
	 *               array('s', "you are number"), array('i', 1)
	 *              )
	 * </pre>
	 *
	 * Note that convert_bindings will not recognize the $bind_params format
	 * until it's been through this function.
	 * @access private
	 */
	protected function preproc_params($bound_params) {
		$return = array();
		foreach ($bound_params as $value) {
			if (is_array($value) && count($value) == 2) {
				// The user already provided a type, check it!
				if ($value[0] == 's' && (is_object($value[1]) || is_array($value[1]))) $this->handle_error('Bound parameter defined as string, but is not a string.');
				if ($value[0] == 'b' && (is_object($value[1]) || is_array($value[1]))) $this->handle_error('Bound parameter defined as blob, but is not a blob.');
				if ($value[0] == 'i' && (!is_numeric($value[1]) || intVal($value[1]) != $value[1])) $this->handle_error('Bound parameter defined as integer, but is not an integer.');
				if ($value[0] == 'd' && !is_numeric($value[1])) $this->handle_error('Bound parameter defined as float, but is not a float.');
				$return[] = $value;
			} else if (is_numeric($value) && intval($value) == $value && substr($value, 0, 1) != '0') {
				// The data type is an integer.
				$return[] = array('i', $value);
			} else if (is_numeric($value) && floatval($value) == $value && strstr($value, '.')) {
				// The data type is a float/double
				$return[] = array('d', $value);
			} else {
				// The data type is a string
				$return[] = array('s', $value);
			}
		}
		return $return;
	}

	/**
	 * Pre-process query for advanced placeholder syntax
	 *
	 * This function is designed to allow advanced placeholder syntax like
	 * ?* and ?{4}.  These are most useful with the mysql IN operator. For example:
	 * <pre>$db->getcolumn("SELECT party FROM presidents WHERE lastname IN (?*)",
	 *                "Washington", "Lincoln", "Clinton");
	 * // equivalent alternative
	 * $db->getcolumn("SELECT party FROM presidents WHERE lastname IN (?{3})",
	 *                "Washington", "Lincoln", "Clinton");</pre>
	 *
	 * @access private
	 */
	protected function preproc_placeholders($query, $bindcount) {
		list ($squery, $strings) = db::remove_quoted_strings($query);

		// replace [pre] with table prefix
		$squery = str_replace("[pre]", $this->settings['table_prefix'], $squery);

		$wild = strstr($squery, '?*');
		if ($wild > 1) {
			$this->handle_error("You may not have more than one wild placeholder (?*).");
			return FALSE;
		}

		if ($wild || strstr($squery, '?{')) {
			// expand ?{} placeholders
			while (preg_match('/\?\{(\d+)\}/', $squery, $match)) {
				$num = $match[1];
				$q = array();
				for ($i = 0; $i < $num; $i++) $q[] = '?';
				$r = implode(',',$q);
				$squery = str_replace('?{'.$num.'}', $r, $squery);
			}

			// expand ?* placeholders
			preg_match_all('/\?([^*]|$)/', $squery, $match);
			$qcount = count($match[0]);
			$num = $bindcount - $qcount;
			$q = array();
			for ($i = 0; $i < $num; $i++) $q[] = '?';
			$r = implode(',',$q);
			$squery = preg_replace('/\?\*/', $r, $squery);
		}

		return db::replace_quoted_strings($squery, $strings);
	}

	/**
	 * @access private
	 */
	protected static function remove_quoted_strings($query) {
		// remove all the quoted strings
		$pattern = '/([\'"`])(.*?[^\\\])??([\\\][\\\])*\\1/';
		// from left to right:
		// the first grouping ([\'"`]) captures any of the three quote characters ' " or `
		// the second grouping (.*?[^\\\]) captures a string that does not end with a backslash \
		// the ?? after the second grouping makes the string optional in a NON-GREEDY fashion
		//     a single ? would make the string greedy and would break the whole thing
		// the third grouping ([\\\][\\\])* captures any even number of backslashes.  An even
		//     number of backslashes cancel each other out and so cannot be escaping the end quote
		// the final \\1 is a backreference to the first grouping, it will match ' " or `, whichever
		//     was the starting quote character

		// for this function to break, a query would have to contain %%$$%% OUTSIDE
		// a quoted string, so even large blobs should be safe
		preg_match_all($pattern, $query, $matches);
		$query = preg_replace($pattern, '%%$$%%', $query);

		return array($query, $matches[0]);
	}

	/**
	 * @access private
	 */
	protected static function replace_quoted_strings($query, $strings) {
		// put all the quoted strings back in
		$sections = explode('%%$$%%', $query);
		foreach ($sections as $i => $section) {
			$result .= $section . $strings[$i];
		}
		return $result;
	}

	/**
	 * Convert Query with Bindings to a valid SQL string
	 *
	 * This will parse bound parameters and place them into the query. Useful for queries
	 * using the old mysql extension, or the mysqli extension in places where it does not
	 * support parameter binding.
	 *
	 * It expects $bound_params to be in the array('i', $integer) or array('s', $string)
	 * format, so each bound param should be in an array with its data type definition.
	 * @access private
	 */
	protected function convert_bindings($query, $bound_params) {

		// if there's a ? inside a quoted string, i.e. WHERE column="why?"
		// then that's NOT a call for a bound parameter
		list ($query, $strings) = db::remove_quoted_strings($query);

		// split up the string around the question marks
		$sections = explode('?', $query);

		// sanity check
		if (count($bound_params) != (count($sections) - 1)) {
			$this->handle_error('Invalid bound parameter count in database query.');
			return FALSE;
		}

		// insert the bound values
		foreach ($sections as $i => $section) {
			$type = $bound_params[$i][0];
			$val = $bound_params[$i][1];
			if ($type == 'd' || $type == 'i') { # INT or FLOAT
				if (empty($val)) $val = 0;
				$new_query .= $section . $val;
			} else if ($type == 's' || $type == 'b') { # STRING or BLOB
				$new_query .= $section . "'" . $this->escape_str($val) . "'";
			} else { # presumably this is the final section
				$new_query .= $section;
			}
		}

		return db::replace_quoted_strings($new_query, $strings);
	}

	/**
	 * Handle Errors
	 *
	 * This generates a formatted error message whenever the class detects an
	 * error condition. It loads the input message into $this->errormsg so that
	 * it can be retrieved by {@link errmsg()}.
	 *
	 * This method also echoes to output immediately if print_error is switched on,
	 * and halts execution immediately if raise_error is switched on.
	 * @access private
	 */
	protected function handle_error($msg) {
		// temporary implementation, this will get nicer
		$this->error = TRUE;
		foreach (debug_backtrace() as $trace) {
			if ($trace['file'] != __FILE__) {
				$msg .= "\n" . "at " . $trace['file'] . " line " . $trace['line'] . ".";
				break;
			}
		}
		$this->errormsg = $msg;
		if ($this->settings['print_error']) {
			echo '<span style="font-family: times; font-size: 1.7ex; color: red">' . nl2br(htmlspecialchars($msg)) . "</span><br>\n";
		}
		if ($this->settings['raise_error']) {
			exit;
		}
	}

	/**
	 * Undo magic quotes
	 *
	 * This function strips slashes from all form input, because
	 * magic quotes is pure, unmitigated evil.  EEEEEVVVIIIILLLLLL!
	 * @access private
	 */
	private static function undo_magic_quotes() {
		static $done = FALSE;
		if (!get_magic_quotes_gpc() || $done) return;

		db::array_stripslashes($_REQUEST);
		db::array_stripslashes($_GET);
		db::array_stripslashes($_POST);
		db::array_stripslashes($_COOKIES);

		$done = TRUE;
	}

	/**
	 * Undo addslashes() on an array
	 *
	 * Recurses through array and makes sure every scalar value has
	 * had stripslashes() run on it.
	 *
	 * @access private
	 */
	private static function array_stripslashes(&$array) {
		if (is_array($array)) array_walk($array, array('db', 'array_stripslashes'));
		else $array = stripslashes($array);
	}
}
?>
