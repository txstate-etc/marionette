<?php 
/**
 * Compatibility DateTime class
 * 
 *  @package phpmanage
 */

if (!class_exists('DateTime')) {
	/**
	 * DateTime class for compatibility
	 *
	 * the DateTime class only appears in PHP 5.2.0 
	 * 
	 * I'm providing a simple compatibility version here in
	 * case we're running on 5.1.x  
	 *
	 * @package phpmanage
	 */
	class DateTime {
		private $time;
		function __construct($str = '') {
			if (!$str) $this->time = time();
			else $this->time = strtotime($str);
		}
		public function format($format) {
			return date($format, $this->time);
		}
	}
}
?>