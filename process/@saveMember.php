<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원정보를 저장한다.
 *
 * @file /modules/member/process/@saveMember.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2020. 4. 9.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$insert = array();
$errors = array();

if ($idx) {
	$member = $this->getMember($idx);
	if ($member->idx == 0) {
		$results->success = false;
		$results->message = $this->getErrorText('NOT_FOUND');
		return;
	}
	
	$isValid = $this->isValidMemberData($idx,$_POST,$insert,$errors,true);
} else {
	$domain = Param('domain');
	$language = Param('language');
	$site = $this->db()->select($this->IM->getTable('site'))->where('domain',$domain)->where('language',$language)->getOne();
	if ($site == null) {
		$results->success = false;
		$results->message = $this->getErrorText('NOT_FOUND_SITE');
		return;
	}
	
	$_POST['password_confirm'] = Request('password');
	
	$labels = Request('labels');
	$isValid = $this->isValidMemberData($labels,$_POST,$insert,$errors,false,$site);
}

if ($isValid && count($errors) == 0) {
	if ($idx) {
		$this->db()->update($this->table->member,$insert)->where('idx',$idx)->execute();
	} else {
		
		
		if ($site->member == 'UNIVERSAL') $insert['domain'] = '*';
		else $insert['domain'] = $site->domain;
		$insert['type'] = 'MEMBER';
		$insert['point'] = 0;
		$insert['exp'] = 0;
		$insert['reg_date'] = time();
		$insert['verified'] = 'NONE';
		$insert['status'] = 'ACTIVATED';
		
		$idx = $this->db()->insert($this->table->member,$insert)->execute();
		foreach ($labels as $label) {
			if ($label !== 0) {
				$this->db()->insert($this->table->member_label,array('idx'=>$idx,'label'=>$label,'reg_date'=>$insert['reg_date']))->execute();
			}
		}
		
		$this->sendPoint($idx,$this->getModule()->getConfig('point'),'member','signup',array('referer'=>(isset($_SERVER['HTTP_REFERER']) == true ? $_SERVER['HTTP_REFERER'] : '')),false,$insert['reg_date']);
		$this->addActivity($idx,$this->getModule()->getConfig('exp'),'member','signup',array('referer'=>(isset($_SERVER['HTTP_REFERER']) == true ? $_SERVER['HTTP_REFERER'] : '')),$insert['reg_date']);
	}
	
	$results->success = true;
} else {
	$results->success = false;
	$results->errors = $errors;
}
?>