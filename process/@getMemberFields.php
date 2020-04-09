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
 * @modified 2020. 4. 9.
 */
if (defined('__IM__') == false) exit;

$midx = Request('midx');
if ($midx) {
	$labels = $this->db()->select($this->table->member_label)->where('idx',$midx)->get('label');
	$labels[] = 0;
} else {
	$labels = Request('labels');
	$unique = null;
	for ($i=0, $loop=count($labels);$i<$loop;$i++) {
		$label = $this->getLabel($labels[$i]);
		if ($label->idx > 0 && $label->is_unique == true && count($labels) > 2) {
			$results->success = false;
			$results->message = $this->getErrorText('TOO_MANY_SELECTED_UNIQUE_LABEL').'<br>- '.$label->title;
			return;
		}
	}
}

$fields = array();
$defaults = $extras = array();
$forms = $this->db()->select($this->table->signup)->where('label',$labels,'IN')->orderBy('sort','asc')->get();
for ($i=0, $loop=count($forms);$i<$loop;$i++) {
	if (in_array($forms[$i]->name,array('agreement','privacy')) == true) continue;
	if ($midx && $forms[$i]->name == 'password') continue;
	if (in_array($forms[$i]->name,$fields) == true) continue;
	
	$field = $this->getInputField($forms[$i]);
	
	if ($forms[$i]->label == 0) array_push($defaults,$field);
	else array_push($extras,$field);
	
	array_push($fields,$forms[$i]->name);
}

$results->success = true;
$results->labels = $labels;
$results->fields = new stdClass();
$results->fields->defaults = $defaults;
$results->fields->extras = $extras;
?>