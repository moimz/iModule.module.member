<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 통합로그인을 사용하는 사이트를 가져온다.
 *
 * @file /modules/member/process/getUniversalSites.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2019. 11. 12.
 */
if (defined('__IM__') == false) exit;

$sites = $this->IM->db()->select($this->IM->getTable('site'),'domain,is_https')->where('domain',$this->IM->domain,'!=')->where('member','UNIVERSAL')->groupBy('domain')->groupBy('is_https')->get();
for ($i=0, $loop=count($sites);$i<$loop;$i++) {
	$sites[$i] = ($sites[$i]->is_https == 'TRUE' ? 'https://' : 'http://').$sites[$i]->domain;
}
$results->success = true;
$results->token = $this->makeSessionToken();
$results->sites = $sites;
?>