<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 회원 로그인 세션을 동기화한다.
 *
 * @file /modules/member/process/syncSession.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160923
 *
 * @post int $token 세션토큰
 * @return object $results
 */
if (defined('__IM__') == false) exit;

$callback = Request('callback');
$token = Decoder(Request('token'));

if ($token !== false) {
	$session = json_decode($token);
	
	if ($session->idx == 0) {
		unset($_SESSION['MEMBER_LOGGED']);
		
		$results->success = true;
		$results->success = null;
	} else {
		$member = $this->getMember($session->idx);
		if ($member->idx == 0 || in_array($member->status,array('LEAVE','DEACTIVATED')) == true) {
			$results->success = false;
			$results->message = 'NOT_FOUND';
		} else {
			$logged = new stdClass();
			$logged->idx = $session->idx;
			$logged->time = time();
			$logged->ip = $_SERVER['REMOTE_ADDR'];
			
			$_SESSION['MEMBER_LOGGED'] = Encoder(json_encode($logged));
			
			$results->success = true;
			$results->logged = $logged;
		}
	}
} else {
	$results->success = false;
	$results->message = 'TOKEN_ERROR';
}

exit($callback.'('.json_encode($results).');');
?>