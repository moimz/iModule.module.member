<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원가입폼 필드를 가져온다.
 *
 * @file /modules/member/process/@getSignUpField.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2018. 4. 9.
 */
if (defined('__IM__') == false) exit;

$label = Request('label');
$name = Request('name');

$data = $this->db()->select($this->table->signup)->where('label',$label)->where('name',$name)->getOne();
if ($data == null) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
} else {
	if ($data->type == 'etc') {
		$data->name_etc = $data->name;
		$data->name = 'etc';
	}
	
	$data->title_languages = json_decode($data->title_languages);
	$data->help_languages = json_decode($data->help_languages);
	$data->is_required = $data->is_required == 'TRUE';
	
	$configs = json_decode($data->configs);
	unset($data->configs);
	
	if (in_array($data->input,array('select','radio','checkbox')) == true) {
		$data->options = $configs->options;
		if ($data->input == 'checkbox') $data->max = $configs->max;
	}
	
	$results->success = true;
	$results->data = $data;
}
?>