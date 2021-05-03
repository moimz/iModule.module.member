<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 포인트 내역을 가져온다.
 *
 * @file /modules/member/process/@getPoints.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2021. 5. 3.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$start = Request('start');
$limit = Request('limit');
$keyword = Request('keyword');

$lists = $this->db()->select($this->table->point.' p','p.*')->join($this->table->member.' m','m.idx=p.midx','LEFT');
if ($idx) $lists->where('p.midx',$idx);
if ($keyword) $lists->where('(')->where('name','%'.$keyword.'%','LIKE')->orWhere('nickname','%'.$keyword.'%','LIKE')->orWhere('email','%'.$keyword.'%','LIKE')->where(')');
$total = $lists->copy()->count();
$lists = $lists->orderBy('p.reg_date','desc')->limit($start,$limit)->get();
for ($i=0, $loop=count($lists);$i<$loop;$i++) {
	$mModule = $this->IM->getModule($lists[$i]->module);
	$lists[$i]->module_title = $this->getModule()->getTitle($lists[$i]->module);
	
	$lists[$i]->content = json_decode($lists[$i]->content);
	if (method_exists($mModule,'syncMember') == true) {
		$data = new stdClass();
		$data->code = $lists[$i]->code;
		$data->content = $lists[$i]->content;
		$content = $mModule->syncMember('point_history',$data);
		$lists[$i]->content = $content == null ? $lists[$i]->content : $content;
	}
	
	$lists[$i]->content = is_string($lists[$i]->content) == true ? $lists[$i]->content : json_encode($lists[$i]->content,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
	$lists[$i]->accumulation = $this->db()->select($this->table->point,'SUM(point) as accumulation')->where('midx',$lists[$i]->midx)->where('reg_date',$lists[$i]->reg_date,'<=')->getOne()->accumulation;
	
	if ($idx == null) {
		$member = $this->IM->getModule('member')->getMember($lists[$i]->midx);
		$lists[$i]->photo = $member->photo;
		$lists[$i]->member = $member->name;
	}
}
$results->success = true;
$results->lists = $lists;
$results->total = $total;
?>