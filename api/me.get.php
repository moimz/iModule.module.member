<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 현재 로그인된 사용자의 정보 API 를 처리한다.
 * 
 * @file /modules/member/api/me.get.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 22.
 */
if (defined('__IM__') == false) exit;

if ($this->isLogged() == false) {
	$data->success = false;
	$data->message = 'NOT LOGGED';
} else {
	$data->success = true;
	$data->me = $this->getMember();
	$data->me->photo = $this->IM->getHost(true).$data->me->photo;
}
?>