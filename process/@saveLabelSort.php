<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 회원라벨의 표시순서를 저장한다.
 *
 * @file /modules/member/process/@saveLabelSort.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0.160923
 *
 * @return object $results
 */
if (defined('__IM__') == false) exit;

$updated = json_decode(Request('updated'));
for ($i=0, $loop=count($updated);$i<$loop;$i++) {
	if ($updated[$i]->idx > 0) $this->db()->update($this->table->label,array('sort'=>$updated[$i]->sort))->where('idx',$updated[$i]->idx)->execute();
}

$results->success = true;
?>