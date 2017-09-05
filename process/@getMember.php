<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 회원정보를 가져온다.
 *
 * @file /modules/member/process/@getMember.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0.160923
 *
 * @return object $results
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$data = $this->db()->select($this->table->member)->where('idx',$idx)->getOne();

if ($data == null) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
	return;
}

$results->success = true;
$results->data = $data;
?>