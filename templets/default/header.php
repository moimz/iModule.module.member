<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 회원 기본템플릿 헤더
 *
 * @file /modules/member/templets/default/header.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2017. 11. 30.
 */
if (defined('__IM__') == false) exit;
if (defined('__IM_CONTAINER__') == true) {
	$IM->addHeadResource('style',$me->getTemplet()->getDir().'/styles/container.css');
	$container = explode('/',$IM->container);
	
	if (defined('__IM_CONTAINER_POPUP__') == true) {
?>
<header>
	<div class="container">
		<h1><?php echo $me->getContextTitle(end($container)); ?></h1>
		<button type="button" onclick="self.close();"><i class="mi mi-close"></i></button>
	</div>
</header>
<?php } ?>

<div class="<?php echo defined('__IM_CONTAINER_POPUP__') == true ? 'container' : 'center'; ?>">
<?php } ?>