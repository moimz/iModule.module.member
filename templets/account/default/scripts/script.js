Member.account = {
	slideMenu:function() {
		if ($(".accountLayout > aside.menu").is(":visible") == true) {
			$(".accountLayout > aside.menu").animate({width:0},{step:function(now) {
				$(this).parent().css("width","calc(100% + "+now+"px)");
				if (now == 0) $(this).hide();
			}});
		} else {
			if ($(document).scrollTop() > 60) $(document).scrollTop(60);
			$(".accountLayout > aside.menu").css("display","table-cell").css("width",0);
			$(".accountLayout > aside.menu").animate({width:240},{step:function(now) {
				$(this).parent().css("width","calc(100% + "+now+"px)");
			}});
		}
	}
};

$(window).on("resize",function() {
	if ($(".accountTitle > aside").is(":visible") == true) {
		$(".accountLayout").css("width","100%");
	} else if ($(".accountLayout > aside.menu").is(":visible") == true) {
		$(".accountLayout > aside.menu").parent().css("width","calc(100% + "+$(".accountLayout > aside.menu").width()+"px)");
	}
	
	$("#ModuleMemberAccountContext").css("minHeight",$(window).height() - $("header").height());
});

$(document).ready(function() {
	$("#ModuleMemberAccountContext").css("minHeight",$(window).height() - $("header").height());
});

$(document).on("scroll",function() {
	if ($(document).scrollTop() >= 60) {
		$("body").addClass("fixed");
	} else {
		$("body").removeClass("fixed");
	}
})