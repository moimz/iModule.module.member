<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 회원 기본템플릿 푸터
 *
 * @file /modules/member/templets/default/footer.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 30.
 */
if (defined('__IM__') == false) exit;
if (defined('__IM_CONTAINER_POPUP__') == true) {
?>
</div>

<script>
$(document).on("init",function() {
	var lastHeight = 0;
	var popupResize = function(is_center) {
		if (lastHeight != $("div[data-module=member]").outerHeight()) {
			iModule.resizeWindow(null,$("div[data-module=member]").outerHeight(),is_center === true);
			lastHeight = $("div[data-module=member]").outerHeight();
		}
		setTimeout(popupResize,500);
	};
	popupResize(true);
});
</script>
<?php } ?>