<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 정보수정 템플릿
 *
 * @file /modules/member/templets/default/modify.php
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
			<?php if ($step == 'password') { ?>
				<i class="photo" style="background-image:url(<?php echo $member->photo; ?>);"></i>
				
				<h4><?php echo $member->nickname; ?></h4>
				<small><?php echo $member->email; ?></small>
				
				<div data-role="input">
					<input type="password" name="password" placeholder="<?php echo $me->getText('text/password'); ?>">
				</div>
				
				<div data-role="button">
					<button type="submit"><?php echo $me->getText('button/confirm'); ?></button>
					<?php if (defined('__IM_CONTAINER__') == false) { ?><a href="<?php echo $IM->getUrl(false); ?>"><?php echo $me->getText('button/cancel'); ?></a><?php } ?>
				</div>
			<?php } ?>
			
			<?php if ($step == 'insert') { ?>
			
				<h4>기본정보수정</h4>
			
				<ul data-role="form" class="inner black">
					<?php foreach ($defaults as $field) { ?>
					<li>
						<label<?php echo $field->name != 'password' && $field->is_required == true ? ' class="required"' : ''; ?>><?php echo $field->title; ?></label>
						<?php echo $field->inputHtml; ?>
					</li>
					<?php } ?>
				</ul>
				
				<?php if (count($extras) > 0) { ?>
				<h4>부가정보수정</h4>
				
				<ul data-role="form" class="inner black">
					<?php foreach ($extras as $field) { ?>
					<li>
						<label<?php echo $field->is_required == true ? ' class="required"' : ''; ?>><?php echo $field->title; ?></label>
						<?php echo $field->inputHtml; ?>
					</li>
					<?php } ?>
				</ul>
				<?php } ?>
				
				<?php if (count($oauths) > 0) { ?>
				<h4>로그인 연동</h4>
				
				<ul data-role="form" class="inner black social">
					<?php foreach ($oauths as $oauth) { ?>
					<li>
						<label><?php echo $me->getText('social/'.$oauth->site->site); ?></label>
						<div>
							<?php if ($oauth->token == null) { ?>
							<a href="<?php echo $me->getSocialLoginUrl($oauth->site->site); ?>" data-social="<?php echo $oauth->site->site; ?>"><i class="site"></i><span><?php echo str_replace('{SITE}',$me->getText('social/'.$oauth->site->site),$me->getText('social/connect')); ?></span><i class="connect"></i></a>
							<?php } else { ?>
							<button type="button" data-social="<?php echo $oauth->site->site; ?>"><i class="site"></i><span><?php echo str_replace('{SITE}',$me->getText('social/'.$oauth->site->site),$me->getText('social/disconnect')); ?></span><i class="disconnect"></i></button>
							<?php } ?>
						</div>
					</li>
					<?php } ?>
				</ul>
				<?php } ?>
			
				<div data-role="button">
					<button type="submit"><?php echo $me->getText('button/confirm'); ?></button>
					<?php if (defined('__IM_CONTAINER__') == false) { ?><a href="<?php echo $IM->getUrl(false); ?>"><?php echo $me->getText('button/cancel'); ?></a><?php } ?>
				</div>
			<?php } ?>
		</div>
	</div>
</section>