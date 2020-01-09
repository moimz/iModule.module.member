<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원가입 필드목록을 가져온다.
 *
 * @file /modules/member/process/@getSignUpFields.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2019. 4. 9.
 */
if (defined('__IM__') == false) exit;

$is_default = Request('is_default') == 'true';
$is_extra = Request('is_extra') == 'true';
$label = Request('label');
if ($is_default == true) $labels = array(0,$label);
else $labels = array($label);
$lists = $this->db()->select($this->table->signup)->where('label',$labels,'IN')->orderBy('sort','asc')->get();
$agreements = $this->db()->select($this->table->signup)->where('label',$label)->where('name',array('agreement','privacy'),'IN')->count();

for ($i=0, $loop=count($lists);$i<$loop;$i++) {
	if ($is_extra === true && $lists[$i]->type == 'etc') $lists[$i]->name = 'extra.'.$lists[$i]->name;
	
	$lists[$i]->title = $lists[$i]->title == 'LANGUAGE_SETTING' ? $this->getText('text/'.$lists[$i]->type) : $lists[$i]->title;
	$lists[$i]->help = $lists[$i]->help == 'LANGUAGE_SETTING' ? $this->getText('signup/'.$lists[$i]->type.'_help') : $lists[$i]->help;
	$lists[$i]->is_required = $lists[$i]->is_required == 'TRUE';
	
	if ($lists[$i]->name == 'agreement' && $lists[$i]->sort != -2) {
		if ($is_default == false) {
			$this->db()->update($this->table->signup,array('sort'=>-2))->where('label',$lists[$i]->label)->where('name',$lists[$i]->name)->execute();
		}
		$lists[$i]->sort = -2;
	}
	
	if ($lists[$i]->name == 'privacy' && $lists[$i]->sort != -1) {
		if ($is_default == false) {
			$this->db()->update($this->table->signup,array('sort'=>-1))->where('label',$lists[$i]->label)->where('name',$lists[$i]->name)->execute();
		}
		$lists[$i]->sort = -1;
	}
	
	if (in_array($lists[$i]->name,array('agreement','privacy')) == false && ($lists[$i]->sort < 0 || $lists[$i]->sort != $i - $agreements)) {
		if ($is_default == false) {
			$this->db()->update($this->table->signup,array('sort'=>max(0,$i - $agreements)))->where('label',$lists[$i]->label)->where('name',$lists[$i]->name)->execute();
		}
		$lists[$i]->sort = max(0,$i - $agreements);
	}
}

$results->success = true;
$results->lists = $lists;
$results->total = count($lists);
?>