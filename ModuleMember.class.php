<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 회원과 관련된 모든 기능을 제어한다.
 * 
 * @file /modules/member/ModuleMember.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2020. 4. 24.
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
	 * @private object $DB DB접속객체
	 * @private string[] $table DB 테이블 별칭 및 원 테이블명을 정의하기 위한 변수
	 */
	private $DB;
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
	 * 기본 URL (다른 모듈에서 호출되었을 경우에 사용된다.)
	 */
	private $baseUrl = null;
	
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
		$this->table->password = 'member_password_table';
		$this->table->point = 'member_point_table';
		$this->table->social_oauth = 'member_social_oauth_table';
		$this->table->social_sort = 'member_social_sort_table';
		$this->table->social_token = 'member_social_token_table';
		$this->table->label = 'member_label_table';
		$this->table->member_label = 'member_member_label_table';
		$this->table->token = 'member_token_table';
		$this->table->activity = 'member_activity_table';
		$this->table->login = 'member_login_table';
		
		/**
		 * 회원메뉴를 제공하기 위한 자바스크립트 및 스타일시트를 로딩한다.
		 * 회원모듈은 글로벌모듈이기 때문에 모듈클래스 선언부에서 선언해주어야 사이트 레이아웃에 반영된다.
		 */
		if (defined('__IM_ADMIN__') == false) {
			$this->IM->addHeadResource('style',$this->getModule()->getDir().'/styles/style.css');
			$this->IM->addHeadResource('script',$this->getModule()->getDir().'/scripts/script.js');
		}
		
		/**
		 * SESSION 을 검색하여 현재 로그인중인 사람의 정보를 구한다.
		 */
		$this->logged = Request('IM_MEMBER_LOGGED','session') != null && Decoder(Request('IM_MEMBER_LOGGED','session')) != false ? json_decode(Decoder(Request('IM_MEMBER_LOGGED','session'))) : null;
		
		/**
		 * 세션토큰이 있을 경우 로그인처리를 한다.
		 */
		$session = Request('session');
		if ($session !== null) $this->loginBySessionToken($session);
		
		/**
		 * 로그인 세션쿠키를 이용해 로그인처리를 한다.
		 */
		$cookie = Request('IM_MEMBER_COOKIE','cookie');
		if ($cookie !== null) $this->loginByCookie($cookie);
		
		/**
		 * 통합로그인을 사용한다고 설정되어 있을 경우 통합로그인 세션처리를 위한 자바스크립트 파일을 로딩한다.
		 */
		if (defined('__IM_ADMIN__') == false && $this->getModule()->getConfig('universal_login') == true) $this->IM->addHeadResource('script',$this->getModule()->getDir().'/scripts/session.js');
		
		/**
		 * 이메일 인증을 사용하고 있고, 이메일 인증이 완료되지 않은 회원이 로그인하였을 경우
		 */
		if (defined('__IM_SITE__') == true && defined('__IM_CONTAINER__') == false && $this->getModule()->getConfig('verified_email') == true && $this->isLogged() == true && $this->getMember(null,true,false)->verified == 'FALSE') {
			header('location:'.$this->IM->getModuleUrl('member','verification'));
		}
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
		if ($this->DB == null || $this->DB->ping() === false) $this->DB = $this->IM->db($this->getModule()->getInstalled()->database);
		return $this->DB;
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
	 * URL 을 가져온다.
	 *
	 * @param string $view
	 * @param string $idx
	 * @return string $url
	 */
	function getUrl($view=null,$idx=null) {
		$url = $this->baseUrl ? $this->baseUrl : $this->IM->getUrl(null,null,false);
		
		$view = $view === null ? $this->getView($this->baseUrl) : $view;
		if ($view == null || $view == false) return $url;
		$url.= '/'.$view;
		
		$idx = $idx === null ? $this->getIdx($this->baseUrl) : $idx;
		if ($idx == null || $idx == false) return $url;
		
		return $url.'/'.$idx;
	}
	
	/**
	 * 다른모듈에서 호출된 경우 baseUrl 을 설정한다.
	 *
	 * @param string $url
	 * @return $this
	 */
	function setUrl($url) {
		$this->baseUrl = $this->IM->getUrl(null,null,$url,false);
		return $this;
	}
	
	/**
	 * view 값을 가져온다.
	 *
	 * @return string $view
	 */
	function getView() {
		return $this->IM->getView($this->baseUrl);
	}
	
	/**
	 * idx 값을 가져온다.
	 *
	 * @return string $idx
	 */
	function getIdx() {
		return $this->IM->getIdx($this->baseUrl);
	}
	
	/**
	 * [코어] 사이트 외부에서 현재 모듈의 API를 호출하였을 경우, API 요청을 처리하기 위한 함수로 API 실행결과를 반환한다.
	 * 소스코드 관리를 편하게 하기 위해 각 요쳥별로 별도의 PHP 파일로 관리한다.
	 *
	 * @param string $protocol API 호출 프로토콜 (get, post, put, delete)
	 * @param string $api API명
	 * @param any $idx API 호출대상 고유값
	 * @param object $params API 호출시 전달된 파라메터
	 * @return object $datas API처리후 반환 데이터 (해당 데이터는 /api/index.php 를 통해 API호출자에게 전달된다.)
	 * @see /api/index.php
	 */
	function getApi($protocol,$api,$idx=null,$params=null) {
		$data = new stdClass();
		
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('beforeGetApi',$this->getModule()->getName(),$api,$values);
		
		/**
		 * 모듈의 api 폴더에 $api 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->getModule()->getPath().'/api/'.$api.'.'.$protocol.'.php') == true) {
			INCLUDE $this->getModule()->getPath().'/api/'.$api.'.'.$protocol.'.php';
		}
		
		unset($values);
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('afterGetApi',$this->getModule()->getName(),$api,$values,$data);
		
		return $data;
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
	 * @return object $lists 전체 컨텍스트 목록
	 */
	function getContexts() {
		$lists = array();
		foreach ($this->getText('context') as $context=>$title) {
			$lists[] = array('context'=>$context,'title'=>$title);
		}
		
		return $lists;
	}
	
	/**
	 * 특정 컨텍스트에 대한 제목을 반환한다.
	 *
	 * @param string $context 컨텍스트명
	 * @return string $title 컨텍스트 제목
	 */
	function getContextTitle($context) {
		return $this->getText('context/'.$context);
	}
	
	/**
	 * [사이트관리자] 모듈의 컨텍스트 환경설정을 구성한다.
	 *
	 * @param object $site 설정대상 사이트
	 * @param object $values 설정값
	 * @param string $context 설정대상 컨텍스트명
	 * @return object[] $configs 환경설정
	 */
	function getContextConfigs($site,$values,$context) {
		$configs = array();
		
		if ($context == 'signup') {
			$label = new stdClass();
			$label->title = $this->getText('text/label');
			$label->name = 'label';
			$label->type = 'select';
			$label->data = array();
			$label->data[] = array('',$this->getText('text/select_label'));
			$label->data[] = array(0,$this->getText('text/no_label'));
			$labels = $this->db()->select($this->table->label,'idx,title')->get();
			for ($i=0, $loop=count($labels);$i<$loop;$i++) {
				$label->data[] = array($labels[$i]->idx,$labels[$i]->title);
			}
			$label->value = $values != null && isset($values->label) == true ? $values->label : '';
			$configs[] = $label;
		}
		
		$templet = new stdClass();
		$templet->title = $this->IM->getText('text/templet');
		$templet->name = 'templet';
		$templet->type = 'templet';
		$templet->target = 'member';
		$templet->use_default = true;
		$templet->value = $values != null && isset($values->templet) == true ? $values->templet : '#';
		$configs[] = $templet;
		
		return $configs;
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
	 * 언어셋파일에 정의된 코드를 이용하여 사이트에 설정된 언어별로 텍스트를 반환한다.
	 * 코드에 해당하는 문자열이 없을 경우 1차적으로 package.json 에 정의된 기본언어셋의 텍스트를 반환하고, 기본언어셋 텍스트도 없을 경우에는 코드를 그대로 반환한다.
	 *
	 * @param string $code 언어코드
	 * @param string $replacement 일치하는 언어코드가 없을 경우 반환될 메세지 (기본값 : null, $code 반환)
	 * @return string $language 실제 언어셋 텍스트
	 */
	function getText($code,$replacement=null) {
		if ($this->lang == null) {
			if (is_file($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json'));
				if ($this->IM->language != $this->getModule()->getPackage()->language && is_file($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json') == true) {
					$this->oLang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json'));
				}
			} elseif (is_file($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json') == true) {
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
		
		$this->IM->fireEvent('afterGetText',$this->getModule()->getName(),$code,$returnString);
		
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
		$error->type = 'BACK';
		
		if ($isRawData === true) return $error;
		else return $this->IM->getErrorText($error);
	}
	
	/**
	 * 템플릿 정보를 가져온다.
	 *
	 * @param string $this->getTemplet($configs) 템플릿명
	 * @return string $package 템플릿 정보
	 */
	function getTemplet($templet=null) {
		$templet = $templet == null ? '#' : $templet;
		
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
	 * 모듈 외부컨테이너를 가져온다.
	 *
	 * @param string $container 컨테이너명
	 * @return string $html 컨텍스트 HTML / FileBytes 파일 바이너리
	 */
	function getContainer($container) {
		$this->IM->removeTemplet();
		
		switch ($container) {
			case 'signup' :
				$html = $this->getContext('signup');
				break;
				
			case 'modify' :
				$html = $this->getContext('modify');
				break;
				
			case 'verification' :
				$html = $this->getContext('verification');
				break;
				
			case 'password' :
				$html = $this->getContext('password');
				break;
				
			case 'photo' :
				$midx = $this->getView() ? $this->getView() : 0;
				
				if (($this->getModule()->getConfig('photo_privacy') == false || $this->isLogged() == true) && is_file($this->IM->getAttachmentPath().'/member/'.$midx.'.jpg') == true) {
					$mime = 'image/jpeg';
					$path = $this->IM->getAttachmentPath().'/member/'.$midx.'.jpg';
				} else {
					$mime = 'image/png';
					$path = $this->getModule()->getPath().'/images/nophoto.png';
				}
				
				header('Content-Type: '.$mime);
				header('Content-Length: '.filesize($path));
				
				readfile($path);
				exit;
		}
		
		$footer = $this->IM->getFooter();
		$header = $this->IM->getHeader();
		
		return $header.$html.$footer;
	}
	
	/**
	 * 페이지 컨텍스트를 가져온다.
	 *
	 * @param string $context 컨텍스트 종류
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getContext($context,$configs=null) {
		/**
		 * 컨텍스트 컨테이너를 설정한다.
		 */
		$html = PHP_EOL.'<!-- MEMBER MODULE -->'.PHP_EOL.'<div data-role="context" data-type="module" data-module="'.$this->getModule()->getName().'" data-base-url="'.($this->baseUrl == null ? $this->IM->getUrl(null,null,false) : $this->baseUrl).'" data-context="'.$context.'" data-configs="'.GetString(json_encode($configs),'input').'">'.PHP_EOL;
		
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
				
			case 'verification' :
				$html.= $this->getVerificationContext($configs);
				break;
				
			case 'password' :
				$html.= $this->getPasswordContext($configs);
				break;
				
			case 'point' :
				$html.= $this->getPointContext($configs);
				break;
				
			case 'connect' :
				$html.= $this->getConnectContext($configs);
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
	 * @param object $confgs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return $html 컨텍스트 HTML
	 */
	function getSignUpContext($configs=null) {
		if ($this->getModule()->getConfig('allow_signup') == false) return $this->getError('NOT_ALLOWED_SIGNUP');
		
		$step = $this->getView() !== null ? $this->getView() : 'start';
		$label = $configs != null && isset($configs->label) == true && strlen($configs->label) > 0 ? $configs->label : null;
		$label = $this->getIdx() !== null ? $this->getIdx() : $label;
		
		if ($this->isLogged() == true && $step != 'complete') return $this->getError('ALREADY_LOGGED');
		if ($step == 'complete' && $this->isLogged() == false) return $this->getError('FORBIDDEN');
		
		if ($step == 'start') {
			if ($label === null && $this->db()->select($this->table->label)->where('allow_signup','TRUE')->count() > 0) {
				$labels = $this->db()->select($this->table->label)->where('allow_signup','TRUE')->orderBy('sort','asc')->get();
				
				$header = PHP_EOL.'<form id="ModuleMemberSignUpForm" method="post">'.PHP_EOL;
				$header.= '<input type="hidden" name="step" value="'.$step.'">'.PHP_EOL;
				$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Member.signup.init();</script>'.PHP_EOL;
				
				return $this->getTemplet($configs)->getContext('signup',get_defined_vars(),$header,$footer);
			} elseif ($label === null) {
				$label = 0;
			}
			
			// 약관동의나, 개인정보보호정책 등 동의항목이 있는 경우
			if ($this->db()->select($this->table->signup)->where('label',$label)->where('(type=? or type=?)',array('agreement','privacy'))->has() == true) {
				header("location:".$this->getUrl('agreement',$label));
				exit;
			} else {
				header("location:".$this->getUrl('register',$label));
				exit;
			}
		} elseif ($label === null) {
			$label = 0;
		}
		
		if ($step != 'start' && $step != 'complete' && $label === null) return $this->getError('NOT_FOUND_PAGE');
		if ($this->getLabel($label) == null || $this->getLabel($label)->allow_signup == false) return $this->getTemplet($configs)->getError('NOT_ALLOWED_SIGNUP',$this->getLabel($label));
		
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
				$agreement->name = $form->name;
				$agreement->title = $title == 'LANGUAGE_SETTING' ? $this->getText('text/agreement') : $title;
				$agreement->content = $configs->content;
				$agreement->help = $help == 'LANGUAGE_SETTING' ? $this->getText('signup/agree') : $help;
				$agreement->value = 'TRUE';
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
				$privacy->name = $form->name;
				$privacy->title = $title == 'LANGUAGE_SETTING' ? $this->getText('text/agreement') : $title;
				$privacy->content = $configs->content;
				$privacy->help = $help == 'LANGUAGE_SETTING' ? $this->getText('signup/agree') : $help;
				$privacy->value = 'TRUE';
			} else {
				$privacy = null;
			}
			
			$nextStep = 'register';
		}
		
		if ($step == 'register') {
			/**
			 * 가입폼을 가져온다.
			 * 회원약관이나, 개인정보보호정책 등 이미 동의한 항목은 생략한다.
			 */
			$fields = array();
			$defaults = $extras = array();
			$forms = $this->db()->select($this->table->signup)->where('label',array(0,$label),'IN')->orderBy('sort','asc')->get();
			for ($i=0, $loop=count($forms);$i<$loop;$i++) {
				if (in_array($forms[$i]->name,array('agreement','privacy')) == true) continue;
				if (in_array($forms[$i]->name,$fields) == true) continue;
				
				$field = $this->getInputField($forms[$i]);
				$field->inputHtml = $this->getInputFieldHtml($field);
				
				if ($forms[$i]->label == 0) array_push($defaults,$field);
				else array_push($extras,$field);
				
				array_push($fields,$forms[$i]->name);
			}
			
			$nextStep = 'complete';
		}
		
		if ($step == 'complete') {
			$member = $this->getMember();
			$is_verified_email = $this->getModule()->getConfig('verified_email');
			
			$verified_email_link = $this->IM->getModuleUrl('member',defined('__IM_CONTAINER_POPUP__') == true ? '@verification' : 'verification',false);
			
			$nextStep = false;
		}
		
		/**
		 * 회원가입폼을 정의한다.
		 */
		$header = PHP_EOL.'<form id="ModuleMemberSignUpForm" method="post" action="'.$this->getUrl($nextStep,$label).'">'.PHP_EOL;
		$header.= '<input type="hidden" name="step" value="'.$step.'">'.PHP_EOL;
		if ($step != 'start') $header.= '<input type="hidden" name="label" value="'.$label.'">'.PHP_EOL;
		if ($step == 'register') {
			foreach ($_POST as $key=>$value) {
				$header.= '<input type="hidden" name="'.$key.'" value="'.$value.'">'.PHP_EOL;
			}
		}
		$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Member.signup.init();</script>'.PHP_EOL;
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('signup',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 회원가입 컨텍스트를 가져온다.
	 *
	 * @param object $confgs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return $html 컨텍스트 HTML
	 */
	function getModifyContext($configs=null) {
		if ($this->isLogged() == false) return $this->getError('REQUIRED_LOGIN');
		
		$member = $this->getMember();
		
		$password = Request('password');
		if (strlen($member->password) == 65 && $password == null) {
			$step = 'password';
		} else {
			if (strlen($member->password) == 65) {
				$mHash = new Hash();
				$password = $password === null ? false : Decoder($password);
				if ($password === false || $mHash->password_validate($password,$member->password) == false) return $this->getError('INCORRECT_PASSWORD');
			}
			$step = 'insert';
			
			$this->IM->addHeadResource('script',$this->getModule()->getDir().'/scripts/jquery.cropit.min.js');
			
			$member = $this->getMember();
			$labels = array(0);
			foreach ($member->label as $label) {
				$labels[] = $label->idx;
			}
			
			/**
			 * 회원 필드를 가져온다.
			 * 회원약관이나, 개인정보보호정책 등 이미 동의한 항목은 생략한다.
			 */
			$fields = array();
			$defaults = $extras = array();
			
			$photo = $this->getInputField('photo');
			$photo->inputHtml = $this->getInputFieldHtml($photo,$member);
			array_push($defaults,$photo);
			
			$forms = $this->db()->select($this->table->signup)->where('label',$labels,'IN')->orderBy('sort','asc')->get();
			for ($i=0, $loop=count($forms);$i<$loop;$i++) {
				if (in_array($forms[$i]->name,array('agreement','privacy')) == true) continue;
				if (in_array($forms[$i]->name,$fields) == true) continue;
				
				$field = $this->getInputField($forms[$i]);
				$field->inputHtml = $this->getInputFieldHtml($field,$member);
				
				if ($forms[$i]->label == 0) array_push($defaults,$field);
				else array_push($extras,$field);
				
				array_push($fields,$forms[$i]->name);
			}
			
			/**
			 * 소셜계정연결을 가져온다.
			 */
			$oauths = $this->db()->select($this->table->social_oauth.' o','o.site')->join($this->table->social_sort.' s','s.site=o.site','LEFT')->where('o.domain',array('*',$this->IM->domain),'IN')->groupBy('o.site')->orderBy('s.sort','asc')->get();
			for ($i=0, $loop=count($oauths);$i<$loop;$i++) {
				$site = $this->db()->select($this->table->social_oauth)->where('domain',array('*',$this->IM->domain),'IN')->where('site',$oauths[$i]->site)->getOne();
				$token = $this->db()->select($this->table->social_token)->where('midx',$this->getLogged())->where('domain',$site->domain)->where('site',$site->site)->getOne();
				
				$oauths[$i] = new stdClass();
				$oauths[$i]->site = $site;
				$oauths[$i]->token = $token;
			}
		}
		
		/**
		 * 회원가입폼을 정의한다.
		 */
		$header = PHP_EOL.'<form id="ModuleMemberModifyForm">'.PHP_EOL;
		$header.= '<input type="hidden" name="step" value="'.$step.'">'.PHP_EOL;
		if ($password) $header.= '<input type="hidden" name="password" value="'.Encoder($password).'">'.PHP_EOL;
		$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Member.modify.init();</script>'.PHP_EOL;
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('modify',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 이메일인증 컨텍스트를 가져온다.
	 *
	 * @param object $confgs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return $html 컨텍스트 HTML
	 */
	function getVerificationContext($configs=null) {
		$mode = null;
		$token = $this->getView();
		
		/**
		 * 이메일에 포함된 인증링크를 클릭한 경우, 링크에 포함된 토큰으로 인증한다.
		 */
		if ($token) {
			$mode = 'token';
			
			$check = Decoder($token,null,'hex') !== false ? json_decode(Decoder($token,null,'hex')) : null;
			if ($check == null) return $this->printError('INVALID_EMAIL_VERIFICATION_TOKEN');
			
			$code = $this->db()->select($this->table->email)->where('midx',$check[0])->where('email',$check[1])->getOne();
			if ($code == null) return $this->printError('INVALID_EMAIL_VERIFICATION_TOKEN');
			
			$member = $this->getMember($code->midx);
		}
		
		/**
		 * 현재 로그인이 되어 있는 경우 로그인 정보와 인증코드를 이용하여 인증한다.
		 */
		if ($mode == null && $this->isLogged() == true) {
			$mode = 'code';
			
			$member = $this->getMember();
		}
		
		if ($mode == null) return $this->printError('INVALID_EMAIL_VERIFICATION_TOKEN');
		
		$header = PHP_EOL.'<form id="ModuleMemberVerificationForm">'.PHP_EOL;
		$header.= '<input type="hidden" name="mode" value="'.$mode.'">'.PHP_EOL;
		if ($mode == 'token') {
			$header.= '<input type="hidden" name="token" value="'.$token.'">'.PHP_EOL;
		}
		$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Member.verification.init();</script>'.PHP_EOL;
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('verification',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 패스워드 찾기 컨텍스트를 가져온다.
	 *
	 * @param object $confgs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return $html 컨텍스트 HTML
	 */
	function getPasswordContext($configs=null) {
		$view = $this->getView() ? $this->getView() : 'send';
		
		if ($view == 'reset') {
			$token = $this->getIdx();
			
			$check = $this->db()->select($this->table->password)->where('token',$token)->getOne();
			if ($check == null || $check->status == 'RESET' || $check->reg_date < time() - 60 * 60 * 6) return $this->getError('EXPIRED_LINK');
			
			$member = $this->getMember($check->midx);
			if ($member->idx == 0) return $this->getError('EXPIRED_LINK');
		}
		
		$header = PHP_EOL.'<form id="ModuleMemberPasswordForm">'.PHP_EOL;
		if ($view == 'reset') {
			$header.= '<input type="hidden" name="token" value="'.$token.'">'.PHP_EOL;
		}
		$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Member.password.init();</script>'.PHP_EOL;
		
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('password',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 포인트현황 컨텍스트를 가져온다.
	 *
	 * @param object $confgs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return $html 컨텍스트 HTML
	 */
	function getPointContext($configs=null) {
		if ($this->isLogged() == false) return $this->getError('REQUIRED_LOGIN');
		
		$member = $this->getMember();
		
		$view = $this->getView() ? $this->getView() : 'all';
		$t = Request('t');
		$p = is_numeric($this->getIdx()) == true && $this->getIdx() > 0 ? $this->getIdx() : 1;
		$limit = 20;
		$start = ($p - 1) * $limit;
		
		if ($t != null) {
			$check = $this->db()->select($this->table->point)->where('midx',$this->getLogged())->where('reg_date',$t)->getOne();
			if ($check != null) {
				$later = $this->db()->select($this->table->point)->where('midx',$this->getLogged())->where('reg_date',$t,'>')->count();
				$p = ceil($later/$limit);
			} else {
				$t = null;
			}
		}
		
		$lists = $this->db()->select($this->table->point)->where('midx',$this->getLogged());
		if ($view == 'increase') $lists->where('point',0,'>');
		elseif ($view == 'decrease') $lists->where('point',0,'<');
		
		$total = $lists->copy()->count();
		$lists = $lists->orderBy('reg_date','desc')->limit($start,$limit)->get();
		
		$loopnum = $total - ($p - 1) * $limit;
		for ($i=0, $loop=count($lists);$i<$loop;$i++) {
			$lists[$i]->loopnum = $loopnum - $i;
			
			if ($lists[$i]->module) {
				$mModule = $this->IM->getModule($lists[$i]->module);
				if (method_exists($mModule,'syncMember') == true) {
					$code = new stdClass();
					$code->code = $lists[$i]->code;
					$code->midx = $lists[$i]->midx;
					$code->content = json_decode($lists[$i]->content);
					$lists[$i]->content = $mModule->syncMember('point_history',$code);
				}
			}
			
			$accumulation = $this->db()->select($this->table->point,'sum(point) as accumulation')->where('midx',$this->getLogged())->where('reg_date',$lists[$i]->reg_date,'<=')->getOne();
			$lists[$i]->accumulation = $accumulation->accumulation ? $accumulation->accumulation : 0;
			
			$lists[$i]->reg_date = floor($lists[$i]->reg_date / 1000);
		}
		
		$pagination = $this->getTemplet()->getPagination($p,ceil($total/$limit),10,$this->getUrl($view,'{PAGE}'));
		
		$header = PHP_EOL.'<form id="ModuleMemberPointForm">'.PHP_EOL;
		$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Member.point.init("ModuleMemberPointForm");</script>'.PHP_EOL;
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('point',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 소셜계정 연결 컨텍스트를 가져온다.
	 *
	 * @param object $confgs 사이트맵
	 * @return $html 컨텍스트 HTML
	 */
	function getConnectContext($configs=null) {
		/**
		 * 소셜 로그인 세션을 가져온다.
		 */
		$logged = Request('IM_SOCIAL_LOGGED','session');
		if ($logged == null) {
			$this->printError('OAUTH_API_ERROR',null,null,true);
		}
		
		if (is_array($logged->midx) == true) {
			$type = 'select';
			
			$members = array();
			for ($i=0, $loop=count($logged->midx);$i<$loop;$i++) {
				$members[$i] = $this->getMember($logged->midx[$i]);
			}
			
			$header = PHP_EOL.'<form id="ModuleMemberConnectForm">'.PHP_EOL;
			$footer = PHP_EOL.'</form>'.PHP_EOL;
		} else {
			$type = 'login';
			$member = $this->getMember($logged->midx);
			
			$header = PHP_EOL.'<form id="ModuleMemberConnectForm">'.PHP_EOL;
			$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Member.connect.init();</script>'.PHP_EOL;
		}
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('connect',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 로그인 모달을 가져온다.
	 *
	 * @return string $modalHtml
	 */
	function getLoginModal() {
		$title = $this->getText('text/login');
		
		$content = PHP_EOL;
		$content.= '<div data-role="input"><input type="email" name="email" placeholder="'.$this->getText('text/email').'"></div>';
		$content.= '<div data-role="input"><input type="password" name="password" placeholder="'.$this->getText('text/password').'"></div>';
		
		$oauths = $this->db()->select($this->table->social_oauth.' o','o.site')->join($this->table->social_sort.' s','s.site=o.site','LEFT')->where('o.domain',array('*',$this->IM->domain),'IN')->groupBy('o.site')->orderBy('s.sort','asc')->get();
		if (count($oauths) > 0) {
			$content.= '<ul data-module="member" data-role="social" class="'.(count($oauths) > 3 ? 'icon' : 'button').'">';
			foreach ($oauths as $oauth) {
				$content.= '<li class="'.$oauth->site.'"><a href="'.$this->getSocialLoginUrl($oauth->site).'"><i></i><span>'.str_replace('{SITE}',$this->getText('social/'.$oauth->site),$this->getText('social/login')).'</span></a></li>';
			}
			$content.= '</ul>';
		}
		
		if ($this->getModule()->getConfig('allow_signup') == true || $this->getModule()->getConfig('allow_reset_password') == true) {
			$content.= '<ul data-module="member" data-role="link">';
			$content.= '<li><div data-role="input"><label><input type="checkbox" name="remember" value="TRUE">'.$this->getText('text/remember').'</label></div></li>';
			if ($this->getModule()->getConfig('allow_signup') == true) {
				$signup = $this->IM->getContextUrl('member','signup');
				$content.= '<li>'.($signup == null ? '<button type="button" onclick="Member.signupPopup();">'.$this->getText('text/signup').'</button>' : '<a href="'.$signup.'">'.$this->getText('text/signup').'</a>').'</li>';
			}
			
			if ($this->getModule()->getConfig('allow_signup') == true) {
				$help = $this->IM->getContextUrl('member','password');
				$content.= '<li>'.($help == null ? '<button type="button" onclick="Member.helpPopup();">'.$this->getText('text/help').'</button>' : '<a href="'.$help.'">'.$this->getText('text/help').'</a>').'</li>';
			}
			$content.= '</ul>';
		}
		
		$buttons = array();
		
		$button = new stdClass();
		$button->type = 'close';
		$button->text = '취소';
		$buttons[] = $button;
		
		$button = new stdClass();
		$button->type = 'submit';
		$button->text = '로그인';
		$buttons[] = $button;
		
		return $this->getTemplet()->getModal($title,$content,true,array('width'=>300),$buttons);
	}
	
	/**
	 * 회원사진 편집 모달을 가져온다.
	 *
	 * @return string $modalHtml
	 */
	function getPhotoModal() {
		$title = $this->getText('text/photo');
		
		$content = '<div data-role="photo-editor">'.PHP_EOL;
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
		
		$buttons = array();
		
		$button = new stdClass();
		$button->type = 'close';
		$button->text = '취소';
		$buttons[] = $button;
		
		$button = new stdClass();
		$button->type = 'upload';
		$button->text = '파일선택';
		$button->class = 'danger';
		$buttons[] = $button;
		
		$button = new stdClass();
		$button->type = 'submit';
		$button->text = '확인';
		$buttons[] = $button;
		
		return $this->getTemplet()->getModal($title,$content,true,array('width'=>400),$buttons);
	}
	
	/**
	 * 회원사진 편집 모달을 가져온다.
	 *
	 * @return string $modalHtml
	 */
	function getPasswordModal() {
		$title = $this->getText('signup/password_change');
		
		$content = '<input type="hidden" name="password">';
		$content.= '<div data-role="input"><input type="password" name="new_password" placeholder="'.$this->getText('signup/new_password').'"></div>';
		$content.= '<div data-role="input"><input type="password" name="new_password_confirm" placeholder="'.$this->getText('signup/password_confirm').'"></div>';
		
		$buttons = array();
		
		$button = new stdClass();
		$button->type = 'close';
		$button->text = '취소';
		$buttons[] = $button;
		
		$button = new stdClass();
		$button->type = 'submit';
		$button->text = '확인';
		$buttons[] = $button;
		
		return $this->getTemplet()->getModal($title,$content,true,array('width'=>300),$buttons);
	}
	
	/**
	 * API 호출에서 회원인증을 처리한다.
	 *
	 * @param string $authorization 인증헤더를 통해 넘어온 엑세스 토큰
	 */
	function authorizationToken($authorization) {
		$authorization = explode(' ',$authorization);
		$type = strtoupper(array_shift($authorization));
		$token = implode(' ',$authorization);
		
		/**
		 * iModule 의 경우 BEARER 형식의 토큰만 처리한다.
		 * 다른 방식의 경우 Event 를 발생시켜 다른 플러그인 또는 모듈에서 받아서 처리할 수 있도록 한다.
		 */
		if ($type != 'BEARER') {
			$this->IM->fireEvent('authorization',$this->getModule()->getName(),$type,$token);
			
			/**
			 * 이벤트 처리결과 로그인 상태가 아니라면 에러메세지를 발생한다.
			 */
			if ($this->isLogged() == false) {
				header("HTTP/1.1 401 Unauthorized");
				header("Content-type: text/json; charset=utf-8",true);
				
				$results = new stdClass();
				$results->success = false;
				$results->message = 'Access token Error : Unauthorized';
		
				exit(json_encode($results,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
			}
			
			return true;
		} elseif (Decoder($token,null,'hex') !== false) {
			$data = json_decode(Decoder($token,null,'hex'));
			$idx = $data->idx;
			$client_id = $data->client_id;
			$this->login($idx);
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * 회원로그인 세션을 만든다.
	 * 탈퇴회원이나, 비활성화 계정의 경우 세션을 만들지 않는다.
	 *
	 * @param int $midx 회원 고유번호
	 * @param boolean $isLogged 로그인여부
	 */
	function login($midx,$is_fire_event=true,$is_logging=true) {
		if ($this->getLogged() == $midx) return true;
		$member = $this->db()->select($this->table->member)->where('idx',$midx)->getOne();
		if ($member == null || in_array($member->status,array('LEAVE','DEACTIVATED')) == true) return false;
		
		$logged = new stdClass();
		$logged->idx = $midx;
		$logged->time = time();
		$logged->ip = $_SERVER['REMOTE_ADDR'];
		
		if ($is_logging == true) {
			if ($this->isLogged() == true) {
				$activity = $this->addActivity($midx,0,'member','login_from',array('origin'=>$this->getLogged()));
			} else {
				$this->db()->update($this->table->member,array('latest_login'=>$logged->time))->where('idx',$midx)->execute();
				$activity = $this->addActivity($midx,0,'member','login',array('referer'=>(isset($_SERVER['HTTP_REFERER']) == true ? $_SERVER['HTTP_REFERER'] : '')));
			}
		}
		
		$_SESSION['IM_MEMBER_LOGGED'] = Encoder(json_encode($logged));
		$this->logged = $logged;
		
		unset($_SESSION['LOGGED_FAIL']);
		
		if ($is_fire_event == true) {
			$results = new stdClass();
			$results->success = true;
			
			$values = new stdClass();
			$this->IM->fireEvent('afterDoProcess',$this->getModule()->getName(),'login',$values,$results);
		}
		
		return true;
	}
	
	/**
	 * 현재 로그인된 세션을 저장한다.
	 */
	function makeCookie() {
		$midx = $this->getLogged();
		
		/**
		 * 로그인상태가 아니거나, 이미 로그인 쿠키가 있다면 저장을 취소한다.
		 */
		if ($midx == 0 || Request('IM_MEMBER_COOKIE','cookie') != null) return;
		
		$this->db()->setLockMethod('WRITE')->lock($this->table->login);
		/**
		 * 현재 회원번호와 현재 접속한 아이피로 생성된 해시가 있다면 그것을 사용한다.
		 */
		$check = $this->db()->select($this->table->login)->where('midx',$midx)->where('latest_ip',$_SERVER['REMOTE_ADDR'])->getOne();
		if ($check != null) {
			$hash = $check->hash;
		} else {
			while (true) {
				$hash = sha1($this->getLogged().time().$_SERVER['REMOTE_ADDR'].rand(1000,9999));
				if ($this->db()->select($this->table->login)->where('hash',$hash)->has() == false) break;
			}
		}
		$this->db()->replace($this->table->login,array('hash'=>$hash,'midx'=>$midx,'latest_ip'=>$_SERVER['REMOTE_ADDR'],'latest_date'=>time()))->execute();
		$this->db()->unlock();
		
		setcookie('IM_MEMBER_COOKIE',$hash,time() + 60 * 60 * 24 * 365,'/');
	}
	
	/**
	 * 특정 로그인쿠키를 제거한다.
	 *
	 * @param string $hash
	 */
	function removeCookie($hash) {
		if (Request('IM_MEMBER_COOKIE','cookie') == $hash) {
			setcookie('IM_MEMBER_COOKIE','',time() - 3600,'/');
		}
		
		$this->db()->delete($this->table->login)->where('hash',$hash)->execute();
	}
	
	/**
	 * 로그인 세션 쿠키로부터 로그인을 처리한다.
	 */
	function loginByCookie($cookie=null) {
		/**
		 * 이미 로그인되어 있다면 무시한다.
		 */
		if ($this->isLogged() == true) return;
		$hash = $cookie == null ? Request('IM_MEMBER_COOKIE','cookie') : $cookie;
		if ($hash == null) return;
		
		$check = $this->db()->select($this->table->login)->where('hash',$hash)->getOne();
		
		/**
		 * 로그인세션 테이블에 없다면, 쿠키를 삭제한다.
		 */
		if ($check == null) {
			setcookie('IM_MEMBER_COOKIE','',time() - 3600,'/');
		} else {
			setcookie('IM_MEMBER_COOKIE',$hash,time() + 60 * 60 * 24 * 365,'/');
			$this->login($check->midx);
			$this->db()->update($this->table->login,array('latest_ip'=>$_SERVER['REMOTE_ADDR'],'latest_date'=>time()))->where('hash',$hash)->execute();
		}
	}
	
	/**
	 * GET 으로 전달받은 세션토큰으로 로그인을 처리한다.
	 *
	 * @param string $session 세션토큰
	 */
	function loginBySessionToken($session) {
		$token = Decoder($session,null,'hex');
		$session = $token !== false ? json_decode($token) : null;
		
		if ($session != null) {
			if ($session->idx == 0) {
				unset($_SESSION['IM_MEMBER_LOGGED']);
				
				$results->success = true;
				$results->success = null;
			} else {
				$member = $this->db()->select($this->table->member)->where('idx',$session->idx)->getOne();
				if ($member->idx > 0 && in_array($member->status,array('LEAVE','DEACTIVATED')) == false) {
					$logged = new stdClass();
					$logged->idx = $session->idx;
					$logged->time = time();
					$logged->ip = $_SERVER['REMOTE_ADDR'];
					
					$_SESSION['IM_MEMBER_LOGGED'] = Encoder(json_encode($logged));
				}
			}
		}
		
		$url = explode('?',$this->IM->getRequestUri());
		
		header("location:".$url[0].$this->IM->getQueryString(array('session'=>''),$url[1]));
		exit;
	}
	
	/**
	 * 소셜로그인을 통해 로그인을 처리한다.
	 * 가입되어 있지 않은 회원의 경우 자동으로 가입처리를 한다.
	 *
	 * @param string $code 소셜사이트
	 * @param string $client_id OAuth 클라이언트 아이디
	 * @param string $name 소셜사이트 유저아이디
	 * @param string $email 이메일
	 * @param string $photo 프로필 이미지 주소
	 * @param string $accessToken
	 * @param string $refreshToken
	 * @param string $scope OAuth 인증시 요청된 scope
	 * @param string $redirecUrl 로그인 후 이동될 주소
	 */
	function loginBySocial() {
		/**
		 * 소셜 로그인 세션을 가져온다.
		 */
		$logged = Request('IM_SOCIAL_LOGGED','session');
		if ($logged == null) {
			$this->printError('OAUTH_API_ERROR',null,null,true);
		}
		
		if ($this->isLogged() == true) {
			$midx = $this->getLogged();
		} else {
			/**
			 * 기존에 해당 소셜계정으로 로그인한 이력이 있는지 확인한다.
			 */
			$check = $this->db()->select($this->table->social_token)->where('domain',$logged->site->domain)->where('site',$logged->site->site)->where('id',$logged->user->id)->get();
			if (count($check) == 0) {
				/**
				 * 이메일주소로 기존 회원을 검색한다.
				 */
				$check = $this->db()->select($this->table->member)->where('email',$logged->user->email);
				if ($this->IM->getSite(false)->member == 'UNIVERSAL') $check->where('domain','*');
				else $check->where('domain',$this->IM->domain);
				$check = $check->getOne();
				
				/**
				 * 이메일주소로 등록되어 있는 회원계정이 없다면 회원을 생성한다.
				 */
				if ($check == null) {
					if ($this->getModule()->getConfig('allow_signup') == false) {
						$this->printError('NOT_ALLOWED_SIGNUP',null,null,true);
					}
					
					$midx = $this->db()->insert($this->table->member,array(
						'domain'=>$this->IM->getSite(false)->member == 'UNIVERSAL' ? '*' : $this->IM->domain,
						'type'=>'MEMBER',
						'email'=>$logged->user->email,
						'password'=>'',
						'name'=>$logged->user->name,
						'nickname'=>$logged->user->nickname,
						'exp'=>$this->getModule()->getConfig('exp'),
						'point'=>$this->getModule()->getConfig('point'),
						'reg_date'=>time(),
						'status'=>$this->getModule()->getConfig('approve_signup') == true ? 'WAITING' : 'ACTIVATED'
					))->execute();
				} else {
					$midx = $check->idx;
				}
			} elseif (count($check) > 1) {
				$midx = Request('midx');
				
				if ($midx == null) {
					$logged->midx = array();
					for ($i=0, $loop=count($check);$i<$loop;$i++) {
						$logged->midx[$i] = $check[$i]->midx;
					}
					
					$html = $this->getContext('connect');
					$this->IM->removeTemplet();
					$html.= PHP_EOL.$this->IM->getFooter();
					$html = $this->IM->getHeader().PHP_EOL.$html;
					echo $html;
					exit;
				} else {
					$check = $this->db()->select($this->table->social_token)->where('midx',$midx)->where('site',$logged->site->site)->where('id',$logged->user->id)->getOne();
					if ($check == null) {
						$this->printError('FORBIDDEN',null,null,true);
					}
				}
			} else {
				$midx = $check[0]->midx;
			}
			
			$member = $this->db()->select($this->table->member)->where('idx',$midx)->getOne();
			if ($member == null || $member->status != 'ACTIVATED') {
				$this->printError('NOT_ACTIVATED_ACCOUNT',null,null,true);
			}
			
			/**
			 * 검색된 회원이 소셜사이트 로그인을 사용한적이 없고, 회원의 패스워드가 존재하는 경우 계정연결 페이지를 띄운다.
			 */
			$check = $this->db()->select($this->table->social_token)->where('midx',$member->idx)->where('site',$logged->site->site)->getOne();
			if ($check == null && strlen($member->password) == 65) {
				$logged->midx = $member->idx;
				
				$html = $this->getContext('connect');
				$this->IM->removeTemplet();
				$html.= PHP_EOL.$this->IM->getFooter();
				$html = $this->IM->getHeader().PHP_EOL.$html;
				echo $html;
				exit;
			}
		}
		
		/**
		 * 엑세스정보를 갱신한다.
		 */
		$this->db()->replace($this->table->social_token,array('midx'=>$midx,'domain'=>$logged->site->domain,'site'=>$logged->site->site,'id'=>$logged->user->id,'scope'=>$logged->site->scope,'access_token'=>$logged->token->access,'refresh_token'=>$logged->token->refresh,'latest_login'=>time()))->execute();
		
		/**
		 * 회원사진이 없다면 갱신한다.
		 */
		if (file_exists($this->IM->getAttachmentPath().'/member/'.$midx.'.jpg') == false) {
			if (SaveFileFromUrl($logged->user->photo,$this->IM->getAttachmentPath().'/member/'.$midx.'.jpg','image') == true) {
				$this->IM->getModule('attachment')->createThumbnail($this->IM->getAttachmentPath().'/member/'.$midx.'.jpg',$this->IM->getAttachmentPath().'/member/'.$midx.'.jpg',250,250,false,'jpg');
			}
		}
		
		if ($this->isLogged() == false) {
			$this->login($midx);
		}
		unset($_SESSION['OAUTH_ACCESS_TOKEN'],$_SESSION['OAUTH_REFRESH_TOKEN'],$_SESSION['IM_SOCIAL_LOGGED']);
		
		/**
		 * 로그인 콜백 도메인과, 이동할 도메인이 다를 경우 로그인토큰을 생성한 뒤 리다이렉트 한다.
		 */
		$parseUrl = parse_url($logged->redirect);
		if ($parseUrl['host'] != $this->IM->domain) {
			header('location:'.$logged->redirect.(strpos($logged->redirect,'?') === false ? '?' : '&').'session='.$this->makeSessionToken());
		} else {
			header('location:'.$logged->redirect);
		}
		exit;
	}
	
	/**
	 * 현재 사용자가 로그인중인지 확인한다.
	 *
	 * @return boolean $isLogged
	 */
	function isLogged() {
		if ($this->logged === null) return false;
		else return true;
	}
	
	/**
	 * 현재 로그인한 사용자가 최고관리자인지 확인한다.
	 *
	 * @param int $midx 회원고유번호 (없을경우 현재 로그인한 사용자)
	 * @return boolean $isAdmin
	 */
	function isAdmin($midx=null) {
		$member = $this->getMember($midx);
		return $member->type == 'ADMINISTRATOR';
	}
	
	/**
	 * 현재 로그인한 사용자가 특정모듈의 관리자권한이 있는지 확인한다.
	 *
	 * @param int $midx 회원고유번호 (없을경우 현재 로그인한 사용자)
	 * @param string $module 모듈명 (없을경우 전체모듈)
	 * @return boolean $hasAdmin
	 */
	function hasAdmin($midx=null,$module=null) {
		if ($this->isAdmin() == true) return true;
		
		if ($module == null) {
			$modules = $this->getModule()->getAdminModules();
			for ($i=0, $loop=count($modules);$i<$loop;$i++) {
				$mModule = $this->IM->getModule($modules[$i]->module);
				if (method_exists($mModule,'isAdmin') == true && $mModule->isAdmin($midx) !== false) return true;
			}
		} elseif ($this->getModule()->isInstalled($module) == true) {
			$mModule = $this->IM->getModule($module);
			if (method_exists($mModule,'isAdmin') == true && $mModule->isAdmin($midx) !== false) return true;
		}
		
		return false;
	}
	
	/**
	 * 세션토큰을 생성한다.
	 * 서로 다른 도메인간 통합로그인을 사용하기 위해 사용된다.
	 *
	 * @param int $midx 회원고유값
	 * @return string $token
	 */
	function makeSessionToken($midx=null) {
		$midx = $midx == null ? $this->getLogged() : $midx;
		$token = array('idx'=>$this->getLogged(),'ip'=>ip2long($_SERVER['REMOTE_ADDR']),'lifetime'=>time() + 60);
		return Encoder(json_encode($token),null,'hex');
	}
	
	/**
	 * API 인증토큰을 생성한다.
	 * 회원인증이 필요한 API 호출시 사용된다.
	 *
	 * @return string $token
	 */
	function makeAuthToken($client_id,$idx) {
		$token = array('idx'=>$idx,'client_id'=>$client_id);
		return Encoder(json_encode($token),null,'hex');
	}
	
	/**
	 * 전체 회원라벨을 가져온다.
	 *
	 * @return object[] $labels
	 */
	function getLabels() {
		$labels = $this->db()->select($this->table->label)->orderBy('sort','asc')->get();
		for ($i=0, $loop=count($labels);$i<$loop;$i++) {
			$labels[$i] = $this->getLabel($labels[$i]->idx);
		}
		
		return $labels;
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
				
				unset($label->languages,$label->member,$label->sort);
			}
		}
		
		$this->labels[$idx] = $label;
		return $this->labels[$idx];
	}
	
	/**
	 * 회원라벨 정보를 업데이트한다.
	 *
	 * @param int $label 라벨고유번호
	 */
	function updateLabel($label) {
		$count = $this->db()->select($this->table->member_label)->where('label',$label)->count();
		$this->db()->update($this->table->label,array('member'=>$count))->where('idx',$label)->execute();
	}
	
	/**
	 * 현재 로그인중인 회원고유번호를 가져온다.
	 *
	 * @return int $midx 회원고유번호, 로그인되어 있지 않은 경우 0 반환
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
	function getMember($midx=null,$forceReload=false,$fireEvent=true) {
		$midx = $midx !== null ? $midx : $this->getLogged();
		if ($forceReload == true || isset($this->members[$midx]) == false) {
			$member = $this->db()->select($this->table->member)->where('idx',$midx)->getOne();
			
			if ($member == null) {
				$member = new stdClass();
				$member->idx = 0;
				$member->code = '';
				$member->type = 'GUEST';
				$member->name = $member->nickname = 'Unknown';
				$member->email = 'unknown@unknown.com';
				$member->photo = $this->getModule()->getDir().'/images/nophoto.png';
				$member->nickcon = null;
				$member->cellphone = null;
				$member->level = $this->getLevel(0);
				$member->label = array();
				$member->extras = null;
				$member->point = 0;
				$member->address = null;
			} else {
				$member->name = $member->name ? $member->name : $member->nickname;
				$member->nickname = $member->nickname ? $member->nickname : $member->name;
				$member->photo = $this->IM->getModuleUrl('member','photo',$member->idx,false).'/profile.jpg';
				$member->nickcon = is_file($this->IM->getAttachmentPath().'/nickcon/'.$midx.'.gif') == true ? $this->IM->getAttachmentDir().'/nickcon/'.$midx.'.gif' : null;
				$member->level = $this->getLevel($member->exp);
				$temp = explode('-',$member->birthday);
				$member->birthday = count($temp) == 3 ? $temp[2].'-'.$temp[0].'-'.$temp[1] : '';
				$member->label = $this->getMemberLabel($midx);
				$member->extras = json_decode($member->extras);
				$member->address = json_decode($member->address);
				
				/**
				 * 추가정보를 $member 객체에 추가한다.
				 */
				if ($member->extras !== null) {
					foreach ($member->extras as $key=>$value) {
						$member->$key = $value;
					}
				}
			}
			
			if ($fireEvent == true) $this->IM->fireEvent('afterGetData',$this->getModule()->getName(),'member',$member);
			if ($forceReload == true) return $member;
			
			$this->members[$midx] = $member;
			
		}
		
		return $this->members[$midx];
	}
	
	/**
	 * 실명 또는 닉네임을 검색하여 회원번호를 가져온다.
	 *
	 * @param string $keyword 검색어
	 * @param string $mode 검색모드 (NAME : 실명검색 / NICKNAME : 닉네임검색 / BOTH : 실명 및 닉네임검색)
	 * @param boolean $is_exact 완전일치여부
	 * @return int[] $midxes 검색된 회원번호
	 */
	function getSearchResults($keyword,$mode='NICKNAME',$is_exact=false) {
		$midxes = $this->db()->select($this->table->member,'idx');
		
		if ($is_exact == true) {
			if ($mode == 'NAME') $midxes->where('name',$keyword);
			elseif ($mode == 'NICKNAME') $midxes->where('nickname',$keyword);
			elseif ($mode == 'BOTH') $midxes->where('(name = ? or nickname = ?)',array($keyword,$keyword));
		} else {
			$keyword = '%'.$keyword.'%';
			if ($mode == 'NAME') $midxes->where('name',$keyword,'LIKE');
			elseif ($mode == 'NICKNAME') $midxes->where('nickname',$keyword,'LIKE');
			elseif ($mode == 'BOTH') $midxes->where('(name like ? or nickname like ?)',array($keyword,$keyword));
		}
		
		return $midxes->get('idx');
	}
	
	/**
	 * 코드값으로 회원정보를 가져온다.
	 *
	 * @param string $code
	 * @return object $member
	 */
	function getMemberByCode($code) {
		if ($code == null || $code == '') return null;
		$member = $this->db()->select($this->table->member,'idx')->where('code',$code)->getOne();
		if ($member == null) return null;
		
		return $this->getMember($member->idx);
	}
	
	/**
	 * 회원이름을 가져온다.
	 *
	 * @param int $midx 회원번호, 없을 경우 현재 로그인한 회원번호
	 * @param string $replacement 비회원일 경우 대치할 이름
	 * @param boolean $nickcon 닉이미지 사용여부 (기본값 : true)
	 * @return string $name
	 */
	function getMemberName($midx=null,$replacement='') {
		if ($midx === 0) {
			$name = $replacement;
		} else {
			$member = $this->getMember($midx);
			$midx = $member->idx;
			$name = $member->idx == 0 ? $replacement : $member->name;
		}
		
		return '<span data-module="member" data-role="name" data-type="name"'.($midx ? ' data-idx="'.$midx.'"' : '').'>'.$name.'</span>';
	}
	
	/**
	 * 회원 닉네임을 가져온다.
	 *
	 * @param int $midx 회원번호, 없을 경우 현재 로그인한 회원번호
	 * @param string $replacement 비회원일 경우 대치할 이름
	 * @param boolean $nickcon 닉이미지 사용여부 (기본값 : true)
	 * @return string $nickname
	 */
	function getMemberNickname($midx=null,$replacement='',$nickcon=true) {
		if ($midx === 0) {
			$nickname = $replacement;
		} else {
			$member = $this->getMember($midx);
			$midx = $member->idx;
			$nickname = $member->idx == 0 ? $replacement : $member->nickname;
		}
		
		if ($nickcon == true && $midx > 0 && is_file($this->IM->getAttachmentPath().'/nickcon/'.$midx.'.gif') == true) {
			return '<span data-module="member" data-role="name" data-type="nickcon" data-idx="'.$midx.'" style="background-image:url('.$this->IM->getAttachmentDir().'/nickcon/'.$midx.'.gif);" title="'.$nickname.'">'.$nickname.'</span>';
		} else {
			return '<span data-module="member" data-role="name" data-type="nickname"'.($midx ? ' data-idx="'.$midx.'"' : '').'>'.$nickname.'</span>';
		}
	}
	
	/**
	 * 회원 사진주소를 가져온다.
	 *
	 * @param int $midx 회원번호
	 * @param boolean $isFullUrl 전체경로여부
	 * @return string $photo
	 */
	function getMemberPhotoUrl($midx=null,$isFullUrl=false) {
		return $this->IM->getModuleUrl('member','photo',$midx,'profile.jpg',$isFullUrl);
	}
	
	/**
	 * 회원 사진을 가져온다.
	 *
	 * @param int $midx 회원번호, 없을 경우 현재 로그인한 회원번호
	 * @param int $width 가로크기
	 * @param int $height 세로크기
	 * @return string $photo 회원사진 태그
	 */
	function getMemberPhoto($midx=null,$width=null,$height=null) {
		$style = 'background-image:url('.$this->getMemberPhotoUrl($midx).');';
		if ($width) $style.= ' width:'.$width.'px;';
		if ($height) $style.= ' height:'.$height.'px;';
		
		return '<i data-module="member" data-role="photo"'.($midx ? ' data-idx="'.$midx.'"' : '').' style="'.$style.'"></i>';
	}
	
	/**
	 * 회원라벨을 가지고 온다.
	 *
	 * @param int $midx(옵션) 회원고유번호, 이 값이 없는 경우 현재 로그인한 회원고유번호
	 * @param int $label(옵션) 라벨고유번호, 이 값이 없는 경우 회원이 가지고 있는 모든 라벨을 배열로 반환하고, 있는 경우 해당 라벨이 있는지 여부를 boolean 으로 반환한다.
	 * @param boolean $isIdx 라벨고유번호만 리턴받을지 여부
	 * @return object[] $labels 회원라벨
	 */
	function getMemberLabel($midx=null,$label=null,$isIdx=false) {
		$midx = $midx == null ? $this->getLogged() : $midx;
		
		/**
		 * 회원이 가지고 있는 전체라벨을 반환한다.
		 */
		if ($label == null) {
			$labels = $this->db()->select($this->table->member_label)->where('idx',$midx)->get();
			for ($i=0, $loop=count($labels);$i<$loop;$i++) {
				if ($isIdx === true) {
					$labels[$i] = $labels[$i]->label;
				} else {
					$label = $this->getLabel($labels[$i]->label);
					$label->reg_date = $labels[$i]->reg_date;
					$labels[$i] = $label;
				}
			}
			
			return $labels;
		} else {
			return $this->db()->select($this->table->member_label)->where('idx',$midx)->where('label',$label)->has();
		}
	}
	
	/**
	 * 회원라벨을 추가한다.
	 *
	 * @param int $midx 회원고유번호
	 * @param int $label 회원라벨고유번호
	 * @return boolean $success 추가여부
	 */
	function addMemberLabel($midx,$label) {
		$label = $this->getLabel($label);
		if ($label == null) return false;
		
		/**
		 * 이미 해당라벨을 가지고 있는 경우 중단한다.
		 */
		if ($this->db()->select($this->table->member_label)->where('idx',$midx)->where('label',$label->idx)->has() == true) return false;
		
		/**
		 * 추가하고자 하는 라벨이 UNIQUE 라벨일 경우 모든 라벨을 지운다.
		 */
		if ($label->is_unique == true) $this->removeMemberLabel($midx);
		
		$this->db()->insert($this->table->member_label,array('idx'=>$midx,'label'=>$label->idx,'reg_date'=>time()))->execute();
		$this->updateLabel($label->idx);
		
		return true;
	}
	
	/**
	 * 회원라벨을 제거한다.
	 *
	 * @param int $midx 회원고유번호
	 * @param int $label(옵션) 회원라벨고유번호 (없을 경우 모든 라벨을 지운다.)
	 * @return boolean $success 제거여부
	 */
	function removeMemberLabel($midx,$label=null) {
		/**
		 * 대상회원의 라벨정보를 가져온다.
		 */
		$labels = $this->db()->select($this->table->member_label,'label')->where('idx',$midx);
		if ($label !== null) $labels->where('label',$label);
		$labels = $labels->get();
		
		for ($i=0, $loop=count($labels);$i<$loop;$i++) {
			$this->db()->delete($this->table->member_label)->where('idx',$midx)->where('label',$labels[$i]->label)->execute();
			$this->updateLabel($labels[$i]->label);
		}
		
		return count($labels) > 0;
	}
	
	/**
	 * 회원의 추가정보를 가져온다.
	 *
	 * @param int $midx(옵션) 회원고유값, 없다면 현재 로그인한 회원고유값
	 * @param string $key 가져올 키값
	 * @return object $data
	 */
	function getMemberExtraValue($midx=null,$key) {
		$member = $this->getMember($midx);
		if ($member->extras != null && isset($member->extras->$key) == true) return $member->extras->$key;
		return null;
	}
	
	/**
	 * 회원의 추가정보를 저장한다.
	 *
	 * @param int $midx 회원고유값
	 * @param string $key 저장할 키값
	 * @param object $value 저장할 값
	 * @return boolean $success 저장여부
	 */
	function setMemberExtraValue($midx,$key,$value) {
		$member = $this->db()->select($this->table->member,array('extras'))->where('idx',$midx)->getOne();
		if ($member == null) return false;
		
		$extras = json_decode($member->extras);
		if ($extras == null) $extras = new stdClass();
		$extras->$key = $value;
		$extras = json_encode($extras,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
		
		$this->db()->update($this->table->member,array('extras'=>$extras))->where('idx',$midx)->execute();
		return true;
	}
	
	/**
	 * 회원활동기록을 추가한다.
	 *
	 * @param int $midx 회원 고유번호
	 * @param int $exp 활동에 따른 경험치
	 * @param string $module 활동이 발생한 모듈명
	 * @param string $code 활동코드
	 * @param object[] $content 활동에 따른 정보
	 * @param int $reg_date 활동시각
	 * @return int $reg_date 등록시각
	 */
	function addActivity($midx,$exp,$module,$code,$content=array(),$reg_date=null) {
		$member = $this->db()->select($this->table->member)->where('idx',$midx)->getOne();
		if ($member == null || in_array($member->status,array('LEAVE','DEACTIVATED')) == true) return false;
		
		$reg_date = $reg_date ? $reg_date * 1000 : time() * 1000;
		while (true) {
			if ($this->db()->select($this->table->activity)->where('midx',$member->idx)->where('reg_date',$reg_date)->has() == false) break;
			$reg_date++;
		}
		$ip = isset($_SERVER['REMOTE_ADDR']) == true ? $_SERVER['REMOTE_ADDR'] : '';
		$agent = isset($_SERVER['HTTP_USER_AGENT']) == true ? $_SERVER['HTTP_USER_AGENT'] : '';
		
		$result = $this->db()->insert($this->table->activity,array('midx'=>$member->idx,'module'=>$module,'code'=>$code,'content'=>json_encode($content,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES),'exp'=>$exp,'reg_date'=>$reg_date,'ip'=>$ip,'agent'=>$agent))->execute();
		
		if ($result === false) {
			return $this->addActivity($midx,$exp,$module,$code,$content,ceil($reg_date / 1000));
		}
		
		if ($exp > 0) $this->db()->update($this->table->member,array('exp'=>$member->exp + $exp))->where('idx',$member->idx)->execute();
		
		return $reg_date;
	}
	
	/**
	 * 포인트를 적립한다.
	 *
	 * @param int $midx 회원번호
	 * @param int $point 적립할 포인트
	 * @param string $module 포인트 적립을 요청한 모듈명
	 * @param any[] $content 포인트 적립을 요청한 모듈에서 사용할 데이터
	 * @param boolean $isForce - 포인트를 강제로 사용할 지 여부
	 * @param int $reg_date 적립시각
	 * @return boolean $success
	 */
	function sendPoint($midx,$point,$module='',$code='',$content=array(),$isForce=false,$reg_date=null) {
		if ($point == 0) return false;
		
		$member = $this->db()->select($this->table->member)->where('idx',$midx)->getOne();
		if ($member == null || in_array($member->status,array('LEAVE','DEACTIVATED')) == true) return false;
		if ($isForce == false && $point < 0 && $member->point < $point * -1) return false;
		
		if ($module && $this->IM->getModule()->isInstalled($module) == true) {
			$mModule = $this->IM->getModule($module);
			if (method_exists($mModule,'syncMember') == true) {
				if ($mModule->syncMember('send_point',$midx) === false) return false;
			}
		}
		
		$reg_date = $reg_date ? $reg_date * 1000 : time() * 1000;
		while (true) {
			if ($this->db()->select($this->table->point)->where('midx',$member->idx)->where('reg_date',$reg_date)->has() == false) break;
			$reg_date++;
		}
		
		$result = $this->db()->insert($this->table->point,array('midx'=>$member->idx,'point'=>$point,'module'=>$module,'code'=>$code,'content'=>json_encode($content,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES),'reg_date'=>$reg_date))->execute();
		if ($result === false) {
			return $this->sendPoint($midx,$point,$module,$code,$content,$isForce,ceil($reg_date / 1000));
		}
		$this->db()->update($this->table->member,array('point'=>$member->point + $point))->where('idx',$member->idx)->execute();
		
		return true;
	}
	
	/**
	 * 이메일 인증을 사용할 경우 인증메일을 발송한다.
	 *
	 * @param int $midx 회원고유번호
	 * @param string $email(옵션) 이메일주소, 없을 경우 회원정보의 이메일주소로 발송한다.
	 * @return boolean/string 이메일 발송결과 (true : 성공, ALREADY_VERIFIED_EMAIL : 이미 인증됨, WAIT_VERIFIED_EMAIL : 이메일을 발송하고 대기중, SEND_FAILED_VERIFIED_EMAIL : 이메일 발송실패)
	 */
	function sendVerificationEmail($midx,$email=null) {
		$member = $this->db()->select($this->table->member)->where('idx',$midx)->getOne();
		if ($member == null) return 'FAIL';
		
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
			return 'ALREADY_VERIFIED_EMAIL';
		} else {
			return 'WAIT_VERIFIED_EMAIL';
		}
		
		if ($isSendEmail == true) {
			/**
			 * @todo 메일발송부분 언어팩 설정
			 */
			$subject = '['.$this->IM->getSiteTitle().'] 이메일주소 확인메일';
			$content = '회원님이 입력하신 이메일주소가 유효한 이메일주소인지 확인하기 위한 이메일입니다.<br>회원가입하신적이 없거나, 최근에 이메일주소변경신청을 하신적이 없다면 본 메일은 무시하셔도 됩니다.';
			if ($member->status == 'VERIFYING') {
				$content.= '<br><br>아래의 인증코드 6자리를 인증번호 확인란에 입력하시거나, 링크를 클릭하여 회원가입을 완료할 수 있습니다.';
			} else {
				$content.= '<br><br>아래의 인증코드 6자리를 인증번호 확인란에 입력하여 이메일주소변경을 완료할 수 있습니다.';
			}
			$content.= '<br><br>인증코드 : <b>'.$code.'</b>';
			
			if ($member->verified == 'FALSE') {
				$link = $this->IM->getModuleUrl('member','verification',Encoder(json_encode(array($midx,$email)),null,'hex'),false,true);
				$content.= '<br><br><a href="'.$link.'" target="_blank" style="word-break:break-all;">'.$link.'</a>';
			}
			$content.= '<br><br>본 메일은 발신전용메일로 회신되지 않습니다.<br>감사합니다.';
			
			$result = $this->IM->getModule('email')->addTo($email,$member->nickname)->setSubject($subject)->setContent($content)->send();
			if ($result == false) return 'SEND_FAILED_VERIFIED_EMAIL';
		}
		
		if ($member->verified != 'TRUE' && $member->email != $email) {
			$this->db()->update($this->table->member,array('email'=>$email))->where('idx',$member->idx)->execute();
		}
		
		return true;
	}
	
	/**
	 * 이메일 인증을 사용할 경우 인증메일을 발송한다.
	 *
	 * @param int $midx 회원고유번호
	 * @param string $email(옵션) 이메일주소, 없을 경우 회원정보의 이메일주소로 발송한다.
	 * @return boolean/string 이메일 발송결과 (true : 성공, ALREADY_VERIFIED_EMAIL : 이미 인증됨, WAIT_VERIFIED_EMAIL : 이메일을 발송하고 대기중, SEND_FAILED_VERIFIED_EMAIL : 이메일 발송실패)
	 */
	function sendResetPasswordEmail($midx) {
		$member = $this->getMember($midx);
		
		$check = $this->db()->select($this->table->password)->where('midx',$midx)->where('status','SENDING')->getOne();
		$this->db()->setLockMethod('WRITE')->lock($this->table->password);
		if ($check == null) {
			while (true) {
				$token = sha1($midx.time().rand(10000,99999));
				if ($this->db()->select($this->table->password)->where('token',$token)->has() == false) break;
			}
		} elseif ($check->reg_date > time() - 300) {
			return 'WAIT_RESET_PASSWORD_EMAIL';
		} else {
			$token = $check->token;
		}
		
		$this->db()->replace($this->table->password,array('token'=>$token,'midx'=>$midx,'reg_date'=>time()))->execute();
		
		$this->db()->unlock();
		
		/**
		 * @todo 메일발송부분 언어팩 설정
		 */
		$subject = '['.$this->IM->getSiteTitle().'] 패스워드 초기화';
		$content = '회원님의 로그인 패스워드를 초기화하기 위한 이메일입니다.<br>아래의 링크를 클릭하여 패스워드를 초기화할 수 있습니다.<br>패스워드 초기화요청을 하신적이 없다면 본 메일은 무시하셔도 됩니다.';
		
		$link = $this->IM->getModuleUrl('member','password','reset',$token,true);
		$content.= '<br><br><a href="'.$link.'" target="_blank" style="word-break:break-all;">'.$link.'</a>';
		
		$sendLink = $this->IM->getModuleUrl('member','password',false,false,true);
		$content.= '<br><br>위의 링크는 앞으로 약 6시간동안만 유효하며, 링크가 만료되었을 경우 <a href="'.$sendLink.'" target="_blank" style="word-break:break-all;">'.$sendLink.'</a> 에서 다시 발송가능합니다.<br><br>본 메일은 발신전용메일로 회신되지 않습니다.<br>감사합니다.';
		
		$result = $this->IM->getModule('email')->addTo($member->email,$member->nickname)->setSubject($subject)->setContent($content)->send();
		if ($result == false) {
			$this->db()->delete($this->table->password)->where('token',$token)->execute();
		}
		
		return $result;
	}
	
	/**
	 * 회원가입 / 회원수정 필드데이터를 가공한다.
	 *
	 * @param object $rawData $this->table->signup 테이블의 RAW 데이터
	 * @return object $field
	 */
	function getInputField($rawData) {
		if (is_object($rawData) == true) {
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
				$field->help = $field->help == 'LANGUAGE_SETTING' ? $this->getText('signup/'.$field->name.'_help') : $field->help;
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
		} else {
			$field = new stdClass();
			$field->label = 0;
			$field->name = $rawData;
			$field->type = $rawData;
			$field->input = 'system';
			$field->title = $this->getText('text/'.$rawData);
			$field->help = $this->getText('signup/'.$rawData.'_help') != 'signup/'.$rawData.'_help' ? $this->getText('signup/'.$rawData.'_help') : '';
			$field->is_extra = false;
			$field->is_required = false;
		}
		
		return $field;
	}
	
	/**
	 * 회원가입 / 회원수정 필드를 출력하기 위한 함수
	 *
	 * @param object $field 입력폼 데이터
	 * @param boolean $member 회원정보
	 * @param string $html
	 */
	function getInputFieldHtml($field,$member=null) {
		$html = array();
		
		if ($field->input == 'system') {
			/**
			 * 이메일
			 */
			if ($field->name == 'email' || $field->name == 'email_verification_email') {
				array_push($html,
					'<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<input type="email" name="'.$field->name.'"'.($member !== null && isset($member->email) == true ? ' value="'.GetString($member->email,'input').'"' : '').'>',
					'</div>'
				);
			}
			
			/**
			 * 패스워드
			 */
			if ($field->name == 'password') {
				if ($member == null) {
					array_push($html,
						'<div data-role="inputset" data-name="password" data-default="'.$field->help.'">',
							'<div data-role="input">',
								'<input type="password" name="password" placeholder="'.$this->getText('signup/password').'">',
							'</div>',
							'<div data-role="input">',
								'<input type="password" name="password_confirm" placeholder="'.$this->getText('signup/password_confirm').'" data-error="'.$this->getErrorText('NOT_MATCHED_PASSWORD_CONFIRM').'">',
							'</div>',
						'</div>'
					);
				} else {
					array_push($html,
						'<div data-role="input">',
							'<button type="button" data-action="password">'.$this->getText('signup/password_change').'</button>',
						'</div>'
					);
				}
			}
			
			/**
			 * 실명, 닉네임
			 */
			if ($field->name == 'name' || $field->name == 'nickname') {
				array_push($html,
					'<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<input type="text" name="'.$field->name.'"'.($member !== null && isset($member->{$field->name}) == true ? ' value="'.GetString($member->{$field->name},'input').'"' : '').'>',
					'</div>'
				);
			}
			
			/**
			 * 생일
			 */
			if ($field->name == 'birthday') {
				array_push($html,
					'<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<input type="date" name="'.$field->name.'"'.($member !== null && isset($member->{$field->name}) == true ? ' value="'.$member->{$field->name}.'"' : '').'>',
					'</div>'
				);
			}
			
			/**
			 * 전화번호 및 휴대전화번호
			 */
			if ($field->name == 'telephone' || $field->name == 'cellphone') {
				array_push($html,
					'<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<input type="tel" name="'.$field->name.'"'.($member !== null && isset($member->{$field->name}) == true ? ' value="'.$member->{$field->name}.'"' : '').'>',
					'</div>'
				);
			}
			
			/**
			 * 홈페이지
			 */
			if ($field->name == 'homepage') {
				array_push($html,
					'<div data-role="input" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<input type="url" name="'.$field->name.'"'.($member !== null && isset($member->{$field->name}) == true ? ' value="'.$member->{$field->name}.'"' : '').'>',
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
							'<label><input type="radio" name="'.$field->name.'" value="MALE"'.($member !== null && isset($member->{$field->name}) == true && $member->{$field->name} == 'MALE' ? ' checked="checked"' : '').'>'.$this->getText('text/male').'</label>',
						'</div>',
						'<div data-role="input">',
							'<label><input type="radio" name="'.$field->name.'" value="FEMALE"'.($member !== null && isset($member->{$field->name}) == true && $member->{$field->name} == 'FEMALE' ? ' checked="checked"' : '').'>'.$this->getText('text/female').'</label>',
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
						'<div data-role="inputset" class="flex">',
							'<div data-role="input">',
								'<input type="text" name="'.$field->name.'_zipcode" placeholder="'.$this->getText('signup/zipcode').'" value="'.($member !== null && $member->address !== null && isset($member->address->zipcode) == true ? $member->address->zipcode : '').'">',
							'</div>',
							'<div data-role="text">('.$this->getText('signup/zipcode').')</div>',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_address1" placeholder="'.$this->getText('signup/address1').'" value="'.($member !== null && $member->address !== null && isset($member->address->address1) == true ? $member->address->address1 : '').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_address2" placeholder="'.$this->getText('signup/address2').'" value="'.($member !== null && $member->address !== null && isset($member->address->address2) == true ? $member->address->address2 : '').'">',
						'</div>',
						'<div data-role="inputset" class="flex">',
							'<div data-role="input">',
								'<input type="text" name="'.$field->name.'_city" placeholder="'.$this->getText('signup/city').'" value="'.($member !== null && $member->address !== null && isset($member->address->city) == true ? $member->address->city : '').'">',
							'</div>',
							'<div data-role="input">',
								'<input type="text" name="'.$field->name.'_state" placeholder="'.$this->getText('signup/state').'" value="'.($member !== null && $member->address !== null && isset($member->address->state) == true ? $member->address->state : '').'">',
							'</div>',
						'</div>',
					'</div>'
				);
			}
			
			/**
			 * 회원사진
			 */
			if ($field->name == 'photo') {
				array_push($html,
					'<div data-role="photo">',
						'<textarea name="'.$field->name.'"></textarea>',
						'<div class="preview" style="background-image:url('.($member != null ? $member->photo : $this->getModule()->getDir().'/images/nophoto.png').');">',
							'<button type="button" data-action="photo">'.$this->getText('button/edit').'</button>',
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
				foreach ($field->options as $val=>$text) {
					$html[] = '<option value="'.$val.'"'.($member !== null && isset($member->extras->{$field->name}) == true && $member->extras->{$field->name} == $val ? ' selected="selected"' : '').'>'.$text.'</option>';
				}
				$html[] = '</select>';
				$html[] = '</div>';
			}
			
			
			/**
			 * 체크박스
			 */
			if ($field->input == 'checkbox') {
				$html[] = '<div data-role="inputset" data-name="'.$field->name.'" class="inline" data-default="'.$field->help.'">';
				foreach ($field->options as $val=>$text) {
					array_push($html,
						'<div data-role="input">',
							'<label><input type="checkbox" name="'.$field->name.'[]" value="'.$val.'"'.($member !== null && isset($member->extras->{$field->name}) == true && is_array($member->extras->{$field->name}) == true && in_array($val,$member->extras->{$field->name}) == true ? ' checked="checked"' : '').'>'.$text.'</label>',
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
				foreach ($field->options as $val=>$text) {
					array_push($html,
						'<div data-role="input">',
							'<label><input type="radio" name="'.$field->name.'" value="'.$val.'"'.($member !== null && isset($member->extras->{$field->name}) == true && $member->extras->{$field->name} == $val ? ' checked="checked"' : '').'>'.$text.'</label>',
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
						'<input type="'.$field->input.'" name="'.$field->name.'"'.($member !== null && isset($member->extras->{$field->name}) == true ? ' value="'.GetString($member->extras->{$field->name},'input').'"' : '').'>',
					'</div>'
				);
			}
			
			/**
			 * 주소
			 */
			if ($field->input == 'address') {
				array_push($html,
					'<div data-role="inputset" class="block" data-name="'.$field->name.'" data-default="'.$field->help.'">',
						'<div data-role="inputset" class="flex">',
							'<div data-role="input">',
								'<input type="text" name="'.$field->name.'_zipcode" placeholder="'.$this->getText('signup/zipcode').'" value="'.($member !== null && isset($member->extras->{$field->name}) == true && isset($member->extras->{$field->name}->zipcode) == true ? $member->extras->{$field->name}->zipcode : '').'">',
							'</div>',
							'<div data-role="text">('.$this->getText('signup/zipcode').')</div>',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_address1" placeholder="'.$this->getText('signup/address1').'" value="'.($member !== null && isset($member->extras->{$field->name}) == true && isset($member->extras->{$field->name}->address1) == true ? $member->extras->{$field->name}->address1 : '').'">',
						'</div>',
						'<div data-role="input">',
							'<input type="text" name="'.$field->name.'_address2" placeholder="'.$this->getText('signup/address2').'" value="'.($member !== null && isset($member->extras->{$field->name}) == true && isset($member->extras->{$field->name}->address2) == true ? $member->extras->{$field->name}->address2 : '').'">',
						'</div>',
						'<div data-role="inputset" class="flex">',
							'<div data-role="input">',
								'<input type="text" name="'.$field->name.'_city" placeholder="'.$this->getText('signup/city').'" value="'.($member !== null && isset($member->extras->{$field->name}) == true && isset($member->extras->{$field->name}->city) == true ? $member->extras->{$field->name}->city : '').'">',
							'</div>',
							'<div data-role="input">',
								'<input type="text" name="'.$field->name.'_state" placeholder="'.$this->getText('signup/state').'" value="'.($member !== null && isset($member->extras->{$field->name}) == true && isset($member->extras->{$field->name}->state) == true ? $member->extras->{$field->name}->state : '').'">',
							'</div>',
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
						'<textarea name="'.$field->name.'">'.($member !== null && isset($member->extras->{$field->name}) == true ? $member->extras->{$field->name} : '').'</textarea>',
					'</div>'
				);
			}
		}
		
		return implode(PHP_EOL,$html);
	}
	
	/**
	 * 회원가입 / 정보수정의 데이터가 유효한지 파악하여, DB처리를 위한 배열 및 에러처리를 위한 배열을 만든다.
	 *
	 * @param int/int[] $idx 회원고유번호 또는 회원라벨
	 * @param object/array $data 사용자가 입력한 데이터
	 * @param &array $insert(옵션) DB처리를 위한 배열 포인터
	 * @param &array $errors(옵션) 에러처리를 위한 배열 포인터
	 * @param boolean $isValid 유효한지 여부
	 */
	function isValidMemberData($idx,$data,&$insert=null,&$errors=null,$isAdmin=false,$site=null) {
		if (is_array($data) == true) $data = (object)$data;
		
		$siteType = $site ? $site->member : $this->IM->getSite(false)->member;
		
		if (is_array($idx) == true) {
			$labels = $idx;
			$member = null;
		} else {
			$member = $this->getMember($idx);
			if ($member->idx == 0) return false;
			$labels = array(0);
			foreach ($member->label as $label) {
				$labels[] = $label->idx;
			}
		}
		
		if ($insert != null) $insert['extras'] = array();
		
		$success = true;
		$forms = $this->db()->select($this->table->signup)->where('label',$labels,'IN')->orderBy('sort','asc')->get();
		for ($i=0, $loop=count($forms);$i<$loop;$i++) {
			if ($forms[$i]->name == 'agreement' || $forms[$i]->name == 'privacy') continue;
			$field = $this->getInputField($forms[$i]);
			
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
								if ($member != null) $check->where('idx',$member->idx,'!=');
								
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
								if ($member != null) $check->where('idx',$member->idx,'!=');
								
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
						
						if ($member != null && $isAdmin == true) {
							$field->value = $member->password;
						} elseif ($field->value !== null) {
							$mHash = new Hash();
							
							if ($member == null) {
								if (is_string($field->value) == false) {
									$field->error = $this->getErrorText('STRING_TYPE_ONLY');
									break;
								}
								
								if (strlen($field->value) < 6) {
									$field->error = $this->getErrorText('TOO_SHORT_PASSWORD');
								} elseif (isset($data->password_confirm) == true && $field->value != $data->password_confirm) {
									$field->error = $this->getErrorText('NOT_MATCHED_PASSWORD_CONFIRM');
								} else {
									$field->value = $mHash->password_hash($field->value);
								}
							} else {
								$password = Decoder($field->value);
								if ($password === false || $mHash->password_validate($password,$member->password) == false) {
									$field->error = $this->getErrorText('INCORRECT_PASSWORD');
								} else {
									$field->value = $mHash->password_hash($password);
								}
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
							
							if (in_array($field->value,array('NONE','MALE','FEMALE')) == false) {
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
							
							$field->value = array(
								'zipcode'=>$data->{$field->name.'_zipcode'},
								'address1'=>$data->{$field->name.'_address1'},
								'address2'=>$data->{$field->name.'_address2'},
								'state'=>isset($data->{$field->name.'_state'}) == true ? $data->{$field->name.'_state'} : '',
								'city'=>isset($data->{$field->name.'_city'}) == true ? $data->{$field->name.'_city'} : ''
							);
						} else {
							$field->value = null;
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
			$insert['extras'] = isset($insert['extras']) == true && count($insert['extras']) > 0 ? json_encode($insert['extras'],JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) : '{}';
		}
		
		return $success;
	}
	
	/**
	 * 소셜사이트 로그인을 위한 주소를 가져온다.
	 *
	 * @param string $site 소셜사이트명 (google, facebook, twitter, kakao, naver, github)
	 * @return string $url
	 */
	function getSocialLoginUrl($site) {
		$site = $this->db()->select($this->table->social_oauth)->where('site',$site)->where('domain',array('*',$this->IM->domain),'IN')->orderBy('domain','desc')->getOne();
		if ($site == null) return '#';
		
		return $site->callback_domain == '*' || $site->callback_domain == $this->IM->domain ? __IM_DIR__.'/oauth/'.$site->site : $this->IM->getUrl(false,false,false,false,true,$site->callback_domain,false).'/oauth/'.$site->site;
	}
	
	/**
	 * 회원정보처리 관련된 에러메세지를 띄운다.
	 *
	 * @param string $code 에러코드
	 */
	function printError($code,$path=null) {
		$error = new stdClass();
		$error->message = $this->getErrorText($code);
		$error->description = $path;
		$error->type = 'back';
		
		$this->IM->printError($error);
	}
	
	/**
	 * 회원계정을 비활성화한다.
	 *
	 * @param int $midx 회원고유값
	 */
	function deleteMember($midx) {
		if (!$midx) return;
		$this->removeMemberLabel($midx);
		$this->db()->update($this->table->member,array('status'=>'LEAVE'))->where('idx',$midx)->execute();
	}
	
	/**
	 * 회원모듈과 동기화한다.
	 *
	 * @param string $action 동기화작업
	 * @param any[] $data 정보
	 */
	function syncMember($action,$data) {
		if ($action == 'point_history') {
			switch ($data->code) {
				case 'admin' :
					return '관리자 적립 ('.$data->content->content.')';
					
				case 'signup' :
					return '회원가입 포인트';
			}
			
			return json_encode($data);
		}
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
		
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('beforeDoProcess',$this->getModule()->getName(),$action,$values);
		
		/**
		 * 모듈의 process 폴더에 $action 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->getModule()->getPath().'/process/'.$action.'.php') == true) {
			INCLUDE $this->getModule()->getPath().'/process/'.$action.'.php';
		}
		
		unset($values);
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('afterDoProcess',$this->getModule()->getName(),$action,$values,$results);
		
		return $results;
	}
}
?>