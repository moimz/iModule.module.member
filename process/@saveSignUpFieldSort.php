<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 회원가입폼의 필드순서를 저장한다.
 *
 * @file /modules/member/process/@saveSignUpFieldSort.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2017. 11. 30.
 */
if (defined('__IM__') == false) exit;

$updated = json_decode(Request('updated'));
for ($i=0, $loop=count($updated);$i<$loop;$i++) {
	$this->db()->update($this->table->signup,array('sort'=>$updated[$i]->sort))->where('label',$updated[$i]->label)->where('name',$updated[$i]->name)->execute();
}

$results->success = true;
?>