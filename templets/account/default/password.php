<section class="column">
	<aside>
		<h4><?php echo $Module->getLanguage('password/title'); ?></h4>
		
		<div>
			강력한 패스워드를 설정하고 다른 계정에 동일한 패스워드를 사용하지 마세요.<br>
			패스워드를 변경하면 휴대전화를 비롯한 모든 기기에서 로그아웃되므로 모든 기기에 새로운 비밀번호를 입력해야 합니다.
		</div>
	</aside>
	
	<section data-role="form">
		<div class="formBox">
			<?php if ($type == 'default') { ?>
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('password/old_password'); ?></div></label>
				<div class="inputBlock">
					<input type="password" name="old_password" class="inputControl" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('password/help/old_password/default'); ?>" data-success="<?php echo $Module->getLanguage('password/help/old_password/success'); ?>"></div>
				</div>
			</div>
			<?php } ?>
			
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('password/password'); ?></div></label>
				
				<div class="inputBlock">
					<input type="password" name="password" class="inputControl" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/password/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/password/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/password/success'); ?>"></div>
				</div>
			</div>
			
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('info/password_confirm'); ?></div></label>
				
				<div class="inputBlock">
					<input type="password" name="password_confirm" class="inputControl" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/password_confirm/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/password_confirm/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/password_confirm/success'); ?>"></div>
				</div>
			</div>
		</div>
		
		<button type="submit" data-loading="<?php echo $Module->getLanguage('signup/loading'); ?>"><?php echo $Module->getLanguage('button/confirm'); ?></button>
	</section>
</section>