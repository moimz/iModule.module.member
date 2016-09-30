<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 회원가입 컨텍스트를 위한 기본템플릿
 *
 * @file /modules/member/templets/default/signup.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160922
 * @see /modules/member/ModuleMember.class.php -> getSignUpContext()
 */
?>

<ul class="step">
	<?php for ($i=0, $loop=count($steps);$i<$loop;$i++) { ?>
	<li class="<?php echo $steps[$i]; ?><?php echo $steps[$i] == $step ? ' selected' : ''; ?>">
		<i></i>
		<?php echo $Module->getLanguage('text/signup_step/'.$steps[$i]); ?>
	</li>
	<?php } ?>
</ul>

<section class="<?php echo $step; ?>">
	<h4><?php echo $Module->getLanguage('text/signup_step/'.$step); ?></h4>
	
	<?php if ($step == 'agreement') { ?>
	<!-- 약관동의 -->
	
	<?php if ($agreement != null) { // 회원약관이 있을 경우, ?>
	<h5><?php echo $agreement->title; ?></h5>
	
	<article>
		<?php echo $agreement->content; ?>
	</article>
	
	<div data-role="input">
		<label><input type="checkbox" name="agreements[]" value="<?php echo $agreement->value; ?>"><?php echo $agreement->help; ?></label>
	</div>
	<?php } ?>
	
	<?php if ($privacy != null) { // 개인정보보호정책이 있을 경우, ?>
	<h5><?php echo $privacy->title; ?></h5>
	
	<article>
		<?php echo $privacy->content; ?>
	</article>
	
	<div data-role="input">
		<label><input type="checkbox" name="agreements[]" value="<?php echo $privacy->value; ?>"><?php echo $privacy->help; ?></label>
	</div>
	<?php } ?>
	
	<!--// 약관동의 -->
	<?php } ?>
	
	
	<?php if ($step == 'label') { ?>
	<!-- 회원유형선택 -->
	
	<div data-role="inputset" class="inline">
		<?php for ($i=0, $loop=count($labels);$i<$loop;$i++) { ?>
		<div data-role="input">
			<label><input type="radio" name="label" value="<?php echo $labels[$i]->idx; ?>"<?php echo $labels[$i]->allow_signup == false ? ' disabled="disabled"' : ''; ?>><?php echo $labels[$i]->title; ?></label>
		</div>
		<?php } ?>
	</div>
	
	<!--// 회원유형선택 -->
	<?php } ?>
	
	
	<?php
	if ($step == 'insert') {
		
	?>
	<!-- 회원정보입력 -->
	
	<h5>기본정보입력</h5>
	
	<ul data-role="table" class="red form inner outer">
		<?php foreach ($defaults as $field) { ?>
		<li>
			<span class="thead"><?php echo $field->title; ?></span>
			<span class="tbody">
				<?php echo $field->inputHtml; ?>
			</span>
		</li>
		<?php } ?>
	</ul>
	
	<h5>추가정보입력</h5>
	
	<ul data-role="table" class="red form inner outer">
		<?php foreach ($extras as $field) { ?>
		<li>
			<span class="thead"><?php echo $field->title; ?></span>
			<span class="tbody">
				<?php echo $field->inputHtml; ?>
			</span>
		</li>
		<?php } ?>
	</ul>
	
	<!--// 회원정보입력 -->
	<?php } ?>

<div data-role="button">
	<button type="submit"><?php echo $Module->getLanguage('button/next'); ?></button>
	<a href="<?php echo $IM->getUrl(false); ?>"><?php echo $Module->getLanguage('button/cancel'); ?></a>
</div>