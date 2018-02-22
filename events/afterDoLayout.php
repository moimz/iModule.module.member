<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * afterDoLayout 이벤트를 처리한다.
 * 
 * @file /modules/member/events/afterDoLayout.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 1. 23.
 */
if (defined('__IM__') == false) exit;

if ($_SERVER['HTTP_HOST'] == 'dev.minitalk.kr') {

if ($target == 'core') {
	if ($me->isLogged() == true && $me->getMember()->verified == 'FALSE') {
		define('__IM_CONTAINER__',true);
		$html = $me->getContainer('verification');
	}
}

}
?>