<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 포인트 내역 템플릿
 * 
 * @file /modules/moimz/templets/default/point.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 2. 19.
 */
if (defined('__IM__') == false) exit;
$IM->loadWebFont('Roboto');
?>
<div data-role="toolbar">
	<h4>총 포인트 : <?php echo number_format($member->point); ?></h4>
	
	<div data-role="input">
		<select name="type">
			<option value="all">전체</option>
			<option value="increase"<?php echo $view == 'increase' ? ' selected="selected"' : ''; ?>>적립</option>
			<option value="decrease"<?php echo $view == 'decrease' ? ' selected="selected"' : ''; ?>>사용</option>
		</select>
	</div>
</div>

<ul data-role="table" class="black inner">
	<li class="thead">
		<span class="loopnum">번호</span>
		<span class="title">내역</span>
		<span class="change">변동</span>
		<span class="accumulation">누적포인트</span>
		<span class="date">날짜 <i class="fa fa-caret-down"></i></span>
	</li>
	<?php foreach ($lists as $item) { ?>
	<li class="tbody">
		<span class="loopnum"><?php echo $item->loopnum; ?></span>
		<span class="title"><?php echo $item->content; ?></span>
		<span class="change"><?php echo $item->point > 0 ? '<span class="increase">+'.number_format($item->point).'</span>' : '<span class="decrease">'.number_format($item->point).'</span>'; ?></span>
		<span class="accumulation"><?php echo $item->accumulation > 0 ? '<span class="increase">+'.number_format($item->accumulation).'</span>' : '<span class="decrease">'.number_format($item->accumulation).'</span>'; ?></span>
		<span class="date"><?php echo GetTime('Y-m-d(D) H:i',$item->reg_date); ?></span>
	</li>
	<?php } ?>
	<?php if (count($lists) == 0) { ?>
	<li class="empty">
		내역이 없습니다.
	</li>
	<?php } ?>
</ul>

<div class="pagination"><?php echo $pagination; ?></div>