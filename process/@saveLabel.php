<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 모든 회원라벨을 가져온다.
 *
 * @file /modules/member/process/@getLabels.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 4. 9.
 */
if (defined('__IM__') == false) exit;

$errors = array();

$idx = Request('idx');
$title = $idx === '0' && Request('is_default_language_setting') == 'on' ? 'LANGUAGE_SETTING' : (Request('title') ? Request('title') : $errors['title'] = $this->getErrorText('REQUIRED'));
$allow_signup = Request('allow_signup') == 'on' ? 'TRUE' : 'FALSE';
$approve_signup = Request('approve_signup') == 'on' ? 'TRUE' : 'FALSE';
$is_change = Request('is_change') == 'on' ? 'TRUE' : 'FALSE';
$is_unique = Request('is_unique') == 'on' ? 'TRUE' : 'FALSE';

$codes = Request('codes');
$titles = Request('titles');
$languages = new stdClass();
for ($i=0, $loop=count($codes);$i<$loop;$i++) {
	if (preg_match('/^[a-z]{2}$/',trim($codes[$i])) == true && strlen(trim($titles[$i])) > 0) {
		$languages->{trim($codes[$i])} = trim($titles[$i]);
	}
}

if ($idx !== '0') {
	if ($this->Module->getConfig('default_label_title') == $title || ($this->Module->getConfig('default_label_title') == 'LANGUAGE_SETTING' && $this->getText('admin/label/default_label_title') == $title)) {
		$errors['title'] = $this->getErrorText('DUPLICATED');
	}
}

if ($idx == null) {
	if ($this->db()->select($this->table->label)->where('title',$title)->has() == true) {
		$errors['title'] = $this->getErrorText('DUPLICATED');
	}
} else {
	if ($this->db()->select($this->table->label)->where('title',$title)->where('idx',$idx,'!=')->has() == true) {
		$errors['title'] = $this->getErrorText('DUPLICATED');
	}
}

$insert = array();
if (count($errors) == 0) {
	if ($idx !== '0') {
		$insert['title'] = $title;
		$insert['allow_signup'] = $allow_signup;
		$insert['approve_signup'] = $approve_signup;
		$insert['is_change'] = $is_change;
		$insert['is_unique'] = $is_unique;
		$insert['languages'] = json_encode($languages,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
		
		if ($idx == null) {
			$insert['sort'] = $this->db()->select($this->table->label)->count();
			$idx = $this->db()->insert($this->table->label,$insert)->execute();
		} else {
			$this->db()->update($this->table->label,$insert)->where('idx',$idx)->execute();
		}
	} else {
		$this->Module->setConfig('default_label_title',$title);
		$this->Module->setConfig('default_label_title_languages',$languages);
		$this->Module->setConfig('allow_signup',$allow_signup == 'TRUE');
		$this->Module->setConfig('approve_signup',$approve_signup == 'TRUE');
	}
	
	$results->success = true;
	$results->idx = $idx;
} else {
	$results->success = false;
	$results->errors = $errors;
}
?>