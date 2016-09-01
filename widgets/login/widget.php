<?php
if (defined('__IM__') == false) exit;

if ($Widget->getTempletDir() == null) $IM->printError('NOT_SELECTED_TEMPLET');
if (file_exists($Widget->getTempletPath().'/styles/style.css') == true) $IM->addSiteHeader('style',$Widget->getTempletDir().'/styles/style.css');
if (file_exists($Widget->getTempletPath().'/scripts/script.js') == true) $IM->addSiteHeader('script',$Widget->getTempletDir().'/scripts/script.js');

$forceLogin = $Widget->getValue('forceLogin') === true ? true : false;

if ($forceLogin == true || $Module->isLogged() == false) {
	$signupUrl = $Module->getMemberPage('signup') != null ? $IM->getUrl($Module->getMemberPage('signup')->menu,$Module->getMemberPage('signup')->page,false) : '#';
	$findUrl = $Module->getMemberPage('find') != null ? $IM->getUrl($Module->getMemberPage('find')->menu,$Module->getMemberPage('find')->page,false) : '#';
	
	$processUrl = $IM->getProcessUrl('member','login');
	echo '<form method="post" action="'.$processUrl.'" onsubmit="return Member.login(this);">'.PHP_EOL;
	INCLUDE $Widget->getTempletFile();
	echo '</form>'.PHP_EOL;
} else {
	$member = $Module->getMember();
	
	$pushUrl = $Module->getMemberPage('push') != null ? $IM->getUrl($Module->getMemberPage('push')->menu,$Module->getMemberPage('push')->page,false) : '#';
	$pointUrl = $Module->getMemberPage('point') != null ? $IM->getUrl($Module->getMemberPage('point')->menu,$Module->getMemberPage('point')->page,false) : '#';
	$mypageUrl = $Module->getMemberPage('mypage') != null ? $IM->getUrl($Module->getMemberPage('mypage')->menu,$Module->getMemberPage('mypage')->page,false) : '#';
	$modifyUrl = $Module->getMemberPage('modify') != null ? $IM->getUrl($Module->getMemberPage('modify')->menu,$Module->getMemberPage('modify')->page,false) : '#';
	$configUrl = $Module->getMemberPage('config') != null ? $IM->getUrl($Module->getMemberPage('config')->menu,$Module->getMemberPage('config')->page,false) : '#';
	
	INCLUDE $Widget->getTempletFile();
}
?>