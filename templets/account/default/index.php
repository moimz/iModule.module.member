<?php $IM->addWebFont('OpenSans'); ?>
<div class="ModuleMemberAccountDefault">
	<header>
		<div class="top">
			<h1<?php echo $IM->getSite()->logo !== null ? ' style="background-image:url('.$IM->getSiteLogo().');"' : ''; ?>><a href="<?php echo __IM_DIR__.'/'.$IM->language.'/'; ?>"><?php echo $IM->getSite()->title; ?></a></h1>
		</div>
		<div class="accountLayout accountTitle">
			<aside>
				<div class="title">
					<?php echo $values->groupTitle == '' ? $values->title : $values->groupTitle; ?>
				</div>
			</aside>
			
			<section>
				<div class="title">
					<i class="fa fa-bars" onclick="Member.account.slideMenu();"></i>
					<h2><?php echo $values->pageTitle; ?></h2>
				</div>
			</section>
		</div>
	</header>

	<div id="ModuleMemberAccountContext" class="accountLayout">
		<aside class="menu">
			<ul>
				<?php
				for ($i=0, $loop=count($values->pages);$i<$loop;$i++) {
					if (isset($values->pages[$i]->page) == true) {
						echo '<li class="group"><a href="'.$IM->getUrl(null,$values->pages[$i]->page,false).'"'.($values->pages[$i]->page == $IM->page ? ' class="selected"' : '').'>'.$values->pages[$i]->title.'</a></li>';
					} else {
						echo '<li class="group"><span>'.$values->pages[$i]->title.'</span></li>';
						foreach ($values->pages[$i]->pages as $page=>$title) {
							echo '<li class="page"><a href="'.$IM->getUrl(null,$page,false).'"'.($page == $IM->page ? ' class="selected"' : '').'>'.$title.'</a></li>';
						}
					}
				}
				?>
			</ul>
		</aside>
		
		<section>
			<div class="panelWrapper">
				<?php echo $pageContext; ?>
			</div>
		</section>
	</div>
</div>