<?php
/**
 * 이 파일은 iModule 코스모스연동모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 모달창을 가져온다.
 * 
 * @file /modules/member/progress/getModal.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160910
 */
if (defined('__IM__') == false) exit;

$modal = Request('modal');

if ($modal == 'login') {
	$results->success = true;
	$results->modalHtml = $this->getLoginModal();
}
?>