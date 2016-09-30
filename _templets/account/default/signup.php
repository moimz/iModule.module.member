<div data-role="templet-inputGroup" class="formBox"></div>

<div data-role="templet-inputRow" class="inputbox">
	<label><div data-role="inputLabel"></div></label>
	
	<div data-role="inputForm" class="inputBlock"></div>
</div>

<section class="column">
	<?php if ($view == 'agreement') { ?>
	<aside>
		<h4><?php echo $Module->getLanguage('signup/terms'); ?></h4>
		
		<div><?php echo $Module->Module->getConfig('signupText'); ?></div>
	</aside>
	
	<section data-role="form">
		<div class="formBox">
			<div style="min-height:300px;">
				
			</div>
		</div>
		<button type="submit" data-loading="<?php echo $Module->getLanguage('signup/loading'); ?>"><?php echo $Module->getLanguage('signup/next'); ?></button>
	</section>
	<?php } ?>
	
	<?php if ($view == 'cert') { ?>
	<aside>
		<h4><?php echo $Module->getLanguage('signup/cert'); ?></h4>
		
		<div><?php echo $Module->Module->getConfig('signupText'); ?></div>
	</aside>
	
	<section data-role="form">
		
		<button type="submit" data-loading="<?php echo $Module->getLanguage('signup/loading'); ?>"><?php echo $Module->getLanguage('signup/next'); ?></button>
	</section>
	<?php } ?>
	
	<?php if ($view == 'insert') { ?>
	<aside>
		<h4><?php echo $Module->getLanguage('signup/default'); ?></h4>
		
		<div><?php echo $Module->Module->getConfig('signupText'); ?></div>
	</aside>
	
	<section data-role="form">
		<div data-role="inputGroup" class="formBox">
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('info/email'); ?></div></label>
				
				<div class="inputBlock">
					<input type="email" name="email" class="inputControl" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/email/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/email/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/email/success'); ?>"></div>
				</div>
			</div>
			
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('info/password'); ?></div></label>
				
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
			
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('info/name'); ?></div></label>
				
				<div class="inputBlock">
					<input type="text" name="name" class="inputControl" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/name/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/name/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/name/success'); ?>"></div>
				</div>
			</div>
			
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('info/nickname'); ?></div></label>
				
				<div class="inputBlock">
					<input type="text" name="nickname" class="inputControl">
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/nickname/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/nickname/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/nickname/success'); ?>"></div>
				</div>
			</div>
		</div>
		
		<button type="submit" data-loading="<?php echo $Module->getLanguage('signup/loading'); ?>"><?php echo $Module->getLanguage('signup/next'); ?></button>
	</section>
	<?php } ?>
	
	
	<?php if ($view == 'verify') { ?>
	<aside>
		<h4><?php echo $Module->getLanguage('signup/email_verify'); ?></h4>
		
		<div><?php echo $Module->Module->getConfig('signupText'); ?></div>
	</aside>
	
	<section data-role="form">
		<div class="formBox">
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('info/email'); ?></div></label>
				
				<div class="inputBlock">
					<input type="email" name="email" class="inputControl" value="<?php echo $registerInfo->email; ?>" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/email/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/email/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/email/success'); ?>"></div>
				</div>
			</div>
			
			<div class="inputBox">
				<label><div><?php echo $Module->getLanguage('verifyEmail/email_verify_code'); ?></div></label>
				
				<div class="inputBlock">
					<input type="text" name="email_verify_code" class="inputControl" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/email_verify_code/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/email_verify_code/error'); ?>"></div>
				</div>
			</div>
			
			<div class="inputBox">
				<div class="inputBlock clickBlock">
					<div class="text"><?php echo $Module->getLanguage('button/email_verify_resend'); ?></div>
					<i class="fa fa-angle-right"></i>
				</div>
			</div>
		</div>
		
		<button type="submit" data-loading="<?php echo $Module->getLanguage('signup/loading'); ?>"><?php echo $Module->getLanguage('signup/next'); ?></button>
	</section>
	<?php } ?>
	
	<?php if ($view == 'complete') { ?>
	<aside>
		<h4><?php echo $Module->getLanguage('signup/step/complete'); ?></h4>
		
		<div><?php echo $Module->Module->getConfig('signupText'); ?></div>
	</aside>
	
	<section data-role="form">
		<div class="formBox">
			<div style="padding:20px; line-height:1.8; font-size:15px;">
				<i class="fa fa-info-circle"></i> 회원가입이 완료되었습니다.<br><br>
				이제 홈페이지로 이동하여, 회원로그인을 하신 후 모든 서비스를 이용하실 수 있습니다.<br>
				회원님의 가입을 진심으로 환영합니다.
			</div>
		</div>
		
		<button type="submit" onclick="location.href='<?php echo $IM->getHost(); ?>';">홈페이지로 이동</button>
	</section>
	<?php } ?>
</section>