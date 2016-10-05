<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 회원가입폼 양식을 가져온다.
 *
 * @file /modules/member/process/@getSignUpForms.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0.160923
 *
 * @post int $label 회원라벨
 * @return object $results
 */
if (defined('__IM__') == false) exit;

$label = Request('label');
$lists = $this->db()->select($this->table->signup)->where('label',$label)->orderBy('sort','asc')->get();
$agreements = $this->db()->select($this->table->signup)->where('label',$label)->where('name',array('agreement','privacy'),'IN')->count();

for ($i=0, $loop=count($lists);$i<$loop;$i++) {
	$lists[$i]->title = $lists[$i]->title == 'LANGUAGE_SETTING' ? $this->getText('text/'.$lists[$i]->type) : $lists[$i]->title;
	$lists[$i]->help = $lists[$i]->help == 'LANGUAGE_SETTING' ? $this->getText('signup/form/'.$lists[$i]->type.'_help') : $lists[$i]->help;
	$lists[$i]->is_required = $lists[$i]->is_required == 'TRUE';
	
	if ($lists[$i]->name == 'agreement' && $lists[$i]->sort != -2) {
		$this->db()->update($this->table->signup,array('sort'=>-2))->where('label',$lists[$i]->label)->where('name',$lists[$i]->name)->execute();
		$lists[$i]->sort = -2;
	}
	
	if ($lists[$i]->name == 'privacy' && $lists[$i]->sort != -1) {
		$this->db()->update($this->table->signup,array('sort'=>-1))->where('label',$lists[$i]->label)->where('name',$lists[$i]->name)->execute();
		$lists[$i]->sort = -1;
	}
	
	if (in_array($lists[$i]->name,array('agreement','privacy')) == false && ($lists[$i]->sort < 0 || $lists[$i]->sort != $i - $agreements)) {
		$this->db()->update($this->table->signup,array('sort'=>max(0,$i - $agreements)))->where('label',$lists[$i]->label)->where('name',$lists[$i]->name)->execute();
		$lists[$i]->sort = max(0,$i - $agreements);
	}
}

$results->success = true;
$results->lists = $lists;
$results->count = count($lists);
?>