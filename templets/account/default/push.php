<section class="row">
	<aside>
		<h4><?php echo $Module->getLanguage('list/title'); ?></h4>
		
		<div><?php echo $Module->getLanguage('list/description'); ?></div>
	</aside>
	
	<section data-role="grid">
		<div class="gridBox">
			<table class="push">
				<col width="55"><col width="100%">
				<thead>
					<tr>
						<td colspan="2">
							<?php echo $Module->getLanguage('list/content'); ?>
							<button onclick="Push.readAll(event);"><?php echo $Module->getLanguage('button/read_all'); ?></button>
						</td>
					</tr>
				</thead>
				<tbody>
					<?php for ($i=0, $loop=count($lists);$i<$loop;$i++) { ?>
					<tr data-push="true" data-module="<?php echo $lists[$i]->module; ?>" data-code="<?php echo $lists[$i]->code; ?>" data-fromcode="<?php echo $lists[$i]->fromcode; ?>" class="<?php echo $lists[$i]->is_read == 'TRUE' ? 'readed' : 'unread'; ?><?php echo isset($lists[$i]->push) == false || $lists[$i]->push->link == null ? ' notarget' : ''; ?>"<?php echo isset($lists[$i]->push) == true && $lists[$i]->push->link != null ? ' data-link="'.$lists[$i]->push->link.'"' : ''; ?> onclick="Push.read(this);">
						<?php if (isset($lists[$i]->push) == true) { ?>
						<td class="image">
							<img src="<?php echo $lists[$i]->push->image; ?>">
						</td>
						<td class="content">
							<?php echo $lists[$i]->push->content; ?>
							<div class="reg_date"><?php echo GetTime('Y-m-d H:i:s',$lists[$i]->reg_date); ?></div>
						</td>
						<?php } else { ?>
						<td colspan="2"><?php echo $lists[$i]->content; ?></td>
						<?php } ?>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		
		<div class="center"><?php echo $pagination->html; ?></div>
	</section>
</section>