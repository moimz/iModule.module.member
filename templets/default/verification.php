<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 이메일인증 템플릿
 *
 * @file /modules/member/templets/default/verification.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 1. 25.
 */
if (defined('__IM__') == false) exit;
?>
<section class="box">
	<div>
		<div>
			<h4>이메일 인증하기</h4>
			
			<?php if ($mode == 'token') { ?>
				<div data-role="text">
					<?php echo $member->nickname; ?>님의 이메일주소 <?php echo $member->email; ?> 인증을 위하여 아래의 이메일 인증하기 버튼을 클릭하여 주십시오.
				</div>
				
				<div data-role="button" class="block">
					<button type="submit">이메일주소 인증하기</button>
				</div>
			<?php } elseif ($mode == 'code') { ?>
			
				<?php if ($member->verified !== 'FALSE') { ?>
				<div data-role="text">
					<?php echo $member->nickname; ?>님의 이메일주소 <?php echo $member->email; ?> 은 이미 인증되었거나, 인증이 더 이상 필요하지 않습니다.
				</div>
				
				<div data-role="button">
					<button type="button" data-action="main" class="submit"><?php echo $me->getText('button/back_to_main'); ?></button>
				</div>
				<?php } else { ?>
				<div data-role="text">
					이메일 인증코드가 <?php echo $member->email; ?> 주소로 발송되었습니다.<br>
					이메일에 포함된 인증코드를 아래에 입력하여 주십시오.
				</div>
				
				<div data-role="input">
					<input type="text" name="code" placeholder="인증코드">
				</div>
				
				<div data-role="button" class="block">
					<button type="submit">이메일주소 인증하기</button>
				</div>
				
				
				<div data-role="text" class="line">
					이메일을 받지 못한 경우 아래의 인증메일 재발송 버튼을 클릭하여 재발송받을 수 있습니다.<br>
					또는 이메일주소 변경이 필요할 경우, 이메일주소변경 버튼을 클릭하여 수정하실 수 있습니다.
				</div>
				
				<div data-role="button" class="half">
					<button type="button" data-action="resend" class="submit">인증메일주소 재발송</button>
					<button type="button" data-action="update">이메일주소변경</button>
				</div>
				
				<div data-role="text" class="line">
					이메일인증을 나중에 하고자 할 경우 아래의 로그아웃버튼을 클릭하시면 사이트 메인으로 돌아갈 수 있습니다.
				</div>
				
				<div data-role="button" class="block">
					<button type="button" data-action="logout" class="danger">로그아웃</button>
				</div>
				<?php } ?>
			<?php } else { ?>
				<div data-role="text">
					이메일인증을 진행할 수 없습니다.<br>
					먼저 로그인을 하시거나, 인증확인 이메일에 포함된 링크를 클릭하여 주십시오.
				</div>
				
				<div data-role="button">
					<button type="button" data-action="login" class="submit"><?php echo $me->getText('button/login'); ?></button>
					<button type="button" data-action="main"><?php echo $me->getText('button/back_to_main'); ?></button>
				</div>
			<?php } ?>
		</div>
	</div>
</section>