<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원정보를 저장한다.
 *
 * @file /modules/member/process/@saveMember.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 4. 9.
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
	$label = Request('label');
	$labels = $label == 0 ? array(0) : array(0,$label);
	$isValid = $this->isValidMemberData($labels,$_POST,$insert,$errors);
}

if ($isValid && count($errors) == 0) {
	if ($idx) {
		$this->db()->update($this->table->member,$insert)->where('idx',$idx)->execute();
	} else {
		$this->db()->insert($this->table->member,$insert)->execute();
	}
	
	$results->success = true;
} else {
	$results->success = false;
	$results->errors = $errors;
}
?>