<?php

/*
----------------------------------------
---- Mcr Users System by Qexy.org ------
---- Version: 1.3 ----------------------
---- Site: http://qexy.org -------------
---- Support: support@qexy.org ---------
----------------------------------------
*/

class usdb{
	public function MQ($query)		{return BD($query);}
	public function MFA($query)		{return mysql_fetch_array($query);}
	public function MFAS($query)	{return mysql_fetch_assoc($query);}
	public function MNR($query)		{return mysql_num_rows($query);}
	public function MRES($query)	{return mysql_real_escape_string($query);}
	public function HSC($query)		{return htmlspecialchars($query);}
}

class us{
	private $cfg = array();
	private $user = false;
	private $db = false;
	private $cp_lvl = false;
	private $user_lvl = 0;
	private $user_login = "";

	public function __construct($user, $cfg){

		$this->cfg = $cfg;
		$this->user = $user;
		$this->user_lvl = (empty($this->user)) ? 0 : $this->user->lvl();
		$this->user_login = (empty($this->user)) ? "" : $this->user->name();

		if($this->user_lvl < $this->cfg['us_lvl_min']){ header('Location: '.BASE_URL.'go/403/'); exit; }

		$this->cp_lvl = ($this->user_lvl < $this->cfg['us_lvl_adm']) ? false : true;

		$this->db = new usdb();
	}

	private function notify($text, $url='', $type=4){
		$_SESSION['us_info'] = $text;
		$_SESSION['us_info_t'] = $type;
		header('Location: '.US_URL.$url); exit;
		return true;
	}

	public function info(){
		ob_start();
		switch($_SESSION['us_info_t']){
			case 1: $type = 'alert-success'; break;
			case 2: $type = 'alert-info'; break;
			case 3: $type = 'alert-error'; break;

			default: $type = ''; break;
		}

		include_once(US_STYLE.'info.html');
		return ob_get_clean();
	}

	private function pagination($table, $res=10, $page='', $where=''){
		ob_start();

		if(isset($_GET['pid'])){$pid = intval($_GET['pid']);}else{$pid = 1;}
		$start	= $pid * $res - $res; if($table===0 || $res===0 || $page===0 || $where===0){ return $start; }
		$query	= $this->db->MQ("SELECT COUNT(*) FROM $table $where");
		$ar		= $this->db->MFA($query);
		$max	= intval(ceil($ar[0] / $res));
		if($pid<=0 || $pid>$max){ return ob_get_clean(); }
		if($max>1)
		{
			$FirstPge='<li><a href="'.US_URL.$page.'1"><<</a></li>';
			if($pid-2>0){$Prev2Pge	='<li><a href="'.US_URL.$page.($pid-2).'">'.($pid-2).'</a></li>';}else{$Prev2Pge ='';}
			if($pid-1>0){$PrevPge	='<li><a href="'.US_URL.$page.($pid-1).'">'.($pid-1).'</a></li>';}else{$PrevPge ='';}
			$SelectPge = '<li><a href="'.US_URL.$page.$pid.'"><b>'.$pid.'</b></a></li>';
			if($pid+1<=$max){$NextPge	='<li><a href="'.US_URL.$page.($pid+1).'">'.($pid+1).'</a></li>';}else{$NextPge ='';}
			if($pid+2<=$max){$Next2Pge	='<li><a href="'.US_URL.$page.($pid+2).'">'.($pid+2).'</a></li>';}else{$Next2Pge ='';}
			$LastPge='<li><a href="'.US_URL.$page.$max.'">>></a></li>';
			include(US_STYLE."pagination.html");
		}

		return ob_get_clean();
	}

	private function get_menu(){
		if(!$this->cp_lvl){ return; }

		ob_start();

		include_once(US_STYLE.'menu.html');

		return ob_get_clean();
	}

	private function get_config(){
		include(MCR_ROOT."config.php");

		$array	= array_merge($bd_names, $bd_users);

		$array	= array_merge($site_ways, $array);

		$bd_money = array(
						'uname' => $bd_money['login'],
						'money' => $bd_money['money'],
						'rm' => 'realmoney'
					);

		$array	= array_merge($bd_money, $array);

		return $array;
	}
	
	private function save(){

		$txt  = '<?php'.PHP_EOL;
		$txt .= '$cfg = '.var_export($this->cfg, true).';'.PHP_EOL;
		$txt .= '?>';

		$result = file_put_contents(MCR_ROOT."configs/us.cfg.php", $txt);

		if (is_bool($result) and $result == false){return false;}

		return true;
	}

	private function settings(){
		ob_start();

		if(!$this->cp_lvl){ $this->notify("Access Denied", "", 3); }

		$title			= $this->cfg['us_s_title'];
		$lvl_min		= $this->cfg['us_lvl_min'];
		$lvl_adm		= $this->cfg['us_lvl_adm'];
		$rop			= $this->cfg['us_rop'];

		$ajax			= ($this->cfg['us_ajax']==true) ? "selected" : "";
		$mailbox		= ($this->cfg['us_mailbox']==true) ? "selected" : "";

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(isset($_POST['submit'])){

				$this->cfg['us_s_title']	= $_POST['us_s_title'];
				$this->cfg['us_lvl_min']	= intval($_POST['us_lvl_min']);
				$this->cfg['us_lvl_adm']	= intval($_POST['us_lvl_adm']);
				$this->cfg['us_rop']		= (intval($_POST['us_rop'])<1) ? 1 : intval($_POST['us_rop']);

				$this->cfg['us_ajax']		= (intval($_POST['us_ajax'])==1) ? true : false;
				$this->cfg['us_mailbox']	= (intval($_POST['us_mailbox'])==1) ? true : false;

				if(!$this->save()){ $this->notify("Ошибка сохранения настроек", "settings/", 3); }

				$this->notify("Настройки успешно сохранены", "settings/", 1);
			}else{
				$this->notify("Hacking Attempt", "", 3);
			}
		}


		include_once(US_STYLE.'settings.html');

		return ob_get_clean();
	}

	private function us_array(){
		ob_start();

		$cfg		= $this->get_config();

		$start		= $this->pagination(0, $this->cfg['us_rop'], 0);
		$end		= $this->cfg['us_rop'];
		$order		= "ORDER BY `u`.`{$cfg['id']}` ASC";
		$skin_path	= BASE_URL.$cfg['mcraft']."tmp/skin_buffer/";
		$def_path	= BASE_URL.$cfg['mcraft'].'/tmp/skin_buffer/default/';

		$query = $this->db->MQ("SELECT `u`.`{$cfg['id']}`, `u`.`{$cfg['login']}`, `u`.`{$cfg['female']}`, `u`.default_skin AS `ds`, `g`.name AS gname
								FROM `{$cfg['users']}` AS `u`
								LEFT JOIN `{$cfg['groups']}` AS `g`
									ON `g`.id = `u`.`{$cfg['group']}`
								$order
								LIMIT $start, $end");

		if(!$query || $this->db->MNR($query)<=0){ include_once(US_STYLE.'users-none.html'); return ob_get_clean(); }

		while($ar	= $this->db->MFAS($query)){

			$def_ava	= (intval($cfg['female'])==0) ? $def_path.'Char_Mini.png' : $def_path.'Char_Mini_female.png';

			$id			= intval($ar[$cfg['id']]);
			$login		= $this->db->HSC($ar[$cfg['login']]);
			$avatar		= (intval($ar['ds'])==1) ? $def_ava : $skin_path.$login."_Mini.png";
			$female		= (intval($ar[$cfg['female']])==0) ? "Мальчик" : "Девочка";
			$group		= $this->db->HSC($ar['gname']);

			$is_ajax	= ($this->cfg['us_ajax']) ? "onclick=\"LoadProfile('customp',$id); return false\"" : "";

			include(US_STYLE.'users-id.html');
		}

		return ob_get_clean();
	}

	private function us_list(){
		ob_start();

		$cfg = $this->get_config();

		$users		= $this->us_array();
		$pagination = $this->pagination("`".$cfg['users']."` AS `u`", $this->cfg['us_rop'], "page-");

		include_once(US_STYLE.'users.html');

		return ob_get_clean();
	}

	private function get_mailbox($login){
		ob_start();

		if(!$this->cfg['us_mailbox']){ return ob_get_clean(); }

		include_once(US_STYLE.'mailbox.html');

		return ob_get_clean();
	}

	private function us_full(){
		ob_start();

		if(!isset($_GET['op'])){ $this->notify("Hacking Attempt", "", 3); }

		$cfg = $this->get_config();

		$login = $this->db->MRES($_GET['op']);

		$query = $this->db->MQ("SELECT	`u`.`{$cfg['id']}`, `u`.`{$cfg['female']}`, `u`.comments_num,
										`u`.create_time, `u`.active_last, `u`.default_skin,
										`g`.name AS gname, `i`.`{$cfg['money']}`, `i`.`{$cfg['rm']}`
								FROM `{$cfg['users']}` AS `u`
								LEFT JOIN `{$cfg['groups']}` AS `g`
									ON `g`.id = `u`.`{$cfg['group']}`
								LEFT JOIN `{$cfg['iconomy']}` AS `i`
									ON `i`.`{$cfg['uname']}` = `u`.`{$cfg['login']}`
								WHERE `u`.`{$cfg['login']}`='$login'");

		if(!$query || $this->db->MNR($query)<=0){ $this->notify(mysql_error()."Hacking Attempt", "", 3); }

		$ar		= $this->db->MFAS($query);

		$id			= intval($ar[$cfg['id']]);
		$female		= (intval($ar[$cfg['female']])==0) ? "Мальчик" : "Девочка";
		$comments	= intval($ar['comments_num']);
		$date_reg	= $this->db->HSC($ar['create_time']);
		$date_last	= $this->db->HSC($ar['active_last']);
		$group		= $this->db->HSC($ar['gname']);
		$balance	= floatval($ar[$cfg['money']]);
		$real		= floatval($ar[$cfg['rm']]);
		$skin		= "?user_id=$id&refresh=".rand(1000,9999);
		$mailbox	= $this->get_mailbox($login);

		include_once(US_STYLE.'users-full.html');

		return ob_get_clean();
	}

	public function _list(){
		ob_start();

		$do			= (isset($_GET['do'])) ? $_GET['do'] : "list";
		$op			= (isset($_GET['op'])) ? $this->db->MRES($_GET['op']) : "";

		switch($do){
			case "full":		$content = $this->us_full();						break;
			case "settings":	$content = $this->settings();						break;

			default:			$content = $this->us_list();						break;
		}

		include_once(US_STYLE.'global.html');

		return ob_get_clean();
	}
}


/*
----------------------------------------
---- Mcr Users System by Qexy.org ------
---- Version: 1.3 ----------------------
---- Site: http://qexy.org -------------
---- Support: support@qexy.org ---------
----------------------------------------
*/
?>