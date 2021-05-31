<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 네이버 계정을 이용하여 로그인한다.
 * 
 * @file /modules/member/process/naver.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2021. 5. 31.
 */
if (defined('__IM__') == false) exit;

$site = $this->getOAuth($action);
if ($site == null) $this->printError('OAUTH_API_ERROR',null,null,true);

$oauth = new OAuthClient();
$oauth->setUserAgent('iModule OAuth2.0 client')->setClientId($site->client_id)->setClientSecret($site->client_secret)->setScope($site->scope)->setAccessType('offline')->setAuthUrl($site->auth_url)->setTokenUrl($site->token_url)->setApprovalPrompt('auto');

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
	$_logged->redirect = isset($_SERVER['HTTP_REFERER']) == true && strpos('oauth/'.$action,$_SERVER['HTTP_REFERER']) === false ? $_SERVER['HTTP_REFERER'] : $this->IM->getIndexUrl();
	
	$_SESSION['IM_SOCIAL_LOGGED'] = $_logged;
	
	/**
	 * 네이버는 CALLBACK_URL 을 체크할 뿐만 아니라, 요청한 REFERER 를 확인한다.
	 * 따라서 header("location ... 방식으로 페이지를 이동할 수 없다. FUCK!
	 */
	$authUrl = $oauth->getAuthenticationUrl();
	echo '<script>location.href = "'.$authUrl.'";</script>';
	exit;
}

$_logged = Request('IM_SOCIAL_LOGGED','session');
if ($_logged == null) {
	$this->printError('OAUTH_API_ERROR',null,null,true);
}

$data = $oauth->get('https://openapi.naver.com/v1/nid/me');
if ($data === false || empty($data->response->email) == true) $this->printError('OAUTH_API_ERROR');

$_logged->user = new stdClass();
$_logged->user->id = $data->response->enc_id;
$_logged->user->name = $data->response->name;
$_logged->user->nickname = $data->response->nickname;
$_logged->user->email = $data->response->email;
$_logged->user->photo = $data->response->profile_image;

$_logged->access_token = $oauth->getAccessToken(true)->access_token;
$_logged->access_token_expired = $oauth->getAccessToken(true)->expires_in;
$_logged->refresh_token = $oauth->getRefreshToken() == null ? '' : $oauth->getRefreshToken();

$this->loginByOAuth($_logged);
?>