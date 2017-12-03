<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 회원가입을 처리한다.
 *
 * @file /modules/member/process/signup.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 30.
 */
if (defined('__IM__') == false) exit;

$site = $this->IM->getSite();
$label = Request('label') ? Request('label') : 0;
$steps = $this->getModule()->getConfig('signup_step');

/**
 * 사용자가 선택한 회원라벨이 회원가입 허용인지 아닌지 파악한다.
 */
if ($this->getLabel($label)->allow_signup === false) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_ALLOWED_SIGNUP');
} else {
	$insert = array();
	$errors = array();
	
	$isValid = $this->isValidSignUpData('signup',$_POST,$insert,$errors);
	
	if ($isValid && count($errors) == 0) {
		if ($site->member == 'UNIVERSAL') $insert['domain'] = '*';
		else $insert['domain'] = $this->IM->getSite()->domain;
		$insert['type'] = 'MEMBER';
		
		$insert['point'] = $this->getModule()->getConfig('point');
		$insert['exp'] = $this->getModule()->getConfig('exp');
		$insert['reg_date'] = time();
		
		$insert['verified'] = $this->getModule()->getConfig('verified_email') == true ? 'FALSE' : 'NONE';
		
		if ($this->getLabel($label)->approve_signup == true) $insert['status'] = 'WAITING';
		else $insert['status'] = 'ACTIVATED';
		
		$idx = $this->db()->insert($this->table->member,$insert)->execute();
		if ($label !== 0) {
			$this->db()->insert($this->table->member_label,array('idx'=>$idx,'label'=>$label,'reg_date'=>$insert['reg_date']))->execute();
		}
		$this->login($idx);
		
		if ($this->getModule()->getConfig('verified_email') == true) $this->sendVerificationEmail($idx);
		
		$results->success = true;
	} else {
		$results->success = false;
		$results->errors = $errors;
	}
}
?>