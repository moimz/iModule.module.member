<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 회원 로그아웃을 처리한다.
 *
 * @file /modules/member/process/logout.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 23.
 */
if (defined('__IM__') == false) exit;

unset($_SESSION['IM_MEMBER_LOGGED']);
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) == true) {
	$results->success = true;
	$results->universal_login = $this->getModule()->getConfig('universal_login');
} else {
	$redirect = Request('redirect') ? Request('redirect') : __IM_DIR__.'/'.$this->IM->language;
	header("location:".urldecode($redirect));
	exit;
}
?>