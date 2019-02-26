<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 로그인 위젯 기본템플릿 - 로그인 상태화면
 *
 * @file /modules/member/widgets/login/templets/default/logged.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 2. 25.
 */
if (defined('__IM__') == false) exit;

$IM->loadWebFont('Roboto');
$IM->loadWebFont('FontAwesome');
?>
<div data-role="user">
	<i class="photo" style="background-image:url(<?php echo $member->photo; ?>);">
		<button type="button" onclick="Member.modifyPopup();"><i class="fa fa-cog"></i></button>
	</i>
	
	<div>
		<div class="nickname">
			<button type="button" onclick="Member.logout(this);"><?php echo $Widget->getText('button/logout'); ?></button><?php echo $member->nickname; ?>
		</div>
		
		<div class="level">
			<label>LV.<b><?php echo $member->level->level; ?></b></label>
			<div class="graph">
				<div class="bar">
					<div style="width:<?php echo $member->level->exp / $member->level->next * 100; ?>%;"></div>
				</div>
				
				<div class="detail">
					<div><?php echo number_format($member->level->exp); ?>/<?php echo number_format($member->level->next); ?></div>
				</div>
			</div>
		</div>
	</div>
</div>

<div data-role="point">
	<?php echo $point == null ? '<button type="button" onclick="Member.pointPopup();">' : '<a href="'.$point.'">'; ?>내역보기<?php echo $point == null ? '</button>' : '</a>'; ?><i class="xi xi-wallet"></i>포인트 : <?php echo number_format($member->point); ?>
</div>

<ul data-role="button">
	<li>
		<?php echo $message == null ? '<button type="button" onclick="Member.messagePopup();">' : '<a href="'.$message.'">'; ?>
		<i class="xi xi-postbox"></i><span>메세지</span></button>
		<?php echo $message == null ? '</button>' : '</a>'; ?>
		<label>1</label>
	</li>
	<li>
		<button type="button" data-action="push"><i class="xi xi-bell"></i><span>알림</span></button>
		<label>1,000</label>
		
		<!--
		<?php if ($push == null) { ?><button type="button" onclick="Push.listPopup();"><?php } else { ?><a href="<?php echo $push; ?>"><?php } ?>
		<i class="xi xi-bell"></i><span>알림</span></button>
		<?php if ($push == null) { ?></button><?php } else { ?></a><?php } ?>
		<label>1,000</label>
		-->
	</li>
	<li>
		<?php echo $modify == null ? '<button type="button" onclick="Member.modifyPopup();">' : '<a href="'.$modify.'">'; ?>
		<i class="xi xi-user-info"></i><span>정보수정</span></button>
		<?php echo $modify == null ? '</button>' : '</a>'; ?>
	</li>
	<li>
		<?php echo $activity == null ? '<button type="button" onclick="Member.activityPopup();">' : '<a href="'.$activity.'">'; ?>
		<i class="xi xi-paper"></i><span>활동기록</span></button>
		<?php echo $activity == null ? '</button>' : '</a>'; ?>
	</li>
</ul>