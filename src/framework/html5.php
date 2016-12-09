<?php
/**
 * @package html5
 */
 
/**
 * Navigation Section
 *
 * Corresponds to HTML5's nav tag
 *
 * @package html5
 */
class nav extends container {
	public function output($pspace='', $optin=FALSE) {
		if (doc::html5())
			return '<nav'.element::output().'>'.container::output($pspace, $optin).'</nav>';
		else
			return '<div'.element::output().'>'.container::output($pspace, $optin).'</div>';
	}
}

/**
 * Individual Article
 *
 * Corresponds to HTML5's article tag
 *
 * @package html5
 */
class article extends container {
	public function output($pspace='', $optin=FALSE) {
		if (doc::html5())
			return '<article'.element::output().'>'.container::output($pspace, $optin).'</article>';
		else
			return '<div'.element::output().'>'.container::output($pspace, $optin).'</div>';
	}
}
?>