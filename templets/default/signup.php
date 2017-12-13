<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 회원가입 템플릿
 *
 * @file /modules/member/templets/default/signup.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 30.
 */
if (defined('__IM__') == false) exit;
?>
<section class="box">
	<div>
		<div class="<?php echo $step; ?>">
			<?php if ($step == 'agreement') { ?>
			
				<?php if ($agreement != null) { ?>
				<h4><?php echo $agreement->title; ?></h4>
				
				<article>
					<?php echo $agreement->content; ?>
				</article>
				
				<div data-role="input">
					<label><input type="checkbox" name="<?php echo $agreement->name; ?>" value="<?php echo $privacy->value; ?>"><?php echo $privacy->help; ?></label>
				</div>
				<?php } ?>
				
				<?php if ($privacy != null) { ?>
				<h4><?php echo $privacy->title; ?></h4>
				
				<article>
					<?php echo $privacy->content; ?>
				</article>
				
				<div data-role="input">
					<label><input type="checkbox" name="<?php echo $privacy->name; ?>" value="<?php echo $privacy->value; ?>"><?php echo $privacy->help; ?></label>
				</div>
				<?php } ?>
				
			<?php } ?>
			
			<?php if ($step == 'register') { ?>
			
				<h4>기본정보입력</h4>
			
				<ul data-role="form" class="inner black">
					<?php foreach ($defaults as $field) { ?>
					<li>
						<label><?php echo $field->title; ?></label>
						<?php echo $field->inputHtml; ?>
					</li>
					<?php } ?>
				</ul>
				
				<?php if (count($extras) > 0) { ?>
				<h4>부가정보입력</h4>
				<ul data-role="form" class="inner exteras">
					<?php foreach ($extras as $field) { ?>
					<li>
						<label><?php echo $field->title; ?></label>
						<?php echo $field->inputHtml; ?>
					</li>
					<?php } ?>
				</ul>
				<?php } ?>
			
			<?php } ?>
			
			<div data-role="button">
				<button type="submit"><?php echo $step == 'complete' ? $me->getText('button/back_to_main') : $me->getText('button/next'); ?></button>
				<?php if (defined('__IM_CONTAINER__') == false) { ?><a href="<?php echo $IM->getUrl(false); ?>"><?php echo $me->getText('button/cancel'); ?></a><?php } ?>
			</div>
		</div>
	</div>
</section>