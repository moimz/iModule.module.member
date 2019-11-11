<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 서로 다른 도메인간 회원 로그인 세션을 동기화한다.
 *
 * @file /modules/member/process/syncSession.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2019. 11. 12.
 */
if (defined('__IM__') == false) exit;

$callback = Request('callback');
$token = Decoder(Request('token'),null,'hex');

if ($token !== false) {
	$session = json_decode($token);
	
	if ($session->idx == 0) {
		unset($_SESSION['IM_MEMBER_LOGGED']);
		
		$results->success = null;
	} else {
		$results->success = $this->login($session->idx,true,false);
		$results->logged = $results->success == true ? $this->getLogged() : null;
	}
} else {
	$results->success = false;
	$results->message = 'TOKEN_ERROR';
}

exit($callback.'('.json_encode($results).');');
?>