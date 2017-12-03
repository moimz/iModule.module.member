<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 구글 계정을 이용하여 로그인한다.
 * 
 * @file /modules/member/progress/google.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 29.
 */
if (defined('__IM__') == false) exit;

$site = $this->db()->select($this->table->social_oauth)->where('site',$action)->getOne();
if ($site == null) $this->IM->printError('OAUTH_API_ERROR',null,null,true);

$_auth_url = 'https://accounts.google.com/o/oauth2/auth';
$_token_url = 'https://accounts.google.com/o/oauth2/token';

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

$data = $oauth->get('https://www.googleapis.com/plus/v1/people/me');
if ($data === false || empty($data->emails) == true) $this->IM->printError('OAUTH_API_ERROR');

$_logged->user = new stdClass();
$_logged->user->id = $data->id;
$_logged->user->name = $data->displayName;
$_logged->user->nickname = $data->displayName;

for ($i=0, $loop=count($data->emails);$i<$loop;$i++) {
	if ($data->emails[$i]->type == 'account') {
		$_logged->user->email = $data->emails[$i]->value;
		break;
	}
}

$_logged->user->photo = str_replace('sz=50','sz=250',$data->image->url);

$_logged->token = new stdClass();
$_logged->token->access = $oauth->getAccessToken();
$_logged->token->refresh = $oauth->getRefreshToken() == null ? '' : $oauth->getRefreshToken();

$this->loginBySocial();
?>