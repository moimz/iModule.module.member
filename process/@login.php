<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 다른 회원계정으로 로그인한다.
 *
 * @file /modules/member/process/@login.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2018. 4. 9.
 */
 
$idx = Request('idx');
if ($this->getMember($idx)->idx != 0) {
	$this->login($idx);
	
	$results->success = true;
} else {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
}
?>