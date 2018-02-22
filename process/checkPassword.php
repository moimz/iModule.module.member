<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 회원가입 / 정보수정 폼에서 입력된 값의 유효성을 실시간으로 확인한다.
 *
 * @file /modules/member/process/liveCheckValue.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 10.
 */
if (defined('__IM__') == false) exit;

if ($this->isLogged() == false) {
	$results->success = false;
	$results->message = $this->getErrorText('REQUIRED_LOGIN');
} else {
	$password = Request('password');
	
	$member = $this->getMember();
	$mHash = new Hash();
	
	if ($mHash->password_validate($password,$member->password) == true) {
		$results->success = true;
		$results->password = Encoder($password);
	} else {
		$results->success = false;
		$results->message = $this->getErrorText('INCORRECT_PASSWORD');
	}
}
?>