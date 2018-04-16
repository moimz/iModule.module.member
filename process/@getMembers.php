<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 모든 회원목록을 가져온다.
 *
 * @file /modules/member/process/@getMembers.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 4. 9.
 */
if (defined('__IM__') == false) exit;

$start = Request('start') ? Request('start') : 0;
$limit = Request('limit') ? Request('limit') : 50;
$sort = Request('sort') ? Request('sort') : 'idx';
$dir = Request('dir') ? Request('dir') : 'DESC';

$label = Request('label') ? Request('label') : 0;
$keyword = Request('keyword');

$lists = $this->db()->select($this->table->member.' m');
if ($label) $lists->join($this->table->member_label.' l','l.idx=m.idx','LEFT')->where('l.label',$label);
if ($keyword) $lists->where('(m.email like ? or m.name like ? or m.nickname like ?)',array('%'.$keyword.'%','%'.$keyword.'%','%'.$keyword.'%'));
$total = $lists->copy()->count();
$lists = $lists->orderBy('m.'.$sort,$dir)->limit($start,$limit)->get();
for ($i=0, $loop=count($lists);$i<$loop;$i++) {
	$lists[$i]->photo = $this->IM->getModuleUrl('member','photo',$lists[$i]->idx).'/profile.jpg';
}

$results->success = true;
$results->lists = $lists;
$results->total = $total;
?>