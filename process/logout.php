<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 회원 로그아웃을 처리한다.
 *
 * @file /modules/member/templets/default/logout.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160922
 *
 * @return object $results
 */

if (defined('__IM__') == false) exit;

unset($_SESSION['MEMBER_LOGGED']);
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) == true) {
	$results->success = true;
} else {
	$redirect = Request('redirect') ? Request('redirect') : __IM_DIR__.'/'.$this->IM->language;
	header("location:".urldecode($redirect));
	exit;
}
?>