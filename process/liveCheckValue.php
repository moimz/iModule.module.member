<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 회원가입 / 정보수정 폼에서 입력된 값의 유효성을 실시간으로 확인한다.
 *
 * @file /modules/member/process/liveCheckValue.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160923
 *
 * @post string $name 입력값 종류
 * @post string $value 사용자 입력값
 * @return object $results
 */
if (defined('__IM__') == false) exit;

$name = Request('name');
$value = Request('value');
$isSignUp = Request('mode') == 'signup';
$siteType = $this->IM->getSite()->member;

if ($name == 'email' || $name == 'email_verification_email') {
	if (CheckEmail($value) == true) {
		$check = $this->db()->select($this->table->member)->where('email',$value);
		if (($isSignUp == false && $this->isLogged() == true) || $name == 'email_verification_email') $check->where('idx',$this->getLogged(),'!=');
		
		$checkAdmin = $check->copy();
		
		if ($siteType == 'UNIVERSAL') $check->where('domain','*');
		else $check->where('domain',$this->IM->domain);
		
		$checkAdmin->where('type','ADMINISTRATOR');
		
		if ($check->has() == true || $checkAdmin->has() == true) {
			$results->success = false;
			$results->message = $this->getErrorText('DUPLICATED');
		} else {
			$results->success = true;
			$results->message = $this->getText('signup/form/email_success');
		}
	} else {
		$results->success = false;
		$results->message = $this->getErrorText('INVALID_EMAIL');
	}
}

if ($name == 'name') {
	$results->success = CheckName($value);
	if ($results->success == false) $results->message = $this->getErrorText('INVALID_USERNAME');
}

if ($name == 'nickname') {
	if (CheckNickname($value) == true) {
		$check = $this->db()->select($this->table->member)->where('nickname',$value);
		if ($isSignUp == false && $this->isLogged() == true) $check->where('idx',$this->getLogged(),'!=');
		
		$checkAdmin = $check->copy();
		
		if ($siteType == 'UNIVERSAL') $check->where('domain','*');
		else $check->where('domain',$this->IM->domain);
		
		$checkAdmin->where('type','ADMINISTRATOR');
		
		if ($check->has() == true || $checkAdmin->has() == true) {
			$results->success = false;
			$results->message =  $this->getErrorText('DUPLICATED');
		} else {
			$results->success = true;
			$results->message = $this->getText('signup/form/nickname_success');
		}
	} else {
		$results->success = false;
		$results->message = $this->getErrorText('INVALID_NICKNAME');
	}
}

if ($name == 'old_password') {
	if ($this->isLogged() == false) {
		$results->success = false;
		$results->message = $this->getText('error/notLogged');
	} else {
		$mHash = new Hash();
		if ($mHash->password_validate($value,$this->getMember()->password) == true) {
			$results->success = true;
			$results->message = $this->getText('password/help/old_password/success');
		} else {
			$results->success = false;
			$results->message = $this->getText('password/help/old_password/error');
		}
	}
}
?>