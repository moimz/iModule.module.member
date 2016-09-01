<div class="modal" data-role="modal">
	<header>
		<i class="fa fa-times" onclick="iModule.modal.close();"></i>
		<?php echo $title; ?>
	</header>
	
	<div class="content">
		<?php echo $content; ?>
	</div>
	
	<div class="button">
		<div><button class="cancel" onclick="iModule.modal.close();"><?php echo $Module->getLanguage('button/cancel'); ?></button></div>
		<?php echo isset($actionButton) == true ? '<div>'.$actionButton.'</div>' : ''; ?>
		<div><button type="submit" class="submit" data-loading="<?php echo $Module->getLanguage('loading'); ?>"><?php echo $Module->getLanguage('button/confirm'); ?></button></div>
	</div>
</div>