<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원을 비활성화한다.
 *
 * @file /modules/member/process/@@deactiveMember.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2019. 7. 2.
 */
if (defined('__IM__') == false) exit;

$idxes = Request('idxes') ? explode(',',Request('idxes')) : array();
if (count($idxes) > 0) {
	$this->db()->update($this->table->member,array('status'=>'DEACTIVATED'))->where('idx',$idxes,'IN')->execute();
}

$results->success = true;
?>