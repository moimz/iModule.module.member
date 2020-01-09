<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원정보를 가져온다.
 *
 * @file /modules/member/process/@getMember.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2018. 4. 9.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$data = $this->getMember($idx);

if ($data->idx == 0) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
	return;
}

if ($data->extras != null) {
	foreach ($data->extras as $key=>$value) {
		$data->$key = $value;
	}
}

unset($data->password);
unset($data->extras);

$labels = $this->db()->select($this->table->member_label)->where('idx',$idx)->get('label');
$labels[] = 0;
$fields = $this->db()->select($this->table->signup)->where('label',$labels,'IN')->get();

$results->success = true;
$results->data = $data;
$results->fields = $fields;
?>