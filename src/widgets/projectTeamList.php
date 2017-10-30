<?php 
/**
 * Traitmanage Class
 *
 * @package phpmanage
 */

/**
 * Widget for displaying and editing project team members
 * 
 *
 * Display and function will vary depending on the $settings 'editable' parameter
 * Editable = true, displays a list of checkboxes representing all users not currently on the project team so they may be added
 * Editable = false, simply displays the list of members on the project team
 * 
 *
 * $settings = array(
 *     'users' => array containing the full user list
 *     'projectteam' => array containing the current project's project team members
 *     'editable' => bool that determines widget's overall function (see above)  
 *     'checkboxtag' => string used as the name in checkbox creation 
 * )
 *
 * @package phpmanage
 */

class projectTeamList extends widget {
	protected function create($parent, $settings) {
		$userList = $settings['users'];
		$allowEdit = $settings['editable'];
		$ptUserList = $settings['projectteam'];
		$cbtag = $settings['checkboxtag'] ? $settings['checkboxtag'] : 'checkbox';

		doc::getdoc()->addCSS("label { float:none; }");

		if ($allowEdit) {
			foreach ($userList as $user) {
				if (self::containsUser($ptUserList, $user)) { continue; }

				$chk = new checkbox($parent, $cbtag, $user['userid'], false);
				$chk->setLabel($user['firstname'] . ' ' . $user['lastname']);
				$parent->br();
			}
		} else {
			foreach ($ptUserList as $user) {
				$lbl = new label($parent, $user['displayname']);
				$parent->br();
			}
		}

	}

	function containsUser($list, $user) {
		if (!$list || count($list) < 1)
			return false;

		foreach ($list as $l) {
			if ($l['userid'] == $user['userid']) {
				return true;
			}
		}
		return false;
	}
}

?>