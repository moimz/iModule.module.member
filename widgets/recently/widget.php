<?php
if (defined('__IM__') == false) exit;

if ($Widget->getTempletDir() == null) $IM->printError('NOT_SELECTED_TEMPLET');
if (file_exists($Widget->getTempletPath().'/styles/style.css') == true) $IM->addSiteHeader('style',$Widget->getTempletDir().'/styles/style.css');
if (file_exists($Widget->getTempletPath().'/scripts/script.js') == true) $IM->addSiteHeader('script',$Widget->getTempletDir().'/scripts/script.js');

$gidx = $Widget->getValue('gidx');
$gidx = $gidx !== null && is_array($gidx) == false ? array($gidx) : $gidx;
$title = $Widget->getValue('title') ? $Widget->getValue('title') : $Widget->getLanguage('title');
$titleIcon = $Widget->getValue('titleIcon') ? $Widget->getValue('titleIcon') : '<i class="fa fa-male"></i>';
$photoOnly = $Widget->getValue('photoOnly') === true;
$count = is_numeric($Widget->getValue('count')) == true ? $Widget->getValue('count') : 10;
$cache = $Widget->getValue('cache') ? $Widget->getValue('cache') : 3600;

if ($Widget->cacheCheck() < time() - $cache) {
	if ($gidx) {
		$members = $Module->db()->select($Module->getTable('member').' m','m.idx')->join($Module->getTable('member_label').' l','m.idx=l.idx','LEFT')->where('l.label',$gidx,'IN')->orderBy('m.idx','desc');
	} else {
		$members = $Module->db()->select($Module->getTable('member'),'idx')->orderBy('idx','desc');
	}
	if ($photoOnly === true) $members = $members->limit($count * 10)->get();
	else $members = $members->limit($count)->get();
	
	$lists = array();
	for ($i=0, $loop=count($members);$i<$loop;$i++) {
		if ($photoOnly == false || file_exists($this->IM->getAttachmentPath().'/member/'.$members[$i]->idx.'.jpg') == true) {
			$lists[] = $Module->getMember($members[$i]->idx);
		}
		
		if (count($lists) >= $count) break;
	}
	
	$data = json_encode($lists,JSON_UNESCAPED_UNICODE);
	$Widget->cacheStore($data);
} else {
	$data = $Widget->cache();
}

$lists = json_decode($data);

INCLUDE $Widget->getTempletFile();
?>