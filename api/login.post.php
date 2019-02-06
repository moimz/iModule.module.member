<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 로그인 API 를 처리한다.
 * 
 * @file /modules/member/api/login.post.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 22.
 */
if (defined('__IM__') == false) exit;

$email = Request('email');
$password = Request('password');
$client_id = Request('client_id');

$loginIdx = $this->isValidate($email,$password);
if ($loginIdx === false) {
	$data->success = false;
} else {
	$data->success = true;
	$data->idx = $loginIdx;
	$data->access_token = $this->makeAuthToken($client_id,$loginIdx);
}
?>