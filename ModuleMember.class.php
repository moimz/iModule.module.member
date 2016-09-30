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
	 * 에러 발생여부
	 */
	private $isError = false;
	
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
		$this->IM->addHeadResource('style',$this->Module->getDir().'/styles/style.css');
		$this->IM->addHeadResource('script',$this->Module->getDir().'/scripts/script.js');
		
		/**
		 * SESSION 을 검색하여 현재 로그인중인 사람의 정보를 구한다.
		 */
		$this->logged = Request('MEMBER_LOGGED','session') != null && Decoder(Request('MEMBER_LOGGED','session')) != false ? json_decode(Decoder(Request('MEMBER_LOGGED','session'))) : false;
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
		return $this->IM->db($this->Module->getInstalled()->database);
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
		if (is_file($this->Module->getPath().'/api/'.$api.'.php') == true) {
			INCLUDE $this->Module->getPath().'/api/'.$api.'.php';
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
				$autoActive = $this->Module->getConfig('autoActive');
				$allowSignup = $this->Module->getConfig('allowSignup');
			} else {
				$label = $this->db()->select($this->table->label)->where('idx',$label)->getOne();
				if ($label == null) {
					$autoActive = $allowSignup = false;
					$errors['label'] = $this->getLanguage('error/not_found');
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
						$insert['email'] = CheckEmail(Request('email')) == true ? Request('email') : $errors['email'] = $this->getLanguage('signup/help/email/error');
						if ($this->db()->select($this->table->member)->where('email',$insert['email'])->where('domain',$siteType == 'MERGE' ? '*' : $this->IM->domain)->has() == true || $this->db()->select($this->table->member)->where('email',$insert['email'])->where('type','ADMINISTRATOR')->has() == true) {
							$errors['email'] = $this->getLanguage('signup/help/email/duplicated');
						}
						break;
					
					case 'password' :
						$insert['password'] = strlen(Request('password')) >= 4 ? Request('password') : $errors['password'] = $this->getLanguage('signup/help/password/error');
						if (strlen(Request('password')) < 4 || Request('password') != Request('password_confirm')) {
							$errors['password_confirm'] = $this->getLanguage('signup/help/password_confirm/error');
						}
						break;
						
					case 'name' :
						$insert['name'] = CheckNickname(Request('name')) == true ? Request('name') : $errors['name'] = $this->getLanguage('signup/help/name/error');
						break;
						
					case 'nickname' :
						$insert['nickname'] = CheckNickname(Request('nickname')) == true ? Request('nickname') : $errors['nickname'] = $this->getLanguage('signup/help/nickname/error');
						if ($this->db()->select($this->table->member)->where('nickname',$insert['nickname'])->where('domain',$siteType == 'MERGE' ? '*' : $this->IM->domain)->has() == true || $this->db()->select($this->table->member)->where('nickname',$insert['nickname'])->where('type','ADMINISTRATOR')->has() == true) {
							$errors['nickname'] = $this->getLanguage('signup/help/nickname/duplicated');
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
								$errors['telephone'] = $this->getLanguage('error/required');
							}
						}
						
						if ($forms[$i]->allow_blank == 'FALSE' && strlen($insert['telephone']) < 10) $errors['telephone'] = $this->getLanguage('error/required');
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
								$errors['cellphone'] = $this->getLanguage('error/required');
							}
						}
						
						if ($forms[$i]->allow_blank == 'FALSE' && strlen($insert['cellphone']) < 10) $errors['cellphone'] = $this->getLanguage('error/required');
						break;
						
					case 'birthday' :
						$birthday = Request('birthday') ? strtotime(Request('birthday')) : 0;
						$insert['birthday'] = $birthday > 0 ? date('m-d-Y',$birthday) : '';
						
						if ($forms[$i]->allow_blank == 'FALSE' && !$insert['birthday']) $errors['birthday'] = $this->getLanguage('error/required');
						break;
						
					case 'gender' :
						$insert['gender'] = in_array(Request('gender'),array('MALE','FEMALE')) == true ? Request('gender') : '';
						
						if ($forms[$i]->allow_blank == 'FALSE' && !$insert['gender']) $errors['gender'] = $this->getLanguage('error/required');
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
				$insert['status'] = in_array('verify',$this->Module->getConfig('signupStep')) === true ? 'VERIFYING' : ($autoActive == true ? 'ACTIVE' : 'WAITING');
				$insert['point'] = $this->Module->getConfig('signupPoint');
				$insert['reg_date'] = time();
				
				$idx = $this->db()->insert($this->table->member,$insert)->execute();
				if ($label != 0) {
					$this->db()->insert($this->table->member_label,array('idx'=>$idx,'label'=>$label,'reg_date'=>$insert['reg_date']))->execute();
					$membernum = $this->db()->select($this->table->member_label)->where('label',$label)->count();
					$this->db()->update($this->table->label,array('membernum'=>$membernum))->where('idx',$label)->execute();
				}
				
				if ($idx !== false) {
					$data->success = true;
					if (in_array('verify',$this->Module->getConfig('signupStep')) === true) $this->sendVerifyEmail($idx);
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
		if ($count > 0) $push->content = $this->getLanguage('push/'.$code.'s');
		else $push->content = $this->getLanguage('push/'.$code);
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
		$Module = $this->Module;
		
		ob_start();
		INCLUDE $this->Module->getPath().'/admin/configs.php';
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
		$Module = $this->Module;
		
		ob_start();
		INCLUDE $this->Module->getPath().'/admin/index.php';
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
		$contexts = $this->getLanguage('admin/contexts');
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
			$label->title = $this->getLanguage('text/label');
			$label->name = 'label';
			$label->type = 'select';
			$label->data = array();
			$label->data[] = array(0,$this->getLanguage('text/no_label'));
			$labels = $this->db()->select($this->table->label,'idx,title')->get();
			for ($i=0, $loop=count($labels);$i<$loop;$i++) {
				$label->data[] = array($labels[$i]->idx,$labels[$i]->title);
			}
			$label->value = 0;
			$configs[] = $label;
		}
		
		$templet = new stdClass();
		$templet->title = $this->IM->getLanguage('text/templet');
		$templet->name = 'templet';
		$templet->type = 'select';
		$templet->data = array();
		
		$templet->data[] = array('#',$this->getLanguage('admin/configs/form/default_setting'));
		
		$templets = $this->Module->getTemplets();
		for ($i=0, $loop=count($templets);$i<$loop;$i++) {
			$templet->data[] = array($templets[$i]->name,$templets[$i]->title.' ('.$templets[$i]->dir.')');
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
	function getLanguage($code,$replacement=null) {
		if ($this->lang == null) {
			if (file_exists($this->Module->getPath().'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->Module->getPath().'/languages/'.$this->IM->language.'.json'));
				if ($this->IM->language != $this->Module->getPackage()->language) {
					$this->oLang = json_decode(file_get_contents($this->Module->getPath().'/languages/'.$this->Module->getPackage()->language.'.json'));
				}
			} else {
				$this->lang = json_decode(file_get_contents($this->Module->getPath().'/languages/'.$this->Module->getPackage()->language.'.json'));
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
		elseif (in_array(reset($temp),array('text','button','action')) == true) return $this->IM->getLanguage($code,$replacement);
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
	function getErrorMessage($code,$value=null,$isRawData=false) {
		$message = $this->getLanguage('error/'.$code,$code);
		if ($message == $code) return $this->IM->getErrorMessage($code,$value,null,$isRawData);
		
		$description = null;
		switch ($code) {
			case 'NOT_ALLOWED_SIGNUP' :
				if ($value != null && is_object($value) == true) {
					$description = $value->title;
				}
				break;
			
			default :
				if (is_object($value) == false && $value) $description = $value;
		}
		
		$error = new stdClass();
		$error->message = $message;
		$error->description = $description;
		
		if ($isRawData === true) return $error;
		else return $this->IM->getErrorMessage($error);
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
		return $this->getLanguage('admin/contexts/'.$context);
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
	 * @param string $templet 템플릿명
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
		$templet = $templet == '#' ? $this->Module->getConfig('templet') : $templet;
		$package = $this->Module->getTempletPackage($templet);
		
		return $package == null ? $templet : $package;
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
		 * 이벤트를 발생시킨다.
		 */
		$this->IM->fireEvent('beforeGetContext',$this->Module->getName(),$context,$configs,null);
		
		/**
		 * 컨텍스트 컨테이너를 설정한다.
		 */
		$html = PHP_EOL.'<!-- MEMBER MODULE -->'.PHP_EOL.'<div data-role="context" data-type="module" data-module="'.$this->Module->getName().'">'.PHP_EOL;
		
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
		
		/**
		 * 에러가 발생했다면, 에러메세지를 반환한다.
		 */
		if ($this->isError !== false) return $this->isError;
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		$this->IM->fireEvent('afterGetContext',$this->Module->getName(),$context,$configs,null,$html);
		
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
		 * 에러가 발생했다면, 화면구성을 중단한다.
		 */
		if ($this->isError === true) return;
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		$this->IM->fireEvent('beforeGetContext',$this->Module->getName(),'header',$configs,null);
		
		/**
		 * 템플릿 정보를 가져온다.
		 */
		$templet = $this->getTemplet($configs);
		if (is_object($templet) == false) return $this->printError('NOT_FOUND_TEMPLET',$templet);
		$templetPath = $templet->path;
		$templetDir = $templet->dir;
		
		/**
		 * 템플릿에 style 파일이나, script 파일이 있을 경우 불러온다.
		 */
		if (is_file($templetPath.'/scripts/script.js') == true) {
			$this->IM->addHeadResource('script',$templetDir.'/scripts/script.js');
		}
		
		if (is_file($templetPath.'/styles/style.js') == true) {
			$this->IM->addHeadResource('styles',$templetDir.'/styles/style.css');
		}
		
		$html = '';
		
		/**
		 * 템플릿에 헤더파일이 있을 경우 출력한다.
		 */
		if (is_file($templetPath.'/header.php') == true) {
			ob_start();
			
			/**
			 * 템플릿파일에서 iModule 코어와, 현재 모듈에 접근하기 위한 변수설정
			 */
			$IM = $this->IM;
			$Module = $this;
			
			INCLUDE $templetPath.'/header.php';
			$html.= ob_get_contents();
			ob_clean();
		}
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		$this->IM->fireEvent('afterGetContext',$this->Module->getName(),'header',null,null,$html);
		
		return $html;
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
		 * 에러가 발생했다면, 화면구성을 중단한다.
		 */
		if ($this->isError === true) return;
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		$this->IM->fireEvent('beforeGetContext',$this->Module->getName(),'footer',$configs,null);
		
		/**
		 * 템플릿 정보를 가져온다.
		 */
		$templet = $this->getTemplet($configs);
		if (is_object($templet) == false) return $this->printError('NOT_FOUND_TEMPLET',$templet);
		$templetPath = $templet->path;
		$templetDir = $templet->dir;
		
		$html = '';
		
		/**
		 * 템플릿에 푸터파일이 있을 경우 출력한다.
		 */
		if (is_file($templetPath.'/footer.php') == true) {
			/**
			 * 템플릿파일에서 iModule 코어와, 현재 모듈에 접근하기 위한 변수설정
			 */
			$IM = $this->IM;
			$Module = $this;
			
			INCLUDE $templetPath.'/footer.php';
			$html = ob_get_contents();
			ob_clean();
		}
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		$this->IM->fireEvent('afterGetContext',$this->Module->getName(),'footer',null,null,$html);
		
		return $html;
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
		$error = $this->getErrorMessage($code,$value,true);
		return $this->IM->getError($error);
	}
	
	/**
	 * 에러메세지를 출력하고 컨텍스트 렌더링을 중단한다.
	 *
	 * @param string $code 에러코드 (에러코드는 iModule 코어에 의해 해석된다.)
	 * @param object $value 에러코드에 따른 에러값
	 * @return $html 에러메세지 HTML
	 */
	function printError($code,$value=null) {
		/**
		 * iModule 코어를 통해 에러메세지를 구성한다.
		 */
		$this->isError = $this->getError($code,$value,true);
		return '';
	}
	
	/**
	 * 회원가입 컨텍스트를 가져온다.
	 *
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getSignUpContext($configs) {
		/**
		 * 에러가 발생했다면, 화면구성을 중단한다.
		 */
		if ($this->isError === true) return;
		
		/**
		 * 템플릿 정보를 가져온다.
		 * 템플릿파일이 없으면 에러를 반환한다.
		 */
		$templet = $this->getTemplet($configs);
		if (is_object($templet) == false) return $this->printError('NOT_FOUND_TEMPLET',$templet);
		$templetPath = $templet->path;
		$templetDir = $templet->dir;
		if (is_file($templetPath.'/signup.php') == false) return $this->printError('NOT_FOUND_TEMPLET_FILE',$templetDir.'/signup.php');
		
		$label = isset($configs->label) == true ? $configs->label : null;
		$label = $label == null ? Request('label') : $label;
		$label = $label == null || is_numeric($label) == false ? 0 : $label;
		
		/**
		 * 선택한 회원라벨의 회원가입이 가능한지 확인한다.
		 */
		if ($this->getLabel($label) == null || $this->getLabel($label)->allow_signup == false) return $this->printError('NOT_ALLOWED_SIGNUP',$this->getLabel($label));
		
		/**
		 * 모듈설정에 정의된 회원가입절차를 가져온다.
		 */
		$steps = $this->Module->getConfig('signup_step');
		
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
				$form = $this->db()->select($this->table->signup)->where('label',$label)->where('name','agreement')->getOne();
				$form = $form == null && $label != 0 ? $this->db()->select($this->table->signup)->where('label',0)->where('name','agreement')->getOne() : $form;
				
				if ($form != null) {
					$title_languages = json_decode($form->title_languages);
					$help_languages = json_decode($form->help_languages);
					$configs = json_decode($form->configs);
					
					$title = isset($title_languages->{$this->IM->language}) == true ? $title_languages->{$this->IM->language} : $form->title;
					$help = isset($help_languages->{$this->IM->language}) == true ? $help_languages->{$this->IM->language} : $form->help;
					
					$agreement = new stdClass();
					$agreement->title = $title == 'LANGUAGE_SETTING' ? $this->getLanguage('text/agreement') : $title;
					$agreement->content = $configs->content;
					$agreement->help = $help == 'LANGUAGE_SETTING' ? $this->getLanguage('signup/agree') : $help;
					$agreement->value = 'agreement-'.$form->label;
				} else {
					$agreement = null;
				}
				
				/**
				 * 선택 회원라벨의 개인정보보호정책을 가져온다.
				 */
				$form = $this->db()->select($this->table->signup)->where('label',$label)->where('name','privacy')->getOne();
				$form = $form == null && $label != 0 ? $this->db()->select($this->table->signup)->where('label',0)->where('name','privacy')->getOne() : $form;
				
				if ($form != null) {
					$title_languages = json_decode($form->title_languages);
					$help_languages = json_decode($form->help_languages);
					$configs = json_decode($form->configs);
					
					$title = isset($title_languages->{$this->IM->language}) == true ? $title_languages->{$this->IM->language} : $form->title;
					$help = isset($help_languages->{$this->IM->language}) == true ? $help_languages->{$this->IM->language} : $form->help;
					
					$privacy = new stdClass();
					$privacy->title = $title == 'LANGUAGE_SETTING' ? $this->getLanguage('text/agreement') : $title;
					$privacy->content = $configs->content;
					$privacy->help = $help == 'LANGUAGE_SETTING' ? $this->getLanguage('signup/agree') : $help;
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
				$is_agree = explode(',',$agreements);
				$defaults = $extras = array();
				$forms = $this->db()->select($this->table->signup)->where('label',array(0,$label),'IN')->orderBy('sort','asc')->get();
				for ($i=0, $loop=count($forms);$i<$loop;$i++) {
					$field = $forms[$i];
					if (in_array($field->name.'-'.$field->label,$is_agree) == true) continue;
					
					$title_languages = json_decode($field->title_languages);
					$field->title = isset($title_languages->{$this->IM->language}) == true ? $title_languages->{$this->IM->language} : $field->title;
					unset($field->title_languages);
					
					$help_languages = json_decode($field->help_languages);
					$field->help = isset($help_languages->{$this->IM->language}) == true ? $help_languages->{$this->IM->language} : $field->help;
					unset($field->help_languages);
					
					$configs = json_decode($field->configs);
					unset($field->configs);
					
					if ($field->type == 'etc') {
						$field->name = '@'.$field->name;
					} else {
						$field->title = $field->title == 'LANGUAGE_SETTING' ? $this->getLanguage('text/'.$field->name) : $field->title;
						$field->help = $field->help == 'LANGUAGE_SETTING' ? $this->getLanguage('signup/form/'.$field->name.'_help') : $field->help;
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
					
					$field->inputHtml = $this->parseMemberInputField($field);
					
					if ($forms[$i]->label == 0) array_push($defaults,$field);
					else array_push($extras,$field);
				}
				
				echo '<pre>';
				print_r($defaults);
				print_r($extras);
				echo '</pre>';
			}
			
			break;
		}
		
		/**
		 * 회원가입폼을 정의한다.
		 */
		$html = PHP_EOL.'<form id="ModuleMemberSignUpForm">'.PHP_EOL;
		$html.= '<input type="text" name="step" value="'.$step.'">'.PHP_EOL;
		$html.= '<input type="text" name="prev" value="'.$prevStep.'">'.PHP_EOL;
		$html.= '<input type="text" name="next" value="'.$nextStep.'">'.PHP_EOL;
		if ($step != 'label') $html.= '<input type="text" name="label" value="'.$label.'">'.PHP_EOL;
		if ($step != 'agreement') $html.= '<input type="text" name="agreements" value="'.$agreements.'">'.PHP_EOL;
		$html.= '<input type="text" name="templet" value="'.$templet->name.'">'.PHP_EOL;
		 
		
		/**
		 * 템플릿파일에서 iModule 코어와, 현재 모듈에 접근하기 위한 변수설정 및 템플릿파일을 불러온다.
		 */
		ob_start();
		$IM = $this->IM;
		$Module = $this;
		
		INCLUDE $templetPath.'/signup.php';
		$html.= ob_get_contents();
		ob_clean();
		
		$html.= PHP_EOL.'</form>'.PHP_EOL.'<script>Member.signup.init();</script>'.PHP_EOL;
		/*
		ob_start();
		
		if (isset($config->label) == true || Request('label') != null) {
			$label = Request('label') != null ? Request('label') : $config->label;
			$form = $this->db()->select($this->table->signup)->where('label',$label)->orderBy('sort','asc')->get();
		} else {
			$form = $this->db()->select($this->table->signup)->where('label',0)->orderBy('sort','asc')->get();
		}
		
		$step = $this->Module->getConfig('signupStep');
		$view = $this->IM->view == '' ? 'agreement' : $this->IM->view;
		
		$currentStep = array_search($view,$step);
		$nextStep = $currentStep !== null && isset($step[$currentStep+1]) == true ? $step[$currentStep+1] : '';
		
		if ($view == 'verify') {
			if (in_array('verify',$step) == false) header("location: ".$this->IM->getUrl($this->IM->menu,$this->IM->page,'complete',false));
			if (Request('code') != null) {
				$code = Decoder(Request('code'));
				if ($code === false) header("location: ".$this->IM->getUrl($this->IM->menu,$this->IM->page,'verify',false));
				else $code = json_decode($code);
				
				$check = $this->db()->select($this->table->email)->where('midx',$code->midx)->where('email',$code->email)->getOne();
				if ($check != null && $check->code == $code->code) {
					$this->db()->update($this->table->email,array('status'=>'VERIFIED'))->where('midx',$code->midx)->where('email',$code->email)->execute();
					$this->db()->update($this->table->member,array('status'=>'ACTIVE'))->where('idx',$code->midx)->execute();
					header("location: ".$this->IM->getUrl($this->IM->menu,$this->IM->page,'complete',false));
				} else {
					header("location: ".$this->IM->getUrl($this->IM->menu,$this->IM->page,'verify',false));
				}
			}
			
			$registerIDX = Request('MEMBER_REGISTER_IDX','session') != null ? Decoder(Request('MEMBER_REGISTER_IDX','session')) : false;
			if ($registerIDX == false) return $this->getError('ERROR!');
			$registerInfo = $this->db()->select($this->table->member)->where('idx',$registerIDX)->getOne();
			if ($registerInfo == null) return $this->getError('ERROR!');
			
			$status = $this->sendVerifyEmail($registerIDX);
			if ($registerInfo->status != 'VERIFYING') {
				header("location: ".$this->IM->getUrl($this->IM->menu,$this->IM->page,'complete',false));
				exit;
			} elseif ($status == 'VERIFIED') {
				$this->db()->update($this->table->member,array('status'=>'ACTIVE'))->where('idx',$registerIDX)->execute();
				header("location: ".$this->IM->getUrl($this->IM->menu,$this->IM->page,'complete',false));
				exit;
			}
		}

		echo '<form id="ModuleMemberSignUpForm" method="post">'.PHP_EOL;
		foreach ($_POST as $key=>$value) {
			if ($key == 'step' || $key == 'next') continue;
			echo '<input type="hidden" name="'.$key.'" value="'.$value.'">'.PHP_EOL;
		}
		echo '<input type="hidden" name="step" value="'.$view.'">'.PHP_EOL;
		echo '<input type="hidden" name="next" value="'.($nextStep != '' ? $this->IM->getUrl(null,null,$nextStep) : '').'">'.PHP_EOL;
		if ($view == 'verify') echo '<input type="hidden" name="registerIDX" value="'.$registerIDX.'">'.PHP_EOL;
		
		if (preg_match('/\.php$/',$config->templet) == true) {
			$temp = explode('/',$config->templet);
			$templetFile = array_pop($temp);
			$templetPath = implode('/',$temp);
			$templetDir = str_replace(__IM_PATH__,__IM_DIR__,$templetPath);
		} else {
			$templetPath = $this->getTempletPath('signup',$config->templet);
			$templetDir = $this->getTempletDir('signup',$config->templet);
			
			if (file_exists($templetPath.'/styles/style.css') == true) {
				$this->IM->addSiteHeader('style',$templetDir.'/styles/style.css');
			}
			
			$templetFile = 'templet.php';
		}
		
		$IM = $this->IM;
		$Module = $this;
		$Module->templetPath = $templetPath;
		$Module->templetDir = $templetDir;
		
		if (file_exists($templetPath.'/'.$templetFile) == true) {
			INCLUDE $templetPath.'/'.$templetFile;
		}
		
		echo '</form>'.PHP_EOL;
		echo '<script>$(document).ready(function() { Member.signup.init(); });</script>'.PHP_EOL;
		
		$context = ob_get_contents();
		ob_end_clean();
		*/
		return $html;
	}
	
	/**
	 * Get modify context
	 *
	 * @param object $config configs
	 * @return string $context context html code
	 */
	function getModifyContext($config) {
		ob_start();
		
		if ($this->isLogged() == false) {
			return $this->getError($this->getLanguage('error/notLogged'));
		}

		$member = $this->getMember();
		if (strlen($this->getMember()->password) == 0) {
			if (preg_match('/\.php$/',$config->templet) == true) {
				$temp = explode('/',$config->templet);
				$templetFile = array_pop($temp);
				$templetPath = implode('/',$temp);
				$templetDir = str_replace(__IM_PATH__,__IM_DIR__,$templetPath);
				
				$config->templet = $templetPath.'/password.php';
				return $this->getPasswordContext($config,true);
			} else {
				return $this->getPasswordContext($config,true);
			}
		} elseif (Request('MEMBER_MODIFY_PASSWORD','session') !== true && Request('password') == null) {
			$step = 'verify';
		} else {
			$this->IM->addSiteHeader('script',__IM_DIR__.'/scripts/jquery.cropit.min.js');
			
			$mHash = new Hash();
			$password = Decoder(Request('password'));
			
			if (Request('MEMBER_MODIFY_PASSWORD','session') !== true && ($password == false || $mHash->password_validate($password,$member->password) == false)) {
				return $this->getError($this->getLanguage('verify/help/password/error'));
			}
			
			$step = 'modify';
		}
		
		unset($_SESSION['MEMBER_MODIFY_PASSWORD']);
		
		echo '<form id="ModuleMemberModifyForm" method="post">'.PHP_EOL;
		echo '<input type="hidden" name="templet" value="'.$config->templet.'">'.PHP_EOL;
		echo '<input type="hidden" name="step" value="'.$step.'">'.PHP_EOL;
		
		if (preg_match('/\.php$/',$config->templet) == true) {
			$temp = explode('/',$config->templet);
			$templetFile = array_pop($temp);
			$templetPath = implode('/',$temp);
			$templetDir = str_replace(__IM_PATH__,__IM_DIR__,$templetPath);
		} else {
			$templetPath = $this->getTempletPath('modify',$config->templet);
			$templetDir = $this->getTempletDir('modify',$config->templet);
		
			if (file_exists($templetPath.'/styles/style.css') == true) {
				$this->IM->addSiteHeader('style',$templetDir.'/styles/style.css');
			}
			
			$templetFile = 'templet.php';
		}
		
		$IM = $this->IM;
		$Module = $this;
		$Module->templetPath = $templetPath;
		$Module->templetDir = $templetDir;
		
		if (file_exists($templetPath.'/'.$templetFile) == true) {
			INCLUDE $templetPath.'/'.$templetFile;
		}
		
		echo '</form>'.PHP_EOL;
		echo '<script>$(document).ready(function() { Member.modify.init(); });</script>'.PHP_EOL;
		
		$context = ob_get_contents();
		ob_end_clean();
		
		return $context;
	}
	
	/**
	 * Get confirm member's password (using social login or enter modify form or password modify modal)
	 *
	 * @param object $config configs
	 * @param boolean $isModify call by password modify modal
	 * @return string $context context html code
	 * @todo maybe this function sometimes not working...
	 */
	function getPasswordContext($config,$isModify=false) {
		ob_start();
		
		if ($this->isLogged() == false) {
			return $this->getError($this->getLanguage('error/notLogged'));
		}
		
		$member = $this->getMember();
		
		if (strlen($this->getMember()->password) == 0) {
			$type = 'social';
		} else {
			$type = 'default';
		}
		
		unset($_SESSION['MEMBER_MODIFY_PASSWORD']);
		
		echo '<form id="ModuleMemberPasswordForm">'.PHP_EOL;
		echo '<input type="hidden" name="type" value="'.($isModify == true ? 'modify' : 'confirm').'">'.PHP_EOL;
		
		if (preg_match('/\.php$/',$config->templet) == true) {
			$temp = explode('/',$config->templet);
			$templetFile = array_pop($temp);
			$templetPath = implode('/',$temp);
			$templetDir = str_replace(__IM_PATH__,__IM_DIR__,$templetPath);
		} else {
			$templetPath = $this->getTempletPath('password',$config->templet);
			$templetDir = $this->getTempletDir('password',$config->templet);
		
			if (file_exists($templetPath.'/styles/style.css') == true) {
				$this->IM->addSiteHeader('style',$templetDir.'/styles/style.css');
			}
			
			$templetFile = 'templet.php';
		}
		
		$IM = $this->IM;
		$Module = $this;
		$Module->templetPath = $templetPath;
		$Module->templetDir = $templetDir;
		
		if (file_exists($templetPath.'/'.$templetFile) == true) {
			INCLUDE $templetPath.'/'.$templetFile;
		}
		
		echo '</form>'.PHP_EOL;
		echo '<script>$(document).ready(function() { Member.password.init(); });</script>'.PHP_EOL;
		
		$context = ob_get_contents();
		ob_end_clean();
		
		return $context;
	}
	
	/**
	 * Get point context (member's point changed list context)
	 *
	 * @param object $config configs
	 * @return string $context context html code
	 */
	function getPointContext($config) {
		ob_start();
		
		if ($this->isLogged() == false) {
			return $this->getError($this->getLanguage('error/notLogged'));
		}
		
		$member = $this->getMember();
		
		if (preg_match('/\.php$/',$config->templet) == true) {
			$temp = explode('/',$config->templet);
			$templetFile = array_pop($temp);
			$templetPath = implode('/',$temp);
			$templetDir = str_replace(__IM_PATH__,__IM_DIR__,$templetPath);
		} else {
			if (preg_match('/^@/',$config->templet) == true) {
				$templetPath = $this->IM->getTempletPath().'/templets/modules/member/templets/point/'.preg_replace('/^@/','',$config->templet);
				$templetDir = $this->IM->getTempletDir().'/templets/modules/member/templets/point/'.preg_replace('/^@/','',$config->templet);
			} else {
				$templetPath = $this->Module->getPath().'/templets/point/'.$config->templet;
				$templetDir = $this->Module->getDir().'/templets/point/'.$config->templet;
			}
		
			if (file_exists($templetPath.'/styles/style.css') == true) {
				$this->IM->addSiteHeader('style',$templetDir.'/styles/style.css');
			}
			
			$templetFile = 'templet.php';
		}
		
		$page = Request('p') ? Request('p') : 1;
		$start = ($page - 1) * 20;
		$lists = $this->db()->select($this->table->point)->where('midx',$this->getLogged());
		$total = $lists->copy()->count();
		$lists = $lists->limit($start,20)->orderBy('reg_date','desc')->get();
		for ($i=0, $loop=count($lists);$i<$loop;$i++) {
			if ($this->IM->Module->isInstalled($lists[$i]->module) == true) {
				$mModule = $this->IM->getModule($lists[$i]->module);
				if (method_exists($mModule,'getPoint') == true) {
					$lists[$i]->content = $mModule->getPoint($lists[$i]->code,$lists[$i]->content ? json_decode($lists[$i]->content) : null);
				} else {
					$lists[$i]->content = $lists[$i]->module.'@'.$lists[$i]->code.'@'.$lists[$i]->content;
				}
			} else {
				$lists[$i]->content = $lists[$i]->module.'@'.$lists[$i]->code.'@'.$lists[$i]->content;
			}
		}
		
		$pagination = GetPagination($page,ceil($total/20),7,'LEFT',$this->IM->getUrl(null,null,false));
		
		$IM = $this->IM;
		$Module = $this;
		$Module->templetPath = $templetPath;
		$Module->templetDir = $templetDir;
		
		if (file_exists($templetPath.'/'.$templetFile) == true) {
			INCLUDE $templetPath.'/'.$templetFile;
		}
		
		$context = ob_get_contents();
		ob_end_clean();
		
		return $context;
	}
	
	/**
	 * Get push context (member's point changed list context)
	 *
	 * @param object $config configs
	 * @return string $context context html code
	 */
	function getPushContext($config) {
		return $this->IM->getModule('push')->getContext('list',$config);
	}
	
	/**
	 * Get social login context, if someone logged via social account and not linked iModule's account, showing this context
	 * someone can be linked iModule's account, or select iModule's account
	 *
	 * @param object $config configs
	 * @return string $context context html code
	 */
	function getSocialContext($config) {
		ob_start();
		
		$formName = 'ModuleMemberSocialForm';
		echo '<input type="hidden" name="type" value="'.$config->type.'">'.PHP_EOL;
		if ($config->type == 'duplicated') {
			echo '<form name="'.$formName.'" method="post" onsubmit="return Member.login(this);">'.PHP_EOL;
			echo '<input type="hidden" name="idx" value="'.$config->member->idx.'">'.PHP_EOL;
			
			$member = $config->member;
		} else {
			echo '<form>'.PHP_EOL;
			$photo = $config->photo;
			$redirectUrl = $config->redirectUrl;
			$accounts = array();
			for ($i=0, $loop=count($config->account);$i<$loop;$i++) {
				$accounts[$i] = $this->getMember($config->account[$i]->midx);
			}
		}
		
		if (file_exists($this->IM->getTempletPath().'/templets/modules/member/templets/social/'.$config->type) == true) {
			$templetPath = $this->IM->getTempletPath().'/templets/modules/member/templets/social/'.$config->type;
			$templetDir = $this->IM->getTempletDir().'/templets/modules/member/templets/social/'.$config->type;
		} else {
			$templetPath = $this->Module->getPath().'/templets/social/'.$config->type;
			$templetDir = $this->Module->getDir().'/templets/social/'.$config->type;
		}
		
		if (file_exists($templetPath.'/styles/style.css') == true) {
			$this->IM->addSiteHeader('style',$templetDir.'/styles/style.css');
		}
		
		$IM = $this->IM;
		$Module = $this;
		$Module->templetPath = $templetPath;
		$Module->templetDir = $templetDir;
		
		if (file_exists($templetPath.'/templet.php') == true) {
			INCLUDE $templetPath.'/templet.php';
		}
		
		echo '</form>'.PHP_EOL;
		
		$context = ob_get_contents();
		ob_end_clean();
		
		return $context;
	}
	
	/**
	 * Make logged session data, logged session is encrypt by remove ip, member idx and current time
	 * iModule always check session data for security.
	 *
	 * @param int $midx im_member_table idx
	 */
	function login($idx) {
		$_SESSION['MEMBER_LOGGED'] = Encoder(json_encode(array('idx'=>$idx,'time'=>time(),'ip'=>$_SERVER['REMOTE_ADDR'])));
		$this->logged = Request('MEMBER_LOGGED','session') != null && Decoder(Request('MEMBER_LOGGED','session')) != false ? json_decode(Decoder(Request('MEMBER_LOGGED','session'))) : false;
	}
	
	/**
	 * Login by oauth2.0 protocol(BEARER), check token in HTTP request header
	 *
	 * @param string $token
	 */
	function loginByToken($token) {
		$token = explode(' ',$token);
		if (count($token) != 2 || strtoupper($token[0]) != 'BEARER' || Decoder($token[1]) === false) {
			header("HTTP/1.1 401 Unauthorized");
			header("Content-type: text/json; charset=utf-8",true);
			
			$results = new stdClass();
			$results->success = false;
			$results->message = 'Access token Error : Unauthorized';
	
			exit(json_encode($results,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
		} else {
			$data = json_decode(Decoder($token[1]));
			$midx = array_pop($data);
			$client_id = array_pop($data);
			$this->login($midx);
		}
		
		return true;
	}
	
	/**
	 * Make access_token by oauth2.0 protocol(BEARER)
	 *
	 * @param string $client_id app id
	 * @param int $midx im_member_table idx
	 * @return string $token oauth2.0 access_token, not used expired time
	 * @todo check app id (is app id registed or not)
	 */
	function makeAuthToken($client_id,$midx) {
		return Encoder(json_encode(array($client_id,$midx)));
	}
	
	/**
	 * Check login token for tokenLogin API
	 *
	 * @param int $midx member idx
	 * @return string $loginToken
	 */
	function makeLoginToken($midx) {
		return urlencode(Encoder(json_encode(array('idx'=>$midx,'ip'=>$_SERVER['REMOTE_ADDR'],'time'=>time()))));
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
			
			$languages = $this->Module->getConfig('default_label_title_languages');
			$title = isset($languages->{$this->IM->language}) == true ? $languages->{$this->IM->language} : $this->Module->getConfig('default_label_title');
			$label->idx = 0;
			$label->title = $title == 'LANGUAGE_SETTING' ? $this->getLanguage('text/default_label_title') : $title;
			$label->allow_signup = $this->Module->getConfig('allow_signup') === true;
			$label->approve_signup = $this->Module->getConfig('approve_signup') === true;
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
	 * Get Member Information (parsed member data)
	 *
	 * @param int $midx(optional) im_member_table idx, if not exists this param, used logged member idx
	 * @param boolean $forceReload(optional) this function used cache, if this value is true, not use cache data and get data from database
	 * @return object $memberInfo
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
				$member->photo = $this->Module->getDir().'/images/nophoto.png';
				$member->nickcon = null;
				$member->level = $this->getLevel(0);
				$member->label = array();
			} else {
				$member->name = $member->name ? $member->name : $member->nickname;
				$member->nickname = $member->nickname ? $member->nickname : $member->name;
				$member->photo = file_exists($this->IM->getAttachmentPath().'/member/'.$midx.'.jpg') == true ? $this->IM->getAttachmentDir().'/member/'.$midx.'.jpg' : $this->Module->getDir().'/images/nophoto.png';
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
		if ($midx === null && $this->isLogged() == false) return '<img src="'.$this->Module->getDir().'/images/nophoto.png" alt="unknown">';
		
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
		$page->title = $this->getLanguage('account/title');
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
	
	/**
	 * send verify email when user signup or modify email address
	 *
	 * @param int $midx im_member_table idx
	 * @param string $email(optional) if not exist email, send email to member's email address
	 * @return string SENDING or VERIFIED
	 * @todo email context configure via database (email subject and email body)
	 */
	function sendVerifyEmail($midx,$email=null) {
		$member = $this->db()->select($this->table->member)->where('idx',$midx)->getOne();
		if ($member == null) return null;
		
		$email = $email == null ? $member->email : $email;
		$check = $this->db()->select($this->table->email)->where('midx',$midx)->where('email',$email)->getOne();
		
		$code = GetRandomString(6);
		$isSendEmail = false;
		if ($check == null) {
			$this->db()->insert($this->table->email,array('midx'=>$midx,'email'=>$email,'code'=>$code,'reg_date'=>time(),'status'=>'SENDING'))->execute();
			$isSendEmail = true;
		} elseif ($check->status == 'CANCELED') {
			$this->db()->update($this->table->email,array('code'=>$code,'reg_date'=>time(),'status'=>'SENDING'))->where('midx',$midx)->where('email',$email)->execute();
			$isSendEmail = true;
		} elseif ($check->status == 'VERIFIED') {
			return 'VERIFIED';
		}
		
		if ($isSendEmail == true) {
			$subject = '['.$this->IM->getSiteTitle().'] 이메일주소 확인메일';
			$content = '회원님이 입력하신 이메일주소가 유효한 이메일주소인지 확인하기 위한 이메일입니다.<br>회원가입하신적이 없거나, 최근에 이메일주소변경신청을 하신적이 없다면 본 메일은 무시하셔도 됩니다.';
			if ($member->status == 'VERIFYING') {
				$content.= '<br><br>아래의 인증코드 6자리를 인증번호 확인란에 입력하시거나, 인증링크를 클릭하여 회원가입을 완료할 수 있습니다.';
			} else {
				$content.= '<br><br>아래의 인증코드 6자리를 인증번호 확인란에 입력하여 이메일주소변경을 완료할 수 있습니다.';
			}
			$content.= '<br><br>인증코드 : <b>'.$code.'</b>';
			if ($member->status == 'VERIFYING') {
				$signupPage = $this->getMemberPage('signup');
				$link = $this->IM->getUrl($signupPage->menu,$signupPage->page,'verify',false,true).'?code='.urlencode(Encoder(json_encode(array('midx'=>$midx,'email'=>$email,'code'=>$code))));
				$content.= '<br>인증주소 : <a href="'.$link.'" target="_blank">'.$link.'</a>';
			}
			$content.= '<br><br>본 메일은 발신전용메일로 회신되지 않습니다.<br>감사합니다.';
			
			$this->IM->getModule('email')->addTo($email,$member->name)->setSubject($subject)->setContent($content)->send();
		}
		
		if ($member->status == 'VERIFYING' && $member->email != $email) {
			$this->db()->update($this->table->member,array('email'=>$email))->where('idx',$member->idx)->execute();
		}
		
		return 'SENDING';
	}
	
	function getPhotoEditModal($templet) {
		ob_start();
		
		$templetPath = $this->getTempletPath('modify',$templet);
		$templetDir = $this->getTempletDir('modify',$templet);
		
		$title = $this->getLanguage('photoEdit/title');
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
		
		if (file_exists($templetPath.'/modal.php') == true) {
			INCLUDE $templetPath.'/modal.php';
		} else {
			INCLUDE $this->Module->getPath().'/templets/modal.php';
		}
		
		echo '</form>'.PHP_EOL.'<script>Member.photoEdit.init();</script>';
		
		$context = ob_get_contents();
		ob_end_clean();
		
		return $context;
	}
	
	function getModifyEmail($templet) {
		ob_start();
		
		if (preg_match('/^@/',$templet) == true) {
			$templetPath = $this->IM->getTempletPath().'/templets/modules/member/templets/modify/'.preg_replace('/^@/','',$templet);
			$templetDir = $this->IM->getTempletDir().'/templets/modules/member/templets/modify/'.preg_replace('/^@/','',$templet);
		} else {
			$templetPath = $this->Module->getPath().'/templets/modify/'.$templet;
			$templetDir = $this->Module->getDir().'/templets/modify/'.$templet;
		}
		
		$title = $this->getLanguage('modifyEmail/title');
		echo '<form name="ModuleMemberModifyEmailForm" onsubmit="return Member.modify.modifyEmail(this);">'.PHP_EOL;
		echo '<input type="hidden" name="confirm" value="TRUE">'.PHP_EOL;
		
		$content = '<div class="message">'.$this->getLanguage('modifyEmail/email').'</div>'.PHP_EOL;
		$content.= '<div class="inputBlock">'.PHP_EOL;
		$content.= '	<input type="text" name="email" class="inputControl" required>'.PHP_EOL;
		$content.= '	<div class="helpBlock" data-default="'.$this->getLanguage('modifyEmail/help/email/default').'"></div>'.PHP_EOL;
		$content.= '</div>'.PHP_EOL;
		
		$content.= '<button type="button" class="btn btnRed" style="margin:5px 0px; width:100%;" data-loading="'.$this->getLanguage('modifyEmail/sending').'" onclick="Member.modify.sendVerifyEmail(this);"><i class="fa fa-check"></i> '.$this->getLanguage('modifyEmail/sendVerifyEmail').'</button>'.PHP_EOL;
		
		$content.= '<div class="message">'.$this->getLanguage('modifyEmail/code').'</div>'.PHP_EOL;
		$content.= '<div class="inputBlock">'.PHP_EOL;
		$content.= '	<input type="text" name="code" class="inputControl" required>'.PHP_EOL;
		$content.= '	<div class="helpBlock" data-default="'.$this->getLanguage('modifyEmail/help/code/default').'"></div>'.PHP_EOL;
		$content.= '</div>'.PHP_EOL;
		
//		$actionButton = '<button type="button" class="danger" onclick="$(\'input.cropit-image-input\').click();">사진선택</button>';
		
		$IM = $this->IM;
		$Module = $this;
		
		if (file_exists($templetPath.'/modal.php') == true) {
			INCLUDE $templetPath.'/modal.php';
		} else {
			INCLUDE $this->Module->getPath().'/templets/modal.php';
		}
		
		echo '</form>'.PHP_EOL;
		
		$context = ob_get_contents();
		ob_end_clean();
		
		return $context;
	}
	
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
	
	function addActivity($midx,$exp,$module,$code,$content=array()) {
		$member = $this->getMember($midx);
		if ($member == null) return;
		
		$this->db()->insert($this->table->activity,array('midx'=>$member->idx,'module'=>$module,'code'=>$code,'content'=>json_encode($content,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK),'exp'=>$exp,'reg_date'=>time()))->execute();
		if ($exp > 0) $this->db()->update($this->table->member,array('exp'=>$member->exp + $exp))->where('idx',$member->idx)->execute();
	}
	
	/**
	 * 회원가입 / 회원수정 필드를 출력하기 위한 함수
	 *
	 * @param object $field 입력폼 데이터
	 * @param boolean $isModify 정보수정 필드인지 여부
	 * @param string $html
	 */
	function parseMemberInputField($field,$isModify=false) {
		$html = array();
		
		if ($field->input == 'system') {
			/**
			 * 이메일
			 */
			if ($field->name == 'email') {
				array_push($html,
					'<div data-role="input" data-name="email" data-default="'.$field->help.'">',
						'<input type="email" name="email">',
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
							'<input type="password" name="password" placeholder="'.$this->getLanguage('signup/form/password').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="password" name="password_confirm" placeholder="'.$this->getLanguage('signup/form/password_confirm').'">',
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
			 * 체크박스, 라디오버튼
			 */
			if ($field->input == 'checkbox' || $field->input == 'radio') {
				$html[] = '<div data-role="inputset" data-name="'.$field->name.'" class="inline" data-default="'.$field->help.'">';
				foreach ($field->options as $value=>$display) {
					array_push($html,
						'<div data-role="input">',
							'<label><input type="'.$field->input.'" name="'.$field->name.'" value="'.$value.'">'.$display.'</label>',
						'</div>'
					);
				}
				$html[] = '</div>';
			}
			
		}
		
		return implode(PHP_EOL,$html);
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
		$values = new stdClass();
		
		/**
		 * 모듈의 process 폴더에 $action 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->Module->getPath().'/process/'.$action.'.php') == true) {
			INCLUDE $this->Module->getPath().'/process/'.$action.'.php';
		}
		
		if ($action == 'check') {
			$name = Request('name');
			$value = Request('value');
			
			if ($name == 'email') {
				$siteType = $this->IM->getSite()->member;
				
				if (CheckEmail($value) == true) {
					if ($this->db()->select($this->table->member)->where('email',$value)->where('idx',$this->getLogged(),'!=')->where('domain',$siteType == 'MERGE' ? '*' : $this->IM->domain)->has() == true || $this->db()->select($this->table->member)->where('email',$value)->where('idx',$this->getLogged(),'!=')->where('type','ADMINISTRATOR')->has() == true) {
						$results->success = false;
						$results->message = $this->getLanguage('signup/help/email/duplicated');
					} else {
						$results->success = true;
					}
				} else {
					$results->success = false;
					$results->message = $this->getLanguage('signup/help/email/error');
				}
			}
			
			if ($name == 'name') {
				if (strlen($value) > 0) {
					$results->success = true;
				} else {
					$results->success = false;
					$results->message = $this->getLanguage('signup/help/name/error');
				}
			}
			
			if ($name == 'nickname') {
				$siteType = $this->IM->getSite()->member;
				
				if (CheckNickname($value) == true) {
					if ($this->db()->select($this->table->member)->where('nickname',$value)->where('idx',$this->getLogged(),'!=')->where('domain',$siteType == 'MERGE' ? '*' : $this->IM->domain)->has() == true || $this->db()->select($this->table->member)->where('nickname',$value)->where('idx',$this->getLogged(),'!=')->where('type','ADMINISTRATOR')->has() == true) {
						$results->success = false;
						$results->message =  $this->getLanguage('signup/help/nickname/duplicated');
					} else {
						$results->success = true;
					}
				} else {
					$results->success = false;
					$results->message = $this->getLanguage('signup/help/nickname/error');
				}
			}
			
			if ($name == 'old_password') {
				if ($this->isLogged() == false) {
					$results->success = false;
					$results->message = $this->getLanguage('error/notLogged');
				} else {
					$mHash = new Hash();
					if ($mHash->password_validate($value,$this->getMember()->password) == true) {
						$results->success = true;
						$results->message = $this->getLanguage('password/help/old_password/success');
					} else {
						$results->success = false;
						$results->message = $this->getLanguage('password/help/old_password/error');
					}
				}
			}
		}
		
		if ($action == 'forceLogin') {
			$code = Decoder(Request('code'));
			
			if ($code === false) {
				$results->success = false;
				$results->message = $this->getLanguage('error/invalidCode');
			} else {
				$data = json_decode($code);
				if ($data != null && $data->ip == $_SERVER['REMOTE_ADDR']) {
					$this->login($data->idx);
					$results->success = true;
				} else {
					$results->success = false;
					$results->message = $this->getLanguage('error/invalidCode');
				}
			}
		}
		
		if ($action == 'login') {
			$mHash = new Hash();
			$email = Request('email');
			$password = Request('password');
			
			$results->errors = array();
			
			$loginFail = Request('loginFail','session') != null && is_array(Request('loginFail','session')) == true ? Request('loginFail','session') : array('count'=>0,'time'=>0);
			if ($loginFail['time'] > time()) {
				$results->success = false;
				$results->message = $this->getLanguage('login/error/login');
			} else {
				$siteType = $this->IM->getSite()->member;
				
				if ($siteType == 'MERGE') {
					$check = $this->db()->select($this->table->member)->where('email',$email)->where('domain','*')->getOne();
				} else {
					$check = $this->db()->select($this->table->member)->where('email',$email)->where('domain',$this->IM->domain)->getOne();
				}
				
				// not found member, search ADMINISTRATOR
				if ($check == null) {
					$check = $this->db()->select($this->table->member)->where('email',$email)->where('type','ADMINISTRATOR')->getOne();
				}
				
				if ($check == null || $check->status == 'LEAVE') {
					$results->success = false;
					$results->errors['email'] = $this->getLanguage('login/error/email');
					$loginFail['count']++;
					if ($loginFail['count'] == 5) {
						$loginFail['count'] = 0;
						$loginFail['time'] = time() + 60 * 5;
					}
					
					$values->email = $email;
					$values->password = $password;
				} elseif ($mHash->password_validate($password,$check->password) == false) {
					$results->success = false;
					$results->errors['password'] = $this->getLanguage('login/error/password');
					$loginFail['count']++;
					if ($loginFail['count'] == 5) {
						$loginFail['count'] = 0;
						$loginFail['time'] = time() + 60 * 5;
					}
					
					$values->email = $email;
					$values->password = $password;
				} else {
					$loginFail = array('count'=>0,'time'=>0);
					
					if ($check->status == 'ACTIVE') {
						$this->db()->update($this->table->member,array('last_login'=>time()))->where('idx',$check->idx)->execute();
						$this->login($check->idx);
						$results->success = true;
					} elseif ($check->status == 'VERIFYING') {
						$_SESSION['MEMBER_REGISTER_IDX'] = Encoder($check->idx);
						$page = $this->getMemberPage('signup');
						$results->success = false;
						$results->redirect = $this->IM->getUrl($page->menu,$page->page,'verify');
					} else {
						$results->success = false;
						$results->message = $this->getLanguage('error/'.$check->status);
					}
				}
			}
			$_SESSION['loginFail'] = $loginFail;
		}
		
		if ($action == 'logout') {
			$redirect = Request('redirect');
			unset($_SESSION['MEMBER_LOGGED']);
			$results->success = true;
			
			if ($redirect != null && $redirect) {
				header("location:".urldecode($redirect));
				exit;
			}
		}
		
		if ($action == 'cert') {
			$results->success = true;
		}
		
		if ($action == 'signup') {
			$label = Request('label') ? Request('label') : 0;
			$siteType = $this->IM->getSite()->member;
			
			$insert = array();
			$errors = array();
			
			if ($label == 0) {
				$autoActive = $this->Module->getConfig('autoActive');
				$allowSignup = $this->Module->getConfig('allowSignup');
			} else {
				$label = $this->db()->select($this->table->label)->where('idx',$label)->getOne();
				if ($label == null) {
					$autoActive = $allowSignup = false;
					$errors['label'] = $this->getLanguage('error/not_found');
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
						$insert['email'] = CheckEmail(Request('email')) == true ? Request('email') : $errors['email'] = $this->getLanguage('signup/help/email/error');
						if ($this->db()->select($this->table->member)->where('email',$insert['email'])->where('domain',$siteType == 'MERGE' ? '*' : $this->IM->domain)->has() == true || $this->db()->select($this->table->member)->where('email',$insert['email'])->where('type','ADMINISTRATOR')->has() == true) {
							$errors['email'] = $this->getLanguage('signup/help/email/duplicated');
						}
						break;
					
					case 'password' :
						$insert['password'] = strlen(Request('password')) >= 4 ? Request('password') : $errors['password'] = $this->getLanguage('signup/help/password/error');
						if (strlen(Request('password')) < 4 || Request('password') != Request('password_confirm')) {
							$errors['password_confirm'] = $this->getLanguage('signup/help/password_confirm/error');
						}
						break;
						
					case 'name' :
						$insert['name'] = CheckNickname(Request('name')) == true ? Request('name') : $errors['name'] = $this->getLanguage('signup/help/name/error');
						break;
						
					case 'nickname' :
						$insert['nickname'] = CheckNickname(Request('nickname')) == true ? Request('nickname') : $errors['nickname'] = $this->getLanguage('signup/help/nickname/error');
						if ($this->db()->select($this->table->member)->where('nickname',$insert['nickname'])->where('domain',$siteType == 'MERGE' ? '*' : $this->IM->domain)->has() == true || $this->db()->select($this->table->member)->where('nickname',$insert['nickname'])->where('type','ADMINISTRATOR')->has() == true) {
							$errors['nickname'] = $this->getLanguage('signup/help/nickname/duplicated');
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
								$errors['telephone'] = $this->getLanguage('error/required');
							}
						}
						
						if ($forms[$i]->allow_blank == 'FALSE' && strlen($insert['telephone']) < 10) $errors['telephone'] = $this->getLanguage('error/required');
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
								$errors['cellphone'] = $this->getLanguage('error/required');
							}
						}
						
						if ($forms[$i]->allow_blank == 'FALSE' && strlen($insert['cellphone']) < 10) $errors['cellphone'] = $this->getLanguage('error/required');
						break;
				}
			}
			
			$values = new stdClass();
			$values->insert = $insert;
			$values->errors = $errors;
			$this->IM->fireEvent('beforeDoProcess','member',$action,$values);
			$insert = $values->insert;
			$errors = $values->errors;
			
			$values = new stdClass();
			if (empty($errors) == true) {
				$mHash = new Hash();
				
				$insert['domain'] = $siteType == 'MERGE' ? '*' : $this->IM->domain;
				$insert['password'] = $mHash->password_hash($insert['password']);
				$insert['status'] = in_array('verify',$this->Module->getConfig('signupStep')) === true ? 'VERIFYING' : ($autoActive == true ? 'ACTIVE' : 'WAITING');
				$insert['point'] = $this->Module->getConfig('signupPoint');
				$insert['reg_date'] = time();
				
				$idx = $this->db()->insert($this->table->member,$insert)->execute();
				if ($label != 0) {
					$this->db()->insert($this->table->member_label,array('idx'=>$idx,'label'=>$label,'reg_date'=>$insert['reg_date']))->execute();
					$membernum = $this->db()->select($this->table->member_label)->where('label',$label)->count();
					$this->db()->update($this->table->label,array('membernum'=>$membernum))->where('idx',$label)->execute();
				}
				
				if ($idx !== false) {
					$results->success = true;
					$_SESSION['MEMBER_REGISTER_IDX'] = Encoder($idx);
					if (in_array('verify',$this->Module->getConfig('signupStep')) === true) $this->sendVerifyEmail($idx);
					$values->idx = $idx;
				} else {
					$results->success = false;
				}
			} else {
				$results->success = false;
				$results->errors = $errors;
			}
		}
		
		if ($action == 'verifyEmail') {
			$registerIDX = Request('registerIDX');
			
			if ($registerIDX == null) {
				$results->success = false;
			} else {
				$email = Request('email');
				$email_verify_code = Request('email_verify_code');
				$check = $this->db()->select($this->table->email)->where('midx',$registerIDX)->where('email',$email)->getOne();
				
				if ($check == null) {
					$results->success = false;
					$results->errors = array('email'=>$this->getLanguage('verifyEmail/help/email/notFound'));
				} elseif ($check->code == $email_verify_code) {
					$this->db()->update($this->table->email,array('status'=>'VERIFIED'))->where('midx',$registerIDX)->where('email',$email)->execute();
					$this->db()->update($this->table->member,array('status'=>'ACTIVE'))->where('idx',$registerIDX)->execute();
					$results->success = true;
				} else {
					$results->success = false;
					$results->errors = array('email_verify_code'=>$this->getLanguage('verifyEmail/help/email_verify_code/error'));
				}
			}
		}
		
		if ($action == 'sendVerifyEmail') {
			$registerIDX = Request('registerIDX');
			$email = Request('email');
			
			if ($this->isLogged() == true) {
				if (CheckEmail($email) == false) {
					$results->success = false;
					$results->errors = array('email'=>$this->getLanguage('modifyEmail/help/email/error'));
				} elseif ($this->db()->select($this->table->member)->where('email',$email)->count() == 1) {
					$results->success = false;
					$results->errors = array('email'=>$this->getLanguage('modifyEmail/help/email/duplicated'));
				} else {
					$check = $this->db()->select($this->table->email)->where('midx',$this->getLogged())->where('email',$email)->getOne();
					if ($check == null || $check->status != 'SENDING' || ($check->status == 'SENDING' && $check->reg_date + 300 < time())) {
						$this->db()->delete($this->table->email)->where('midx',$this->getLogged())->where('email',$email)->execute();
						$status = $this->sendVerifyEmail($this->getLogged(),$email);
						$results->success = true;
						$results->message = $this->getLanguage('verifyEmail/sending');
					} else {
						$results->success = false;
						$results->message = $this->getLanguage('verifyEmail/error/sending');
					}
				}
			} elseif ($registerIDX != null) {
				$member = $this->db()->select($this->table->member)->where('idx',$registerIDX)->getOne();
				if ($member == null || $member->status != 'VERIFYING') {
					$results->success = false;
					$results->message = $this->getLanguage('verifyEmail/error/target');
				} else {
					if (CheckEmail($email) == false) {
						$results->success = false;
						$results->message = $this->getLanguage('verifyEmail/error/email');
					} else {
						$check = $this->db()->select($this->table->email)->where('midx',$registerIDX)->where('email',$email)->getOne();
						if ($check->status == 'VERIFIED') {
							$signupPage = $this->getMemberPage('signup');
							$results->success = true;
							$this->db()->update($this->table->member,array('status'=>'ACTIVE'))->where('idx',$registerIDX)->execute();
							$results->redirect = $this->IM->getUrl($signupPage->menu,$signupPage->page,'complete');
						} elseif ($check == null || $check->status == 'CANCELED' || ($check->status == 'SENDING' && $check->reg_date + 300 < time())) {
							$this->db()->delete($this->table->email)->where('midx',$registerIDX)->where('email',$email)->execute();
							$status = $this->sendVerifyEmail($registerIDX,$email);
							$results->success = true;
							$results->message = $this->getLanguage('verifyEmail/sending');
						} else {
							$results->success = false;
							$results->message = $this->getLanguage('verifyEmail/error/sending');
						}
					}
				}
			} else {
				$results->success = false;
				$results->message = $this->getLanguage('error/notLogged');
			}
		}
		
		if ($action == 'photoEdit') {
			$templet = Request('templet');
			if ($this->isLogged() == true) {
				$results->success = true;
				$results->modalHtml = $this->getPhotoEditModal($templet);
				$results->photo = $this->getMember()->photo;
			} else {
				$results->success = false;
				$results->message = $this->getLanguage('error/notLogged');
			}
		}
		
		if ($action == 'photoUpload') {
			$photo = Request('photo');
			if ($this->isLogged() == false) {
				$results->success = false;
				$results->message = $this->getLanguage('error/notLogged');
			} else {
				if (preg_match('/^data:image\/(.*?);base64,(.*?)$/',$photo,$match) == true) {
					$bytes = base64_decode($match[2]);
					file_put_contents($this->IM->getAttachmentPath().'/member/'.$this->getLogged().'.jpg',$bytes);
					$this->IM->getModule('attachment')->createThumbnail($this->IM->getAttachmentPath().'/member/'.$this->getLogged().'.jpg',$this->IM->getAttachmentPath().'/member/'.$this->getLogged().'.jpg',250,250,false,'jpg');
					
					$results->success = true;
					$results->message = $this->getLanguage('photoEdit/success');
				} else {
					$results->success = false;
					$results->message = $this->getLanguage('photoEdit/error');
				}
			}
		}
		
		if ($action == 'modifyEmail') {
			$confirm = Request('confirm');
			
			if ($confirm == 'TRUE') {
				$email = Request('email');
				$code = Request('code');
				$check = $this->db()->select($this->table->email)->where('midx',$this->getLogged())->where('email',$email)->getOne();
				
				if ($check == null || $check->code != $code) {
					$results->success = false;
					$results->errors = array('code'=>$this->getLanguage('modifyEmail/help/code/error'));
				} else {
					$this->db()->update($this->table->email,array('status'=>'VERIFIED'))->where('midx',$this->getLogged())->where('email',$email)->execute();
					$this->db()->update($this->table->member,array('email'=>$email))->where('idx',$this->getLogged())->execute();
					$results->success = true;
					$results->message = $this->getLanguage('modifyEmail/success');
				}
			} else {
				$templet = Request('templet');
				if ($this->isLogged() == true) {
					$results->success = true;
					$results->modalHtml = $this->getModifyEmail($templet);
				} else {
					$results->success = false;
					$results->message = $this->getLanguage('error/notLogged');
				}
			}
		}
		
		if ($action == 'modify') {
			$step = Request('step');
			
			if ($step == 'verify') {
				$member = $this->getMember();
				$password = Request('password');
				
				$mHash = new Hash();
				
				if ($mHash->password_validate($password,$member->password) == true) {
					$results->success = true;
					$results->password = Encoder($password);
				} else {
					$results->success = false;
					$results->errors = array('password'=>$this->getLanguage('verify/help/password/error'));
				}
			}
			
			if ($step == 'modify') {
				$errors = array();
				$values->name = Request('name') ? Request('name') : $errors['name'] = $this->getLanguage('signup/help/name/error');
				$values->nickname = Request('nickname') ? Request('nickname') : $errors['nickname'] = $this->getLanguage('signup/help/nickname/error');
				
				if ($this->isLogged() == false) {
					$results->success = false;
					$results->message = $this->getLangauge('error/notLogged');
				} elseif (count($errors) == 0) {
					$insert = array();
					$insert['name'] = $values->name;
					$insert['nickname'] = $values->nickname;
					
					$this->db()->update($this->table->member,$insert)->where('idx',$this->getLogged())->execute();
					$results->success = true;
					$results->message = $this->getLanguage('modify/success');
				} else {
					$results->success = false;
					$results->errors = $errors;
				}
			}
		}
		
		if ($action == 'password') {
			$errors = array();
			$password = strlen(Request('password')) >= 4 ? Request('password') : $errors['password'] = $this->getLanguage('signup/help/password/error');
			if (strlen(Request('password')) < 4 || Request('password') != Request('password_confirm')) {
				$errors['password_confirm'] = $this->getLanguage('signup/help/password_confirm/error');
			}
			
			if ($this->isLogged() == false) {
				$results->success = false;
				$results->message = $this->getLangauge('error/notLogged');
			} else {
				$mHash = new Hash();
				
				if (strlen($this->getMember()->password) == 65) {
					$old_password = Request('old_password');
					if ($old_password == '' || $mHash->password_validate($old_password,$this->getMember()->password) == false) {
						$errors['old_password'] = $this->getLanguage('password/help/old_password/error');
					}
				}
				
				if (count($errors) == 0) {
					$password = $mHash->password_hash($password);
					$this->db()->update($this->table->member,array('password'=>$password))->where('idx',$this->getLogged())->execute();
					$results->success = true;
					$results->message = $this->getLanguage('password/success');
				} else {
					$results->success = false;
					$results->errors = $errors;
				}
			}
		}
		
		if ($action == 'facebook' || $action == 'google' || $action == 'github') {
			$_oauth = $this->db()->select($this->table->social_oauth)->where('domain',$this->IM->domain)->where('code',$action)->getOne();
			if ($_oauth == null) $this->IM->printError('OAUTH_DOMAIN_ERROR');
			$_client_id = $_oauth->client_id;
			$_client_secret = $_oauth->client_secret;
			$_scope = array();
			$_store_scope = array();
			
			$_is_refresh_token = false;
			if ($this->isLogged() == true) {
				$check = $this->db()->select($this->table->social_token)->where('midx',$this->getLogged())->where('code',$action)->getOne();
				if ($check !== null) {
					$_store_scope = $check->scope ? explode(',',$check->scope) : array();
					$_request_scope = $check->request_scope ? explode(',',$check->request_scope) : array();
					$_scope = array_merge($_store_scope,$_request_scope);
					$_is_refresh_token = strlen($check->refresh_token) == 0;
				}
			}
			
			if ($action == 'facebook') {
				$_auth_url = 'https://graph.facebook.com/oauth/authorize';
				$_token_url = 'https://graph.facebook.com/oauth/access_token';
				$_scope = array_unique(array_merge($_scope,array('public_profile','email')));
			} elseif ($action == 'google') {
				$_auth_url = 'https://accounts.google.com/o/oauth2/auth';
				$_token_url = 'https://accounts.google.com/o/oauth2/token';
				$_scope = array_unique(array_merge($_scope,array('https://www.googleapis.com/auth/plus.me','https://www.googleapis.com/auth/userinfo.email')));
			} elseif ($action == 'github') {
				$_auth_url = 'https://github.com/login/oauth/authorize';
				$_token_url = 'https://github.com/login/oauth/access_token';
				$_scope = array_unique(array_merge($_scope,array('user')));
			}
			
			if ($_is_refresh_token == true || (count($_store_scope) > 0 && count(array_diff($_scope,$_store_scope)) > 0)) {
				$_approval_prompt = 'force';
			} else {
				$_approval_prompt = 'auto';
			}
			
			if (Request('SOCIAL_REDIRECT_URL','session') == null) {
				$_SESSION['SOCIAL_REDIRECT_URL'] = isset($_SERVER['HTTP_REFERER']) == true ? $_SERVER['HTTP_REFERER'] : __IM_DIR__.'/';
			}
			
			$oauth = new OAuthClient();
			$oauth->setClientId($_client_id)->setClientSecret($_client_secret)->setScope($action == 'google' ? implode(' ',$_scope) : implode(',',$_scope))->setAccessType('offline')->setAuthUrl($_auth_url)->setTokenUrl($_token_url)->setApprovalPrompt($_approval_prompt);

			if (isset($_GET['code']) == true) {
				if ($oauth->authenticate($_GET['code']) == true) {
					$redirectUrl = $oauth->getRedirectUrl();
					header('location:'.$redirectUrl);
				}
				exit;
			} elseif ($oauth->getAccessToken() == null) {
				$authUrl = $oauth->getAuthenticationUrl();
				header('location:'.$authUrl);
				exit;
			}
			
			$_id = $_name = $_email = $_photo = null;
			
			if ($action == 'facebook') {
				$data = $oauth->get('https://graph.facebook.com/me',array('fields'=>'id,email,name'));
				if ($data === false || empty($data->email) == true) $this->IM->printError('OAUTH_API_ERROR');
				
				$_id = $data->id;
				$_name = $data->name;
				$_email = $data->email;
				$_photo = 'https://graph.facebook.com/'.$data->id.'/picture?width=250&height=250';
			}
			
			if ($action == 'google') {
				$data = $oauth->get('https://www.googleapis.com/plus/v1/people/me');
				if ($data === false || empty($data->emails) == true) $this->IM->printError('OAUTH_API_ERROR');
				for ($i=0, $loop=count($data->emails);$i<$loop;$i++) {
					if ($data->emails[$i]->type == 'account') {
						$data->email = $data->emails[$i]->value;
						break;
					}
				}
				
				$_id = $data->id;
				$_name = $data->displayName;
				$_email = $data->email;
				$_photo = str_replace('sz=50','sz=250',$data->image->url);
			}
			
			if ($action == 'github') {
				$data = $oauth->get('https://api.github.com/user');
				if ($data === false || empty($data->email) == true) $this->IM->printError('OAUTH_API_ERROR');
				
				$_id = $data->id;
				$_name = $data->name;
				$_email = $data->email;
				$_photo = $data->avatar_url;
			}
			
			if ($_id == null || $_name == null || $_email == null || $_photo == null) $this->IM->printError('OAUTH_API_ERROR');
			
			$_accessToken = $oauth->getAccessToken();
			$_refreshToken = $oauth->getRefreshToken() == null ? '' : $oauth->getRefreshToken();
			
			$this->socialLogin($action,$_client_id,$_id,$_name,$_email,$_photo,$_accessToken,$_refreshToken,$_scope);
		}
		
		$this->IM->fireEvent('afterDoProcess','member',$action,$values,$results);
		
		return $results;
	}

	/**
	 * login from social account
	 *
	 * @param string $code via social (facebook, google, github)
	 * @param string $user_id social account id
	 * @param string $name social account name
	 * @param string $email social account email
	 * @param string $photo social account photo url path
	 * @param string $accessToken social oauth access_token
	 * @param string $refreshToken social oauth refresh_token
	 * @return null if run this function, just redirect url directly
	 */
	function socialLogin($code,$client_id,$user_id,$name,$email,$photo,$accessToken,$refreshToken,$scope) {
		$siteType = $this->IM->getSite()->member;
		
		$_oauth = $this->db()->select($this->table->social_oauth)->where('domain',$this->IM->domain)->where('code',$code)->getOne();
		if ($_oauth == null || $_oauth->client_id != $client_id) $this->IM->printError('OAUTH_DOMAIN_ERROR');
		
		if ($this->isLogged() == true) {
			$check = $this->db()->select($this->table->social_token)->where('midx',$this->getLogged())->where('code',$code)->where('client_id',$client_id)->getOne();
			if ($check == null) {
				$this->db()->insert($this->table->social_token,array('midx'=>$this->getLogged(),'code'=>$code,'client_id'=>$client_id,'user_id'=>$user_id,'email'=>$email,'access_token'=>$accessToken,'refresh_token'=>$refreshToken,'scope'=>implode(',',$scope)))->execute();
			} else {
				$refreshToken = $refreshToken ? $refreshToken : $check->refresh_token;
				$this->db()->update($this->table->social_token,array('user_id'=>$user_id,'email'=>$email,'access_token'=>$accessToken,'refresh_token'=>$refreshToken,'scope'=>implode(',',$scope),'request_scope'=>''))->where('midx',$this->getLogged())->where('code',$code)->where('client_id',$client_id)->execute();
			}
			
			// if not exists user's photo, get social photo
			if (file_exists($this->IM->getAttachmentPath().'/member/'.$this->getLogged().'.jpg') == false) {
				if (SaveFileFromUrl($photo,$this->IM->getAttachmentPath().'/member/'.$this->getLogged().'.jpg','image') == true) {
					$this->IM->getModule('attachment')->createThumbnail($this->IM->getAttachmentPath().'/member/'.$this->getLogged().'.jpg',$this->IM->getAttachmentPath().'/member/'.$this->getLogged().'.jpg',250,250,false,'jpg');
				}
			}
		} else {
			$check = $this->db()->select($this->table->social_token)->where('code',$code)->where('client_id',$client_id)->where('user_id',$user_id)->get();
			
			if (count($check) == 0) { // not connected member idx and social token.
				$checkEmail = $this->db()->select($this->table->member)->where('email',$email)->where('domain',$siteType == 'MERGE' ? '*' : $this->IM->domain)->getOne();
				// cannot find social member's email address on im_member_table, search administator
				if ($checkEmail == null) $checkEmail = $this->db()->select($this->table->member)->where('email',$email)->where('type','ADMINISTRATOR')->getOne();
				
				if ($checkEmail == null) { // not found social member's email, new member register
					$insert = array();
					$insert['type'] = 'MEMBER';
					$insert['domain'] = $siteType == 'MERGE' ? '*' : $this->IM->domain;
					$insert['email'] = $email;
					$insert['password'] = '';
					$insert['name'] = $insert['nickname'] = $name;
					$insert['reg_date'] = $insert['last_login'] = time();
					$insert['status'] = 'ACTIVE';
					
					$idx = $this->db()->insert($this->table->member,$insert)->execute();
					$this->login($idx);
					
					header('location:'.$this->IM->getProcessUrl('member',$code));
					exit;
				} elseif (strlen($checkEmail->password) == 65) { // found member and exists password, check account password
					$config = new stdClass();
					$config->type = 'duplicated';
					$config->member = $this->getMember($checkEmail->idx);
					
					$this->IM->addSiteHeader('script',__IM_DIR__.'/scripts/php2js.js.php?language='.$this->IM->language);
					
					$context = $this->getContext('social',$config);
					$header = $this->IM->printHeader();
					$footer = $this->IM->printFooter();
					
					echo $header;
					echo $context;
					echo $footer;
					
					exit;
				} else { // found member and not exists password(someone used social login only), login directly.
					$this->login($checkEmail->idx);
					header('location:'.$this->IM->getProcessUrl('member',$code));
					exit;
				}
			} elseif (count($check) == 1) { // only one account connected via social login, login directly.
				$this->login($check[0]->midx);
				$refresh_token = $refresh_token ? $refresh_token : $check[0]->refresh_token;
				$this->db()->update($this->table->social_token,array('user_id'=>$user_id,'email'=>$email,'access_token'=>$accessToken,'refresh_token'=>$refreshToken,'scope'=>implode(',',$scope),'request_scope'=>''))->where('midx',$check[0]->midx)->where('code',$code)->where('client_id',$client_id)->execute();
			} else { // multiple account connected via social login, select account.
				$config = new stdClass();
				$config->type = 'select';
				$config->account = $check;
				$config->redirectUrl = Request('SOCIAL_REDIRECT_URL','session') != null ? Request('SOCIAL_REDIRECT_URL','session') : '/';
				$config->photo = $photo;
				
				$this->IM->addSiteHeader('script',__IM_DIR__.'/scripts/php2js.js.php?language='.$this->IM->language);
				
				$context = $this->getContext('social',$config);
				$header = $this->IM->printHeader();
				$footer = $this->IM->printFooter();
				
				echo $header;
				echo $context;
				echo $footer;
				
				unset($_SESSION['OAUTH_ACCESS_TOKEN']);
				unset($_SESSION['OAUTH_REFRESH_TOKEN']);
				unset($_SESSION['SOCIAL_REDIRECT_URL']);
				
				exit;
			}
		}
		
		unset($_SESSION['OAUTH_ACCESS_TOKEN']);
		unset($_SESSION['OAUTH_REFRESH_TOKEN']);
		
		$redirectUrl = Request('SOCIAL_REDIRECT_URL','session') != null && preg_match('/\/member\/'.$code.'/',Request('SOCIAL_REDIRECT_URL','session')) == false ? Request('SOCIAL_REDIRECT_URL','session') : __IM_DIR__.'/';
		
		unset($_SESSION['SOCIAL_REDIRECT_URL']);
		
		header('location:'.$redirectUrl);
	}
}
?>