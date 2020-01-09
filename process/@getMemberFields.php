<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원정보에 사용된 필드정보를 가져온다.
 *
 * @file /modules/member/process/@getMemberFields.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2018. 4. 9.
 */
if (defined('__IM__') == false) exit;

$midx = Request('midx');
if ($midx) {
	$labels = $this->db()->select($this->table->member_label)->where('idx',$midx)->get('label');
	$labels[] = 0;
}

$fields = array();
$defaults = $extras = array();
$forms = $this->db()->select($this->table->signup)->where('label',$labels,'IN')->orderBy('sort','asc')->get();
for ($i=0, $loop=count($forms);$i<$loop;$i++) {
	if (in_array($forms[$i]->name,array('agreement','privacy','password')) == true) continue;
	if (in_array($forms[$i]->name,$fields) == true) continue;
	
	$field = $this->getInputField($forms[$i]);
	
	if ($forms[$i]->label == 0) array_push($defaults,$field);
	else array_push($extras,$field);
	
	array_push($fields,$forms[$i]->name);
}

$results->success = true;
$results->fields = new stdClass();
$results->fields->defaults = $defaults;
$results->fields->extras = $extras;
?>