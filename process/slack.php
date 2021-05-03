<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 슬랙 계정을 이용하여 로그인한다.
 * 
 * @file /modules/member/process/slack.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2020. 4. 24.
 */
if (defined('__IM__') == false) exit;

$site = $this->db()->select($this->table->social_oauth)->where('site',$action)->where('domain',array('*',$this->IM->domain),'IN')->orderBy('domain','desc')->getOne();
if ($site == null) $this->printError('OAUTH_API_ERROR',null,null,true);

$_auth_url = 'https://slack.com/oauth/authorize';
$_token_url = 'https://slack.com/api/oauth.access';

$oauth = new OAuthClient();
$oauth->setUserAgent('iModule OAuth2.0 client')->setClientId($site->client_id)->setClientSecret($site->client_secret)->setScope($site->scope)->setAccessType('offline')->setAuthUrl($_auth_url)->setTokenUrl($_token_url)->setHeader(array())->setApprovalPrompt('auto');

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

$access_token = $oauth->getAccessToken(true);
$data = $oauth->post('https://slack.com/api/users.profile.get',array('user'=>$access_token->rawData->user_id));
if ($data === false || empty($data->profile->email) == true) $this->printError('OAUTH_API_ERROR',null,null,true);

$_logged->user = new stdClass();
$_logged->user->id = $access_token->rawData->user_id;
$_logged->user->name = $data->profile->real_name;
$_logged->user->nickname = $data->profile->display_name;
$_logged->user->email = $data->profile->email;
$_logged->user->photo = $data->profile->image_512;

$_logged->token = new stdClass();
$_logged->token->access = $oauth->getAccessToken();
$_logged->token->refresh = $oauth->getRefreshToken() == null ? '' : $oauth->getRefreshToken();

$this->loginBySocial();
?>