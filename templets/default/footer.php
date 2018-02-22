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

if (defined('__IM_CONTAINER__') == true) {
?>
</div>

<?php if (defined('__IM_CONTAINER_POPUP__') == true) { ?>
<script>
$(document).on("init",function() {
	var $container = $("div[data-module=member]");
	var lastHeight = 0;
	var popupResize = function(is_center) {
		var height = $("header",$container).outerHeight(true) + $("section.box",$container).outerHeight(true) + 20;
		
		if (screen.height - 100 > height && lastHeight != height) {
			iModule.resizeWindow(null,height,is_center === true);
			lastHeight = height;
		}
		setTimeout(popupResize,500);
	};
	popupResize(true);
});
</script>
<?php }} ?>