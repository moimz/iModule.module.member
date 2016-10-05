/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 회원모듈 기본템플릿 스타일정의
 *
 * @file /modules/member/templets/default/styles/style.css
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160922
 */

var Member = {
	/**
	 * 회원로그인
	 *
	 * @param object $form 로그인폼
	 */
	login:function($form) {
		$form.send(ENV.getProcessUrl("member","login"),function(result) {
			if (result.success == true) {
				location.href = location.href;
			}
		});
	},
	logout:function(button) {
		iModule.buttonStatus($(button),"loading");
		
		$.ajax({
			type:"POST",
			url:ENV.getProcessUrl("member","logout"),
			dataType:"json",
			success:function(result) {
				if (result.success == true) {
					location.href = location.href.split("#").shift();
				} else {
					if (result.message) iModule.alertMessage.show("error",result.message,5);
					
					iModule.buttonStatus($(button),"reset");
				}
			},
			error:function() {
				iModule.alertMessage.show("Server Connect Error!");
			}
		});
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
			var next = $("input[name=next]",$form).val();
			
			if (step == "agreement") {
				$("button[type=submit]",$form).prop("disabled",true);
				$("input[type=checkbox]",$form).on("change",function() {
					$("button[type=submit]",$form).prop("disabled",$("input[type=checkbox]",$form).length != $("input[type=checkbox]:checked",$form).length);
				});
				
				$form.on("submit",function() {
					$("input[name=agreement]",$form).prop("disabled",true);
				});
			}
			
			if (step == "insert" || step == "cert" || step == "verify") {
				$form.inits(Member.signup.submit);
				
				if (step == "insert") {
					Member.signup.check();
					
					if ($("input[type=checkbox][name='agreements[]']",$form).length > 0) {
						$("button[type=submit]",$form).prop("disabled",true);
					}
					
					$("input[type=checkbox][name='agreements[]']",$form).on("change",function() {
						$("button[type=submit]",$form).prop("disabled",$("input[type=checkbox][name='agreements[]']",$form).length != $("input[type=checkbox][name='agreements[]']:checked",$form).length);
					});
				}
				
				if (step == "verify") {
					Member.signup.check();
				}
			} else if (next) {
				$form.attr("method","post");
				$form.attr("action",ENV.getUrl(null,null,next,false));
				$("input[name=step]",$form).prop("disabled",true);
				$("input[name=prev]",$form).prop("disabled",true);
				$("input[name=next]",$form).prop("disabled",true);
				$("input[name=templet]",$form).prop("disabled",true);
			} else {
				$form.on("submit",function() {
					location.href = ENV.getUrl(false);
					return false;
				});
			}
		},
		/**
		 * 회원가입폼 입력데이터를 확인한다.
		 */
		check:function() {
			var $form = $("#ModuleMemberSignUpForm");
			
			$("input[name=email], input[name=name], input[name=nickname], input[name=email_verification_email]",$form).on("blur",function() {
				var $field = $(this);
				if ($field.val().length > 0 && $field.data("lastValue") != $field.val()) {
					$.send(ENV.getProcessUrl("member","liveCheckValue"),{name:$field.attr("name"),value:$field.val(),mode:"signup"},function(result) {
						$field.status(result.success == true ? "success" : "error",result.message ? result.message : "");
						$field.data("submitValue",$field.val());
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
			var step = $("input[name=step]",$form).val();
			var next = $("input[name=next]",$form).val();
			
			if (step == "insert") {
				$form.send(ENV.getProcessUrl("member","signup"),function(result) {
					if (result.success == true) {
						$form.status("success");
						location.href = ENV.getUrl(null,null,next,false);
					} else {
						$form.status("error",result.errors);
						if (result.message) iModule.alert.show("error",result.message);
					}
				});
			}
			
			if (step == "verify") {
				$form.send(ENV.getProcessUrl("member","verifyEmail"),function(result) {
					if (result.success == true) {
						$form.status("success");
						location.href = ENV.getUrl(null,null,next,false);
					} else {
						$form.status("error",result.errors);
						if (result.message) iModule.alert.show("error",result.message);
					}
				});
			}
		}/*,
		check:function($input) {
			if ($input.attr("name") == "password" || $input.attr("name") == "password_confirm") {
				if ($input.val().length < 4) {
					$input.inputStatus("error");
					return;
				}
				
				if ($input.attr("name") == "password") {
					$input.inputStatus("success");
					if ($("input[name=password_confirm]",$input.parents("form")).val().length > 0) Member.signup.check($("input[name=password_confirm]",$input.parents("form")));
				}
				
				if ($input.attr("name") == "password_confirm") {
					if ($input.val() == $("input[name=password]",$input.parents("form")).val()) {
						$input.inputStatus("success");
					} else {
						$input.inputStatus("error");
					}
				}
			} else if ($input.attr("name") == "email" || $input.attr("name") == "nickname") {
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
		submit:function($form) {
			var step = $("input[name=step]",$form).val();
			var next = $("input[name=next]",$form).val();
			
			if (step == "agreement") {
				$form.attr("action",next);
				$form.off("submit");
				$form.submit();
			} else if (step == "cert") {
				if (typeof Member.signup.cert == "function") {
					Member.signup.cert($form);
				} else {
					$form.attr("action",next);
					$form.off("submit");
					$form.submit();
				}
			} else if (step == "insert") {
				$.ajax({
					type:"POST",
					url:ENV.getProcessUrl("member","signup"),
					data:$form.serialize(),
					dataType:"json",
					success:function(result) {
						$form.formStatus("loading");
						
						if (result.success == true) {
							location.href = next;
						} else {
							$form.formStatus("error",result.errors);
						}
					}
				});
			} else if (step == "verify") {
				$.ajax({
					type:"POST",
					url:ENV.getProcessUrl("member","verifyEmail"),
					data:$form.serialize(),
					dataType:"json",
					success:function(result) {
						$form.formStatus("loading");
						
						if (result.success == true) {
							location.href = next;
						} else {
							$form.formStatus("error",result.errors);
						}
					}
				});
			}
		},
		resendVerifyEmail:function(button) {
			var $form = $("form[name=ModuleMemberSignUpForm]");
			var data = $form.serialize();
			$form.formStatus("loading");
			
			$.ajax({
				type:"POST",
				url:ENV.getProcessUrl("member","sendVerifyEmail"),
				data:data,
				dataType:"json",
				success:function(result) {
					if (result.success == true) {
						iModule.alertMessage.show("success",result.message,5);
					} else {
						$form.formStatus("error",result.errors);
						if (result.message) iModule.alertMessage.show("error",result.message,5);
					}
				}
			});
		}*/
	},
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
	},
	photoEdit:{
		init:function() {
			$("#ModuleMemberPhotoEditForm").formInit(Member.photoEdit.submit);
		},
		submit:function($form) {
			$form.formStatus("loading");
			
			var photoData = $(".photo-editor").cropit("export");
			$("#ModuleMemberPhotoPreview").attr("src",photoData);
			
			$.ajax({
				type:"POST",
				url:ENV.getProcessUrl("member","photoUpload"),
				data:{photo:photoData},
				dataType:"json",
				success:function(result) {
					if (result.success == true) {
						iModule.alertMessage.show("success",result.message,5);
						iModule.modal.close();
					} else {
						$form.formStatus("error");
						iModule.alertMessage.show("error",result.message,5);
					}
				}
			});
			
			return false;
		}
	},
	password:{
		init:function() {
			$("#ModuleMemberPasswordForm").formInit(Member.password.submit,Member.password.check);
		},
		check:function($input) {
			if ($input.attr("name") == "password" || $input.attr("name") == "password_confirm") {
				if ($input.val().length < 4) {
					$input.inputStatus("error");
					return;
				}
				
				if ($input.attr("name") == "password") {
					$input.inputStatus("success");
					if ($("input[name=password_confirm]",$input.parents("form")).val().length > 0) Member.password.check($("input[name=password_confirm]",$input.parents("form")));
				}
				
				if ($input.attr("name") == "password_confirm") {
					if ($input.val() == $("input[name=password]",$input.parents("form")).val()) {
						$input.inputStatus("success");
					} else {
						$input.inputStatus("error");
					}
				}
			} else if ($input.attr("name") == "old_password") {
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
		submit:function($form) {
			var data = $form.serialize();
			var type = $("input[name=type]",$form).val();
			$form.formStatus("loading");
			
			$.ajax({
				type:"POST",
				url:ENV.getProcessUrl("member","password"),
				data:data,
				dataType:"json",
				success:function(result) {
					if (result.success == true) {
						if (type == "modify") {
							location.href = location.href;
						} else {
							iModule.alertMessage.show("success",result.message,5);
							$("input",$form).val("");
							$form.formStatus("default");
						}
					} else {
						$form.formStatus("error",result.errors);
					}
				}
			});
			
			return false;
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
					location.href = redirectUrl ? redirectUrl : location.href.split("#").shift();
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