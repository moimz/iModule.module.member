<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원정보를 수정한다.
 *
 * @file /modules/member/process/modify.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2019. 3. 3.
 */
if (defined('__IM__') == false) exit;

$site = $this->IM->getSite();
$label = Request('label') ? Request('label') : 0;
$steps = $this->getModule()->getConfig('signup_step');

/**
 * 사용자가 로그인중이 아니라면 에러메세지를 출력한다.
 */
if ($this->isLogged() == false) {
	$results->success = false;
	$results->error = $this->getErrorText('REQUIRED_LOGIN');
	return;
} else {
	$insert = array();
	$errors = array();
	
	$member = $this->getMember();
	$isValid = $this->isValidMemberData($member->idx,$_POST,$insert,$errors);
	
	if ($isValid && count($errors) == 0) {
		if ($insert['email'] == $member->email) {
			unset($insert['email']);
		} else {
			$insert['verified'] = $this->isAdmin() == true ? 'TRUE' : ($this->getModule()->getConfig('verified_email') == true ? 'FALSE' : 'NONE');
		}
		
		$this->db()->update($this->table->member,$insert)->where('idx',$member->idx)->execute();
		if ($this->getModule()->getConfig('verified_email') == true && $this->isAdmin() == false) $this->sendVerificationEmail($member->idx);
		
		$photo = Request('photo');
		
		if (preg_match('/^data:image\/(.*?);base64,(.*?)$/',$photo,$match) == true) {
			$bytes = base64_decode($match[2]);
			file_put_contents($this->IM->getAttachmentPath().'/member/'.$this->getLogged().'.jpg',$bytes);
			$this->IM->getModule('attachment')->createThumbnail($this->IM->getAttachmentPath().'/member/'.$this->getLogged().'.jpg',$this->IM->getAttachmentPath().'/member/'.$this->getLogged().'.jpg',250,250,false,'jpg');
		}
		
		$results->success = true;
		$results->message = '회원정보가 성공적으로 수정되었습니다.';
	} else {
		$results->success = false;
		$results->errors = $errors;
	}
}
?>