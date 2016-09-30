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
	signup:{
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
			
			if (step == "insert") {
				$form.inits(Member.signup.submit);
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
		submit:function($form) {
			console.log($form);
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
	},
	login:function(form) {
		var form = $(form);
		iModule.buttonStatus(form,"loading");
		iModule.inputStatus(form,"default");
		
		$.ajax({
			type:"POST",
			url:ENV.getProcessUrl("member","login"),
			data:form.serialize(),
			dataType:"json",
			success:function(result) {
				if (result.success == true) {
					location.href = location.href.split("#").shift();
				} else {
					if (result.redirect) {
						location.href = result.redirect;
					} else {
						for (var field in result.errors) {
							iModule.inputStatus(form.find("input[name="+field+"]"),"error",result.errors[field]);
						}
						
						if (result.message) iModule.alertMessage.show("error",result.message,5);
						
						iModule.buttonStatus(form,"reset");
					}
				}
			},
			error:function() {
				iModule.alertMessage.show("Server Connect Error!");
			}
		});
		
		return false;
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
	}
};