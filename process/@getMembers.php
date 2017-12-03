<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 모든 회원목록을 가져온다.
 *
 * @file /modules/member/process/@getMembers.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0.160923
 *
 * @post int $label 회원라벨 검색
 * @post string $keyword 검색어
 * @return object $results
 */
if (defined('__IM__') == false) exit;

$start = Request('start') ? Request('start') : 0;
$limit = Request('limit') ? Request('limit') : 50;
$sort = Request('sort') ? Request('sort') : 'idx';
$dir = Request('dir') ? Request('dir') : 'DESC';

$label = Request('label') ? Request('label') : 0;
$keyword = Request('keyword');

$lists = $this->db()->select($this->table->member);
$total = $lists->copy()->count();
$lists = $lists->orderBy($sort,$dir)->limit($start,$limit)->get();
for ($i=0, $loop=count($lists);$i<$loop;$i++) {
	$lists[$i]->photo = $this->IM->getModuleUrl('member','photo',$lists[$i]->idx).'/profile.jpg';
}

$results->success = true;
$results->lists = $lists;
$results->total = $total;
?>