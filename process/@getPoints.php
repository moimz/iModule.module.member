<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 포인트 내역을 가져온다.
 *
 * @file /modules/member/process/@getPoints.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 4. 16.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$start = Request('start');
$limit = Request('limit');

$lists = $this->db()->select($this->table->point)->where('midx',$idx);
$total = $lists->copy()->count();
$lists = $lists->orderBy('reg_date','desc')->limit($start,$limit)->get();
for ($i=0, $loop=count($lists);$i<$loop;$i++) {
	$mModule = $this->IM->getModule($lists[$i]->module);
	$lists[$i]->content = json_decode($lists[$i]->content);
	if (method_exists($mModule,'syncMember') == true) {
		$data = new stdClass();
		$data->code = $lists[$i]->code;
		$data->content = $lists[$i]->content;
		$content = $mModule->syncMember('point_history',$data);
		$lists[$i]->content = $content == null ? $lists[$i]->content : $content;
	}
	
	$lists[$i]->content = is_string($lists[$i]->content) == true ? $lists[$i]->content : json_encode($lists[$i]->content,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
}
$results->success = true;
$results->lists = $lists;
$results->total = $total;
?>