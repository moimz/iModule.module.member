<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 로그인 위젯
 *
 * @file /modules/member/widgets/login/index.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0.161001
 */
if (defined('__IM__') == false) exit;

/**
 * 템플릿파일이 없을 경우 에러메세지를 출력한다.
 */

$forceLogin = $Widget->getValue('force_login') === true ? true : false;

if ($forceLogin == true || $me->isLogged() == false) {
	$header = '<form id="'.$Widget->getRandomId().'">'.PHP_EOL;
	$header.= '<section class="login">'.PHP_EOL;
	$footer = PHP_EOL.'</section>'.PHP_EOL;
	$footer.= '</form>'.PHP_EOL.'<script>$("#'.$Widget->getRandomId().'").inits(Member.login);</script>'.PHP_EOL;
	
	return $Templet->getContext('login',get_defined_vars(),$header,$footer);
} else {
	$member = $me->getMember();

	$header = '<section class="logged">'.PHP_EOL;
	$footer = PHP_EOL.'</section>'.PHP_EOL;
	
	return $Templet->getContext('logged',get_defined_vars(),$header,$footer);
}
?>