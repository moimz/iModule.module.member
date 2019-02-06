<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 모든 회원라벨을 가져온다.
 *
 * @file /modules/member/process/@getLabels.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 4. 9.
 */
if (defined('__IM__') == false) exit;

$type = Request('type');
$labels = $this->db()->select($this->table->label)->orderBy('sort','asc')->get();
$lists = array();

if ($type == 'title') {
	$lists[] = array(
		'idx'=>'0',
		'title'=>$this->Module->getConfig('default_label_title') == 'LANGUAGE_SETTING' ? $this->getText('text/default_label_title') : $this->Module->getConfig('default_label_title'),
		'member'=>$this->db()->select($this->table->member)->count(),
		'allow_signup'=>$this->Module->getConfig('allow_signup'),
		'approve_signup'=>$this->Module->getConfig('approve_signup'),
		'is_change'=>true,
		'is_unique'=>false,
		'sort'=>-1
	);
} elseif ($type) {
	$lists[] = array('idx'=>'0','title'=>$this->getText('text/'.$type),'sort'=>-1);
}

for ($i=0, $loop=count($labels);$i<$loop;$i++) {
	$labels[$i]->allow_signup = $labels[$i]->allow_signup == 'TRUE';
	$labels[$i]->approve_signup = $labels[$i]->approve_signup == 'TRUE';
	$labels[$i]->is_change = $labels[$i]->is_change == 'TRUE';
	$labels[$i]->is_unique = $labels[$i]->is_unique == 'TRUE';
	
	if ($labels[$i]->sort != $i) {
		$this->db()->update($this->table->label,array('sort'=>$i))->where('idx',$lists[$i]->idx)->execute();
	}
	
	$lists[] = $labels[$i];
}

$results->success = true;
$results->lists = $lists;
$results->total = count($lists);
?>