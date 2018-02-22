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
			
				<div data-role="button">
					<button type="submit"><?php echo $me->getText('button/confirm'); ?></button>
					<?php if (defined('__IM_CONTAINER__') == false) { ?><a href="<?php echo $IM->getUrl(false); ?>"><?php echo $me->getText('button/cancel'); ?></a><?php } ?>
				</div>
			<?php } ?>
		</div>
	</div>
</section>