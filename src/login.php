<?php
/**
 * Login form
 *
 * Pretty simple, this form handles logins.  If CAS is turned on then
 * it'll redirect the browser to the CAS login page.  Otherwise it generates
 * one.
 *
 * @package phpmanage
 */

require_once("common.php");
$doc = doc::getdoc();
$user = doc::getuser();

function init_CAS() {
	static $once = TRUE;
	if ($once) {
		require_once('includes/CAS.php');
		phpCAS::client(CAS_VERSION_2_0, db_layer::setting('cas_server'), intVal(db_layer::setting('cas_port')), db_layer::setting('cas_path'));
		phpCAS::setNoCasServerValidation();
	}
	$once = FALSE;
}

// deal with local logout requests
if ($_REQUEST['action'] == 'logout') {
	db_layer::session_destroy($user->sid());
	if ($user->caslogin()) {
		init_CAS();
		phpCAS::logoutWithRedirectServiceAndUrl(doc::absolute_url('login.php').'?action=casredirect', doc::absolute_url('index.php'));
	}
}

// deal with redirect from cas server
if ($_REQUEST['action'] == 'casredirect' || $_REQUEST['action'] == 'logout') {
	$doc->addText("You have been logged out.  Click ");
	new link($doc, 'index.php', 'here');
	$doc->addText(' to log back in.');
	$doc->showbuildtime(FALSE);
	$doc->output();
	exit;
}

// this block of code supports CAS single sign-out
if ($_REQUEST['logoutRequest']) {
	db_layer::session_destroy($_REQUEST['sid']);
	if (db_layer::setting('use_cas')) {
		init_CAS();
		phpCAS::handleLogoutRequests(FALSE);
	}
	exit;
}

// this block of code supports CAS logins
if (!$_REQUEST['whichform'] && db_layer::setting('use_cas')) {
	init_CAS();
	if (db_layer::setting('force_cas')) $success = phpCAS::forceAuthentication();
	else $success = phpCAS::checkAuthentication();
	if ($success) {
		$netid = phpCAS::getUser();

		// optionally use peoplesearch to autoprovision users
		$u = db_layer::user_get(array('username'=>$netid));
		if (!$u['userid']) {
			$peopleurl = db_layer::setting('people_search_url');
			if ($peopleurl && $netid) {
				$userinfo = array('username'=>$netid);
				$people = json_decode(file_get_contents($peopleurl.'?q=userid%20is%20'.$netid));
				$result = $people->{'results'}[0];
				if (in_array(strtolower($result->{'category'}), array('staff', 'faculty'))) {
					$userinfo['firstname'] = $result->{'firstname'};
					$userinfo['lastname'] = $result->{'lastname'};
					db_layer::user_update($userinfo);
				}
			}
		}

		if (db_layer::usertosession(array('sessid'=>$user->sessid(), 'username'=>$netid, 'caslogin'=>1))) {
			$redir = $user->grab('redirect_after_login');
			if ($redir) {
				$info = link::parse_href($redir);
				$href = $info['file'];
				$vars = $info['vars'];
				$user->store('redirect_after_login', '');
			} else {
				$href = 'index.php';
				$vars = array();
			}
			$doc->refresh(0, $href, $vars, -1);
			$doc->output();
			exit;
		} else {
			$env = new env($doc);
			$env->addText('Your login was successful, but you have not been added as a user of this system.');
			$doc->output();
			exit;
		}
	}
}

$doc->setTitle('Marionette');
$doc->appendTitle('Log In');
$doc->includeCSS('!common.css');
$doc->includeCSS('!login.css');

function logincheck ($login) {
	if (!db_layer::user_checklogin($login, $_REQUEST['passwd']))
		return ' not found or password does not match.';
	$info = db_layer::user_get(array('username'=>$login));
	if (!$info['userid'])
		return ' password OK, but not yet added as a user of this software.';
	return '';
}

if (form::check_error('login')) {
	db_layer::usertosession(array('sessid'=>$user->sessid(), 'username'=>$_REQUEST['netid']));
	$doc->refresh(0, 'index.php');
} else {
	$div = new div($doc, '', 'logincontainer');
	$form = new form($div, 'login');

	// Net ID
	$tbox = new textbox($form, 'netid', '', 20);
	$tbox->setlabel('NetID:');
	$tbox->check_required();
	$tbox->check_callback('logincheck');
	$form->br();

	// Password
	$tbox = new textbox($form, 'passwd', '', 20);
	$tbox->setlabel('Password:');
	$tbox->password();
	$form->br(2);

	// Submit
	$sbt = new submit($form, 'Login');
	$sbt->setlabel(' ');
}

$doc->output();
?>
