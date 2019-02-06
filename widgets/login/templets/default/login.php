<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 로그인 위젯 기본템플릿
 *
 * @file /modules/member/widgets/login/templets/default/login.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 4. 5.
 */
if (defined('__IM__') == false) exit;
?>
<div data-role="input">
	<input type="email" name="email" placeholder="<?php echo $me->getText('text/email'); ?>">
</div>

<div data-role="input">
	<input type="password" name="password" placeholder="<?php echo $me->getText('text/password'); ?>">
</div>

<?php if (count($oauths) > 0) { ?>
<ul data-module="member" data-role="social" class="<?php echo count($oauths) > 3 ? 'icon' : 'button'; ?>">
	<?php foreach ($oauths as $oauth) { ?>
	<li class="<?php echo $oauth->site; ?>"><a href="<?php echo $oauth->link; ?>"><i></i><span><?php echo str_replace('{SITE}',$me->getText('social/'.$oauth->site),$me->getText('social/login')); ?></span></a></li>
	<?php } ?>
</ul>
<?php } ?>

<div data-role="button">
	<button type="submit"><?php echo $me->getText('button/login'); ?></button>
</div>

<div data-role="input">
	<label><input type="checkbox" name="auto" value="TRUE"><?php echo $me->getText('text/auto_login'); ?></label>
</div>

<?php if ($allow_signup == true || $allow_reset_password == true) { ?>
<div data-role="link">
	<?php if ($allow_signup == true) { ?>
		<?php if ($signup == null) { ?><button type="button" onclick="Member.signupPopup();"><?php echo $Widget->getText('text/signup'); ?></button><?php } else { ?><a href="<?php echo $IM->getUrl($signup->menu,$signup->page,false); ?>"><?php echo $Widget->getText('text/signup'); ?></a><?php } ?>
	<?php } ?>
	
	<?php if ($allow_signup == true && $allow_reset_password == true) { ?><i></i><?php } ?>
	
	<?php if ($allow_reset_password == true) { ?>
		<?php if ($help == null) { ?><button type="button" type="button" onclick="Member.helpPopup();"><?php echo $Widget->getText('text/help'); ?></button><?php } else { ?><a href="<?php echo $IM->getUrl($help->menu,$help->page,false); ?>"><?php echo $Widget->getText('text/help'); ?></a><?php } ?>
	<?php } ?>
</div>
<?php } ?>