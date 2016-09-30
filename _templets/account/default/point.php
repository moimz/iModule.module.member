<section class="row">
	<aside>
		<h4><?php echo $Module->getLanguage('point/title'); ?></h4>
		
		<div><?php echo $Module->getLanguage('point/description'); ?></div>
	</aside>
	
	<section data-role="grid">
		<div class="gridBox">
			<table>
				<thead>
					<tr>
						<td style="width:60%;"><?php echo $Module->getLanguage('point/content'); ?></td>
						<td style="width:80px;"><?php echo $Module->getLanguage('point/point'); ?></td>
						<td style="width:100px;"><?php echo $Module->getLanguage('point/reg_date'); ?></td>
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