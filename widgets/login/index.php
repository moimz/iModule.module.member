<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 로그인 위젯
 *
 * @file /modules/member/widgets/login/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 4. 5.
 */
if (defined('__IM__') == false) exit;

$forceLogin = $Widget->getValue('force_login') === true ? true : false;

if ($forceLogin == true || $me->isLogged() == false) {
	$header = '<form id="'.$Widget->getRandomId().'">'.PHP_EOL;
	$header.= '<section class="login">'.PHP_EOL;
	$footer = PHP_EOL.'</section>'.PHP_EOL;
	$footer.= '</form>'.PHP_EOL.'<script>$("#'.$Widget->getRandomId().'").inits(Member.login);</script>'.PHP_EOL;
	
	$oauths = $me->db()->select($me->getTable('social_oauth').' o','o.site')->join($me->getTable('social_sort').' s','s.site=o.site','LEFT')->where('o.domain',array('*',$IM->domain),'IN')->groupBy('o.site')->orderBy('s.sort','asc')->get();
	for ($i=0, $loop=count($oauths);$i<$loop;$i++) {
		$oauths[$i]->link = $me->getSocialLoginUrl($oauths[$i]->site);
	}
	
	$allow_signup = $me->getModule()->getConfig('allow_signup') == true;
	$allow_reset_password = $me->getModule()->getConfig('allow_reset_password') == true;
	$signup = $IM->getContextUrl('member','signup');
	$help = $IM->getContextUrl('member','password');
	
	return $Templet->getContext('login',get_defined_vars(),$header,$footer);
} else {
	$member = $me->getMember();

	$header = '<section class="logged">'.PHP_EOL;
	$footer = PHP_EOL.'</section>'.PHP_EOL;
	
	$modify = $IM->getContextUrl('member','modify');
	$point = $IM->getContextUrl('member','point');
	$activity = $IM->getContextUrl('member','activity');
	$push = $IM->getContextUrl('push','list');
	
	return $Templet->getContext('logged',get_defined_vars(),$header,$footer);
}
?>