<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 회원과 관련된 모든 기능을 제어한다.
 * 
 * @file /modules/member/ModuleMember.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160910
 */
class ModuleMember {
	/**
	 * iModule 및 Module 코어클래스
	 */
	private $IM;
	private $Module;
	
	/**
	 * DB 관련 변수정의
	 *
	 * @private string[] $table DB 테이블 별칭 및 원 테이블명을 정의하기 위한 변수
	 */
	private $table;
	
	/**
	 * 언어셋을 정의한다.
	 * 
	 * @private object $lang 현재 사이트주소에서 설정된 언어셋
	 * @private object $oLang package.json 에 의해 정의된 기본 언어셋
	 */
	private $lang = null;
	private $oLang = null;
	
	/**
	 * DB접근을 줄이기 위해 DB에서 불러온 데이터를 저장할 변수를 정의한다.
	 *
	 * @private $members 회원정보
	 * @private $labels 라벨정보
	 * @private $memberPages 회원관련 컨텍스트를 사용하고 있는 사이트메뉴 정보
	 * @private $logged 현재 로그인한 회원정보
	 */
	private $members = array();
	private $labels = array();
	private $memberPages = array();
	private $logged = null;
	
	/**
	 * class 선언
	 *
	 * @param iModule $IM iModule 코어클래스
	 * @param Module $Module Module 코어클래스
	 * @see /classes/iModule.class.php
	 * @see /classes/Module.class.php
	 */
	function __construct($IM,$Module) {
		/**
		 * iModule 및 Module 코어 선언
		 */
		$this->IM = $IM;
		$this->Module = $Module;
		
		/**
		 * 모듈에서 사용하는 DB 테이블 별칭 정의
		 * @see 모듈폴더의 package.json 의 databases 참고
		 */
		$this->table = new stdClass();
		$this->table->member = 'member_table';
		$this->table->email = 'member_email_table';
		$this->table->level = 'member_level_table';
		$this->table->signup = 'member_signup_table';
		$this->table->point = 'member_point_table';
		$this->table->social_oauth = 'member_social_oauth_table';
		$this->table->social_token = 'member_social_token_table';
		$this->table->label = 'member_label_table';
		$this->table->member_label = 'member_member_label_table';
		$this->table->token = 'member_token_table';
		$this->table->activity = 'member_activity_table';
		
		/**
		 * 회원메뉴를 제공하기 위한 자바스크립트 및 스타일시트를 로딩한다.
		 * 회원모듈은 글로벌모듈이기 때문에 모듈클래스 선언부에서 선언해주어야 사이트 레이아웃에 반영된다.
		 */
		$this->IM->addHeadResource('style',$this->getModule()->getDir().'/styles/style.css');
		$this->IM->addHeadResource('script',$this->getModule()->getDir().'/scripts/script.js');
		
		/**
		 * SESSION 을 검색하여 현재 로그인중인 사람의 정보를 구한다.
		 */
		$this->logged = Request('MEMBER_LOGGED','session') != null && Decoder(Request('MEMBER_LOGGED','session')) != false ? json_decode(Decoder(Request('MEMBER_LOGGED','session'))) : false;
		
		/**
		 * 통합로그인을 사용한다고 설정되어 있을 경우 통합로그인 세션처리를 위한 자바스크립트 파일을 로딩한다.
		 */
		if ($this->getModule()->getConfig('universal_login') == true) $this->IM->addHeadResource('script',$this->getModule()->getDir().'/scripts/session.js');
	}
	
	/**
	 * 모듈 코어 클래스를 반환한다.
	 * 현재 모듈의 각종 설정값이나 모듈의 package.json 설정값을 모듈 코어 클래스를 통해 확인할 수 있다.
	 *
	 * @return Module $Module
	 */
	function getModule() {
		return $this->Module;
	}
	
	/**
	 * 모듈 설치시 정의된 DB코드를 사용하여 모듈에서 사용할 전용 DB클래스를 반환한다.
	 *
	 * @return DB $DB
	 */
	function db() {
		return $this->IM->db($this->getModule()->getInstalled()->database);
	}
	
	/**
	 * 모듈에서 사용중인 DB테이블 별칭을 이용하여 실제 DB테이블 명을 반환한다.
	 *
	 * @param string $table DB테이블 별칭
	 * @return string $table 실제 DB테이블 명
	 */
	function getTable($table) {
		return empty($this->table->$table) == true ? null : $this->table->$table;
	}
	
	/**
	 * [코어] 사이트 외부에서 현재 모듈의 API를 호출하였을 경우, API 요청을 처리하기 위한 함수로 API 실행결과를 반환한다.
	 * 소스코드 관리를 편하게 하기 위해 각 요쳥별로 별도의 PHP 파일로 관리한다.
	 *
	 * @param string $api API명
	 * @return object $datas API처리후 반환 데이터 (해당 데이터는 /api/index.php 를 통해 API호출자에게 전달된다.)
	 * @see /api/index.php
	 */
	function getApi($api) {
		$data = new stdClass();
		$values = new stdClass();
		
		/**
		 * 모듈의 api 폴더에 $api 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->getModule()->getPath().'/api/'.$api.'.php') == true) {
			INCLUDE $this->getModule()->getPath().'/api/'.$api.'.php';
		}
		
		/**
		 * SignUp
		 *
		 * @param string $email
		 * @todo member labeling
		 */
		if ($api == 'signup') {
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
					$membernum = $this->db()->select($this->table->member_label)->where('label',$label)->count();
					$this->db()->update($this->table->label,array('membernum'=>$membernum))->where('idx',$label)->execute();
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
		}
		
		/**
		 * Login
		 *
		 * @param string $email
		 * @param string $password
		 * @param string $client_id app id using iModule API
		 * @todo check app id
		 */
		if ($api == 'login') {
			$email = Request('email');
			$password = Request('password');
			$client_id = Request('client_id');
			
			$loginIdx = $this->isValidate($email,$password);
			if ($loginIdx === false) {
				$data->success = false;
			} else {
				$data->success = true;
				$data->idx = $loginIdx;
				$data->access_token = $this->makeAuthToken($client_id,$loginIdx);
			}
		}
		
		if ($api == 'tokenLogin') {
			$token = urldecode(Request('token'));
			$token = str_replace(' ','+',$token);
			$token = Decoder($token);
			
			if ($token !== false) {
				$user = json_decode($token);
				
				if ($user != null && $user->ip == $_SERVER['REMOTE_ADDR'] && $user->time > time() - 60) {
					$this->login($user->idx);
					$data->success = true;
				} else {
					$data->success = false;
					$data->message = 'TOKEN_EXPIRED';
				}
			} else {
				$data->success = false;
				$data->message = 'INVALID_TOKEN';
			}
		}
		
		/**
		 * Get My information
		 *
		 * @param string $token (oauth2.0 protocol's access_token in HTTP header)
		 * @return object $memberInfo
		 * @todo remove some information (likes password hash)
		 */
		if ($api == 'me') {
			if ($this->isLogged() == false) {
				$data->success = false;
				$data->message = 'NOT LOGGED';
			} else {
				$data->success = true;
				$data->me = $this->getMember();
				$data->me->photo = $this->IM->getHost(true).$data->me->photo;
			}
		}
		
		$this->IM->fireEvent('afterGetApi','member',$api,$values,$data);
		
		return $data;
	}
	
	/**
	 * [코어] 푸시메세지를 구성한다.
	 *
	 * @param string $code Push Code
	 * @param int $fromcode Push target idx
	 * @param array $content Push datas array
	 * @return string $push convert push code to push message string
	 */
	function getPush($code,$fromcode,$content) {
		$latest = array_pop($content);
		$count = count($content);
		
		$push = new stdClass();
		$push->image = null;
		$push->link = null;
		if ($count > 0) $push->content = $this->getText('push/'.$code.'s');
		else $push->content = $this->getText('push/'.$code);
		/** example
		if ($code == 'ment') {
			$ment = $this->getMent($latest->idx);
			$from = $ment->name;
			$push->image = $this->IM->getModule('member')->getMember($ment->midx)->photo;
			$post = $this->getPost($fromcode);
			$title = GetCutString($post->title,15);
			$push->content = str_replace(array('{from}','{title}'),array('<b>'.$from.'</b>','<b>'.$title.'</b>'),$push->content);
			$page = $this->getPostPage($post->idx);
			$push->link = $this->IM->getUrl($page->menu,$page->page,'view',$post->idx,false,$page->domain);
		}
		*/
		$push->content = str_replace('{count}','<b>'.$count.'</b>',$push->content);
		return $push;
	}
	
	/**
	 * [사이트관리자] 모듈 설정패널을 구성한다.
	 *
	 * @return string $panel 설정패널 HTML
	 */
	function getConfigPanel() {
		/**
		 * 설정패널 PHP에서 iModule 코어클래스와 모듈코어클래스에 접근하기 위한 변수 선언
		 */
		$IM = $this->IM;
		$Module = $this->getModule();
		
		ob_start();
		INCLUDE $this->getModule()->getPath().'/admin/configs.php';
		$panel = ob_get_contents();
		ob_end_clean();
		
		return $panel;
	}
	
	/**
	 * [사이트관리자] 모듈 관리자패널 구성한다.
	 *
	 * @return string $panel 관리자패널 HTML
	 */
	function getAdminPanel() {
		/**
		 * 설정패널 PHP에서 iModule 코어클래스와 모듈코어클래스에 접근하기 위한 변수 선언
		 */
		$IM = $this->IM;
		$Module = $this;
		
		ob_start();
		INCLUDE $this->getModule()->getPath().'/admin/index.php';
		$panel = ob_get_contents();
		ob_end_clean();
		
		return $panel;
	}
	
	/**
	 * [사이트관리자] 모듈의 전체 컨텍스트 목록을 반환한다.
	 *
	 * @param object $site call by site data
	 * @return object $contexts
	 */
	function getContexts() {
		$contexts = $this->getText('admin/contexts');
		$lists = array();
		foreach ($contexts as $context=>$title) {
			$lists[] = array('context'=>$context,'title'=>$title);
		}
		
		return $lists;
	}
	
	/**
	 * [사이트관리자] 모듈의 컨텍스트 환경설정을 구성한다.
	 *
	 * @param object $site 설정대상 사이트
	 * @param string $context 설정대상 컨텍스트명
	 * @return object[] $configs 환경설정
	 */
	function getContextConfigs($site,$context) {
		$configs = array();
		
		if ($context == 'signup') {
			$label = new stdClass();
			$label->title = $this->getText('text/label');
			$label->name = 'label';
			$label->type = 'select';
			$label->data = array();
			$label->data[] = array(0,$this->getText('text/no_label'));
			$labels = $this->db()->select($this->table->label,'idx,title')->get();
			for ($i=0, $loop=count($labels);$i<$loop;$i++) {
				$label->data[] = array($labels[$i]->idx,$labels[$i]->title);
			}
			$label->value = 0;
			$configs[] = $label;
		}
		
		$templet = new stdClass();
		$templet->title = $this->IM->getText('text/templet');
		$templet->name = 'templet';
		$templet->type = 'select';
		$templet->data = array();
		
		$templet->data[] = array('#',$this->getText('admin/configs/form/default_setting'));
		
		$templets = $this->getModule()->getTemplets();
		for ($i=0, $loop=count($templets);$i<$loop;$i++) {
			$templet->data[] = array($templets[$i]->getName(),$templets[$i]->getTitle().' ('.$templets[$i]->getDir().')');
		}
		
		$templet->value = count($templet->data) > 0 ? $templet->data[0][0] : '#';
		$configs[] = $templet;
		
		return $configs;
	}
	
	/**
	 * 언어셋파일에 정의된 코드를 이용하여 사이트에 설정된 언어별로 텍스트를 반환한다.
	 * 코드에 해당하는 문자열이 없을 경우 1차적으로 package.json 에 정의된 기본언어셋의 텍스트를 반환하고, 기본언어셋 텍스트도 없을 경우에는 코드를 그대로 반환한다.
	 *
	 * @param string $code 언어코드
	 * @param string $replacement 일치하는 언어코드가 없을 경우 반환될 메세지 (기본값 : null, $code 반환)
	 * @return string $language 실제 언어셋 텍스트
	 */
	function getText($code,$replacement=null) {
		if ($this->lang == null) {
			if (file_exists($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json'));
				if ($this->IM->language != $this->getModule()->getPackage()->language) {
					$this->oLang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json'));
				}
			} else {
				$this->lang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json'));
				$this->oLang = null;
			}
		}
		
		$returnString = null;
		$temp = explode('/',$code);
		
		$string = $this->lang;
		for ($i=0, $loop=count($temp);$i<$loop;$i++) {
			if (isset($string->{$temp[$i]}) == true) {
				$string = $string->{$temp[$i]};
			} else {
				$string = null;
				break;
			}
		}
		
		if ($string != null) {
			$returnString = $string;
		} elseif ($this->oLang != null) {
			if ($string == null && $this->oLang != null) {
				$string = $this->oLang;
				for ($i=0, $loop=count($temp);$i<$loop;$i++) {
					if (isset($string->{$temp[$i]}) == true) {
						$string = $string->{$temp[$i]};
					} else {
						$string = null;
						break;
					}
				}
			}
			
			if ($string != null) $returnString = $string;
		}
		
		/**
		 * 언어셋 텍스트가 없는경우 iModule 코어에서 불러온다.
		 */
		if ($returnString != null) return $returnString;
		elseif (in_array(reset($temp),array('text','button','action')) == true) return $this->IM->getText($code,$replacement);
		else return $replacement == null ? $code : $replacement;
	}
	
	/**
	 * 상황에 맞게 에러코드를 반환한다.
	 *
	 * @param string $code 에러코드
	 * @param object $value(옵션) 에러와 관련된 데이터
	 * @param boolean $isRawData(옵션) RAW 데이터 반환여부
	 * @return string $message 에러 메세지
	 */
	function getErrorText($code,$value=null,$isRawData=false) {
		$message = $this->getText('error/'.$code,$code);
		if ($message == $code) return $this->IM->getErrorText($code,$value,null,$isRawData);
		
		$description = null;
		switch ($code) {
			case 'NOT_ALLOWED_SIGNUP' :
				if ($value != null && is_object($value) == true) {
					$description = $value->title;
				}
				break;
				
			case 'DISABLED_LOGIN' :
				if ($value != null && is_numeric($value) == true) {
					$description = str_replace('{SECOND}',$value,$this->getText('text/remain_time_second'));
				}
				break;
			
			default :
				if (is_object($value) == false && $value) $description = $value;
		}
		
		$error = new stdClass();
		$error->message = $message;
		$error->description = $description;
		
		if ($isRawData === true) return $error;
		else return $this->IM->getErrorText($error);
	}
	
	/**
	 * Get member module page URL
	 * If not exists container code in im_page_table, use account menu url @see iModule.class.php doLayout() method.
	 * @param string $view container code (signup, modify, password ... etc)
	 * @return object $page {menu:string $menu,page:string $page}, 1st and 2nd page code
	 *
	function getMemberPage($view) {
		if (isset($this->memberPages[$view]) == true) return $this->memberPages[$view];
		
		$this->memberPages[$view] = null;
		$sitemap = $this->IM->getPages();
		foreach ($sitemap as $menu=>$pages) {
			for ($i=0, $loop=count($pages);$i<$loop;$i++) {
				if ($pages[$i]->type == 'MODULE') {
					if ($pages[$i]->context != null && $pages[$i]->context->module == 'member' && $pages[$i]->context->context == $view) {
						$this->memberPages[$view] = $pages[$i];
						break;
					}
				}
			}
		}
		
		if ($this->memberPages[$view] == null) return $this->getAccountPage($view == 'mypage' ? null : $view);
		return $this->memberPages[$view];
	}
	*/
	
	/**
	 * 특정 컨텍스트에 대한 제목을 반환한다.
	 *
	 * @param string $context 컨텍스트명
	 * @return string $title 컨텍스트 제목
	 */
	function getContextTitle($context) {
		return $this->getText('admin/contexts/'.$context);
	}
	
	/**
	 * 사이트맵에 나타날 뱃지데이터를 생성한다.
	 *
	 * @param string $context 컨텍스트종류
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return object $badge 뱃지데이터 ($badge->count : 뱃지숫자, $badge->latest : 뱃지업데이트 시각(UNIXTIME), $badge->text : 뱃지텍스트)
	 * @todo check count information
	 */
	function getContextBadge($context,$config) {
		/**
		 * null 일 경우 뱃지를 표시하지 않는다.
		 */
		return null;
	}
	
	/**
	 * 템플릿 정보를 가져온다.
	 *
	 * @param string $this->getTemplet($configs) 템플릿명
	 * @return string $package 템플릿 정보
	 */
	function getTemplet($templet) {
		/**
		 * 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정일 경우
		 */
		if (is_object($templet) == true) {
			$templet = $templet !== null && isset($templet->templet) == true ? $templet->templet : '#';
		}
		
		/**
		 * 템플릿명이 # 이면 모듈 기본설정에 설정된 템플릿을 사용한다.
		 */
		$templet = $templet == '#' ? $this->getModule()->getConfig('templet') : $templet;
		return $this->getModule()->getTemplet($templet);
	}
	
	/**
	 * 페이지 컨텍스트를 가져온다.
	 *
	 * @param string $context 컨테이너 종류
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getContext($context,$configs=null) {
		/**
		 * 컨텍스트 컨테이너를 설정한다.
		 */
		$html = PHP_EOL.'<!-- MEMBER MODULE -->'.PHP_EOL.'<div data-role="context" data-type="module" data-module="'.$this->getModule()->getName().'">'.PHP_EOL;
		
		/**
		 * 컨텍스트 헤더
		 */
		$html.= $this->getHeader($context,$configs);
		
		/**
		 * 컨테이너 종류에 따라 컨텍스트를 가져온다.
		 */
		switch ($context) {
			case 'signup' :
				$html.= $this->getSignUpContext($configs);
				break;
				
			case 'modify' :
				$html.= $this->getModifyContext($configs);
				break;
				
			case 'social' :
				$html.= $this->getSocialContext($configs);
				break;
		}
		
		/**
		 * 컨텍스트 푸터
		 */
		$html.= $this->getFooter($context,$configs);
		
		/**
		 * 컨텍스트 컨테이너를 설정한다.
		 */
		$html.= PHP_EOL.'</div>'.PHP_EOL.'<!--// MEMBER MODULE -->'.PHP_EOL;
		
		return $html;
	}
	
	/**
	 * 컨텍스트 헤더를 가져온다.
	 *
	 * @param string $context 컨테이너 종류
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getHeader($context,$configs=null) {
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getHeader(get_defined_vars());
	}
	
	/**
	 * 컨텍스트 푸터를 가져온다.
	 *
	 * @param string $context 컨테이너 종류
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getFooter($context,$configs=null) {
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getFooter(get_defined_vars());
	}
	
	/**
	 * 에러메세지를 반환한다.
	 *
	 * @param string $code 에러코드 (에러코드는 iModule 코어에 의해 해석된다.)
	 * @param object $value 에러코드에 따른 에러값
	 * @return $html 에러메세지 HTML
	 */
	function getError($code,$value=null) {
		/**
		 * iModule 코어를 통해 에러메세지를 구성한다.
		 */
		$error = $this->getErrorText($code,$value,true);
		return $this->IM->getError($error);
	}
	
	/**
	 * 회원가입 컨텍스트를 가져온다.
	 *
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getSignUpContext($configs) {
		$label = isset($configs->label) == true ? $configs->label : null;
		$label = $label == null ? Request('label') : $label;
		$label = $label == null || is_numeric($label) == false ? 0 : $label;
		
		/**
		 * 선택한 회원라벨의 회원가입이 가능한지 확인한다.
		 */
		if ($this->getLabel($label) == null || $this->getLabel($label)->allow_signup == false) return $this->getTemplet($configs)->getError('NOT_ALLOWED_SIGNUP',$this->getLabel($label));
		
		/**
		 * 모듈설정에 정의된 회원가입절차를 가져온다.
		 */
		$steps = $this->getModule()->getConfig('signup_step');
		
		/**
		 * iModule 코어의 view 값을 현재 가입단계로 사용한다.
		 */
		$step = $this->IM->view;
		if ($step == null || in_array($step,$steps) == false) $step = $steps[0];
		
		$agreements = Request('agreements') == null ? '' : (is_array(Request('agreements')) == true ? implode(',',Request('agreements')) : Request('agreements'));
		
		while (count($steps) > 0) {
			$position = array_search($step,$steps);
			$prevStep = $position == 0 ? '' : $steps[$position-1];
			$nextStep = $position == count($steps) - 1 ? '' : $steps[$position+1];
		
			/**
			 * 약관동의
			 */
			if ($step == 'agreement') {
				/**
				 * 선택 회원라벨의 약관내용을 가져온다. 개인정보보호정책을 가져온다.
				 */
				$form = $this->db()->select($this->table->signup)->where('label',array($label,0),'IN')->where('name','agreement')->orderBy('label','desc')->getOne();
				
				if ($form != null) {
					$title_languages = json_decode($form->title_languages);
					$help_languages = json_decode($form->help_languages);
					$configs = json_decode($form->configs);
					
					$title = isset($title_languages->{$this->IM->language}) == true ? $title_languages->{$this->IM->language} : $form->title;
					$help = isset($help_languages->{$this->IM->language}) == true ? $help_languages->{$this->IM->language} : $form->help;
					
					$agreement = new stdClass();
					$agreement->title = $title == 'LANGUAGE_SETTING' ? $this->getText('text/agreement') : $title;
					$agreement->content = $configs->content;
					$agreement->help = $help == 'LANGUAGE_SETTING' ? $this->getText('signup/agree') : $help;
					$agreement->value = 'agreement-'.$form->label;
				} else {
					$agreement = null;
				}
				
				/**
				 * 선택 회원라벨의 개인정보보호정책을 가져온다.
				 */
				$form = $this->db()->select($this->table->signup)->where('label',array($label,0),'IN')->where('name','privacy')->orderBy('label','desc')->getOne();
				
				if ($form != null) {
					$title_languages = json_decode($form->title_languages);
					$help_languages = json_decode($form->help_languages);
					$configs = json_decode($form->configs);
					
					$title = isset($title_languages->{$this->IM->language}) == true ? $title_languages->{$this->IM->language} : $form->title;
					$help = isset($help_languages->{$this->IM->language}) == true ? $help_languages->{$this->IM->language} : $form->help;
					
					$privacy = new stdClass();
					$privacy->title = $title == 'LANGUAGE_SETTING' ? $this->getText('text/agreement') : $title;
					$privacy->content = $configs->content;
					$privacy->help = $help == 'LANGUAGE_SETTING' ? $this->getText('signup/agree') : $help;
					$privacy->value = 'privacy-'.$form->label;
				} else {
					$privacy = null;
				}
				
				/**
				 * 약관과 개인정보보호정책이 모두 없을 경우 약관동의 단계를 생략한다.
				 */
				if ($agreement == null && $privacy == null) {
					array_splice($steps,$position,1);
					$step = $nextStep;
					continue;
				}
			}
			
			/**
			 * @todo 실명인증단계를 구현한다. 현재는 없기 때문에 실명인증 단계를 생략한다.
			 */
			if ($step == 'cert') {
				if (true) {
					array_splice($steps,$position,1);
					$step = $nextStep;
					continue;
				}
			}
			
			/**
			 * 회원라벨 선택
			 */
			if ($step == 'label') {
				$labels = $this->db()->select($this->table->label,'idx')->orderBy('sort','asc')->get();
				for ($i=0, $loop=count($labels);$i<$loop;$i++) {
					$labels[$i] = $this->getLabel($labels[$i]->idx);
				}
				
				/**
				 * 기본라벨 외에 회원라벨이 없으면 라벨선택 단계를 생략한다.
				 */
				if (count($labels) == 0) {
					array_splice($steps,$position,1);
					$step = $nextStep;
					continue;
				}
				
				array_unshift($labels,$this->getLabel(0));
			}
			
			/**
			 * 회원정보입력
			 */
			if ($step == 'insert') {
				/**
				 * 가입폼을 가져온다.
				 * 회원약관이나, 개인정보보호정책 중 이미 동의한 항목은 생략한다.
				 */
				$agreement = null;
				$privacy = null;
				
				$agreements = explode(',',$agreements);
				$defaults = $extras = array();
				$forms = $this->db()->select($this->table->signup)->where('label',array(0,$label),'IN')->orderBy('sort','asc')->get();
				for ($i=0, $loop=count($forms);$i<$loop;$i++) {
					if (in_array($forms[$i]->name.'-'.$forms[$i]->label,$agreements) == true) continue;
					
					if ($forms[$i]->name == 'agreement') {
						$title_languages = json_decode($forms[$i]->title_languages);
						$help_languages = json_decode($forms[$i]->help_languages);
						$configs = json_decode($forms[$i]->configs);
						
						$title = isset($title_languages->{$this->IM->language}) == true ? $title_languages->{$this->IM->language} : $forms[$i]->title;
						$help = isset($help_languages->{$this->IM->language}) == true ? $help_languages->{$this->IM->language} : $forms[$i]->help;
						
						$agreement = new stdClass();
						$agreement->title = $title == 'LANGUAGE_SETTING' ? $this->getText('text/agreement') : $title;
						$agreement->content = $configs->content;
						$agreement->help = $help == 'LANGUAGE_SETTING' ? $this->getText('signup/agree') : $help;
						$agreement->value = 'agreement-'.$forms[$i]->label;
						
						continue;
					}
					
					if ($forms[$i]->name == 'privacy') {
						$title_languages = json_decode($forms[$i]->title_languages);
						$help_languages = json_decode($forms[$i]->help_languages);
						$configs = json_decode($forms[$i]->configs);
						
						$title = isset($title_languages->{$this->IM->language}) == true ? $title_languages->{$this->IM->language} : $forms[$i]->title;
						$help = isset($help_languages->{$this->IM->language}) == true ? $help_languages->{$this->IM->language} : $forms[$i]->help;
						
						$privacy = new stdClass();
						$privacy->title = $title == 'LANGUAGE_SETTING' ? $this->getText('text/agreement') : $title;
						$privacy->content = $configs->content;
						$privacy->help = $help == 'LANGUAGE_SETTING' ? $this->getText('signup/agree') : $help;
						$privacy->value = 'privacy-'.$forms[$i]->label;
						
						continue;
					}
					
					$field = $this->parseInputField($forms[$i]);
					$field->inputHtml = $this->getInputFieldHtml($field,'signup');
					
					if ($forms[$i]->label == 0) array_push($defaults,$field);
					else array_push($extras,$field);
				}
			}
			
			/**
			 * 이메일주소 인증
			 */
			if ($step == 'verify') {
				$fields = array();
				
				$field = new stdClass();
				$field->name = 'email_verification_email';
				$field->title = $this->getText('signup/form/email_verification_email');
				$field->help = $this->getText('signup/form/email_verification_email_help');
				$field->input = 'system';
				$field->is_required = true;
				$field->inputHtml = $this->getInputFieldHtml($field,'signup');
				
				$fields[] = $field;
				
				$field = new stdClass();
				$field->name = 'email_verification_code';
				$field->title = $this->getText('signup/form/email_verification_code');
				$field->help = $this->getText('signup/form/email_verification_code_help');
				$field->input = 'system';
				$field->is_required = true;
				$field->inputHtml = $this->getInputFieldHtml($field,'signup');
				
				$fields[] = $field;
			}
			
			break;
		}
		
		$values = get_defined_vars();
		
		/**
		 * 이메일 인증단계나 가입완료단계에서 로그인중이 아니라면, 에러메세지를 출력한다.
		 * 나머지 단계에서 로그인중이라면 에러 메세지를 출력한다.
		 */
		if (in_array($step,array('agreement','label','cert','insert')) == true) {
			if ($this->isLogged() == true) return $this->getTemplet($configs)->getError('ALREADY_LOGGED');
		} else {
			if ($this->isLogged() == false) return $this->getTemplet($configs)->getError('REQUIRED_LOGIN');
		}
		
		/**
		 * 회원가입폼을 정의한다.
		 */
		$header = PHP_EOL.'<form id="ModuleMemberSignUpForm">'.PHP_EOL;
		$header.= '<input type="text" name="step" value="'.$step.'">'.PHP_EOL;
		$header.= '<input type="text" name="prev" value="'.$prevStep.'">'.PHP_EOL;
		$header.= '<input type="text" name="next" value="'.$nextStep.'">'.PHP_EOL;
		if ($step != 'label') $header.= '<input type="text" name="label" value="'.$label.'">'.PHP_EOL;
		if ($step != 'agreement' && $step != 'insert') $header.= '<input type="text" name="agreements" value="'.$agreements.'">'.PHP_EOL;
		if ($step == 'insert') {
			foreach ($agreements as $agree) $header.= '<input type="text" name="agreements[]" value="'.$agree.'">'.PHP_EOL;
		}
		$header.= '<input type="text" name="templet" value="'.$this->getTemplet($configs)->getName().'">'.PHP_EOL;
		
		$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Member.signup.init();</script>'.PHP_EOL;
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('signup',$values,$header,$footer);
	}
	
	/**
	 * 회원로그인 세션을 만든다.
	 * 탈퇴회원이나, 비활성화 계정의 경우 세션을 만들지 않는다.
	 *
	 * @param int $midx 회원 고유번호
	 * @param boolean $isLogged 로그인여부
	 */
	function login($midx) {
		$member = $this->getMember($midx);
		if ($member->idx == 0 || in_array($member->status,array('LEAVE','DEACTIVE')) == true) return false;
		
		$logged = new stdClass();
		$logged->idx = $midx;
		$logged->time = time();
		$logged->ip = $_SERVER['REMOTE_ADDR'];
		
		$_SESSION['MEMBER_LOGGED'] = Encoder(json_encode($logged));
		$this->logged = $logged;
		
		$this->db()->update($this->table->member,array('latest_login'=>$logged->time))->where('idx',$midx)->execute();
		$activity = $this->addActivity($midx,0,'member','login',array('ip'=>$logged->ip,'browser'=>$_SERVER['HTTP_USER_AGENT']));
		
		return true;
	}
	
	/**
	 * 현재 사용자가 로그인중인지 확인한다.
	 *
	 * @return boolean $isLogged
	 */
	function isLogged() {
		if ($this->logged === false) return false;
		else return true;
	}
	
	/**
	 * 현재 로그인한 사용자가 최고관리자인지 확인한다.
	 *
	 * @return boolean $isAdmin
	 */
	function isAdmin() {
		if ($this->isLogged() == true && $this->getMember()->type == 'ADMINISTRATOR') return true;
		return false;
	}
	
	/**
	 * Check member email and password
	 *
	 * @param string $email
	 * @param string $password (not encrypt, plaintext)
	 * @return boolean $isValidate
	 */
	function isValidate($email,$password) {
		$siteType = $this->IM->getSite()->member;
		if ($siteType == 'MERGE') $domain = '*';
		else $domain = $this->IM->getSite()->domain;
		
		$check = $this->db()->select($this->table->member)->where('domain',$domain)->where('email',$email)->where('status','ACTIVE')->getOne();
		if ($check == null) return false;
		$mHash = new Hash();
		return $mHash->password_validate($password,$check->password) == true ? $check->idx : false;
	}
	
	/**
	 * 회원라벨 정보를 가져온다.
	 *
	 * @param int $idx 라벨고유값
	 * @return object $label 라벨데이터
	 */
	function getLabel($idx) {
		if (isset($this->labels[$idx]) == true) return $this->labels[$idx];
		if ($idx == 0) {
			$label = new stdClass();
			
			$languages = $this->getModule()->getConfig('default_label_title_languages');
			$title = isset($languages->{$this->IM->language}) == true ? $languages->{$this->IM->language} : $this->getModule()->getConfig('default_label_title');
			$label->idx = 0;
			$label->title = $title == 'LANGUAGE_SETTING' ? $this->getText('text/default_label_title') : $title;
			$label->allow_signup = $this->getModule()->getConfig('allow_signup') === true;
			$label->approve_signup = $this->getModule()->getConfig('approve_signup') === true;
			$label->is_change = true;
			$label->is_unique = false;
		} else {
			$label = $this->db()->select($this->table->label)->where('idx',$idx)->getOne();
			if ($label != null) {
				$languages = json_decode($label->languages);
				$label->title = isset($languages->{$this->IM->language}) == true ? $languages->{$this->IM->language} : $label->title;
				$label->allow_signup = $label->allow_signup == 'TRUE';
				$label->approve_signup = $label->approve_signup == 'TRUE';
				$label->is_change = $label->is_change == 'TRUE';
				$label->is_unique = $label->is_unique == 'TRUE';
				
				unset($label->languages,$label->membernum,$label->sort);
			}
		}
		
		$this->labels[$idx] = $label;
		return $this->labels[$idx];
	}
	
	/**
	 * Get Logged member idx
	 *
	 * @return int $midx im_member_table idx, if not logged return 0;
	 */
	function getLogged() {
		return $this->logged == null ? 0 : $this->logged->idx;
	}
	
	/**
	 * Get level from exp
	 *
	 * @param int $exp exp point value
	 * @return object $levelInfo level number, next level exp value
	 */
	function getLevel($exp) {
		$level = $this->db()->select($this->table->level)->where('exp',$exp,'<=')->orderBy('level','desc')->getOne();
		$level->level = $level->next == 0 ? $level->level : $level->level + 1;
		$level->next = $level->next == 0 ? $exp - $level->exp : $level->next - $level->exp;
		$level->exp = $exp - $level->exp;
		
		return $level;
	}
	
	/**
	 * 회원정보를 가져온다.
	 *
	 * @param int $midx(옵션) 회원 고유번호
	 * @param boolean $forceReload(optional) 캐싱되어 있는 회원정보가 아닌, 최신의 회원정보를 요청
	 * @return object $member 회원정보
	 */
	function getMember($midx=null,$forceReload=false) {
		$midx = $midx !== null ? $midx : $this->getLogged();
		if ($forceReload == true || isset($this->members[$midx]) == false) {
			$member = $this->db()->select($this->table->member)->where('idx',$midx)->getOne();
			
			if ($member == null) {
				$member = new stdClass();
				$member->idx = 0;
				$member->code = '';
				$member->type = 'GUEST';
				$member->name = $member->nickname = 'Unknown';
				$member->photo = $this->getModule()->getDir().'/images/nophoto.png';
				$member->nickcon = null;
				$member->level = $this->getLevel(0);
				$member->label = array();
			} else {
				$member->name = $member->name ? $member->name : $member->nickname;
				$member->nickname = $member->nickname ? $member->nickname : $member->name;
				$member->photo = file_exists($this->IM->getAttachmentPath().'/member/'.$midx.'.jpg') == true ? $this->IM->getAttachmentDir().'/member/'.$midx.'.jpg' : $this->getModule()->getDir().'/images/nophoto.png';
				$member->nickcon = file_exists($this->IM->getAttachmentPath().'/member/'.$midx.'.gif') == true ? $this->IM->getAttachmentDir().'/member/'.$midx.'.gif' : null;
				$member->level = $this->getLevel($member->exp);
				$temp = explode('-',$member->birthday);
				$member->birthday = count($temp) == 3 ? $temp[2].'-'.$temp[0].'-'.$temp[1] : '';
				$member->label = $this->getMemberLabel($midx);
			}
			
			$this->members[$midx] = $member;
		}
		
		$this->IM->fireEvent('afterGetData','member','member',$this->members[$midx]);
		return $this->members[$midx];
	}
	
	/**
	 * Get Member name
	 *
	 * @param int $midx(optional) im_member_table idx, if not exists this param, used logged member idx
	 * @param string $replaceName(optional) if not found member information, return this name
	 * @return string $name
	 */
	function getMemberName($midx=null,$replaceName='') {
		if ($midx == null && $this->isLogged() == false) return $replaceName;

		$member = $this->getMember($midx);
		if ($member->idx == null && empty($replaceName) == false) return $replaceName;
		
		return $member->name;
	}
	
	/**
	 * Get Member name
	 *
	 * @param int $midx(optional) im_member_table idx, if not exists this param, used logged member idx
	 * @param boolean $nickcon(optional, default true) return nickcon image or not
	 * @param string $replaceName(optional) if not found member information, return this name
	 * @return string $name name string or nickcon image html tag
	 */
	function getMemberNickname($midx=null,$nickcon=true,$replaceName='') {
		if ($midx === null && $this->isLogged() == false) return $replaceName;

		$member = $this->getMember($midx);
		if ($member->idx == null && empty($replaceName) == false) return $replaceName;
		
		$nickname = '<span data-member-idx="'.$member->idx.'" class="ModuleMemberInfoNickname">';
		if ($nickcon == true && $member->nickcon != null) {
			$nickname.= '<img src="'.$member->nickcon.'" alt="'.$member->nickname.'" title="'.$member->nickname.'">';
		} else {
			$nickname.= $member->nickname;
		}
		$nickname.= '</span>';
		return $nickname;
	}
	
	/**
	 * Get member photo html (img tag)
	 *
	 * @param int $midx(optional) im_member_table idx, if not exists this param, used logged member idx
	 * @param int $width(optional) member photo's width, if not exists this param, used default width (500px)
	 * @param int $height(optional) member photo's height, if not exists this param, used default width (500px)
	 * @return string $photo member's photo img tag
	 */
	function getMemberPhoto($midx=null,$width=null,$height=null) {
		if ($midx === null && $this->isLogged() == false) return '<img src="'.$this->getModule()->getDir().'/images/nophoto.png" alt="unknown">';
		
		$member = $this->getMember($midx);
		
		$photo = '<img data-member-idx="'.$member->idx.'" src="'.$member->photo.'" class="ModuleMemberInfoPhoto" alt="'.$member->nickname.'" style="';
		if ($width !== null) $photo.= 'width:'.$width.';';
		if ($height !== null) $photo.= 'height:'.$height.';';
		$photo.= '">';
		
		return $photo;
	}
	
	/**
	 * Get member label
	 *
	 * @param int $midx(optional) im_member_table idx, if not exists this param, used logged member idx
	 * @param int[] $label(optional) if exists this param and member has this label return true or false
	 * @return int[] $labels member's all label idx (im_member_label_table) or $label param is exists, return boolean
	 */
	function getMemberLabel($midx=null,$label=null) {
		if ($midx === null && $this->isLogged() == false) { // midx not exists and not logged
			if ($label == null) return false; // no member doesn't have label
			else return [];
		}
		$midx = $midx == null ? $this->getLogged() : $midx;
		$label = $label !== null && is_array($label) == false ? array($label) : $label;
		
		$labels = $this->db()->select($this->table->member_label.' m','l.title')->join($this->table->label.' l','m.label=l.idx','LEFT')->where('m.idx',$midx)->get(); // get member's all label
		for ($i=0, $loop=count($labels);$i<$loop;$i++) {
			if ($label !== null && in_array($labels[$i]->title,$label) == true) return true;
			$labels[$i] = $labels[$i]->title;
		}
		
		return $label !== null ? false : $labels;
	}
	
	function getForceLoginUrl($idx,$redirectUrl='') {
		$code = Encoder(json_encode(array('idx'=>$idx,'ip'=>$_SERVER['REMOTE_ADDR'])));
		return 'Member.forceLogin(\''.$code.'\',\''.$redirectUrl.'\');';
	}
	
	function getAccountPage($view=null) {
		$page = new stdClass();
		$page->domain = $this->IM->domain;
		$page->language = $this->IM->language;
		$page->menu = 'account';
		$page->page = $view == null ? 'dashboard' : $view;
		$page->title = $this->getText('account/title');
		$page->type = 'MODULE';
		$page->layout = 'empty';
		$page->context = new stdClass();
		$page->context->module = 'member';
		$page->context->context = 'account';
		$page->context->config = new stdClass();
		$page->description = null;
		$page->image = null;
		
		return $page;
	}
	/*
	function getPhotoEditModal($this->getTemplet($configs)) {
		ob_start();
		
		$this->getTemplet($configs)->getPath() = $this->getTempletPath('modify',$this->getTemplet($configs));
		$this->getTemplet($configs)->getDir() = $this->getTempletDir('modify',$this->getTemplet($configs));
		
		$title = $this->getText('photoEdit/title');
		echo '<form id="ModuleMemberPhotoEditForm">'.PHP_EOL;

		$content = '<style>'.PHP_EOL;
		$content.= '.cropit-image-preview-container {width:250px; height:250px; margin:10px auto; margin-bottom:30px;}'.PHP_EOL;
		$content.= '.cropit-image-preview {background-color:#f8f8f8; background-size:cover; border:1px solid #ccc; border-radius:3px; width:250px; height:250px; cursor:move;}'.PHP_EOL;
		$content.= 'input.cropit-image-input {display:none;}'.PHP_EOL;
		$content.= '.cropit-image-background {opacity:0.3; cursor:auto;}'.PHP_EOL;
		$content.= '.cropit-image-zoom-container {height:20px; width:250px; margin:0 auto; font-size:0px; text-align:center;}'.PHP_EOL;
		$content.= '.cropit-image-zoom-container span {width:20px; height:17px; display:inline-block; vertical-align:middle; line-height:17px; margin-top:3px;}'.PHP_EOL;
		$content.= '.cropit-image-zoom-container span.cropit-image-zoom-out i {font-size:10px; margin-top:2px;}'.PHP_EOL;
		$content.= '.cropit-image-zoom-container span.cropit-image-zoom-in i {font-size:14px;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input {-webkit-appearance:none; border:1px solid white; width:140px; position:relative; z-index:10; vertical-align:middle; margin:0px 10px;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input::-webkit-slider-runnable-track {width:100%; height:5px; background:#ddd; border:none; border-radius:3px;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input::-webkit-slider-thumb {-webkit-appearance:none; border:none; height:16px; width:16px; border-radius:50%; background:#e4232c; margin-top:-5px;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input:focus {outline:none;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input:focus::-webkit-slider-runnable-track {background:#ccc;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input::-moz-range-track {width:100%; height:5px; background:#ddd; border:none; border-radius:3px;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input::-moz-range-thumb {border:none; height:16px; width:16px; border-radius:50%; background:#e4232c;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input:-moz-focusring{outline:1px solid white; outline-offset:-1px;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input::-ms-track {width:300px; height:5px; background:transparent; border-color:transparent; border-width:6px 0; color:transparent;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input::-ms-fill-lower {background:#777; border-radius:10px;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input::-ms-fill-upper {background:#ddd; border-radius:10px;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input::-ms-thumb {border:none; height:16px; width:16px; border-radius:50%; background:#e4232c;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input:focus::-ms-fill-lower {background:#888;}'.PHP_EOL;
		$content.= 'input.cropit-image-zoom-input:focus::-ms-fill-upper {background:#ccc;}'.PHP_EOL;
		$content.= '</style>'.PHP_EOL;
		
		$content.= '<div class="photo-editor">'.PHP_EOL;
		$content.= '	<input type="file" class="cropit-image-input">'.PHP_EOL;
		$content.= '	<div class="cropit-image-preview-container">'.PHP_EOL;
		$content.= '		<div class="cropit-image-preview"></div>'.PHP_EOL;
		$content.= '	</div>'.PHP_EOL;
		
		$content.= '	<div class="cropit-image-zoom-container">'.PHP_EOL;
		$content.= '		<span class="cropit-image-zoom-out"><i class="fa fa-picture-o"></i></span>'.PHP_EOL;
		$content.= '		<input type="range" class="cropit-image-zoom-input">'.PHP_EOL;
		$content.= '		<span class="cropit-image-zoom-in"><i class="fa fa-picture-o"></i></span>'.PHP_EOL;
		$content.= '	</div>';
		$content.= '</div>';
		
		$actionButton = '<button type="button" class="danger" onclick="$(\'input.cropit-image-input\').click();">사진선택</button>';
		
		$IM = $this->IM;
		$Module = $this;
		
		if (file_exists($this->getTemplet($configs)->getPath().'/modal.php') == true) {
			INCLUDE $this->getTemplet($configs)->getPath().'/modal.php';
		} else {
			INCLUDE $this->getModule()->getPath().'/templets/modal.php';
		}
		
		echo '</form>'.PHP_EOL.'<script>Member.photoEdit.init();</script>';
		
		$context = ob_get_contents();
		ob_end_clean();
		
		return $context;
	}
	
	
	function getModifyEmail($this->getTemplet($configs)) {
		ob_start();
		
		if (preg_match('/^@/',$this->getTemplet($configs)) == true) {
			$this->getTemplet($configs)->getPath() = $this->IM->getTempletPath().'/templets/modules/member/templets/modify/'.preg_replace('/^@/','',$this->getTemplet($configs));
			$this->getTemplet($configs)->getDir() = $this->IM->getTempletDir().'/templets/modules/member/templets/modify/'.preg_replace('/^@/','',$this->getTemplet($configs));
		} else {
			$this->getTemplet($configs)->getPath() = $this->getModule()->getPath().'/templets/modify/'.$this->getTemplet($configs);
			$this->getTemplet($configs)->getDir() = $this->getModule()->getDir().'/templets/modify/'.$this->getTemplet($configs);
		}
		
		$title = $this->getText('modifyEmail/title');
		echo '<form name="ModuleMemberModifyEmailForm" onsubmit="return Member.modify.modifyEmail(this);">'.PHP_EOL;
		echo '<input type="hidden" name="confirm" value="TRUE">'.PHP_EOL;
		
		$content = '<div class="message">'.$this->getText('modifyEmail/email').'</div>'.PHP_EOL;
		$content.= '<div class="inputBlock">'.PHP_EOL;
		$content.= '	<input type="text" name="email" class="inputControl" required>'.PHP_EOL;
		$content.= '	<div class="helpBlock" data-default="'.$this->getText('modifyEmail/help/email/default').'"></div>'.PHP_EOL;
		$content.= '</div>'.PHP_EOL;
		
		$content.= '<button type="button" class="btn btnRed" style="margin:5px 0px; width:100%;" data-loading="'.$this->getText('modifyEmail/sending').'" onclick="Member.modify.sendVerifyEmail(this);"><i class="fa fa-check"></i> '.$this->getText('modifyEmail/sendVerifyEmail').'</button>'.PHP_EOL;
		
		$content.= '<div class="message">'.$this->getText('modifyEmail/code').'</div>'.PHP_EOL;
		$content.= '<div class="inputBlock">'.PHP_EOL;
		$content.= '	<input type="text" name="code" class="inputControl" required>'.PHP_EOL;
		$content.= '	<div class="helpBlock" data-default="'.$this->getText('modifyEmail/help/code/default').'"></div>'.PHP_EOL;
		$content.= '</div>'.PHP_EOL;
		
//		$actionButton = '<button type="button" class="danger" onclick="$(\'input.cropit-image-input\').click();">사진선택</button>';
		
		$IM = $this->IM;
		$Module = $this;
		
		if (file_exists($this->getTemplet($configs)->getPath().'/modal.php') == true) {
			INCLUDE $this->getTemplet($configs)->getPath().'/modal.php';
		} else {
			INCLUDE $this->getModule()->getPath().'/templets/modal.php';
		}
		
		echo '</form>'.PHP_EOL;
		
		$context = ob_get_contents();
		ob_end_clean();
		
		return $context;
	}
	*/
	/**
	 * Check social oauth token and scope
	 *
	 * @param string $code (google, facebook, github ... etc)
	 * @param string[] $scope checking exists scope
	 * @param boolean $isOffline checking refresh_token for offline access (not support facebook oauth api)
	 * @param int $midx member idx (if checking current logged user, set null)
	 * @return boolean $existed if user token stored for request scope, return true
	 */
	function checkSocialScope($code,$scope=array(),$isOffline=false,$midx=null) {
		$midx = $midx == null ? $this->getLogged() : $midx;
		if ($midx == null) return false;
		
		$_oauth = $this->db()->select($this->table->social_oauth)->where('domain',$this->IM->domain)->where('code',$code)->getOne();
		if ($_oauth == null) return false;
		$check = $this->db()->select($this->table->social_token)->where('midx',$midx)->where('code',$code)->where('client_id',$_oauth->client_id)->getOne();
		if ($check == null) {
			$this->db()->insert($this->table->social_token,array('midx'=>$midx,'code'=>$code,'client_id'=>$_oauth->client_id))->execute();
			return false;
		}
		$store_scope = $check->scope ? explode(',',$check->scope) : array();
		if (count($scope) > 0 && count(array_diff($scope,$store_scope)) > 0) {
			$request_scope = $check->request_scope ? explode(',',$check->request_scope) : array();
			$scope = array_unique(array_merge($scope,$request_scope));
			$this->db()->update($this->table->social_token,array('request_scope'=>implode(',',$scope)))->where('midx',$midx)->where('code',$code)->where('client_id',$_oauth->client_id)->execute();
			return false;
		}
		if ($isOffline == true) {
			if ($code == 'google' && strlen($check->refresh_token) == 0) return false;
		}
		
		return true;
	}
	
	function getSocialToken($code,$midx=null) {
		$midx = $midx == null ? $this->getLogged() : $midx;
		if ($midx == null) return null;
		
		$_oauth = $this->db()->select($this->table->social_oauth)->where('domain',$this->IM->domain)->where('code',$code)->getOne();
		if ($_oauth == null) return null;
		
		$token = $this->db()->select($this->table->social_token)->where('midx',$midx)->where('code',$code)->where('client_id',$_oauth->client_id)->getOne();
		if ($token !== null) {
			$token->client_secret = $_oauth->client_secret;
		}
		return $token;
	}
	
	function sendPoint($midx,$point,$module='',$code='',$content=array(),$isForce=false) {
		if ($point == 0) return false;
		
		$member = $this->getMember($midx);
		if ($member == null) return false;
		if ($isForce == false && $point < 0 && $member->point < $point * -1) return false;
		
		if (in_array($module,array('board','dataroom','qna','forum')) == true && $point > 0) {
			$check = $this->db()->select($this->table->point,'sum(point) as total_point')->where('midx',$midx)->where('reg_date',time() - 60 * 60 * 24,'>=')->getOne();
			if ($check->total_point > $member->level->level * 5000) return true;
		}
		
		$this->db()->update($this->table->member,array('point'=>$member->point + $point))->where('idx',$member->idx)->execute();
		$this->db()->insert($this->table->point,array('midx'=>$member->idx,'point'=>$point,'module'=>$module,'code'=>$code,'content'=>json_encode($content,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK),'reg_date'=>time()))->execute();
		
		return true;
	}
	
	/**
	 * 이메일 인증과정이 있을 경우 이메일 인증메일을 발송한다.
	 *
	 * @param int $midx 회원고유번호
	 * @param string $email(옵션) 이메일주소, 없을 경우 회원정보의 이메일주소로 발송한다.
	 * @return string 이메일 발송코드 (VERIFIED : 이미 인증됨, SENDING : 인증메일을 발송함, WAITING : 이메일을 발송하고 대기중, FAIL : 이메일 발송실패)
	 */
	function sendVerificationEmail($midx,$email=null) {
		$member = $this->getMember($midx);
		if ($member->idx == 0) return 'FAIL';
		
		$email = $email == null ? $member->email : $email;
		$check = $this->db()->select($this->table->email)->where('midx',$midx)->where('email',$email)->getOne();
		
		$code = GetRandomString(6);
		$isSendEmail = false;
		if ($check == null) {
			$this->db()->insert($this->table->email,array('midx'=>$midx,'email'=>$email,'code'=>$code,'reg_date'=>time(),'status'=>'SENDING'))->execute();
			$isSendEmail = true;
		} elseif ($check->status == 'CANCELED' || ($check->status == 'SENDING' && $check->reg_date < time() - 300)) {
			$this->db()->update($this->table->email,array('code'=>$code,'reg_date'=>time(),'status'=>'SENDING'))->where('midx',$midx)->where('email',$email)->execute();
			$isSendEmail = true;
		} elseif ($check->status == 'VERIFIED') {
			return 'VERIFIED';
		} else {
			return 'WAITING';
		}
		
		if ($isSendEmail == true) {
			/**
			 * @todo 메일발송부분 언어팩 설정
			 */
			$subject = '['.$this->IM->getSiteTitle().'] 이메일주소 확인메일';
			$content = '회원님이 입력하신 이메일주소가 유효한 이메일주소인지 확인하기 위한 이메일입니다.<br>회원가입하신적이 없거나, 최근에 이메일주소변경신청을 하신적이 없다면 본 메일은 무시하셔도 됩니다.';
			if ($member->status == 'VERIFYING') {
				$content.= '<br><br>아래의 인증코드 6자리를 인증번호 확인란에 입력하시거나, 인증링크를 클릭하여 회원가입을 완료할 수 있습니다.';
			} else {
				$content.= '<br><br>아래의 인증코드 6자리를 인증번호 확인란에 입력하여 이메일주소변경을 완료할 수 있습니다.';
			}
			$content.= '<br><br>인증코드 : <b>'.$code.'</b>';
			
			if ($member->verified == 'FALSE') {
				$link = $this->IM->getModuleUrl('member','verification',urlencode(Encoder(json_encode(array('midx'=>$midx,'email'=>$email,'code'=>$code)))),true);
				$content.= '<br>인증주소 : <a href="'.$link.'" target="_blank">'.$link.'</a>';
			}
			$content.= '<br><br>본 메일은 발신전용메일로 회신되지 않습니다.<br>감사합니다.';
			
			$this->IM->getModule('email')->addTo($email,$member->name)->setSubject($subject)->setContent($content)->send();
		}
		
		if ($member->verified != 'TRUE' && $member->email != $email) {
			$this->db()->update($this->table->member,array('email'=>$email))->where('idx',$member->idx)->execute();
		}
		
		return 'SENDING';
	}
	
	/**
	 * Add member label
	 *
	 * @param int $midx member idx
	 * @param int $label label idx
	 * @return boolean $result
	 */
	function addMemberLabel($midx,$label) {
		$label = $this->db()->select($this->table->label)->where('idx',$label)->getOne();
		
		if ($label !== null && $this->db()->select($this->table->member_label)->where('idx',$midx)->where('label',$label->idx)->has() == false) {
			$this->db()->insert($this->table->member_label,array('idx'=>$midx,'label'=>$label->idx,'reg_date'=>time()))->execute();
			$membernum = $this->db()->select($this->table->member_label)->where('label',$label->idx)->count();
			$this->db()->update($this->table->label,array('membernum'=>$membernum))->where('idx',$label->idx)->execute();
			
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 회원활동기록을 추가한다.
	 *
	 * @param int $midx 회원 고유번호
	 * @param int $exp 활동에 따른 경험치
	 * @param string $module 활동이 발생한 모듈명
	 * @param string $code 활동코드
	 * @param object[] $content 활동에 따른 정보
	 * @return int $activity 활동 고유번호
	 */
	function addActivity($midx,$exp,$module,$code,$content=array()) {
		$member = $this->getMember($midx);
		if ($member->idx == 0) return false;
		
		$idx = $this->db()->insert($this->table->activity,array('midx'=>$member->idx,'module'=>$module,'code'=>$code,'content'=>json_encode($content,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK),'exp'=>$exp,'reg_date'=>time()))->execute();
		if ($exp > 0) $this->db()->update($this->table->member,array('exp'=>$member->exp + $exp))->where('idx',$member->idx)->execute();
		
		return $idx;
	}
	
	/**
	 * 회원가입 / 회원수정 필드데이터를 가공한다.
	 *
	 * @param object $rawData $this->table->signup 테이블의 RAW 데이터
	 * @return object $field
	 */
	function parseInputField($rawData) {
		$field = $rawData;
		
		$title_languages = json_decode($field->title_languages);
		$field->title = isset($title_languages->{$this->IM->language}) == true ? $title_languages->{$this->IM->language} : $field->title;
		unset($field->title_languages);
		
		$help_languages = json_decode($field->help_languages);
		$field->help = isset($help_languages->{$this->IM->language}) == true ? $help_languages->{$this->IM->language} : $field->help;
		unset($field->help_languages);
		
		$configs = json_decode($field->configs);
		unset($field->configs);
		
		if ($field->type == 'etc') {
			$field->name = $field->name;
			$field->is_extra = true;
		} else {
			$field->title = $field->title == 'LANGUAGE_SETTING' ? $this->getText('text/'.$field->name) : $field->title;
			$field->help = $field->help == 'LANGUAGE_SETTING' ? $this->getText('signup/form/'.$field->name.'_help') : $field->help;
			$field->is_extra = false;
		}
		
		if (in_array($field->input,array('select','radio','checkbox')) == true) {
			$options = $configs->options;
			$field->options = new stdClass();
			foreach ($options as $option) {
				$field->options->{$option->value} = isset($option->languages->{$this->IM->language}) == true ? $option->languages->{$this->IM->language} : $option->display;
			}
			
			if ($field->input == 'checkbox') $field->max = $configs->max;
		}
		
		$field->is_required = $field->is_required == 'TRUE';
		
		return $field;
	}
	
	/**
	 * 회원가입 / 회원수정 필드를 출력하기 위한 함수
	 *
	 * @param object $field 입력폼 데이터
	 * @param boolean $isModify 정보수정 필드인지 여부
	 * @param string $html
	 */
	function getInputFieldHtml($field,$mode) {
		$html = array();
		
		$isSignUp = $mode == 'signup';
		
		if ($this->isLogged() == false) $value = null;
		else $value = $this->getMember();
		
		if ($field->input == 'system') {
			/**
			 * 이메일
			 */
			if ($field->name == 'email' || $field->name == 'email_verification_email') {
				array_push($html,
					'<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<input type="email" name="'.$field->name.'"'.($value !== null && isset($value->email) == true ? ' value="'.GetString($value->email,'input').'"' : '').'>',
					'</div>'
				);
			}
			
			/**
			 * 패스워드
			 */
			if ($field->name == 'password') {
				array_push($html,
					'<div data-role="inputset" data-name="password" data-default="'.$field->help.'">',
						'<div data-role="input">',
							'<input type="password" name="password" placeholder="'.$this->getText('signup/form/password').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="password" name="password_confirm" placeholder="'.$this->getText('signup/form/password_confirm').'" data-error="'.$this->getErrorText('NOT_MATCHED_PASSWORD_CONFIRM').'">',
						'</div>',
					'</div>'
				);
			}
			
			/**
			 * 실명, 닉네임
			 */
			if ($field->name == 'name' || $field->name == 'nickname') {
				array_push($html,
					'<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<input type="text" name="'.$field->name.'">',
					'</div>'
				);
			}
			
			/**
			 * 생일
			 */
			if ($field->name == 'birthday') {
				array_push($html,
					'<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<input type="date" name="'.$field->name.'">',
					'</div>'
				);
			}
			
			/**
			 * 전화번호 및 휴대전화번호
			 */
			if ($field->name == 'telephone' || $field->name == 'cellphone') {
				array_push($html,
					'<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<input type="tel" name="'.$field->name.'">',
					'</div>'
				);
			}
			
			/**
			 * 홈페이지
			 */
			if ($field->name == 'homepage') {
				array_push($html,
					'<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<input type="url" name="'.$field->name.'">',
					'</div>'
				);
			}
			
			/**
			 * 성별
			 */
			if ($field->name == 'gender') {
				array_push($html,
					'<div data-role="inputset" class="inline" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<div data-role="input">',
							'<label><input type="radio" name="'.$field->name.'" value="MALE">'.$this->getText('text/male').'</label>',
						'</div>',
						'<div data-role="input">',
							'<label><input type="radio" name="'.$field->name.'" value="FEMALE">'.$this->getText('text/female').'</label>',
						'</div>',
					'</div>'
				);
			}
			
			/**
			 * 주소
			 */
			if ($field->name == 'address') {
				array_push($html,
					'<div data-role="inputset" class="block" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_zipcode" placeholder="'.$this->getText('text/zipcode').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_address1" placeholder="'.$this->getText('text/address1').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_address2" placeholder="'.$this->getText('text/address2').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_city" placeholder="'.$this->getText('text/city').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_state" placeholder="'.$this->getText('text/state').'">',
						'</div>',
					'</div>'
				);
			}
			
			/**
			 * 이메일주소 인증 코드
			 */
			if ($field->name == 'email_verification_code') {
				array_push($html,
					'<div data-role="inputset" class="flex" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<div data-role="input" class="flex">',
							'<input type="url" name="'.$field->name.'">',
						'</div>',
						'<div data-role="input">',
							'<button type="button" data-action="resend">'.$this->getText('button/resend_email_verification_code').'</button>',
						'</div>',
					'</div>'
				);
			}
		} else {
			/**
			 * 옵션박스
			 */
			if ($field->input == 'select') {
				$html[] = '<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">';
				$html[] = '<select name="'.$field->name.'">';
				foreach ($field->options as $value=>$display) {
					$html[] = '<option value="'.$value.'">'.$display.'</option>';
				}
				$html[] = '</select>';
				$html[] = '</div>';
			}
			
			
			/**
			 * 체크박스
			 */
			if ($field->input == 'checkbox') {
				$html[] = '<div data-role="inputset" data-name="'.$field->name.'" class="inline" data-default="'.$field->help.'">';
				foreach ($field->options as $value=>$display) {
					array_push($html,
						'<div data-role="input">',
							'<label><input type="checkbox" name="'.$field->name.'[]" value="'.$value.'">'.$display.'</label>',
						'</div>'
					);
				}
				$html[] = '</div>';
			}
			
			/**
			 * 라디오버튼
			 */
			if ($field->input == 'radio') {
				$html[] = '<div data-role="inputset" data-name="'.$field->name.'" class="inline" data-default="'.$field->help.'">';
				foreach ($field->options as $value=>$display) {
					array_push($html,
						'<div data-role="input">',
							'<label><input type="radio" name="'.$field->name.'" value="'.$value.'">'.$display.'</label>',
						'</div>'
					);
				}
				$html[] = '</div>';
			}
			
			/**
			 * 텍스트 / 패스워드 / 이메일 / URL / 전화번호 / 날짜필드
			 */
			if (in_array($field->input,array('text','password','email','date','tel')) == true) {
				array_push($html,
					'<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<input type="'.$field->input.'" name="'.$field->name.'">',
					'</div>'
				);
			}
			
			/**
			 * 주소
			 */
			if ($field->input == 'address') {
				array_push($html,
					'<div data-role="inputset" class="block" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_zipcode" placeholder="'.$this->getText('text/zipcode').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_address1" placeholder="'.$this->getText('text/address1').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_address2" placeholder="'.$this->getText('text/address2').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_city" placeholder="'.$this->getText('text/city').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_state" placeholder="'.$this->getText('text/state').'">',
						'</div>',
					'</div>'
				);
			}
			
			/**
			 * 긴글 텍스트
			 */
			if ($field->input == 'textarea') {
				array_push($html,
					'<div data-role="input" data-default="'.$field->help.'">',
						'<textarea name="'.$field->name.'"></textarea>',
					'</div>'
				);
			}
		}
		
		return implode(PHP_EOL,$html);
	}
	
	/**
	 * 회원가입 / 정보수정의 데이터가 유효한지 파악하여, DB처리를 위한 배열 및 에러처리를 위한 배열을 만든다.
	 *
	 * @param string $mode 회원가입(signup) 또는 정보수정(modify)
	 * @param object/array $data 사용자가 입력한 데이터
	 * @param &array $insert(옵션) DB처리를 위한 배열 포인터
	 * @param &array $errors(옵션) 에러처리를 위한 배열 포인터
	 * @param boolean $isValid 유효한지 여부
	 */
	function isValidInsertData($mode,$data,&$insert=null,&$errors=null) {
		if (is_array($data) == true) $data = (object)$data;
		
		$isSignUp = $mode == 'signup';
		$siteType = $this->IM->getSite()->member;
		$label = isset($data->label) == true ? $data->label : 0;
		
		if ($insert != null) $insert['extras'] = array();
		
		$success = true;
		$forms = $this->db()->select($this->table->signup)->where('label',array(0,$label),'IN')->get();
		for ($i=0, $loop=count($forms);$i<$loop;$i++) {
			if ($forms[$i]->name == 'agreement' || $forms[$i]->name == 'privacy') continue;
			$field = $this->parseInputField($forms[$i]);
			
			$field->value = null;
			$field->error = null;
			
			if ($field->input == 'system') {
				switch ($field->name) {
					case 'email' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (CheckEmail($field->value) == true) {
								$check = $this->db()->select($this->table->member)->where('email',$field->value);
								if ($isSignUp == false && $this->isLogged() == true) $check->where('idx',$this->getLogged(),'!=');
								
								$checkAdmin = $check->copy();
								
								if ($siteType == 'UNIVERSAL') $check->where('domain','*');
								else $check->where('domain',$this->IM->domain);
								
								$checkAdmin->where('type','ADMINISTRATOR');
								
								if ($check->has() == true || $checkAdmin->has() == true) {
									$field->error = $this->getErrorText('DUPLICATED');
								}
							} else {
								$field->error = $this->getErrorText('INVALID_EMAIL');
							}
						}
						
						break;
						
					case 'name' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (CheckName($field->value) == false) {
								$field->error = $this->getErrorText('INVALID_USERNAME');
							}
						}
						
						break;
					
					case 'nickname' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (CheckNickname($field->value) == true) {
								$check = $this->db()->select($this->table->member)->where('nickname',$field->value);
								if ($isSignUp == false && $this->isLogged() == true) $check->where('idx',$this->getLogged(),'!=');
								
								$checkAdmin = $check->copy();
								
								if ($siteType == 'UNIVERSAL') $check->where('domain','*');
								else $check->where('domain',$this->IM->domain);
								
								$checkAdmin->where('type','ADMINISTRATOR');
								
								if ($check->has() == true || $checkAdmin->has() == true) {
									$field->error = $this->getErrorText('DUPLICATED');
								}
							} else {
								$field->error = $this->getErrorText('INVALID_NICKNAME');
							}
						}
						
						break;
						
					case 'password' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (strlen($field->value) < 6) {
								$field->error = $this->getErrorText('TOO_SHORT_PASSWORD');
							} elseif (isset($data->password_confirm) == true && $field->value != $data->password_confirm) {
								$field->error = $this->getErrorText('NOT_MATCHED_PASSWORD_CONFIRM');
							} else {
								$mHash = new Hash();
								$field->value = $mHash->password_hash($field->value);
							}
						}
						
						break;
						
					case 'birthday' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$field->value) == true) {
								$field->value = date('m-d-Y',strtotime($field->value));
							} else {
								$field->error = $this->getErrorText('INVALID_DATE');
							}
						}
						
						break;
						
					case 'address' :
						if (isset($data->{$field->name.'_zipcode'}) == true && $data->{$field->name.'_zipcode'} && isset($data->{$field->name.'_address1'}) == true && $data->{$field->name.'_address1'} && isset($data->{$field->name.'_address2'}) == true && $data->{$field->name.'_address2'}) {
							if (is_string($data->{$field->name.'_zipcode'}) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (is_string($data->{$field->name.'_address1'}) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (is_string($data->{$field->name.'_address2'}) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (isset($data->{$field->name.'_state'}) == true && is_string($data->{$field->name.'_state'}) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (isset($data->{$field->name.'_city'}) == true && is_string($data->{$field->name.'_city'}) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							$field->value = json_encode(
								array(
									'zipcode'=>$data->{$field->name.'_zipcode'},
									'address1'=>$data->{$field->name.'_address1'},
									'address2'=>$data->{$field->name.'_address2'},
									'state'=>isset($data->{$field->name.'_state'}) == true ? $data->{$field->name.'_state'} : '',
									'city'=>isset($data->{$field->name.'_city'}) == true ? $data->{$field->name.'_city'} : ''
								),
								JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK
							);
						} else {
							$field->value = null;
						}
						
						break;
						
					case 'gender' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (in_array($field->value,array('MALE','FEMALE')) == false) {
								$field->error = $this->getErrorText('INVALID_GENDER');
							}
						}
						
						break;
						
					case 'homepage' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (preg_match('/^(http|https):\/\//',$field->value) == false) {
								$field->value = 'http://'.$field->value;
							}
						}
						
						break;
						
					default :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
						}
						
						break;
				}
				
				if ($field->error == null) {
					if ($field->value !== null && strlen($field->value) > 0) {
						if (is_array($insert) == true) $insert[$field->name] = $field->value;
					} elseif ($field->is_required == true) {
						$success = false;
						if (is_array($errors) == true) $errors[$field->name] = $this->getErrorText('REQUIRED');
					}
				} else {
					$success = false;
					if (is_array($errors) == true) $errors[$field->name] = $field->error;
				}
			} else {
				switch ($field->input) {
					case 'checkbox' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null && ((is_array($field->value) == true && count($field->value) > 0) || (is_string($field->value) == true && strlen($field->value) > 0))) {
							if (is_array($field->value) == false) $field->value = array($field->value);
							
							foreach ($field->value as $selected) {
								if (isset($field->options->$selected) == false) {
									$field->error = $this->getErrorText('INVALID_SELECT_OPTION');
								}
							}
						} else {
							$field->value = null;
						}
						
						break;
						
					case 'select' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (isset($field->options->{$field->value}) == false) {
								$field->error = $this->getErrorText('INVALID_SELECT_OPTION');
							}
						}
						
						break;
						
					case 'radio' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (isset($field->options->{$field->value}) == false) {
								$field->error = $this->getErrorText('INVALID_SELECT_OPTION');
							}
						}
						
						break;
						
					case 'date' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$field->value) == true) {
								$field->value = date('m-d-Y',strtotime($field->value));
							} else {
								$field->error = $this->getErrorText('INVALID_DATE');
							}
						}
						
						break;
						
					case 'url' :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
							
							if (preg_match('/^(http|https):\/\//',$field->value) == false) {
								$field->value = 'http://'.$field->value;
							}
						}
						
						break;
						
					default :
						$field->value = isset($data->{$field->name}) == true && $data->{$field->name} ? $data->{$field->name} : null;
						
						if ($field->value !== null) {
							if (is_string($field->value) == false) {
								$field->error = $this->getErrorText('STRING_TYPE_ONLY');
								break;
							}
						}
						
						break;
				}
				
				if ($field->error == null) {
					if ($field->value !== null) {
						if (is_array($insert) == true) $insert['extras'][$field->name] = $field->value;
					} elseif ($field->is_required == true) {
						$success = false;
						if (is_array($errors) == true) $errors[$field->name] = $this->getErrorText('REQUIRED');
					}
				} else {
					$success = false;
					if (is_array($errors) == true) $errors[$field->name] = $field->error;
				}
			}
		}
		
		if ($insert !== null) {
			if (isset($insert['name']) == false && isset($insert['nickname']) == true) $insert['name'] = $insert['nickname'];
			if (isset($insert['nickname']) == false && isset($insert['name']) == true) $insert['nickname'] = $insert['name'];
			$insert['extras'] = count($insert['extras']) > 0 ? json_encode($insert['extras'],JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) : '{}';
		}
		
		return $success;
	}
	
	/**
	 * 현재 모듈에서 처리해야하는 요청이 들어왔을 경우 처리하여 결과를 반환한다.
	 * 소스코드 관리를 편하게 하기 위해 각 요쳥별로 별도의 PHP 파일로 관리한다.
	 * 작업코드가 '@' 로 시작할 경우 사이트관리자를 위한 작업으로 최고관리자 권한이 필요하다.
	 *
	 * @param string $action 작업코드
	 * @return object $results 수행결과
	 * @see /process/index.php
	 */
	function doProcess($action) {
		$results = new stdClass();
		
		/**
		 * 모듈의 process 폴더에 $action 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->getModule()->getPath().'/process/'.$action.'.php') == true) {
			INCLUDE $this->getModule()->getPath().'/process/'.$action.'.php';
		}
		
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('afterDoProcess','member',$action,$values,$results);
		
		return $results;
	}
}
?>