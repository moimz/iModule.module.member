<div data-role="tool">
	<h4>총 포인트 : <?php echo number_format($member->point); ?></h4>
	
	<div data-role="input">
		<select name="type">
			<option value="all">전체</option>
			<option value="increase"<?php echo $view == 'increase' ? ' selected="selected"' : ''; ?>>적립</option>
			<option value="decrease"<?php echo $view == 'decrease' ? ' selected="selected"' : ''; ?>>사용</option>
		</select>
	</div>
</div>
<ul data-role="table" class="black inner point">
	<li class="thead">
		<span class="loopnum">번호</span>
		<span class="title">내역</span>
		<span class="count">변동</span>
		<span class="accumulation">누적포인트</span>
		<span class="date">날짜 <i class="fa fa-caret-down"></i></span>
	</li>
	<?php foreach ($lists as $item) { ?>
	<li class="tbody">
		<span class="loopnum"><?php echo $item->loopnum; ?></span>
		<span class="title"><?php echo $item->content; ?></span>
		<span class="count"><?php echo $item->point > 0 ? '<span class="increase">+'.number_format($item->point).'</span>' : '<span class="decrease">'.number_format($item->point).'</span>'; ?></span>
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

<?php /*
<section class="row">
	<aside>
		
	</aside>
	
	<section data-role="grid">
		<div class="gridBox">
			<table>
				<thead>
					<tr><?php /*
						<td style="width:60%;"><?php echo $Module->getLanguage('point/content'); ?></td>
						<td style="width:80px;"><?php echo $Module->getLanguage('point/point'); ?></td>
						<td style="width:100px;"><?php echo $Module->getLanguage('point/reg_date'); ?></td>
						 ?>
					</tr>
				</thead>
				<tbody>
					<?php for ($i=0, $loop=count($lists);$i<$loop;$i++) { ?>
					<tr>
						<td><?php echo $lists[$i]->content; ?></td>
						<td class="number fontBold <?php echo $lists[$i]->point > 0 ? 'fontBlue' : 'fontRed'; ?> right"><?php echo $lists[$i]->point > 0 ? '+' : ''; ?><?php echo number_format($lists[$i]->point); ?></td>
						<td class="number"><?php echo GetTime('Y.m.d H:i',$lists[$i]->reg_date); ?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		
		<div class="center"><?php echo $pagination->html; ?></div>
	</section>
</section>
*/ ?>