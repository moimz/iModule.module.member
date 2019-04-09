<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원가입 필드를 삭제한다.
 *
 * @file /modules/member/process/@deleteSignUpField.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2017. 11. 30.
 */
if (defined('__IM__') == false) exit;

$labels = array();
$fields = json_decode(Request('fields'));
foreach ($fields as $field) {
	if (in_array($field->name,array('email','password','nickname')) == true) continue;
	$this->db()->delete($this->table->signup)->where('label',$field->label)->where('name',$field->name)->execute();
	$labels[] = $field->label;
}

if (count($labels) > 0) {
	foreach ($labels as $label) {
		$fields = $this->db()->select($this->table->signup)->where('label',$label)->where('sort',0,'>=')->get();
		foreach ($fields as $sort=>$field) {
			$this->db()->update($this->table->signup,array('sort'=>$sort))->where('label',$field->label)->where('name',$field->name)->execute();
		}
	}
}

$results->success = true;
?>