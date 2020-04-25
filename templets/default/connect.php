<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 소셜계정과 회원계정을 연결하기 위한 페이지 템플릿
 *
 * @file /modules/member/templets/default/connect.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2017. 11. 29.
 */
if (defined('__IM__') == false) exit;
$IM->loadWebFont('XEIcon');
?>
<section class="box">
	<div>
		<div>
			<?php if ($type == 'login') { ?>
			<ul class="connect">
				<li class="icon"><i class="site" data-social="<?php echo $logged->site->site; ?>"></i><i class="photo" style="background-image:url(<?php echo $logged->user->photo; ?>);"></i></li>
				<li class="link"><i class="xi xi-link"></i></li>
				<li class="icon"><i class="site" style="background-image:url(<?php echo $IM->getSiteEmblem(); ?>);"></i><i class="photo" style="background-image:url(<?php echo $member->photo; ?>);"></i></li>
			</ul>
			
			<h1>기존에 사용하던 계정과 소셜계정을 연동합니다.</h1>
			
			<div data-role="input">
				<input type="email" name="email" value="<?php echo $member->email; ?>" readonly="readonly">
			</div>
			
			<div data-role="input">
				<input type="password" name="password" placeholder="<?php echo $this->getText('text/password'); ?>">
			</div>
			
			<button type="submit"><?php echo $this->getText('button/login'); ?></button>
			<?php } else { ?>
			<h1>로그인할 계정을 선택하여 주십시오.</h1>
			
			<ul class="select">
				<?php foreach ($members as $member) { ?>
				<li>
					<a href="<?php echo $this->IM->getProcessUrl('member',$logged->site->site); ?>?midx=<?php echo $member->idx; ?>">
						<i class="photo" style="background-image:url(<?php echo $member->photo; ?>);"></i>
						<b><?php echo $member->nickname; ?></b>
						<small><?php echo $member->email; ?></small>
					</a>
				</li>
				<?php } ?>
			</ul>
			<?php } ?>
		</div>
	</div>
</section>