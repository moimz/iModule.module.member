<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 * 
 * 템플릿의 환경설정폼을 가져온다.
 *
 * @file /modules/member/process/@getTempletConfigs.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0.160923
 *
 * @return object $results
 */
if (defined('__IM__') == false) exit;

$templet = Request('templet');
$Templet = $this->Module->getTemplet($templet);
if ($this->Module->isInstalled() === true && $this->Module->getConfig('templet') == $templet) $Templet->setConfigs($this->Module->getConfig('templet_configs'));
$configs = $Templet->getConfigs();

$results->success = true;
$results->configs = $configs;
?>