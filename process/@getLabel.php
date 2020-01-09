<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원라벨 정보를 가져온다.
 *
 * @file /modules/member/process/@getLabel.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2018. 4. 9.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');

if ($idx === '0') {
	$data = new stdClass();
	$data->idx = '0';
	$data->title = $this->Module->getConfig('allow_signup') == 'LANGUAGE_SETTING' ? $this->getText('text/default_label_title') : $this->Module->getConfig('allow_signup');
	$data->allow_signup = $this->Module->getConfig('allow_signup');
	$data->approve_signup = $this->Module->getConfig('approve_signup');
	$data->is_change = true;
	$data->is_unique = false;
	$data->is_default_language_setting = $this->Module->getConfig('allow_signup') == 'LANGUAGE_SETTING';
	$data->languages = $this->Module->getConfig('default_label_title_languages');
	
	$results->success = true;
	$results->data = $data;
} else {
	$data = $this->db()->select($this->table->label)->where('idx',$idx)->getOne();
	if ($data == null) {
		$results->success = false;
		$results->message = $this->getErrorText('NOT_FOUND');
	} else {
		$data->allow_signup = $data->allow_signup == 'TRUE';
		$data->approve_signup = $data->approve_signup == 'TRUE';
		$data->is_change = $data->is_change == 'TRUE';
		$data->is_unique = $data->is_unique == 'TRUE';
		$data->languages = json_decode($data->languages);
		
		$results->success = true;
		$results->data = $data;
	}
}
?>