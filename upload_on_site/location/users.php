<?php

/*
----------------------------------------
---- Mcr Users System by Qexy.org ------
---- Version: 1.3 ----------------------
---- Site: http://qexy.org -------------
---- Support: support@qexy.org ---------
----------------------------------------
*/

if (!defined('MCR')){ exit("Hacking Attempt!"); }

require_once(MCR_ROOT.'configs/us.cfg.php');

$page = $cfg['us_s_title'];
$menu->SetItemActive('users');

define('US_VERSION', 'p1.3');
define('US_STYLE', STYLE_URL.'Default/sysuser/');
define('US_URL', BASE_URL.'go/users/');
define('U_ROOT', 'http://'.$_SERVER['SERVER_NAME']);


$content_js .= '<link href="'.US_STYLE.'css/us.css" rel="stylesheet">';
//$content_js .= '<script src="'.US_STYLE.'js/us.js"></script>';

require_once(MCR_ROOT.'instruments/sysuser.class.php'); $us = new us($user, $cfg);

if(isset($_SESSION['us_info'])){ define('US_INFO', $us->info()); }else{ define('US_INFO', ''); }

$content_main = $us->_list();

if(isset($_SESSION['us_info'])){unset($_SESSION['us_info']); unset($_SESSION['us_info_t']);}


/*
----------------------------------------
---- Mcr Users System by Qexy.org ------
---- Version: 1.3 ----------------------
---- Site: http://qexy.org -------------
---- Support: support@qexy.org ---------
----------------------------------------
*/
?>