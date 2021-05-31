<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 깃허브 계정을 이용하여 로그인한다.
 * 
 * @file /modules/member/process/github.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2021. 5. 31.
 */
if (defined('__IM__') == false) exit;

$site = $this->getOAuth($action);
if ($site == null) $this->printError('OAUTH_API_ERROR',null,null,true);

$_auth_url = 'https://github.com/login/oauth/authorize';
$_token_url = 'https://github.com/login/oauth/access_token';

$oauth = new OAuthClient();
$oauth->setUserAgent('iModule OAuth2.0 client')->setClientId($site->client_id)->setClientSecret($site->client_secret)->setScope($site->scope)->setAccessType('offline')->setAuthUrl($_auth_url)->setTokenUrl($_token_url)->setApprovalPrompt('auto');

if (isset($_GET['code']) == true) {
	if ($oauth->authenticate($_GET['code']) == true) {
		$redirectUrl = $oauth->getRedirectUrl();
		header('location:'.$redirectUrl);
		exit;
	} else {
		$this->printError('OAUTH_API_ERROR',null,null,true);
	}
} elseif ($oauth->getAccessToken() == null) {
	$_logged = new stdClass();
	$_logged->site = $site;
	$_logged->redirect = isset($_SERVER['HTTP_REFERER']) == true ? $_SERVER['HTTP_REFERER'] : $this->IM->getIndexUrl();
	
	$_SESSION['IM_SOCIAL_LOGGED'] = $_logged;
	
	$authUrl = $oauth->getAuthenticationUrl();
	header('location:'.$authUrl);
	exit;
}

$_logged = Request('IM_SOCIAL_LOGGED','session');
if ($_logged == null) {
	$this->printError('OAUTH_API_ERROR',null,null,true);
}

$data = $oauth->get('https://api.github.com/user');
if ($data === false || empty($data->email) == true) $this->printError('OAUTH_API_ERROR',null,null,true);

$_logged->user = new stdClass();
$_logged->user->id = $data->id;
$_logged->user->name = $data->name;
$_logged->user->nickname = $data->name;
$_logged->user->email = $data->email;
$_logged->user->photo = $data->avatar_url;

$_logged->access_token = $oauth->getAccessToken(true)->access_token;
$_logged->access_token_expired = $oauth->getAccessToken(true)->expires_in;
$_logged->refresh_token = $oauth->getRefreshToken() == null ? '' : $oauth->getRefreshToken();

$this->loginByOAuth($_logged);
?>