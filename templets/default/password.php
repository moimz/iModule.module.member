<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 패스워드 초기화 템플릿
 *
 * @file /modules/member/templets/default/password.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 2. 10.
 */
if (defined('__IM__') == false) exit;
?>
<section class="box <?php echo $step; ?>">
	<div>
		<div>
			<?php if ($view == 'send') { ?>
			<div data-role="text">가입당시 입력한 이메일주소를 입력하시면, 해당 이메일주소로 패스워드를 초기화할 수 있는 링크주소를 보내드립니다.</div>
			
			<div data-role="input">
				<input type="email" name="email" placeholder="<?php echo $me->getText('text/email'); ?>">
			</div>
			
			<div data-role="button">
				<button type="submit">패스워드 초기화 메일 발송</button>
				<button data-action="main"><?php echo $me->getText('button/back_to_main'); ?></button>
			</div>
			<?php } ?>
			
			<?php if ($view == 'reset') { ?>
			<div data-role="text"><?php echo $member->name; ?>님의 패스워드를 초기화합니다.<br>아래의 입력폼에 새로운 패스워드를 입력하여 주십시오.</div>
			
			<div data-role="inputset">
				<div data-role="input">
					<input type="password" name="password" placeholder="<?php echo $me->getText('signup/password'); ?>">
				</div>
				<div data-role="input">
					<input type="password" name="password_confirm" placeholder="<?php echo $me->getText('signup/password_confirm'); ?>">
				</div>
			</div>
			
			<div data-role="button">
				<button type="submit"><?php echo $me->getText('signup/password_change'); ?></button>
			</div>
			<?php } ?>
		</div>
	</div>
</section>