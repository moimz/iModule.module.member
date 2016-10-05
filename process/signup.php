<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 회원가입을 처리한다.
 *
 * @file /modules/member/process/signup.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0.161001
 *
 * @return object $results
 */
if (defined('__IM__') == false) exit;

$site = $this->IM->getSite();
$label = Request('label') ? Request('label') : 0;
$steps = $this->Module->getConfig('signup_step');

/**
 * 사용자가 선택한 회원라벨이 회원가입 허용인지 아닌지 파악한다.
 */
if ($this->getLabel($label)->allow_signup === false) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_ALLOWED_SIGNUP');
} else {
	$insert = array();
	$errors = array();
	
	$agreements = Request('agreements') != null ? Request('agreements') : array();
	$agreements = is_array($agreements) == false ? array($agreements) : $agreements;
	
	$agreement = $this->db()->select($this->table->signup)->where('label',array($label,0),'IN')->where('name','agreement')->orderBy('label','desc')->getOne();
	if ($agreement != null && in_array('agreement-'.$agreement->label,$agreements) == false) {
		if (isset($errors['agreements']) == true && is_object($errors['agreements']) == true) {
			$errors['agreements']->{'agreement-'.$agreement->label} = $this->getErrorText('REQUIRED');
		} else {
			$errors['agreements'] = new stdClass();
			$errors['agreements']->{'agreement-'.$agreement->label} = $this->getErrorText('REQUIRED');
		}
	}
	
	$privacy = $this->db()->select($this->table->signup)->where('label',array($label,0),'IN')->where('name','privacy')->orderBy('label','desc')->getOne();
	if ($privacy != null && in_array('privacy-'.$privacy->label,$agreements) == false) {
		if (isset($errors['agreements']) == true && is_object($errors['agreements']) == true) {
			$errors['agreements']->{'privacy-'.$privacy->label} = $this->getErrorText('REQUIRED');
		} else {
			$errors['agreements'] = new stdClass();
			$errors['agreements']->{'privacy-'.$privacy->label} = $this->getErrorText('REQUIRED');
		}
	}

	$isValid = $this->isValidInsertData('signup',$_POST,$insert,$errors);
	
	if ($isValid && count($errors) == 0) {
		if ($site->member == 'UNIVERSAL') $insert['domain'] = '*';
		else $insert['domain'] = $this->IM->getSite()->domain;
		$insert['type'] = 'MEMBER';
		
		$insert['point'] = $this->Module->getConfig('point');
		$insert['exp'] = $this->Module->getConfig('exp');
		$insert['reg_date'] = time();
		
		if (in_array('verify',$steps) == true) $insert['verified'] = 'FALSE';
		else $insert['verified'] = 'NONE';
		if ($this->getLabel($label)->approve_signup == true) $insert['status'] = 'WAITING';
		else $insert['status'] = 'ACTIVATED';
		
		$idx = $this->db()->insert($this->table->member,$insert)->execute();
		if ($label !== 0) {
			$this->db()->insert($this->table->member_label,array('idx'=>$idx,'label'=>$label,'reg_date'=>$insert['reg_date']))->execute();
		}
		$this->login($idx);
		
		if (in_array('verify',$steps) == true) $this->sendVerificationEmail($idx);
		
		$results->success = true;
	} else {
		$results->success = false;
		$results->errors = $errors;
	}
}
?>