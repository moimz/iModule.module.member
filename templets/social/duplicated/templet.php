<div class="ModuleMemberSocialBox">
	<h4>소셜계정을 연동하려면 로그인하세요.</h4>
	
	<dlv class="loginBox">
		<div class="photo"><img src="<?php echo $member->photo; ?>"></div>
		
		<div class="inputBlock">
			<input type="text" name="email" value="<?php echo $member->email; ?>" class="inputControl">
			<div class="helpBlock"></div>
		</div>
		
		<div class="inputBlock">
			<input type="password" name="password" class="inputControl" placeholder="패스워드" class="inputControl">
			<div class="helpBlock"></div>
		</div>
		
		<button type="submit" class="btn btnRed"><i class="fa fa-lock"></i> 회원로그인</button>
		
		<div class="description">
			로그인하려고 하는 소셜계정의 이메일주소가 이미 사이트에서 사용중입니다.<br>
			해당 이메일로 가입하신적이 있다면, 해당 계정으로 로그인하여 주십시오. 이후 소셜계정과 연결되어 해당 소셜계정으로 로그인하실 수 있습니다.
		</div>
	</dlv>
</div>