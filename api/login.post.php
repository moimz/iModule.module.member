<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 로그인 API 를 처리한다.
 * 
 * @file /modules/member/api/login.post.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2020. 4. 18.
 */
if (defined('__IM__') == false) exit;

$email = Param('email');
$password = Param('password');
$client_id = Request('client_id');

$mHash = new Hash();
$users = array();
$checks = $this->db()->select($this->table->member,'idx,domain,password')->where('email',$email)->where('status','ACTIVATED')->get();
foreach ($checks as $check) {
	if ($mHash->password_validate($password,$check->password) == true) {
		$user = new stdClass();
		$user->idx = $check->idx;
		$user->domain = $check->domain;
		$user->token = $this->makeAuthToken($client_id,$check->idx);
		$users[] = $user;
	}
}

if (count($users) == 0) {
	$data->success = false;
	$data->message = 'NOT_FOUND_USER';
} else {
	$data->success = true;
	$data->users = $users;
}
?>