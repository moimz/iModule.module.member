<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 최근 가입한 회원목록을 가져온다.
 * 
 * @file /modules/member/widgets/recently/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 8. 26.
 */
if (defined('__IM__') == false) exit;

$count = $Widget->getValue('count');
$cache = $Widget->getValue('cache');

if ($Widget->checkCache() < time() - $cache) {
	$lists = $me->db()->select($me->getTable('member'))->where('status','ACTIVATED');
	$lists = $lists->limit($count)->orderBy('reg_date','desc')->get('idx');
	for ($i=0, $loop=count($lists);$i<$loop;$i++) {
		$lists[$i] = $me->getMember($lists[$i]);
	}
	
	$Widget->storeCache(json_encode($lists,JSON_UNESCAPED_UNICODE));
} else {
	$lists = json_decode($Widget->getCache());
}

return $Templet->getContext('index',get_defined_vars());
?>