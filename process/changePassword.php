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

/**
 * 사용자가 로그인중이 아니라면 에러메세지를 출력한다.
 */
if ($this->isLogged() == false) {
	$results->success = false;
	$results->error = $this->getErrorText('REQUIRED_LOGIN');
	return;
} else {
	$member = $this->getMember();
	
	$mHash = new Hash();
	$password = Decoder(Request('password'));
	if ($password === false || $mHash->password_validate($password,$member->password) == false) {
		$results->success = false;
		$results->error = $this->getErrorText('INCORRECT_PASSWORD');
		return;
	}
	
	$new_password = Request('new_password');
	$new_password_confirm = Request('new_password_confirm');
	
	$errors = array();
	if (strlen($new_password) < 6) {
		$errors['new_password'] = $this->getErrorText('TOO_SHORT_PASSWORD');
	} elseif ($new_password != $new_password_confirm) {
		$errors['new_password_confirm'] = $this->getErrorText('NOT_MATCHED_PASSWORD_CONFIRM');
	}
	
	if (count($errors) == 0) {
		$this->db()->update($this->table->member,array('password'=>$mHash->password_hash($new_password)))->where('idx',$member->idx)->execute();
		
		$results->success = true;
		$results->password = Encoder($new_password);
		$results->message = '패스워드가 성공적으로 변경되었습니다.<br>다음 로그인시 부터 변경된 패스워드로 로그인이 가능합니다.';
	} else {
		$results->success = false;
		$results->errors = $errors;
	}
}
?>