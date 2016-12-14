<?php
/**
 * config.default.php
 *
 * This file contains all the configuration information for phpWebObjects
 *
 * You should make a copy of this file, change the settings to fit your site,
 * and then require_once(thecopy) in all your page loads.
 *
 * When a new version of phpWebObjects is released, you may want to compare this
 * file to your local copy in case there are any changes.
 *
 * @package htmlsupport
 */

/** DATABASE DEFINITIONS **/

$dbref = "0";
$cfg['db'][$dbref]['server'] = "localhost";
$cfg['db'][$dbref]['username'] = "";
$cfg['db'][$dbref]['password'] = "";
$cfg['db'][$dbref]['dbname'] = "yourdatabase";
$cfg['db'][$dbref]['extension'] = "mysqli";
$cfg['db'][$dbref]['compress'] = FALSE;

/** PATH INFORMATION **/

// the full server path to the phpWebObjects library directory
$cfg['library_root'] = dirname(__FILE__);

// web path to phpWebObjects directory
$cfg['library_path'] = "/framework";

// web path to phpWebObjects' javascript directory
$cfg['library_path_js'] = "/framework/js";

// web path to phpWebObjects' CSS directory
$cfg['library_path_css'] = "/framework/css";

// web path to YOUR CSS documents, will be used when you include CSS with a ! in front
$cfg['css_root'] = "/css";

//  web path to YOUR Javascript library directory
$cfg['javascript_root'] = "/js";

// web path to YOUR image directory, will be used when you set an image's source with ! in front
$cfg['image_root'] = "/images";

// your application root, you can write links like "!mypage.php" to specify that
// they're in this directory
$cfg['link_root'] = "/";

// server path to the root directory of the site
$cfg['server_path'] = "/var/www";

// array of domains that should be considered 'local' (session will persist
// across all these domains, as long as they use the same database and all use
// phpWebObjects)
$cfg['local_domains'] = array(
);

/** OPTIONAL PARAMETERS **/

// background color for form elements that don't pass error check
$cfg['form_error_bg'] = 'yellow';

// text color for BBCode image captions
$cfg['caption_color'] = 'black';
// background color for BBCode image captions
$cfg['caption_bg'] = '#a6b68c';
// font size for BBCode image captions
$cfg['caption_size'] = '10px';

/** Time Zone Setting **/
date_default_timezone_set('America/Chicago');
$cfg['database_timezone'] = new DateTimeZone('UTC');

if (file_exists($cfg['library_root'].'/config.local.php'))
	require_once( $cfg['library_root'].'/config.local.php');

/** LOAD phpWebObjects LIBRARIES **/
require_once($cfg['library_root'].'/db.php');
require_once($cfg['library_root'].'/functions.php');
require_once($cfg['library_root'].'/baseclasses.php');
require_once($cfg['library_root'].'/elements.php');
require_once($cfg['library_root'].'/html5.php');
require_once($cfg['library_root'].'/doclevel.php');
require_once($cfg['library_root'].'/forms.php');
require_once($cfg['library_root'].'/userinterface.php');
require_once($cfg['library_root'].'/widgets.php');
require_once($cfg['library_root'].'/session.php');
require_once($cfg['library_root'].'/timestamp.php');

timestamp::setdbzone(date_default_timezone_get());

?>
