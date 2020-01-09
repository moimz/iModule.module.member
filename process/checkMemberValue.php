<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 * 
 * 회원가입 / 정보수정 폼에서 입력된 값의 유효성을 실시간으로 확인한다.
 *
 * @file /modules/member/process/liveCheckValue.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2018. 2. 10.
 */
if (defined('__IM__') == false) exit;

$name = Request('name');
$value = Request('value');
$isSignUp = Request('mode') == 'signup';
$siteType = $this->IM->getSite(false)->member;

if ($name == 'email' || $name == 'email_verification_email') {
	if (CheckEmail($value) == true) {
		$check = $this->db()->select($this->table->member)->where('email',$value);
		if (($isSignUp == false && $this->isLogged() == true) || $name == 'email_verification_email') $check->where('idx',$this->getLogged(),'!=');
		
		$checkAdmin = $check->copy();
		
		if ($siteType == 'UNIVERSAL') $check->where('domain','*');
		else $check->where('domain',$this->IM->domain);
		
		$checkAdmin->where('type','ADMINISTRATOR');
		
		if ($check->has() == true || $checkAdmin->has() == true) {
			$results->success = false;
			$results->message = $this->getErrorText('DUPLICATED');
		} else {
			$results->success = true;
			$results->message = $this->getText('signup/email_success');
		}
	} else {
		$results->success = false;
		$results->message = $this->getErrorText('INVALID_EMAIL');
	}
}

if ($name == 'name') {
	$results->success = CheckName($value);
	if ($results->success == false) $results->message = $this->getErrorText('INVALID_USERNAME');
}

if ($name == 'nickname') {
	if (CheckNickname($value) == true) {
		$check = $this->db()->select($this->table->member)->where('nickname',$value);
		if ($isSignUp == false && $this->isLogged() == true) $check->where('idx',$this->getLogged(),'!=');
		
		$checkAdmin = $check->copy();
		
		if ($siteType == 'UNIVERSAL') $check->where('domain','*');
		else $check->where('domain',$this->IM->domain);
		
		$checkAdmin->where('type','ADMINISTRATOR');
		
		if ($check->has() == true || $checkAdmin->has() == true) {
			$results->success = false;
			$results->message =  $this->getErrorText('DUPLICATED');
		} else {
			$results->success = true;
			$results->message = $this->getText('signup/nickname_success');
		}
	} else {
		$results->success = false;
		$results->message = $this->getErrorText('INVALID_NICKNAME');
	}
}

if ($name == 'old_password') {
	if ($this->isLogged() == false) {
		$results->success = false;
		$results->message = $this->getText('error/notLogged');
	} else {
		$mHash = new Hash();
		if ($mHash->password_validate($value,$this->getMember()->password) == true) {
			$results->success = true;
			$results->message = $this->getText('password/help/old_password/success');
		} else {
			$results->success = false;
			$results->message = $this->getText('password/help/old_password/error');
		}
	}
}

if ($name == 'companyAuth') {
    $companynum = str_replace("-", "", Request('cpnum'));
    $companyname = Request('cpname');
    $success = true;
    $message = $this->getText('signup/companynumber_success');
    if(!$companyname) {
        $success = false;
        $message = $this->getErrorText('INCORRECT_COMPANY_NAME');
    } else {
        if ($companynum) {
            if (CheckCompanyNumber($companynum) === true) {
                $_companynum = substr($companynum, 0, 6);
                $ch = curl_init();
                $url = 'http://apis.data.go.kr/B552015/NpsBplcInfoInqireService/getBassInfoSearch'; /*URL*/
                $queryParams = '?' . urlencode('ServiceKey') . '=YutGcDz8lkoYs6l5uU%2FCwq13APU9kgOWOcQza4UJDYL%2BGJThocro9SY%2F%2FMEIROIigLsJ2L2rYlPcJcuywBGE0g%3D%3D'; /*Service Key*/
                //$queryParams .= '&' . urlencode('ldong_addr_mgpl_dg_cd') . '=' . urlencode('41'); /*시도(행정자치부 법정동 주소코드 참조)*/
                //$queryParams .= '&' . urlencode('ldong_addr_mgpl_sggu_cd') . '=' . urlencode('117'); /*시군구(행정자치부 법정동 주소코드 참조)*/
                //$queryParams .= '&' . urlencode('ldong_addr_mgpl_sggu_emd_cd') . '=' . urlencode('101'); /*읍면동(행정자치부 법정동 주소코드 참조)*/
                $queryParams .= '&' . urlencode('wkpl_nm') . '=' . urlencode($companyname); /*사업장명*/
                $queryParams .= '&' . urlencode('bzowr_rgst_no') . '=' . urlencode($_companynum); /*사업자등록번호(앞에서 6자리)*/
                //$queryParams .= '&' . urlencode('pageNo') . '=' . urlencode('10'); /*페이지번호*/
                //$queryParams .= '&' . urlencode('numOfRows') . '=' . urlencode('1'); /*행갯수*/
                curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                $response = curl_exec($ch);
                curl_close($ch);
                $lists = simplexml_load_string($response);
                $counts = sizeof($lists->body->items->item);
                if($counts < 1) {
                    $success = false;
                    $message = $this->getErrorText('INCORRECT_COMPANY_NUMBER');
                } else {
                    $check = $this->db()->select($this->table->member)->where('cpnumber', $companynum);
                    if ($check->has() === true) {
                        $success = false;
                        $message = $this->getErrorText('DUPLICATED');
                    }
                }
            } else {
                $success = false;
                $message = $this->getErrorText('INCORRECT_COMPANY_NUMBER');
            }
        }
    }
    $results->success = $success;
    $results->message = $message;
}
?>