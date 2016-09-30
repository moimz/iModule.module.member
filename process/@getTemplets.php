<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 서버에 존재하는 회원모듈의 템플릿 목록을 가져온다.
 *
 * @file /modules/member/process/@getTemplets.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0.160923
 *
 * @return object $results
 */
if (defined('__IM__') == false) exit;

$templets = $this->Module->getTemplets();
$lists = array();
for ($i=0, $loop=count($templets);$i<$loop;$i++) {
	$lists[] = array($templets[$i]->name,$templets[$i]->title.' ('.$templets[$i]->dir.')');
}

$results->success = true;
$results->lists = $lists;
$results->total = count($lists);
?>