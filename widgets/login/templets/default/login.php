<div data-role="input">
	<input type="email" name="email" placeholder="<?php echo $me->getText('text/email'); ?>">
</div>

<div data-role="input">
	<input type="password" name="password" placeholder="<?php echo $me->getText('text/password'); ?>">
</div>

<div data-role="button">
	<a href="#"><i class="fa fa-facebook"></i></a>
	<a href="#"><i class="fa fa-facebook"></i></a>
	<button type="submit"><?php echo $me->getText('button/login'); ?></button>
</div>

<div data-role="input">
	<label><input type="checkbox" name="auto" value="TRUE"><?php echo $me->getText('text/auto_login'); ?></label>
</div>

<div class="bottom">
	<div class="link">
		<a href="<?php echo $signupUrl; ?>"><?php echo $Widget->getText('text/signup'); ?></a>
		<span>|</span>
		<a href="<?php echo $findUrl; ?>"><?php echo $Widget->getText('text/help'); ?></a>
	</div>
</div>