<div class="ModuleMemberSocialBox">
	<h4>소셜계정으로 로그인할 계정을 선택하세요.</h4>
	
	<dlv class="loginBox">
		<div class="photo"><img src="<?php echo $photo; ?>"></div>
		<ul>
			<?php foreach ($accounts as $account) { ?>
			<li onclick="<?php echo $Module->getForceLoginUrl($account->idx,$redirectUrl); ?>">
				<div class="photo"><img src="<?php echo $account->photo; ?>"></div>
				<div class="info">
					<div class="name"><?php echo $account->name; ?></div>
					<div class="email"><?php echo $account->email; ?></div>
				</div>
			</li>
			<?php } ?>
		</ul>
	</dlv>
</div>