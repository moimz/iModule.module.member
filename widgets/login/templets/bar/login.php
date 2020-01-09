<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 로그인 위젯 가로바 템플릿 - 로그인폼
 * 
 * @file /modules/member/widgets/login/templets/bar/login.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2019. 6. 8.
 */
if (defined('__IM__') == false) exit;

$Widget->setAttribute('data-thema',$Widget->getValue('thema'));
?>
<button type="button" data-action="login"><?php echo $me->getText('text/login'); ?></button>