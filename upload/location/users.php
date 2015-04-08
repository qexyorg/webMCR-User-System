<?php
/**
 * User-System module for WebMCR
 *
 * General proccess
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.4.0
 *
 */

// Check webmcr constant
if (!defined('MCR')){ exit("Hacking Attempt!"); }

define('QEXY', true);
define('MOD_VERSION', '1.4.0');												// Module version
define('MOD_STYLE', STYLE_URL.'Default/modules/qexy/users/');				// Module style folder
define('MOD_URL', BASE_URL.'?mode=users');									// Base module URL
define('MOD_STYLE_ADMIN', MOD_STYLE.'admin/');								// Module style admin folder
define('MOD_ADMIN_URL', MOD_URL.'&do=admin');								// Base module admin url
define('MOD_CLASS_PATH', MCR_ROOT.'instruments/modules/qexy/users/');		// Root module class folder
define('MCR_URL_ROOT', 'http://'.$_SERVER['SERVER_NAME']);					// Full base url webmcr

// Loading config
require_once(MCR_ROOT.'configs/us.cfg.php');

// Loading API
if(!file_exists(MCR_ROOT."instruments/modules/qexy/api/api.class.php")){ exit("API not found! <a href=\"https://github.com/qexyorg/webMCR-API\" target=\"_blank\">Download</a>"); }

require_once(MCR_ROOT."instruments/modules/qexy/api/api.class.php");

// Set default url for module
$api->url = "?mode=users";

// Set default style path for module
$api->style = MOD_STYLE;

// Set module cfg
$api->cfg = $cfg;

// Check access user level
if($api->user->lvl < $cfg['lvl_access']){ header('Location: '.BASE_URL.'?mode=403'); exit; }

// Set active menu
$menu->SetItemActive('qexy_users');

// Set default module page
$do = (isset($_GET['do'])) ? $_GET['do'] : $cfg['main'];

// Set installation variable
if($cfg['install']==true){ $install = true; }

// Check installation
if(isset($install) && $do!=='install'){ $api->notify("Требуется установка", "&do=install", "Внимание!", 4); }

function get_menu($api){
	ob_start();

	if($api->user->lvl < $api->cfg['lvl_admin']){ return ob_get_clean(); }

	echo $api->sp("admin/menu.html");

	return ob_get_clean();
}

// Select page
switch($do){

	// Load module admin
	case 'admin':
		require_once(MOD_CLASS_PATH.'admin.class.php');
		$module			= new module($api);
		$mod_content	= $module->_list();
		$mod_title		= $module->title;
		$mod_bc			= $module->bc;
	break;

	// Load module main
	case 'list':
		require_once(MOD_CLASS_PATH.'main.class.php');
		$module			= new module($api);
		$mod_content	= $module->_list();
		$mod_title		= $module->title;
		$mod_bc			= $module->bc;
	break;

	// Load installation
	case 'install':
		if(!$cfg['install'] && !isset($_SESSION['step_finish'])){ $api->notify("Установка уже произведена", "", "Упс!", 4); }
		require_once(MCR_ROOT."install_users/install.class.php");
		$module			= new module($api);
		$mod_content	= $module->_list();
		$mod_title		= $module->title;
		$mod_bc			= $module->bc;
	break;
	// Load default menu
	default: $api->notify("Страница не найдена", "&do=list", "404", 3); break;
}

// Set default page title
$page = $cfg['title'].' — '.$mod_title;

// Set data values
$content_data = array(
	"CONTENT"	=> $mod_content,
	"BC"		=> $mod_bc,
	"API_INFO"	=> $api->get_notify(),
	"MENU"		=> get_menu($api),
);

$content_js .= '<link href="'.MOD_STYLE.'css/style.css" rel="stylesheet">';

// Set returned content
$content_main = $api->sp("global.html", $content_data);

/**
 * User-System module for WebMCR
 *
 * General proccess
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.4.0
 *
 */
?>