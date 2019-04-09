<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원로그인을 처리한다.
 *
 * @file /modules/member/process/login.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2018. 4. 6.
 */
if (defined('__IM__') == false) exit;

$mHash = new Hash();
$email = Request('email');
$password = Request('password');
$remember = Request('remember') ? true : false;

$LOGGED_FAIL = Request('LOGGED_FAIL','session') != null && is_array(Request('LOGGED_FAIL','session')) == true ? Request('LOGGED_FAIL','session') : array('count'=>0,'time'=>0);

if ($LOGGED_FAIL['time'] > time()) {
	$results->success = false;
	$results->errors['email'] = $this->getErrorText('DISABLED_LOGIN',$LOGGED_FAIL['time'] - time());
	$results->message = $this->getErrorText('DISABLED_LOGIN',$LOGGED_FAIL['time'] - time());
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
		$results->errors['email'] = $this->getErrorText('NOT_FOUND_EMAIL');
		$LOGGED_FAIL['count']++;
		if ($LOGGED_FAIL['count'] == 5) {
			$LOGGED_FAIL['count'] = 0;
			$LOGGED_FAIL['time'] = time() + 60 * 5;
		}
	} elseif ($check->status == 'DEACTIVATED') {
		$results->success = false;
		$results->errors['email'] = $this->getErrorText('DEACTIVATED_ACCOUNT');
	} elseif ($mHash->password_validate($password,$check->password) == false) {
		$results->success = false;
		$results->errors['password'] = $this->getErrorText('INCORRECT_PASSWORD');
		$LOGGED_FAIL['count']++;
		if ($LOGGED_FAIL['count'] == 5) {
			$LOGGED_FAIL['count'] = 0;
			$LOGGED_FAIL['time'] = time() + 60 * 5;
		}
	} else {
		$LOGGED_FAIL = array('count'=>0,'time'=>0);
		$this->login($check->idx);
		if ($remember == true) $this->makeCookie();
		
		$results->success = true;
	}
}
$_SESSION['LOGGED_FAIL'] = $LOGGED_FAIL;
?>