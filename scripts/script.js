/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 회원모듈 UI/UX를 처리한다.
 *
 * @file /modules/member/scripts/script.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 5. 25.
 */
var Member = {
	getUrl:function(view,idx) {
		var url = $("div[data-module=member]").attr("data-base-url") ? $("div[data-module=member]").attr("data-base-url") : ENV.getUrl(null,null,false);
		if (!view || view == false) return url;
		url+= "/"+view;
		if (!idx || idx == false) return url;
		return url+"/"+idx;
	},
	/**
	 * 회원로그인
	 *
	 * @param object $form 로그인폼
	 */
	login:function($form,callback) {
		$form.send(ENV.getProcessUrl("member","login"),function(result) {
			/**
			 * 이벤트를 발생시킨다.
			 */
			if ($(document).triggerHandler("Member.login",[$form,result]) === false) return false;
			
			if (result.success == true) {
				if (typeof callback == "function") callback();
				else location.replace(location.href.split("#").shift());
			}
		});
	},
	/**
	 * 로그인모달
	 */
	loginModal:function(callback) {
		iModule.modal.get(ENV.getProcessUrl("member","getModal"),{modal:"login"},function($modal,$form) {
			$form.on("submit",function() {
				Member.login($form,callback);
				return false;
			});
			return false;
		});
	},
	/**
	 * 회원 로그아웃
	 *
	 * @param DOM button(옵션) 로그아웃 버튼
	 */
	logout:function(button,callback) {
		if (button) {
			var $button = $(button);
			$button.status("loading");
		}
		
		$.send(ENV.getProcessUrl("member","logout"),function(result) {
			if (result.success == true) {
				if (typeof callback == "function") callback();
				else location.replace(location.href.split("#").shift());
			}
		});
	},
	/**
	 * 회원가입 팝업
	 */
	signupPopup:function() {
		iModule.openPopup(ENV.getModuleUrl("member","@signup"),600,500,1,"signup");
	},
	/**
	 * 정보수정 팝업
	 */
	modifyPopup:function() {
		iModule.openPopup(ENV.getModuleUrl("member","@modify"),600,500,1,"modify");
	},
	/**
	 * 정보수정 팝업
	 */
	helpPopup:function() {
		iModule.openPopup(ENV.getModuleUrl("member","@password"),400,200,1,"password");
	},
	/**
	 * 다른 도메인간 통합로그인을 위한 로그인 세션 동기화
	 *
	 * @param string domain 동기화할 사이트 도메인
	 * @param string token 사용자 세션토큰
	 */
	syncSessionTotal:0,
	syncSessionComplete:0,
	syncSession:function(domain,token,callback,count) {
		if (typeof domain == "string") {
			var count = count ? count : 0;
			
			$.ajax({
				type:"GET",
				url:domain+ENV.getProcessUrl("member","syncSession"),
				data:{token:token},
				dataType:"jsonp",
				success:function(result) {
					Member.syncSessionComplete++;
					if (typeof callback == "function" && Member.syncSessionTotal == Member.syncSessionComplete) callback();
				},
				error:function() {
					if (count == 3) {
						Member.syncSessionComplete++;
						if (typeof callback == "function" && Member.syncSessionTotal == Member.syncSessionComplete) callback();
						return;
					}
					setTimeout(Member.syncSession,1000,domain,token,callback,++count);
				}
			});
		} else {
			if (typeof domain == "function") var callback = domain;
			else callback = null;
			
			$.send(ENV.getProcessUrl("member","getUniversalSites"),function(result) {
				if (result.sites.length == 0) {
					if (callback != null) callback();
				} else {
					Member.syncSessionTotal = result.sites.length;
					Member.syncSessionComplete = 0;
					for (var i=0, loop=result.sites.length;i<loop;i++) {
						Member.syncSession(result.sites[i],result.token,callback);
					}
				}
			});
		}
	},
	/**
	 * 회원가입
	 */
	signup:{
		/**
		 * 회원가입폼을 초기화한다.
		 */
		init:function() {
			var $form = $("#ModuleMemberSignUpForm");
			var step = $("input[name=step]",$form).val();
			
			if (step == "agreement") {
				$("button[type=submit]",$form).disable();
				$("input[type=checkbox]",$form).on("change",function() {
					$("button[type=submit]",$form).setDisabled($("input[type=checkbox]",$form).length != $("input[type=checkbox]:checked",$form).length);
				});
			}
			
			$("button[data-action]",$form).on("click",function() {
				var action = $(this).attr("data-action");
				
				if (action == "main") {
					if (ENV.IS_CONTAINER_POPUP == true) {
						if (opener) opener.location.replace(ENV.getUrl(false));
						window.close();
					} else {
						location.replace(ENV.getUrl(false));
					}
				}
			});
			
			if (step == "register") {
				Member.signup.check();
				$form.inits(Member.signup.submit);
			}
		},
		/**
		 * 회원가입폼 입력데이터를 확인한다.
		 */
		check:function() {
			var $form = $("#ModuleMemberSignUpForm");
			
			$("input[name=email], input[name=name], input[name=nickname]",$form).on("blur",function() {
				var $field = $(this);
				if ($field.val().length > 0 && $field.data("lastValue") != $field.val()) {
					$.send(ENV.getProcessUrl("member","checkMemberValue"),{name:$field.attr("name"),value:$field.val(),mode:"signup"},function(result) {
						$field.status(result.success == true ? "success" : "error",result.message ? result.message : "");
						$field.data("submitValue",$field.val());
						return false;
					});
				}
			});
			
			$("input[name=password], input[name=password_confirm]",$form).on("blur",function() {
				var $password = $("input[name=password]",$form);
				var $password_confirm = $("input[name=password_confirm]",$form);
				
				if ($password.val().length < 6) {
					$(this).status("error");
				} else if (($(this).attr("name") == "password_confirm" || $password_confirm.val().length > 0) && $password.val() != $password_confirm.val()) {
					$(this).status("error",$password_confirm.attr("data-error"));
				} else if ($password.val() == $password_confirm.val()) {
					$(this).status("success");
				}
				
				$password.data("submitValue",$password.val());
				$password_confirm.data("submitValue",$password_confirm.val());
			});
		},
		submit:function($form) {
			$form.send(ENV.getProcessUrl("member","signup"),function(result) {
				if (result.success == true) {
					location.replace(Member.getUrl("complete",false));
				}
			});
		}
	},
	/**
	 * 정보수정
	 */
	modify:{
		/**
		 * 회원가입폼을 초기화한다.
		 */
		init:function() {
			var $form = $("#ModuleMemberModifyForm");
			var step = $("input[name=step]",$form).val();
			
			$("button[data-action]",$form).on("click",function() {
				var action = $(this).attr("data-action");
				
				if (action == "main") {
					if (ENV.IS_CONTAINER_POPUP == true) {
						if (opener) opener.location.replace(ENV.getUrl(false));
						window.close();
					} else {
						location.replace(ENV.getUrl(false));
					}
				}
				
				if (action == "photo") {
					iModule.modal.get(ENV.getProcessUrl("member","getModal"),{modal:"photo"},function($modal,$form) {
						var $modifyForm = $("#ModuleMemberModifyForm");
						var imageUrl = $("div[data-role=photo] > div.preview",$modifyForm).css("backgroundImage").replace(/^url\(/,"").replace(/\)$/,"");
						
						$("div[data-role=photo-editor]",$form).cropit({
							exportZoom:1,
							imageBackground:true,
							imageBackgroundBorderWidth:20,
							imageState:{
								src:imageUrl
							}
						});
						
						$("button[data-action=upload]",$form).on("click",function() {
							$("input[type=file]",$form).click();
						});
						
						$form.on("submit",function() {
							var imageData = $("div[data-role=photo-editor]",$(this)).cropit("export");
							
							$("div[data-role=photo] > div.preview",$modifyForm).css("backgroundImage","url("+imageData+")");
							$("textarea[name=photo]",$modifyForm).val(imageData);
							iModule.modal.close();
							return false;
						});
					});
				}
				
				if (action == "password") {
					iModule.modal.get(ENV.getProcessUrl("member","getModal"),{modal:"password"},function($modal,$form) {
						var $modifyForm = $("#ModuleMemberModifyForm");
						
						$("input[name=password]",$form).val($("input[name=password]",$modifyForm).val());
						
						$form.on("submit",function() {
							$form.send(ENV.getProcessUrl("member","changePassword"),function(result) {
								if (result.success == true) {
									$("input[name=password]",$modifyForm).val(result.password);
									iModule.modal.alert(iModule.getText("text/confirm"),result.message);
									return false;
								}
							});
							return false;
						});
						
						return false;
					});
				}
			});
			
			if (step == "password") {
				$form.inits(Member.modify.checkPassword);
			} else if (step == "insert") {
				Member.modify.check();
				$form.inits(Member.modify.submit);
			}
		},
		/**
		 * 입력데이터를 확인한다.
		 */
		check:function() {
			var $form = $("#ModuleMemberModifyForm");
			
			$("input[name=email], input[name=name], input[name=nickname]",$form).on("blur",function() {
				var $field = $(this);
				if ($field.val().length > 0 && $field.data("lastValue") != $field.val()) {
					$.send(ENV.getProcessUrl("member","checkMemberValue"),{name:$field.attr("name"),value:$field.val(),mode:"modify"},function(result) {
						$field.status(result.success == true ? "success" : "error",result.message ? result.message : "");
						$field.data("submitValue",$field.val());
						return false;
					});
				}
			});
			
			$("input[name=password], input[name=password_confirm]",$form).on("blur",function() {
				var $password = $("input[name=password]",$form);
				var $password_confirm = $("input[name=password_confirm]",$form);
				
				if ($password.val().length < 6) {
					$(this).status("error");
				} else if (($(this).attr("name") == "password_confirm" || $password_confirm.val().length > 0) && $password.val() != $password_confirm.val()) {
					$(this).status("error",$password_confirm.attr("data-error"));
				} else if ($password.val() == $password_confirm.val()) {
					$(this).status("success");
				}
				
				$password.data("submitValue",$password.val());
				$password_confirm.data("submitValue",$password_confirm.val());
			});
		},
		checkPassword:function($form) {
			$form.send(ENV.getProcessUrl("member","checkPassword"),function(result) {
				if (result.success == true) {
					$("input[name=password]",$form).val(result.password);
					$form.off("submit");
					
					$form.status("default");
					$form.attr("action",location.href);
					$form.attr("method","post");
					$form.submit();
				}
			});
		},
		submit:function($form) {
			$form.send(ENV.getProcessUrl("member","modify"),function(result) {
				if (result.success == true) {
					iModule.modal.alert(iModule.getText("text/confirm"),result.message,function() {
						if (ENV.IS_CONTAINER_POPUP == true) {
							if (opener) opener.location.replace(opener.location.href);
							window.close();
						} else {
							location.replace(ENV.getUrl(false));
						}
					});
					return false;
				}
			});
		}
	},
	/**
	 * 이메일인증
	 */
	verification:{
		init:function() {
			var $form = $("#ModuleMemberVerificationForm");
			
			$("button[data-action]",$form).on("click",function() {
				var action = $(this).attr("data-action");
				if (action == "login") {
					Member.loginModal();
				}
				
				if (action == "logout") {
					Member.logout(this,function() {
						if (ENV.IS_CONTAINER_POPUP == true) {
							if (opener) opener.location.replace(ENV.getUrl(false));
							window.close();
						} else {
							location.replace(ENV.getUrl(false));
						}
					});
				}
				
				if (action == "resend") {
					var $button = $(this);
					$button.status("loading");
					
					$.send(ENV.getProcessUrl("member","verifyEmail"),{mode:"resend"},function(result) {
						if (result.success == true) {
							iModule.alert.show("success",result.message,5);
						}
						$button.status("default");
					});
				}
				
				if (action == "update") {
					Member.modifyPopup();
				}
				
				if (action == "main") {
					if (ENV.IS_CONTAINER_POPUP == true) {
						if (opener) opener.location.replace(ENV.getUrl(false));
						window.close();
					} else {
						location.replace(ENV.getUrl(false));
					}
				}
			});
			
			$form.inits(Member.verification.submit);
		},
		submit:function($form) {
			$form.send(ENV.getProcessUrl("member","verifyEmail"),function(result) {
				if (result.success == true) {
					if (ENV.IS_CONTAINER_POPUP == true) {
						if (opener) opener.location.replace(ENV.getUrl(false));
						window.close();
					} else {
						location.replace(ENV.getUrl(false));
					}
				}
			});
		}
	},
	/**
	 * 패스워드 초기화
	 */
	password:{
		init:function() {
			var $form = $("#ModuleMemberPasswordForm");
			
			$("button[data-action]",$form).on("click",function() {
				var action = $(this).attr("data-action");
				
				if (action == "main") {
					if (ENV.IS_CONTAINER_POPUP == true) {
						if (opener) opener.location.replace(ENV.getUrl(false));
						window.close();
					} else {
						location.replace(ENV.getUrl(false));
					}
				}
			});
			
			$form.inits(Member.password.submit);
		},
		submit:function($form) {
			$form.send(ENV.getProcessUrl("member","resetPassword"),function(result) {
				if (result.success == true) {
					iModule.modal.alert(iModule.getText("text/confirm"),result.message,function() {
						if (ENV.IS_CONTAINER_POPUP == true) {
							if (opener) opener.location.replace(opener.location.href);
							window.close();
						} else {
							location.replace(ENV.getUrl(false));
						}
					});
					
					return false;
				}
			});
		}
	}/*,
	modify:{
		init:function() {
			$("#ModuleMemberModifyForm").formInit(Member.modify.submit,Member.modify.check);
		},
		check:function($input) {
			if ($input.attr("name") == "nickname") {
				if ($input.val().length == 0) {
					$input.inputStatus("error");
				} else {
					$.ajax({
						type:"POST",
						url:ENV.getProcessUrl("member","check"),
						data:{name:$input.attr("name"),value:$input.val()},
						dataType:"json",
						success:function(result) {
							if (result.success == true) {
								$input.inputStatus("success",result.message);
							} else {
								$input.inputStatus("error",result.message);
							}
						},
						error:function() {
							iModule.alertMessage.show("Server Connect Error!");
						}
					});
				}
			} else if ($input.attr("required") == "required") {
				if ($input.val().length == 0) {
					$input.inputStatus("error");
				} else {
					$input.inputStatus("success");
				}
			}
		},
		modifyEmail:function(form) {
			if (form && $(form).is("form") == true) {
				var form = $(form);
				
				$.ajax({
					type:"POST",
					url:ENV.getProcessUrl("member","modifyEmail"),
					data:form.serialize(),
					dataType:"json",
					success:function(result) {
						if (result.success == true) {
							iModule.alertMessage.show("success",result.message,5);
							$("form[name=ModuleMemberModifyForm] input[name=email]").val(form.find("input[name=email]").val());
							$("*[data-name=email]").val(form.find("input[name=email]").val());
							iModule.modal.close();
						} else {
							for (error in result.errors) {
								iModule.inputStatus(form.find("input[name="+error+"]"),"error",result.errors[error]);
							}
						}
					}
				});
				
				return false;
			} else {
				$.ajax({
					type:"POST",
					url:ENV.getProcessUrl("member","modifyEmail"),
					data:{templet:$("form[name=ModuleMemberModifyForm] input[name=templet]").val()},
					dataType:"json",
					success:function(result) {
						if (result.success == true) {
							iModule.modal.showHtml(result.modalHtml);
						} else {
							iModule.alertMessage.show("error",result.message,5);
						}
					}
				});
			}
		},
		sendVerifyEmail:function(button) {
			var form = $("form[name=ModuleMemberModifyEmailForm]");
			
			iModule.buttonStatus($(button),"loading");
			
			$.ajax({
				type:"POST",
				url:ENV.getProcessUrl("member","sendVerifyEmail"),
				data:form.serialize(),
				dataType:"json",
				success:function(result) {
					if (result.success == true) {
						iModule.inputStatus(form.find("input[name=email]"),"success","");
						iModule.alertMessage.show("success",result.message,5);
					} else {
						for (error in result.errors) {
							iModule.inputStatus(form.find("input[name="+error+"]"),"error",result.errors[error]);
						}
						
						if (result.message) iModule.alertMessage.show("error",result.message,5);
					}
					
					iModule.buttonStatus($(button),"reset");
				}
			});
		},
		photoEdit:function() {
			$.ajax({
				type:"POST",
				url:ENV.getProcessUrl("member","photoEdit"),
				data:{templet:$("form[name=ModuleMemberModifyForm] input[name=templet]").val()},
				dataType:"json",
				success:function(result) {
					if (result.success == true) {
						iModule.modal.showHtml(result.modalHtml);
						
						$(function() {
							$(".photo-editor").cropit({
								exportZoom:1,
								imageBackground:true,
								imageBackgroundBorderWidth:30,
								imageState:{
									src:result.photo
								}
							});

							$(".export").click(function() {
								var imageData = $('.image-editor').cropit('export');
								window.open(imageData);
							});
						});
					} else {
						iModule.alertMessage.show("error",result.message,5);
					}
				}
			});
		},
		submit:function($form) {
			var step = $("input[name=step]",$form).val();
			var data = $form.serialize();
			
			$form.formStatus("loading");
			
			if (step == "verifying") {
				return true;
			} else {
				$.ajax({
					type:"POST",
					url:ENV.getProcessUrl("member","modify"),
					data:data,
					dataType:"json",
					success:function(result) {
						if (result.success == true) {
							if (step == "verify") {
								$("input[name=step]",$form).val("verifying");
								$("input[name=password]",$form).val(result.password);
								$form.off("submit");
								$form.formStatus("success");
								$form.submit();
							} else {
								iModule.alertMessage.show("success",result.message,5);
								$form.formStatus("default");
							}
						} else {
							$form.formStatus("error",result.errors);
						}
					}
				});
				
				return false;
			}
		}
	}*/,
	/**
	 * 포인트내역 컨텍스트
	 */
	point:{
		init:function(id) {
			var $form = $("#"+id);
			
			if (id == "ModuleMemberPointForm") {
				$("select[name=type]",$form).on("change",function() {
					location.href = ENV.getUrl(null,null,$(this).val(),1);
				});
			}
		}
	},
	/**
	 * 소셜계정연동
	 */
	connect:{
		init:function() {
			var $form = $("#ModuleMemberConnectForm");
			
			$("input[name=password]",$form).focus();
			$form.inits(Member.login);
		}
	},
	forceLogin:function(code,redirectUrl) {
		$.ajax({
			type:"POST",
			url:ENV.getProcessUrl("member","forceLogin"),
			data:{code:code},
			dataType:"json",
			success:function(result) {
				if (result.success == true) {
					location.replace(redirectUrl ? redirectUrl : location.href.split("#").shift());
				} else {
					iModule.alertMessage.show("error",result.message,5);
				}
			},
			error:function() {
				iModule.alertMessage.show("Server Connect Error!");
			}
		});
	}
};