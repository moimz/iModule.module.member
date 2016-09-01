<?php
/**
 * This file is part of iModule - https://www.imodule.kr
 *
 * @file ModuleMember.class.php
 * @author Arzz
 * @license MIT License
 */
class ModuleMember {
	private $IM; // Linked iModule core
	private $Module; // Linked Module core
	
	private $lang = null; // Store language strings
	private $oLang = null; // Store original language(defined package.json) strings
	private $table; // defined modules' database tables
	
	private $logged = null;
	
	// Stored loaded members, memberPages for low DB connection
	private $members = array();
	private $memberPages = array();
	
	/**
	 * construct
	 *
	 * @param object $IM iModule core
	 * @param object $Module Module.class
	 */
	function __construct($IM,$Module) {
		$this->IM = $IM;
		$this->Module = $Module;
		
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

		$this->IM->addSiteHeader('style',$this->Module->getDir().'/styles/style.css');
		$this->IM->addSiteHeader('script',$this->Module->getDir().'/scripts/member.js');
		
		$this->logged = Request('MEMBER_LOGGED','session') != null && Decoder(Request('MEMBER_LOGGED','session')) != false ? json_decode(Decoder(Request('MEMBER_LOGGED','session'))) : false;
	}
	
	/**
	 * Get database for this Module
	 *
	 * @return object DB.class
	 */
	function db() {
		return $this->IM->db($this->Module->getInstalled()->database);
	}
	
	/**
	 * Get Database table from the others class (table is private value)
	 *
	 * @param string $table table code
	 * @return string $tableName return real table name without prefix
	 */
	function getTable($table) {
		return empty($this->table->$table) == true ? null : $this->table->$table;
	}
	
	/**
	 * Get API /$language/api/member/$api
	 *
	 * @param string $api API code
	 * @return object $data return data for API code
	 */
	function getApi($api) {
		$data = new stdClass();
		$values = new stdClass();
		
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
					$errors['label'] = $this->getLanguage('error/notFound');
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
	 * Get Push message
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
	 * Get Context list for Admin's sitemap panel
	 *
	 * @param object $site call by site data
	 * @return object $contexts
	 */
	function getContextList($site) {
		$contexts = $this->getLanguage('contexts');
		$lists = array();
		foreach ($contexts as $context=>$title) {
			$lists[] = array('context'=>$context,'title'=>$title);
		}
		
		$results = new stdClass();
		$results->success = true;
		$results->lists = $lists;
		$results->count = count($lists);
		
		return $results;
	}
	
	/**
	 * Get Context configs for Admin's sitemap panel
	 *
	 * @param object $site call by site data
	 * @param string $context call by context value
	 * @return object $contexts
	 */
	function getContextConfigs($site,$context) {
		$configs = array();
		
		if ($context == 'signup') {
			$label = new stdClass();
			$label->title = $this->getLanguage('label');
			$label->name = 'label';
			$label->type = 'select';
			$label->data = array();
			$label->data[] = array(0,$this->getLanguage('label_none'));
			$labels = $this->db()->select($this->table->label,'idx,title')->get();
			for ($i=0, $loop=count($labels);$i<$loop;$i++) {
				$label->data[] = array($labels[$i]->idx,$labels[$i]->title);
			}
			$label->value = 0;
			$configs[] = $label;
		}
		
		$templet = new stdClass();
		$templet->title = $this->getLanguage('templet');
		$templet->name = 'templet';
		$templet->type = 'select';
		$templet->data = array();
		
		$templetsPath = @opendir($this->Module->getPath().'/templets/'.$context);
		while ($templetName = @readdir($templetsPath)) {
			if ($templetName != '.' && $templetName != '..' && is_dir($this->Module->getPath().'/templets/'.$context.'/'.$templetName) == true) {
				$templet->data[] = array($templetName,__IM_DIR__.'/templets/'.$context.'/'.$templetName);
			}
		}
		@closedir($templetsPath);
		
		$templetsPath = @opendir(__IM_PATH__.'/templets/'.$site->templet.'/templets/modules/member/templets/'.$context);
		while ($templetName = @readdir($templetsPath)) {
			if ($templetName != '.' && $templetName != '..' && is_dir(__IM_PATH__.'/templets/'.$site->templet.'/templets/modules/member/templets/'.$context.'/'.$templetName) == true) {
				$templet->data[] = array('@'.$templetName,__IM_DIR__.'/templets/'.$site->templet.'/templets/modules/member/templets/'.$context.'/'.$templetName);
			}
		}
		@closedir($templetsPath);
		
		$templet->value = 'default';
		$configs[] = $templet;
		
		return $configs;
	}
	
	/**
	 * Get language string from language code
	 *
	 * @param string $code language code (json key)
	 * @return string language string
	 */
	function getLanguage($code) {
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
		
		$temp = explode('/',$code);
		if (count($temp) == 1) {
			return isset($this->lang->$code) == true ? $this->lang->$code : ($this->oLang != null && isset($this->oLang->$code) == true ? $this->oLang->$code : '');
		} else {
			$string = $this->lang;
			for ($i=0, $loop=count($temp);$i<$loop;$i++) {
				if (isset($string->{$temp[$i]}) == true) $string = $string->{$temp[$i]};
				else $string = null;
			}
			
			if ($string == null && $this->oLang != null) {
				$string = $this->oLang;
				for ($i=0, $loop=count($temp);$i<$loop;$i++) {
					if (isset($string->{$temp[$i]}) == true) $string = $string->{$temp[$i]};
					else $string = null;
				}
			}
			return $string == null ? '' : $string;
		}
	}
	
	/**
	 * Get menu or page count information
	 *
	 * @param string $context linked context code (point, activity, ... etc)
	 * @return object $info ($info->count : item count, $info->last_time : reg_date of lastest item
	 * @todo check count information
	 */
	function getCountInfo($context,$config) {
		return null;
	}
	
	/**
	 * Get member module page URL
	 * If not exists container code in im_page_table, use account menu url @see iModule.class.php doLayout() method.
	 * @param string $view container code (signup, modify, password ... etc)
	 * @return object $page {menu:string $menu,page:string $page}, 1st and 2nd page code
	 */
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
	
	/**
	 * Get templet path
	 *
	 * @param string $container context code
	 * @param string $templet templet name
	 * @return string $path templet path
	 */
	function getTempletPath($container,$templet) {
		if (preg_match('/^@/',$templet) == true) { // use site templet
			$path = __IM_PATH__.'/templets/'.$this->IM->getSite()->templet.'/templets/modules/member/templets/'.$container.'/'.preg_replace('/^@/','',$templet);
		} else {
			$path = $this->Module->getPath().'/templets/'.$container.'/'.$templet;
		}
		
		if (is_dir($path) == false) $this->printError('NOT_FOUND_TEMPLET');
		else return $path;
	}
	
	/**
	 * Get templet dir
	 *
	 * @param string $container context code
	 * @param string $templet templet name
	 * @return string $path templet dir
	 */
	function getTempletDir($container,$templet) {
		if (preg_match('/^@/',$templet) == true) { // use site templet
			$dir = __IM_DIR__.'/templets/'.$this->IM->getSite()->templet.'/templets/modules/member/templets/'.$container.'/'.preg_replace('/^@/','',$templet);
		} else {
			$dir = $this->Module->getDir().'/templets/'.$container.'/'.$templet;
		}
		
		if (is_dir(preg_replace('/^'.__IM_DIR__.'/',__IM_PATH__,$dir)) == false) $this->printError('NOT_FOUND_TEMPLET');
		else return $dir;
	}
	
	/**
	 * Get page context
	 *
	 * @param string $container linked context code (signup, account, modify, ... etc)
	 * @return string $context context html code
	 */
	function getContext($container,$config=null) {
		$context = '';
		$values = new stdClass();
		
		switch ($container) {
			case 'account' :
				$context = $this->getAccountContext($config);
				break;
				
			case 'signup' :
				$context = $this->getSignUpContext($config);
				break;
				
			case 'modify' :
				$context = $this->getModifyContext($config);
				break;
				
			case 'social' :
				$context = $this->getSocialContext($config);
				break;
		}
		
		$this->IM->fireEvent('afterGetContext','member',$container,null,null,$context);
		
		return $context;
	}
	
	/**
	 * Get Error message
	 *
	 * @param string $content error message body
	 * @param string $title(optional) error message title (default is Error)
	 * @return $content error message html
	 */
	function getError($content,$title='') {
		return $content;
	}
	
	/**
	 * Print Error message
	 *
	 * @param string $content error message body
	 * @param string $title(optional) error message title (default is Error)
	 * @return $content error message html
	 */
	function printError($content,$title='') {
		echo $this->getError($content,$title);
		exit;
	}
	
	/**
	 * Get account context
	 *
	 * @param object $config context configs
	 * @return string $context context html code
	 * @todo change Korean pages menu to language pack code & fireEvent for addons and the others module
	 */
	function getAccountContext($config) {
		ob_start();
		if ($this->Module->getConfig('accountType') == 'standalone') $this->IM->removeTemplet();
		
		$templetPath = $this->Module->getPath().'/templets/account/'.$this->Module->getConfig('accountTemplet');
		$templetDir = $this->Module->getDir().'/templets/account/'.$this->Module->getConfig('accountTemplet');
		
		if (file_exists($templetPath.'/styles/style.css') == true) {
			$this->IM->addSiteHeader('style',$templetDir.'/styles/style.css');
		}
		
		if (file_exists($templetPath.'/scripts/script.js') == true) {
			$this->IM->addSiteHeader('script',$templetDir.'/scripts/script.js');
		}
		
		$values = new stdClass();
		$values->title = $this->getLanguage('account/title');
		$values->pages = array();
		if ($this->isLogged() == true) {
			if ($this->IM->page == null) $this->IM->page = 'dashboard';
			$values->pages = $this->getLanguage('account/logged');
		} else {
			if ($this->IM->page == null) $this->IM->page = 'signup';
			$values->pages = $this->getLanguage('account/logout');
		}
		
		$values->groupTitle = '';
		$values->pageTitle = '';
		for ($i=0, $loop=count($values->pages);$i<$loop;$i++) {
			if (isset($values->pages[$i]->page) == true && $this->IM->page == $values->pages[$i]->page) {
				$values->pageTitle = $values->pages[$i]->title;
				$values->groupTitle = '';
				break;
			} elseif (isset($values->pages[$i]->pages) == true) {
				$values->groupTitle = $values->pages[$i]->title;
				foreach ($values->pages[$i]->pages as $page=>$title) {
					if ($this->IM->page == $page) {
						$values->pageTitle = $title;
						break;
					}
				}
			}
		}
		
		$pageContext = $this->getAccountViewContext();
		
		$IM = $this->IM;
		$Module = $this;
		
		if (file_exists($templetPath.'/index.php') == true) {
			INCLUDE $templetPath.'/index.php';
		}
		
		$context = ob_get_contents();
		ob_end_clean();
		
		return $context;
	}
	
	/**
	 * Get account view context, each page context from getAccountContext
	 *
	 * @return string $context context html code
	 * @todo this function is just concept.
	 */
	function getAccountViewContext() {
		$templetPath = $this->Module->getPath().'/templets/account/'.$this->Module->getConfig('accountTemplet');
		$templetDir = $this->Module->getDir().'/templets/account/'.$this->Module->getConfig('accountTemplet');
		
		$context = '';
		if (in_array($this->IM->page,array('signup','modify','password','leave','config','social','point','push')) == true) {
			$config = new stdClass();
			$config->templet = $templetPath.'/'.$this->IM->page.'.php';
			if ($this->IM->page == 'signup') $context = $this->getSignUpContext($config);
			if ($this->IM->page == 'modify') $context = $this->getModifyContext($config);
			if ($this->IM->page == 'password') $context = $this->getPasswordContext($config);
			if ($this->IM->page == 'point') $context = $this->getPointContext($config);
			if ($this->IM->page == 'push') $context = $this->getPushContext($config);
		} else {
			ob_start();
		
			$IM = $this->IM;
			$Module = $this;
		
			if ($this->IM->view == null) {
				if (file_exists($templetPath.'/'.$this->IM->page.'.php') == true) {
					INCLUDE $templetPath.'/'.$this->IM->page.'.php';
				}
			} else {
				if (file_exists($templetPath.'/'.$this->IM->page.'.'.$this->IM->view.'.php') == true) {
					INCLUDE $templetPath.'/'.$this->IM->page.'.'.$this->IM->view.'.php';
				}
			}
			
			$context = ob_get_contents();
			ob_end_clean();
		}
		
		return $context;
	}
	
	/**
	 * Get signup context
	 *
	 * @param object $config configs
	 * @return string $context context html code
	 */
	function getSignUpContext($config) {
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
		
		return $context;
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
	 * Check member login status
	 *
	 * @return boolean $isLogged
	 */
	function isLogged() {
		if ($this->logged === false) return false;
		else return true;
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
	
	function doProcess($action) {
		$results = new stdClass();
		$values = new stdClass();
		
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
					$errors['label'] = $this->getLanguage('error/notFound');
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
		
		/**
		 * For admin actions
		 * Admin permission checking in /process/index.php
		 * Admin action name started by '@'
		 */
		if ($action == '@getList') {
			$start = Request('start') ? Request('start') : 0;
			$limit = Request('limit') ? Request('limit') : 50;
			$sort = Request('sort') ? Request('sort') : 'idx';
			$dir = Request('dir') ? Request('dir') : 'DESC';
			
			$lists = $this->db()->select($this->table->member);
			$total = $lists->copy()->count();
			$lists = $lists->orderBy($sort,$dir)->limit($start,$limit)->get();
			
			$results->success = true;
			$results->lists = $lists;
			$results->total = $total;
		}
		
		if ($action == '@getLabel') {
			$idx = Request('idx');
			
			if ($idx !== null) {
				if ($idx == 0) {
					$data = new stdClass();
					$data->title = $this->getLanguage('admin/label/default');
					$data->allow_signup = $this->Module->getConfig('allowSignup') == 'TRUE';
					$data->auto_active = $this->Module->getConfig('autoActive') == 'TRUE';
					$data->is_change = false;
					$data->is_unique = false;
				}
				
				$results->success = true;
				$results->data = $data;
			} else {
				$lists = array();
				$lists[0] = new stdClass();
				$lists[0]->idx = 0;
				$lists[0]->title = '';
				$lists[0]->membernum = $this->db()->select($this->table->member)->count();
				
				$sort = Request('sort') ? Request('sort') : 'title';
				$dir = Request('dir') ? Request('dir') : 'ASC';
				$labels = $this->db()->select($this->table->label)->orderBy($sort,$dir)->get();
				for ($i=0, $loop=count($labels);$i<$loop;$i++) {
					$lists[] = $labels[$i];
				}
				
				$results->success = true;
				$results->lists = $lists;
				$results->total = count($lists);
			}
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