/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 로그인 위젯 기본템플릿 - 로그인 스크립트
 *
 * @file /modules/member/widgets/login/templets/default/scripts/script.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.1.0
 * @modified 2019. 2. 26.
 */
$(document).ready(function() {
	var $widget = $("div[data-widget=member-login][data-templet=default]");
	
	$("button[data-action]",$("ul[data-role=button]",$widget)).on("click",function(e) {
		var $button = $(this);
		var action = $button.attr("data-action");
		
		if (action == "push") {
			var $push = $("div[data-role=layer].push",$widget);
			$button.parent().toggleClass("selected");
			
			var $lists = $("ul",$push);
			$lists.empty();
			$lists.append($("<li>").addClass("loading").append($("<i>").addClass("mi mi-loading")));
			
			if ($button.parent().hasClass("selected") == true) {
				$push.addClass("opened");
				
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
									var item = $button.data("item");
									Push.view(item.module,item.type,item.idx);
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
									var item = $button.data("item");
									Push.view(item.module,item.type,item.idx);
								});
								
								$lists.append($("<li>").append($button));
							}
						}
					} else {
						$lists.append($("<li>").addClass("message").html(result.message));
						return false;
					}
				});
			} else {
				$push.removeClass("opened");
			}
			
			$push.on("click",function(e) {
				e.stopImmediatePropagation();
			});
			
			e.stopImmediatePropagation();
		}
	});
	
	$("body").on("click",function() {
		$("button[data-action=push]",$widget).parent().removeClass("selected");
		$("div[data-role=layer].push",$widget).removeClass("opened");
	});
});