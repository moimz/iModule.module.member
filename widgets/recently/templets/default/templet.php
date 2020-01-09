<div class="WidgetMemberRecentlyDefault">
	<div class="listTitle">
		<?php echo $titleIcon ? $titleIcon : ''; ?> <b><?php echo $title; ?></b>
		<div class="bar"><span></span></div>
	</div>
	
	<div class="photoMap">
		<ul>
			<?php for ($i=0, $loop=count($lists);$i<$loop;$i++) { ?>
			<li>
				<div class="frame" data-member-idx="<?php echo $lists[$i]->idx; ?>">
					<div class="photo" style="background-image:url(<?php echo $lists[$i]->photo; ?>);"></div>
					<div class="nickname"><?php echo $lists[$i]->nickname; ?></div>
				</div>
			</li>
			<?php } ?>
		</ul>
	</div>
</div>