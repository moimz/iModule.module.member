<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 이메일 인증을 처리한다.
 *
 * @file /modules/member/templets/default/verifyEmail.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 1. 27.
 */
if (defined('__IM__') == false) exit;

$mode = Request('mode');

/**
 * 인증메일 재발송을 요청한 경우
 */
if ($mode == 'resend') {
	if ($this->isLogged() == false) {
		$results->success = false;
		$results->message = $this->getErrorText('REQUIRED_LOGIN');
		return;
	}
	
	$result = $this->sendVerificationEmail($this->getLogged());
	if ($result === true) {
		$results->success = true;
		$results->message = $this->getErrorText('RESEND_EMAIL_VERIFICATION_CODE');
	} else {
		$results->success = false;
		$results->message = $this->getErrorText($result);
	}
}

/**
 * 회원로그인이 되어 있고, 이메일 인증코드를 입력하여 인증하는 경우
 */
if ($mode == 'code') {
	if ($this->isLogged() == false) {
		$results->success = false;
		$results->message = $this->getErrorText('REQUIRED_LOGIN');
		return;
	}
	
	$code = Request('code');
	$member = $this->getMember();
	$check = $this->db()->select($this->table->email)->where('midx',$member->idx)->where('email',$member->email)->where('code',$code)->getOne();
	if ($check == null) {
		$results->success = false;
		$results->errors['code'] = $this->getErrorText('INCORRECT_EMAIL_VERIFICATION_CODE');
		return;
	}
	
	$this->db()->delete($this->table->email)->where('midx',$member->idx)->execute();
	$this->db()->delete($this->table->email)->where('email',$member->email)->execute();
	$this->db()->update($this->table->member,array('verified'=>'TRUE'))->where('idx',$member->idx)->execute();
	
	$results->success = true;
}

/**
 * 이메일에 포함된 링크를 클릭하였을 경우
 */
if ($mode == 'token') {
	$token = Request('token');
	
	$check = Decoder($token,null,'hex') !== false ? json_decode(Decoder($token,null,'hex')) : null;
	if ($check == null) {
		$results->success = false;
		$results->message = $this->getErrorText('INVALID_EMAIL_VERIFICATION_TOKEN');
		return;
	}
	
	$code = $this->db()->select($this->table->email)->where('midx',$check[0])->where('email',$check[1])->getOne();
	if ($code == null) {
		$results->success = false;
		$results->message = $this->getErrorText('INVALID_EMAIL_VERIFICATION_TOKEN');
		return;
	}
	
	$member = $this->getMember($code->midx);
	if ($member->idx == 0 || $member->email != $code->email) {
		$results->success = false;
		$results->message = $this->getErrorText('NOT_FOUND_MEMBER');
		return;
	}
	
	$this->login($code->midx);
	
	$this->db()->delete($this->table->email)->where('midx',$member->idx)->execute();
	$this->db()->delete($this->table->email)->where('email',$member->email)->execute();
	$this->db()->update($this->table->member,array('verified'=>'TRUE'))->where('idx',$member->idx)->execute();
	
	$results->success = true;
}
?>