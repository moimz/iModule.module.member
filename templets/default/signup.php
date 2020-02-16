<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 회원가입 템플릿
 *
 * @file /modules/member/templets/default/signup.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2020. 2. 17.
 */
if (defined('__IM__') == false) exit;
?>
<section class="box">
	<div>
		<div class="<?php echo $step; ?>">
			<?php if ($step == 'start') { ?>
				<h4>가입유형 선택</h4>
				
				<ul data-role="labels">
					<li>
						<a href="<?php echo $IM->getUrl(null,null,'register',0); ?>">
							<b>일반회원</b>
							<small>아래의 경우에 해당되지 않는 일반회원</small>
						</a>
					</li>
					<?php foreach ($labels as $label) { ?>
					<li>
						<a href="<?php echo $IM->getUrl(null,null,'register',$label->idx); ?>">
							<b><?php echo $label->title; ?></b>
							<small><?php echo $label->description; ?></small>
						</a>
					</li>
					<?php } ?>
				</ul>
			<?php } ?>
			
			<?php if ($step == 'agreement') { ?>
			
				<?php if ($agreement != null) { ?>
				<h4><?php echo $agreement->title; ?></h4>
				
				<article>
					<?php echo $agreement->content; ?>
				</article>
				
				<div data-role="input">
					<label><input type="checkbox" name="<?php echo $agreement->name; ?>" value="<?php echo $agreement->value; ?>"><?php echo $agreement->help; ?></label>
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
				
				<div data-role="button">
					<button type="submit"><?php echo $me->getText('button/next'); ?></button>
					<?php if (defined('__IM_CONTAINER__') == false) { ?><a href="<?php echo $IM->getIndexUrl(); ?>"><?php echo $me->getText('button/cancel'); ?></a><?php } ?>
				</div>
				
			<?php } ?>
			
			<?php if ($step == 'register') { ?>
			
				<h4>기본정보입력</h4>
			
				<ul data-role="form" class="inner black">
					<?php foreach ($defaults as $field) { ?>
					<li>
						<label<?php echo $field->is_required == true ? ' class="required"' : ''; ?>><?php echo $field->title; ?></label>
						<div>
							<?php echo $field->inputHtml; ?>
						</div>
					</li>
					<?php } ?>
				</ul>
				
				<?php if (count($extras) > 0) { ?>
				<h4>부가정보입력</h4>
				
				<ul data-role="form" class="inner black">
					<?php foreach ($extras as $field) { ?>
					<li>
						<label<?php echo $field->is_required == true ? ' class="required"' : ''; ?>><?php echo $field->title; ?></label>
						<div>
							<?php echo $field->inputHtml; ?>
						</div>
					</li>
					<?php } ?>
				</ul>
				<?php } ?>
				
				<div data-role="button">
					<button type="submit"><?php echo $me->getText('button/next'); ?></button>
					<?php if (defined('__IM_CONTAINER__') == false) { ?><a href="<?php echo $IM->getIndexUrl(); ?>"><?php echo $me->getText('button/cancel'); ?></a><?php } ?>
				</div>
			
			<?php } ?>
			
			<?php if ($step == 'complete') { ?>
			
				<h4><?php echo $member->nickname; ?>님 회원가입을 환영합니다.</h4>
				
				<div data-role="text">
					<?php if ($is_verified_email == true) { ?>
						<?php echo $member->email; ?> 로 이메일인증을 위한 메일을 발송하여 드렸습니다.<br>
						이메일에 포함된 링크를 클릭하시거나, 아래의 버튼을 클릭하여 인증코드를 입력하실 수 있습니다.
					<?php } else { ?>
						메인페이지로 이동하여 모든 서비스를 이용하실 수 있습니다.
					<?php } ?>
				</div>
				
				<div data-role="button">
					<?php if ($is_verified_email == true) { ?><a href="<?php echo $verified_email_link; ?>" class="submit"><?php echo $me->getText('button/verified_email'); ?></a><?php } ?>
					<button type="button" data-action="main"><?php echo $me->getText('button/back_to_main'); ?></button>
				</div>
			<?php } ?>
		</div>
	</div>
</section>