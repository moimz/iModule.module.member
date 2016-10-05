<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 이메일 인증을 처리한다.
 *
 * @file /modules/member/templets/default/verifyEmail.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160922
 *
 * @post string $email_verification_email 인증받을 이메일주소
 * @post string $email_verification_code 인증코드
 * @return object $results
 */

if (defined('__IM__') == false) exit;

if ($this->isLogged() == false) {
	$results->success = false;
	$results->message = $this->getErrorText('REQUIRED_LOGIN');
} else {
	$email = Request('email_verification_email');
	$code = Request('email_verification_code');
	
	$member = $this->getMember();
	$check = $this->db()->select($this->table->email)->where('midx',$this->getLogged())->where('email',$email)->getOne();
	if ($check == null || $member->email != $email) {
		$results->success = false;
		$results->errors['email_verification_email'] = $this->getErrorText('RESEND_EMAIL_VERIFICATION_CODE');
	} elseif ($check->code != $code) {
		$results->success = false;
		$results->errors['email_verification_code'] = $this->getErrorText('INCORRECT_EMAIL_VERIFICATION_CODE');
	} else {
		$this->db()->update($this->table->email,array('status'=>'VERIFIED'))->where('midx',$this->getLogged())->where('email',$email)->execute();
		$this->db()->delete($this->table->email)->where('midx',$this->getLogged())->where('status','VERIFIED','!=')->execute();
		$this->db()->update($this->table->member,array('verified'=>'TRUE'))->where('idx',$this->getLogged())->execute();
		
		$results->success = true;
	}
}
?>