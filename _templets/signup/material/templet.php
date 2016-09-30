<div class="ModuleMemberSignUpMaterial">
	<ul class="stepbar">
		<li<?php echo $view == 'agreement' ? ' class="current"' : ''; ?>>
			<i class="fa fa-square-o"></i>
			<span><?php echo $Module->getLanguage('signup/step/agreement'); ?></span>
		</li>
		<li<?php echo $view == 'insert' ? ' class="current"' : ''; ?>>
			<i class="fa fa-pencil-square-o"></i>
			<span><?php echo $Module->getLanguage('signup/step/insert'); ?></span>
		</li>
		<li<?php echo $view == 'verify' ? ' class="current"' : ''; ?>>
			<i class="fa fa-share-square-o"></i>
			<span><?php echo $Module->getLanguage('signup/step/verify'); ?></span>
		</li>
		<li<?php echo $view == 'complete' ? ' class="current"' : ''; ?>>
			<i class="fa fa-check-square-o"></i>
			<span><?php echo $Module->getLanguage('signup/step/complete'); ?></span>
		</li>
	</ul>

	<div class="box">
		<?php if ($view == 'agreement') { ?>
		<h4><?php echo $Module->getLanguage('signup/terms'); ?></h4>
		
		<div class="terms">
		</div>
		<?php } elseif ($view == 'insert') { ?>
		<h4><?php echo $Module->getLanguage('signup/default'); ?></h4>
		
		<table class="formTable">
		<tr>
			<td class="label"><?php echo $Module->getLanguage('info/email'); ?></td>
			<td class="input">
				<div class="inputBlock">
					<input type="text" name="email" class="inputControl" style="width:250px;" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/email/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/email/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/email/success'); ?>"></div>
				</div>
			</td>
		</tr>
		<tr class="split">
			<td colspan="2"></td>
		</tr>
		<tr>
			<td class="label"><?php echo $Module->getLanguage('info/password'); ?></td>
			<td class="input">
				<div class="inputBlock">
					<input type="password" name="password" class="inputControl" style="width:250px;" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/password/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/password/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/password/success'); ?>"></div>
				</div>
			</td>
		</tr>
		<tr>
			<td class="label"><?php echo $Module->getLanguage('info/password_confirm'); ?></td>
			<td class="input">
				<div class="inputBlock">
					<input type="password" name="password_confirm" class="inputControl" style="width:250px;" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/password_confirm/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/password_confirm/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/password_confirm/success'); ?>"></div>
				</div>
			</td>
		</tr>
		<tr class="split">
			<td colspan="2"></td>
		</tr>
		<tr>
			<td class="label"><?php echo $Module->getLanguage('info/name'); ?></td>
			<td class="input">
				<div class="inputBlock">
					<input type="text" name="name" class="inputControl" style="width:250px;" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/name/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/name/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/name/success'); ?>"></div>
				</div>
			</td>
		</tr>
		<tr class="split">
			<td colspan="2"></td>
		</tr>
		<tr>
			<td class="label"><?php echo $Module->getLanguage('info/nickname'); ?></td>
			<td class="input">
				<div class="inputBlock">
					<input type="text" name="nickname" class="inputControl" style="width:250px;">
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/nickname/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/nickname/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/nickname/success'); ?>"></div>
				</div>
			</td>
		</tr>
		<tr class="splitBottom">
			<td colspan="2"><div></div></td>
		</tr>
		</table>
		
		<?php } elseif ($view == 'verify') { ?>
		<h4><?php echo $Module->getLanguage('signup/email_verify'); ?></h4>
		
		<table class="formTable">
		<tr>
			<td class="info">
				<i class="fa fa-info-circle"></i> <?php echo $Module->getLanguage('signup/help/email_verify'); ?>
			</td>
		</tr>
		</table>
		
		<table class="formTable">
		<tr>
			<td class="label"><?php echo $Module->getLanguage('info/name'); ?></td>
			<td class="input">
				<div class="inputBlock">
					<span class="inputText"><?php echo $registerInfo->name; ?></span>
				</div>
			</td>
		</tr>
		<tr class="split">
			<td colspan="2"></td>
		</tr>
		<tr>
			<td class="label"><?php echo $Module->getLanguage('info/nickname'); ?></td>
			<td class="input">
				<div class="inputBlock">
					<span class="inputText"><?php echo $registerInfo->nickname; ?></span>
				</div>
			</td>
		</tr>
		<tr class="split">
			<td colspan="2"></td>
		</tr>
		<tr>
			<td class="label"><?php echo $Module->getLanguage('info/email'); ?></td>
			<td class="input">
				<div class="inputBlock">
					<input type="text" name="email" class="inputControl" style="width:250px;" value="<?php echo $registerInfo->email; ?>" required>
					<button type="button" class="btn btnWhite" onclick="Member.signup.resendVerifyEmail(this);"><i class="fa fa-send-o"></i> <?php echo $Module->getLanguage('button/email_verify_resend'); ?></button>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/email/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/email/error'); ?>" data-success="<?php echo $Module->getLanguage('signup/help/email/success'); ?>"></div>
				</div>
			</td>
		</tr>
		<tr class="split">
			<td colspan="2"></td>
		</tr>
		<tr>
			<td class="label"><?php echo $Module->getLanguage('info/password'); ?></td>
			<td class="input">
				<div class="inputBlock">
					<input type="text" name="email_verify_code" class="inputControl" style="width:250px;" required>
					<div class="helpBlock" data-default="<?php echo $Module->getLanguage('signup/help/email_verify_code/default'); ?>" data-error="<?php echo $Module->getLanguage('signup/help/email_verify_code/error'); ?>"></div>
				</div>
			</td>
		</tr>
		<tr class="splitBottom">
			<td colspan="2"><div></div></td>
		</tr>
		</table>
		
		<?php } elseif ($view == 'complete') { ?>
		<h4><?php echo $Module->getLanguage('signup/step/complete'); ?></h4>
		
		<table class="formTable">
		<tr>
			<td class="info">
				<i class="fa fa-info-circle"></i> 회원가입이 완료되었습니다.<br><br>
				이제 홈페이지로 이동하여, 회원로그인을 하신 후 알쯔닷컴을 이용하실 수 있습니다.<br>
				회원님의 가입을 진심으로 환영합니다.
			</td>
		</tr>
		<tr class="splitBottom">
			<td><div></div></td>
		</tr>
		</table>
		
		<?php } ?>
		
		<?php if ($view == 'complete') { ?>
		<div class="buttons">
			<a href="/" class="btn btnRed" >홈페이지 메인으로 이동</a>
		</div>
		<?php } else { ?>
		<div class="buttons">
			<button type="submit" class="btn btnRed" data-loading="<?php echo $Module->getLanguage('signup/loading'); ?>"><?php echo $Module->getLanguage('signup/next'); ?></button>
			<button type="button" class="btn btnWhite" onclick="Member.signup.cancel();"><?php echo $Module->getLanguage('signup/cancel'); ?></button>
		</div>
		<?php } ?>
	</div>
</div>