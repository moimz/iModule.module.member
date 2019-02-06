<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 모달창을 가져온다.
 * 
 * @file /modules/member/process/getModal.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 29.
 */
if (defined('__IM__') == false) exit;

$modal = Request('modal');

if ($modal == 'login') {
	$results->success = true;
	$results->modalHtml = $this->getLoginModal();
}

if ($modal == 'photo') {
	$results->success = true;
	$results->modalHtml = $this->getPhotoModal();
}

if ($modal == 'password') {
	$results->success = true;
	$results->modalHtml = $this->getPasswordModal();
}
?>