<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 카카오 계정을 이용하여 로그인한다.
 * 
 * @file /modules/member/process/kakao.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2021. 5. 31.
 */
if (defined('__IM__') == false) exit;

$site = $this->getOAuth($action);
if ($site == null) $this->printError('OAUTH_API_ERROR',null,null,true);

$oauth = new OAuthClient();
$oauth->setUserAgent('iModule OAuth2.0 client')->setClientId($site->client_id)->setClientSecret($site->client_secret)->setMethod('get')->setScope($site->scope)->setAccessType('offline')->setAuthUrl($site->auth_url)->setTokenUrl($site->token_url)->setApprovalPrompt('auto');

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

$data = $oauth->get('https://kapi.kakao.com/v1/user/me');
if ($data === false || empty($data->kaccount_email) == true) $this->printError('OAUTH_API_ERROR');

$_logged->user = new stdClass();
$_logged->user->id = $data->id;
$_logged->user->name = $data->properties->nickname;
$_logged->user->nickname = $data->properties->nickname;
$_logged->user->email = $data->kaccount_email;
$_logged->user->photo = $data->properties->profile_image;

$_logged->access_token = $oauth->getAccessToken(true)->access_token;
$_logged->access_token_expired = $oauth->getAccessToken(true)->expires_in;
$_logged->refresh_token = $oauth->getRefreshToken() == null ? '' : $oauth->getRefreshToken();

$this->loginByOAuth($_logged);
?>