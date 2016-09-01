<?php if ($step == 'verify') { ?>
<section class="login">
	<h4<?php echo $IM->getSite()->logo !== null ? ' style="background-image:url('.$IM->getSiteLogo().');"' : ''; ?>><?php echo $IM->getSiteTitle(); ?></h4>
	
	<p><?php echo $Module->getLanguage('verify/help/password/default'); ?></p>
	
	<div class="formBox">
		<div class="photo" style="background-image:url(<?php echo $member->photo; ?>);"></div>
		<div class="name"><?php echo $member->nickname; ?></div>
		<div class="email"><?php echo $member->email; ?></div>
		
		<div class="inputBlock">
			<input type="password" name="password" placeholder="<?php echo $Module->getLanguage('info/password'); ?>" class="inputControl">
			<div class="helpBlock"></div>
		</div>
	
		<button type="submit"><?php echo $Module->getLanguage('login/login'); ?></button>
	</div>
	<!--
	<ul>
		<li><a href="<?php echo $signupUrl; ?>">Member SignUp</a></li>
		<li><a href="<?php echo $findUrl; ?>">Forgot Password</a></li>
	</ul>
	-->
</section>
<?php } else { ?>
<section class="column">
	<aside>
		<h4><?php echo $Module->getLanguage('modify/default'); ?></h4>
		
		<div>
			서비스를 원활하게 사용하기 위하여 이 기본 정보를 항상 최신의 정보로 업데이트하여 주십시오.<br>
			입력된 개인정보는 개인정보보호정책에 따라 안전하게 보관됩니다.
		</div>
	</aside>
	
	<section data-role="form">
		<div class="formBox">
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('info/email'); ?></div></label>
				
				<div class="inputBlock clickBlock" onclick="Member.modify.modifyEmail();">
					<span class="text" data-name="email"><?php echo $member->email; ?></span>
					<i class="fa fa-angle-right"></i>
				</div>
			</div>
		</div>
		
		<div class="formBox">
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('info/photo'); ?></div></label>
				
				<div class="inputBlock clickBlock" onclick="Member.modify.photoEdit();">
					<img id="ModuleMemberPhotoPreview" src="<?php echo $member->photo; ?>" class="memberPhoto">
					<i class="fa fa-angle-right"></i>
				</div>
			</div>
		</div>
		
		<div class="formBox">
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('info/name'); ?></div></label>
				
				<div class="inputBlock">
					<input type="text" name="name" class="inputControl" value="<?php echo $member->name; ?>" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/name/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/name/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/name/success'); ?>"></div>
				</div>
			</div>
			
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('info/nickname'); ?></div></label>
				
				<div class="inputBlock">
					<input type="text" name="nickname" class="inputControl" value="<?php echo $member->nickname; ?>" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/nickname/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/nickname/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/nickname/success'); ?>" data-duplicated="<?php echo $Module->getLanguage('signup/help/nickname/duplicated'); ?>"></div>
				</div>
			</div>
		</div>
		
		<button type="submit" data-loading="<?php echo $Module->getLanguage('signup/loading'); ?>"><?php echo $Module->getLanguage('button/confirm'); ?></button>
	</section>
</section>
<?php } ?>