<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 네이버 계정을 이용하여 로그인한다.
 * 
 * @file /modules/member/progress/naver.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 29.
 */
if (defined('__IM__') == false) exit;

$site = $this->db()->select($this->table->social_oauth)->where('site',$action)->getOne();
if ($site == null) $this->IM->printError('OAUTH_API_ERROR',null,null,true);

$_auth_url = 'https://nid.naver.com/oauth2.0/authorize';
$_token_url = 'https://nid.naver.com/oauth2.0/token';

$oauth = new OAuthClient();
$oauth->setClientId($site->client_id)->setClientSecret($site->client_secret)->setScope($site->scope)->setAccessType('offline')->setAuthUrl($_auth_url)->setTokenUrl($_token_url)->setApprovalPrompt('auto');

if (isset($_GET['code']) == true) {
	if ($oauth->authenticate($_GET['code']) == true) {
		$redirectUrl = $oauth->getRedirectUrl();
		header('location:'.$redirectUrl);
		exit;
	} else {
		$this->IM->printError('OAUTH_API_ERROR',null,null,true);
	}
} elseif ($oauth->getAccessToken() == null) {
	$_logged = new stdClass();
	$_logged->site = $site;
	$_logged->redirect = isset($_SERVER['HTTP_REFERER']) == true ? $_SERVER['HTTP_REFERER'] : $this->IM->getUrl(false);
	
	$_SESSION['SOCIAL_LOGGED'] = $_logged;
	
	$authUrl = $oauth->getAuthenticationUrl();
	header('location:'.$authUrl);
	exit;
}

$_logged = Request('SOCIAL_LOGGED','session');
if ($_logged == null) {
	$this->IM->printError('OAUTH_API_ERROR',null,null,true);
}

$data = $oauth->get('https://openapi.naver.com/v1/nid/me');
if ($data === false || empty($data->response->email) == true) $this->IM->printError('OAUTH_API_ERROR');

$_logged->user = new stdClass();
$_logged->user->id = $data->response->enc_id;
$_logged->user->name = $data->response->name;
$_logged->user->nickname = $data->response->nickname;
$_logged->user->email = $data->response->email;
$_logged->user->photo = $data->response->profile_image;

$_logged->token = new stdClass();
$_logged->token->access = $oauth->getAccessToken();
$_logged->token->refresh = $oauth->getRefreshToken() == null ? '' : $oauth->getRefreshToken();

$this->loginBySocial();
?>