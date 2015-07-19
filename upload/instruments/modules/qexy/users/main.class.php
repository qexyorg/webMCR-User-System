<?php
/**
 * User-System module for WebMCR
 *
 * Main class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.4.1
 *
 */

// Check Qexy constant
if (!defined('QEXY')){ exit("Hacking Attempt!"); }

class module{

	// Set default variables
	private $user			= false;
	private $db				= false;
	private $api			= false;
	public	$title			= '';
	public	$bc				= '';
	private	$cfg			= array();

	// Set constructor vars
	public function __construct($api){
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->cfg			= $api->cfg;
		$this->api			= $api;
		$this->mcfg			= array();
	}

	private function user_array(){

		$start		= $this->api->pagination($this->cfg['rop_list'], 0, 0); // Set start pagination

		$end		= $this->cfg['rop_list']; // Set end pagination

		$mcfg			= $this->api->getMcrConfig();
		$bd_names		= $mcfg['bd_names'];
		$bd_users		= $mcfg['bd_users'];
		$site_ways		= $mcfg['site_ways'];

		$query = $this->db->query("SELECT `u`.`{$bd_users['id']}`, `u`.`{$bd_users['login']}`, `u`.`{$bd_users['female']}`, `g`.`name` AS `group`, `u`.`default_skin`

									FROM `{$bd_names['users']}` AS `u`

									LEFT JOIN `{$bd_names['groups']}` AS `g`
										ON `g`.id = `u`.`{$bd_users['group']}`

									ORDER BY `u`.`{$bd_users['id']}` DESC

									LIMIT $start,$end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("list/user-none.html"); } // Check returned result

		ob_start();

		while($ar = $this->db->get_row($query)){
			
			$gender = (intval($ar[$bd_users['female']])==0) ? 'Мужской' : 'Женский';

			$login = $this->db->HSC($ar[$bd_users['login']]);

			$group = (empty($ar['group'])) ? '<b class="text-error">Группа удалена</b>' : $this->db->HSC($ar['group']);

			$class = (intval($ar[$bd_users['female']])==0) ? '' : 'row-female';

			$charname = (intval($ar[$bd_users['female']])==0) ? 'Char_Mini.png' : 'Char_Mini_female.png';

			$avatar = (intval($ar['default_skin'])===1) ? 'default/'.$charname.'?refresh='.mt_rand(1000, 9999) : $login.'_Mini.png';

			$data = array(
				"ID"		=> intval($ar[$bd_users['id']]),
				"LOGIN"		=> $login,
				"GROUP"		=> $group,
				"AVATAR"	=> BASE_URL.$site_ways['mcraft'].'/tmp/skin_buffer/'.$avatar,
				"GENDER"	=> $gender,
				"CLASS"		=> $class
			);
			echo $this->api->sp("list/user-id.html", $data);
		}

		return ob_get_clean();
	}

	private function user_list(){

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= 'Главная';

		$this->mcfg		= $this->api->getMcrConfig();
		$bd_names		= $this->mcfg['bd_names'];

		$sql			= "SELECT COUNT(*) FROM `{$bd_names['users']}`"; // Set SQL query for pagination function

		$page			= "&pid="; // Set url for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_list'], $page, $sql); // Set pagination

		$list			= $this->user_array(); // Set content to variable

		$data = array(
			"PAGINATION"	=> $pagination,
			"CONTENT"		=> $list
		);

		return $this->api->sp('list/user-list.html', $data);
	}

	private function user_full(){

		$login			= $this->db->safesql($_GET['uid']);

		// CSRF Security name

		$this->mcfg		= $this->api->getMcrConfig();
		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];
		$site_ways		= $this->mcfg['site_ways'];

		$sql			= "SELECT `u`.`{$bd_users['id']}`, `u`.`{$bd_users['female']}`, `u`.`{$bd_users['login']}`, `u`.`{$bd_users['ctime']}`, `u`.`default_skin`,
									`u`.`comments_num`, `u`.`gameplay_last`, `u`.`active_last`, `g`.`name` AS `group`
							FROM `{$bd_names['users']}` AS `u`
							LEFT JOIN `{$bd_names['groups']}` AS `g`
								ON `g`.id = `u`.`{$bd_users['group']}`
							WHERE `u`.`{$bd_users['login']}`='$login'";

		$query = $this->db->query($sql);

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Страница не найдена", "", "404", 3); }

		$ar = $this->db->get_row($query);

		$id		= intval($ar[$bd_users['id']]);
		$login	= $this->db->HSC($ar[$bd_users['login']]);

		$group			= (empty($ar['group'])) ? '<b class="text-error">Группа удалена</b>' : $this->db->HSC($ar['group']);
		$gender			= (intval($ar[$bd_users['female']])==0) ? 'Мужской' : 'Женский';
		$comments_num	= intval($ar['comments_num']);
		$date_register	= $this->db->HSC($ar[$bd_users['ctime']]);
		$date_active	= $this->db->HSC($ar['active_last']);
		$date_gameplay	= $this->db->HSC($ar['gameplay_last']);

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Профиль пользователя $login" => MOD_URL.'&uid='.$login,
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Профиль пользователя $login";

		$comments		= ($this->cfg['comments'] && $this->user->lvl > 0) ? $this->comment_list($id, $login) : '';

		if($this->user->lvl < 0 && $this->cfg['comments']){
			$comments	= $this->api->sp('list/comments/comment-access.html');
		}

		$mail_data = array(
			"LOGIN" => $login,
		);

		$charname = (intval($ar[$bd_users['female']])==0) ? 'Char.png' : 'Char_female.png';

		$avatar = (intval($ar['default_skin'])===1) ? 'default/'.$charname.'?refresh='.mt_rand(1000, 9999) : $login.'_Mini.png';

		$data = array(
			"LOGIN"			=> $login,
			"GROUP"			=> $group,
			"AVATAR"		=> BASE_URL.$site_ways['mcraft'].'/tmp/skin_buffer/'.$avatar,
			"GENDER"		=> $gender,
			"DATE_REG"		=> $date_register,
			"DATE_LAST"		=> $date_active,
			"DATE_GAME"		=> $date_gameplay,
			"COMMENTS"		=> $comments,
			"MAILBOX"		=> ($this->cfg['mailbox']) ? $this->api->sp('list/user-mailbox.html', $mail_data) : '',
		);

		return $this->api->sp('list/user-full.html', $data);
	}

	private function comment_delete(){

		// To login
		$login = $this->db->safesql($_GET['uid']);

		// Comment id
		$cid = intval(@$_POST['delete']);

		$delete = $this->db->query("DELETE FROM `qx_us_comments` WHERE id='$cid' AND (uid='{$this->user->id}' OR `from`='{$this->user->id}' OR '{$this->user->lvl}'>='{$this->cfg['lvl_admin']}')");
	
		if(!$delete){ $this->api->notify("Произошла ошибка баз данных", "&uid=$login", "Ошибка!", 3); }

		$this->api->notify("Выбранный комментарий успешно удален", "&uid=$login", "Поздравляем!", 1);
	}

	private function comment_list($uid, $login){
		$f_security		= 'us_comment';

		$sql			= "SELECT COUNT(*) FROM `qx_us_comments` WHERE uid='$uid'"; // Set SQL query for pagination function

		$page			= "&uid=$login&pid="; // Set url for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_comments'], $page, $sql); // Set pagination

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=403", "403", 3); }

			if(isset($_POST['delete'])){ $this->comment_delete(); }

			$text = trim($this->db->safesql(@$_POST['comment']));

			if(empty($text)){ $this->api->notify("Не заполнено поле комментария", "&uid=$login", "Ошибка!", 3); }

			$new_data = array(
				"date_create" => time(),
				"date_last" => time(),
			);

			$new_data = $this->db->safesql(json_encode($new_data));

			$insert = $this->db->query("INSERT INTO `qx_us_comments`
											(uid, `from`, `text`, `data`)
										VALUES
											('$uid', '{$this->user->id}', '$text', '$new_data')");

			if(!$insert){
				$this->api->notify("Произошла ошибка баз данных", "&uid=$login", "Ошибка!", 3);
			}
			
			$this->api->notify("Ваш комментарий успешно добавлен", "&uid=$login", "Поздравляем!", 1);

		}

		$data = array(
			"COMMENTS"		=> $this->comments_array($uid),
			"PAGINATION"	=> $pagination,
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
		);

		return $this->api->sp('list/comments/comment-list.html', $data);
	}

	private function comments_array($uid){

		$start		= $this->api->pagination($this->cfg['rop_comments'], 0, 0); // Set start pagination

		$end		= $this->cfg['rop_comments']; // Set end pagination

		$bd_names		= $this->mcfg['bd_names'];
		$bd_users		= $this->mcfg['bd_users'];

		$query = $this->db->query("SELECT `c`.id, `c`.`from`, `c`.`text`, `c`.`data`,
											`u`.`{$bd_users['login']}` AS `login`, `u`.`default_skin`, `u`.`female`
									FROM `qx_us_comments` AS `c`
									LEFT JOIN `{$bd_names['users']}` AS `u`
										ON `u`.`{$bd_users['id']}`=`c`.`from`
									WHERE `c`.`uid`='$uid'
									ORDER BY `c`.id DESC
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp('list/comments/comment-none.html'); }

		ob_start();

		while($ar = $this->db->get_row($query)){

			$text = nl2br($this->db->HSC($ar['text']));

			$data = json_decode($ar['data'], true);

			$act_data = array(
				"ID" => intval($ar['id'])
			);

			$actions = ($uid==$this->user->id || intval($ar['from'])==$this->user->id || $this->user->lvl >= $this->cfg['lvl_admin']) ? $this->api->sp('list/comments/comment-btn.html', $act_data) : "";

			$com_data = array(
				"TEXT" => $text,
				"LOGIN" => $this->db->HSC($ar['login']),
				"DATE_CREATE" => date("d.m.Y в H:i:s", $data['date_create']),
				"ACTIONS" => $actions,
			);

			echo $this->api->sp('list/comments/comment-id.html', $com_data);
		}

		return ob_get_clean();
	}

	public function _list(){
		return (isset($_GET['uid'])) ? $this->user_full() : $this->user_list();
	}
}

/**
 * User-System module for WebMCR
 *
 * Main class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.4.1
 *
 */
?>