<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 다른 회원계정으로 로그인한다.
 *
 * @file /modules/member/process/@login.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0.160923
 *
 * @return object $results
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