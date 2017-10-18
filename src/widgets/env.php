<?php
/**
 * Environment for the site as a whole (tabs at the top)
 *
 * @package phpmanage
 */

/**
 * Environment Widget
 *
 * This widget is the display layer for the overall look & feel of the site.
 *
 * @package phpmanage
 */
class env extends widget {
	protected function create($parent) {
		$doc = doc::getdoc();
		$user = doc::getuser();
		$doc->includeCSS("!common.css");
		$doc->setTitle("Marionette");
		//$doc->addURLIcon("!phpmanage.ico");

		// Only display the tabmenu if the user is authenticated
		$div = new div($parent, '', 'tabmenu');
		if ($user->userid()) {
			new env_menu_tab($div, array('target'=>'index.php', 'title'=>'All Projects'));
			new env_menu_tab($div, array('target'=>'index.php', 'title'=>'My Projects', 'vars'=>array('mine'=>1)));
			new env_menu_tab($div, array('target'=>'filtered.php', 'title'=>'Filtered'));
			new env_menu_tab($div, array('target'=>'index.php', 'title'=>'Completed', 'vars'=>array('complete'=>1)));
			if (checkperm('sysadmin')) {
				new env_menu_tab($div, array('target'=>'users.php', 'title'=>'Users'));
				new env_menu_tab($div, array('target'=>'system.php', 'title'=>'System'));
				new env_menu_tab($div, array('target'=>'config.php', 'title'=>'Config'));
			}
		
			$span = new span($div, '', 'logout');
			new link($span, 'login.php', 'Logout', array('action'=>'logout'));
		} else {
			new link($div, 'login.php', 'Login'); 
		}
		$div = new div($parent, 'contentdiv');

		return $div;
	}
}

class env_menu_tab extends widget {
	protected function create($parent, $s) {
		$span = new span($parent, '', 'menuitem');
		new link($span, $s['target'], $s['title'], $s['vars']);
	}
}

?>
