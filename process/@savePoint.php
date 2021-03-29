<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원포인트를 적립한다.
 *
 * @file /modules/member/process/@savePoint.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2018. 4. 16.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$point = Request('point');
$content = Request('content');

$this->sendPoint($idx,$point,'member','admin',array('content'=>$content,'from'=>$this->getLogged()),true);

$results->success = true;
?>