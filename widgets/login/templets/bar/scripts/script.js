/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 로그인 위젯 가로바 템플릿 UI 처리
 * 
 * @file /modules/member/widgets/login/scripts/script.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2019. 6. 8.
 */
$(document).ready(function() {
	var $widget = $("div[data-widget=member-login][data-templet=bar]");
	
	$("button[data-action]",$widget).on("click",function(e) {
		var $button = $(this);
		var action = $button.attr("data-action");
		
		if (action == "login") {
			Member.loginModal();
		}
		
		if (action.search(/profile|push|message/) > -1) {
			var $current = $button.parent().parent().parent();
			var $layer = $("div[data-role=layer]",$current);
			
			if ($button.parent().hasClass("opened") == true) {
				$button.parent().removeClass("opened");
				$layer.hide();
			} else {
				$("li[data-role]",$widget).removeClass("opened");
				$button.parent().addClass("opened");
				$layer.show();
				
				var left = $button.parent().position().left - $layer.outerWidth() / 2 + $button.parent().width() / 2;
				$layer.css("left",left);
				
				if ($layer.offset().left + $layer.outerWidth() + 10 > $(window).width()) {
					$layer.css("left",left + ($(window).width() - ($layer.offset().left + $layer.outerWidth() + 10)));
				}
				
				$("section[data-role]",$layer).hide();
				
				if (action == "profile") {
					var $profile = $("section[data-role=profile]",$layer);
					$profile.show();
				}
			
				if (action.indexOf("push") > -1) {
					var $push = $("section[data-role=push]",$layer);
					$push.show();
					var $lists = $("ul",$push);
					
					$("ul",$push).css("maxHeight",$(window).height() - $push.offset().top - 100);
					
					$lists.empty();
					$lists.append($("<li>").addClass("loading").append($("<i>").addClass("mi mi-loading")));
					
					Push.getRecently(20,function(result) {
						$lists.empty();
						
						if (result.success == true) {
							var news = [];
							var previous = [];
							for (var i=0, loop=result.lists.length;i<loop;i++) {
								if (result.lists[i].is_checked == false) {
									news.push(result.lists[i]);
								} else {
									previous.push(result.lists[i]);
								}
							}
							
							if (news.length > 0) {
								$lists.append($("<li>").addClass("title").html(Push.getText("text/new")));
								for (var i=0, loop=news.length;i<loop;i++) {
									var item = news[i];
									var $button = $("<button>").attr("type","button").data("item",item);
									if (item.is_readed == false) $button.addClass("unread");
									
									var $icon = $("<i>").addClass("icon");
									$icon.css("backgroundImage","url(" + item.icon + ")");
									$button.append($icon);
									
									var $text = $("<div>").addClass("text");
									$text.append(item.message);
									$text.append($("<time>").html(moment(item.reg_date * 1000).locale($("html").attr("lang")).fromNow()));
									$button.append($text);
									
									$button.on("click",function(e) {
										var item = $(this).data("item");
										Push.view(item.module,item.type,item.idx,$(this));
									});
									
									$lists.append($("<li>").append($button));
								}
							}
							
							if (previous.length > 0) {
								$lists.append($("<li>").addClass("title").html(Push.getText("text/previous")));
								for (var i=0, loop=previous.length;i<loop;i++) {
									var item = previous[i];
									var $button = $("<button>").attr("type","button").data("item",item);
									if (item.is_readed == false) $button.addClass("unread");
									
									var $icon = $("<i>").addClass("icon");
									$icon.css("backgroundImage","url(" + item.icon + ")");
									$button.append($icon);
									
									var $text = $("<div>").addClass("text");
									$text.append(item.message);
									$text.append($("<time>").html(moment(item.reg_date * 1000).locale($("html").attr("lang")).fromNow()));
									$button.append($text);
									
									$button.on("click",function(e) {
										var item = $(this).data("item");
										Push.view(item.module,item.type,item.idx,$(this));
									});
									
									$lists.append($("<li>").append($button));
								}
							}
						} else {
							$lists.append($("<li>").addClass("message").html(result.message));
							return false;
						}
					});
				}
			}
			
			e.stopImmediatePropagation();
		}
	});
});

$(document).ready(function() {
	$("body").on("click",function() {
		$("li[data-role].opened",$("div[data-widget=member-login][data-templet=bar]")).removeClass("opened");
		$("div[data-role=layer]",$("div[data-widget=member-login][data-templet=bar]")).hide();
	});
});