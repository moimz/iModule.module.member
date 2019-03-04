<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 패스워드 초기화를 처리한다.
 *
 * @file /modules/member/process/resetPassword.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 14.
 */
if (defined('__IM__') == false) exit;

$email = Request('email');
$token = Request('token');

if ($token) {
	$check = $this->db()->select($this->table->password)->where('token',$token)->getOne();
	if ($check == null || $check->reg_date < time() - 60 * 60 * 6) return $this->getError('EXPIRED_LINK');
	
	$member = $this->getMember($check->midx);
	if ($member->idx == 0) return $this->getError('EXPIRED_LINK');
	
	$mHash = new Hash();
	$password = Request('password');
	$password_confirm = Request('password_confirm');
	
	$errors = array();
	if (strlen($password) < 6) {
		$errors['password'] = $this->getErrorText('TOO_SHORT_PASSWORD');
	} elseif ($password != $password_confirm) {
		$errors['password_confirm'] = $this->getErrorText('NOT_MATCHED_PASSWORD_CONFIRM');
	}
	
	if (count($errors) == 0) {
		$this->db()->update($this->table->member,array('password'=>$mHash->password_hash($password)))->where('idx',$member->idx)->execute();
		
		$results->success = true;
		$results->message = '패스워드가 성공적으로 변경되었습니다.<br>다음 로그인시 부터 변경된 패스워드로 로그인이 가능합니다.';
	} else {
		$results->success = false;
		$results->errors = $errors;
	}
} else {
	$siteType = $this->IM->getSite(false)->member;
	
	if ($siteType == 'UNIVERSAL') {
		$check = $this->db()->select($this->table->member)->where('email',$email)->where('domain','*')->getOne();
	} else {
		$check = $this->db()->select($this->table->member)->where('email',$email)->where('domain',$this->IM->domain)->getOne();
	}
	
	if ($check == null) {
		$check = $this->db()->select($this->table->member)->where('email',$email)->where('type','ADMINISTRATOR')->getOne();
	}
	
	if ($check == null || $check->status == 'LEAVE') {
		$results->success = false;
		$results->errors = array('email'=>$this->getErrorText('NOT_FOUND_EMAIL'));
		return;
	}
	
	$result = $this->sendResetPasswordEmail($check->idx);
	if ($result === true) {
		$results->success = true;
		$results->message = '패스워드 초기화 이메일을 발송하였습니다.';
	} else {
		$results->success = false;
		$results->message = $this->getErrorText($result);
	}
}