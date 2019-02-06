<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 회원가입 API 를 처리한다.
 * @todo 새버전에 맞게 수정되지 않았음
 * 
 * @file /modules/member/api/signup.post.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 22.
 */
if (defined('__IM__') == false) exit;

/*
$label = Request('label') ? Request('label') : 0;
$client_id = Request('client_id');

$siteType = $this->IM->getSite()->member;

$insert = array();
$errors = array();

if ($label == 0) {
	$autoActive = $this->getModule()->getConfig('autoActive');
	$allowSignup = $this->getModule()->getConfig('allowSignup');
} else {
	$label = $this->db()->select($this->table->label)->where('idx',$label)->getOne();
	if ($label == null) {
		$autoActive = $allowSignup = false;
		$errors['label'] = $this->getText('error/not_found');
		$label = 0;
	} else {
		$autoActive = $label->auto_active == 'TRUE';
		$allowSignup = $label->allow_signup == 'TRUE';
		$label = $label->idx;
	}
}

$forms = $this->db()->select($this->table->signup)->where('label',array(0,$label),'IN')->get();
for ($i=0, $loop=count($forms);$i<$loop;$i++) {
	$configs = json_decode($forms[$i]->configs);
	
	switch ($forms[$i]->type) {
		case 'email' :
			$insert['email'] = CheckEmail(Request('email')) == true ? Request('email') : $errors['email'] = $this->getText('signup/help/email/error');
			if ($this->db()->select($this->table->member)->where('email',$insert['email'])->where('domain',$siteType == 'MERGE' ? '*' : $this->IM->domain)->has() == true || $this->db()->select($this->table->member)->where('email',$insert['email'])->where('type','ADMINISTRATOR')->has() == true) {
				$errors['email'] = $this->getText('signup/help/email/duplicated');
			}
			break;
		
		case 'password' :
			$insert['password'] = strlen(Request('password')) >= 4 ? Request('password') : $errors['password'] = $this->getText('signup/help/password/error');
			if (strlen(Request('password')) < 4 || Request('password') != Request('password_confirm')) {
				$errors['password_confirm'] = $this->getText('signup/help/password_confirm/error');
			}
			break;
			
		case 'name' :
			$insert['name'] = CheckNickname(Request('name')) == true ? Request('name') : $errors['name'] = $this->getText('signup/help/name/error');
			break;
			
		case 'nickname' :
			$insert['nickname'] = CheckNickname(Request('nickname')) == true ? Request('nickname') : $errors['nickname'] = $this->getText('signup/help/nickname/error');
			if ($this->db()->select($this->table->member)->where('nickname',$insert['nickname'])->where('domain',$siteType == 'MERGE' ? '*' : $this->IM->domain)->has() == true || $this->db()->select($this->table->member)->where('nickname',$insert['nickname'])->where('type','ADMINISTRATOR')->has() == true) {
				$errors['nickname'] = $this->getText('signup/help/nickname/duplicated');
			}
			break;
			
		case 'telephone' :
			if (Request('telephone1') != null && Request('telephone2') != null && Request('telephone3') != null) {
				$insert['telephone'] = Request('telephone1').Request('telephone2').Request('telephone3');
			} elseif (Request('telephone') != null) {
				$insert['telephone'] = Request('telephone');
			} else {
				$insert['telephone'] = '';
			}
			
			if ($configs->useCountryCode == true) {
				if ($insert['telephone'] && Request('telephone_country_code')) {
					$insert['telephone'] = Request('telephone_country_code').preg_replace('/^0/','',$insert['telephone']);
				} elseif (!Request('telephone_country_code')) {
					$errors['telephone'] = $this->getText('error/required');
				}
			}
			
			if ($forms[$i]->allow_blank == 'FALSE' && strlen($insert['telephone']) < 10) $errors['telephone'] = $this->getText('error/required');
			break;
			
		case 'cellphone' :
			if (Request('cellphone1') != null && Request('cellphone2') != null && Request('cellphone3') != null) {
				$insert['cellphone'] = Request('cellphone1').Request('cellphone2').Request('cellphone3');
			} elseif (Request('cellphone') != null) {
				$insert['cellphone'] = Request('cellphone');
			} else {
				$insert['cellphone'] = '';
			}
			
			if ($configs->useCountryCode == true) {
				if ($insert['cellphone'] && Request('cellphone_country_code')) {
					$insert['cellphone'] = Request('cellphone_country_code').preg_replace('/^0/','',$insert['cellphone']);
				} elseif (!Request('cellphone_country_code')) {
					$errors['cellphone'] = $this->getText('error/required');
				}
			}
			
			if ($forms[$i]->allow_blank == 'FALSE' && strlen($insert['cellphone']) < 10) $errors['cellphone'] = $this->getText('error/required');
			break;
			
		case 'birthday' :
			$birthday = Request('birthday') ? strtotime(Request('birthday')) : 0;
			$insert['birthday'] = $birthday > 0 ? date('m-d-Y',$birthday) : '';
			
			if ($forms[$i]->allow_blank == 'FALSE' && !$insert['birthday']) $errors['birthday'] = $this->getText('error/required');
			break;
			
		case 'gender' :
			$insert['gender'] = in_array(Request('gender'),array('MALE','FEMALE')) == true ? Request('gender') : '';
			
			if ($forms[$i]->allow_blank == 'FALSE' && !$insert['gender']) $errors['gender'] = $this->getText('error/required');
			break;
	}
}

$values = new stdClass();
$values->insert = $insert;
$values->errors = $errors;
$this->IM->fireEvent('beforeGetApi','member',$api,$values);
$insert = $values->insert;
$errors = $values->errors;

$values = new stdClass();
if (empty($errors) == true) {
	$mHash = new Hash();
	
	$insert['domain'] = $siteType == 'MERGE' ? '*' : $this->IM->domain;
	$insert['password'] = $mHash->password_hash($insert['password']);
	$insert['status'] = in_array('verify',$this->getModule()->getConfig('signupStep')) === true ? 'VERIFYING' : ($autoActive == true ? 'ACTIVE' : 'WAITING');
	$insert['point'] = $this->getModule()->getConfig('signupPoint');
	$insert['reg_date'] = time();
	
	$idx = $this->db()->insert($this->table->member,$insert)->execute();
	if ($label != 0) {
		$this->db()->insert($this->table->member_label,array('idx'=>$idx,'label'=>$label,'reg_date'=>$insert['reg_date']))->execute();
		$count = $this->db()->select($this->table->member_label)->where('label',$label)->count();
		$this->db()->update($this->table->label,array('member'=>$count))->where('idx',$label)->execute();
	}
	
	if ($idx !== false) {
		$data->success = true;
		if (in_array('verify',$this->getModule()->getConfig('signupStep')) === true) $this->sendVerifyEmail($idx);
		$data->idx = $idx;
		$data->access_token = $this->makeAuthToken($client_id,$data->idx);
	} else {
		$data->success = false;
	}
} else {
	$data->success = false;
	$data->errors = $errors;
}
*/
?>