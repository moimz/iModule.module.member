<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 회원모듈 설정을 위한 설정폼을 생성한다.
 * 
 * @file /modules/member/admin/configs.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2018. 3. 18.
 */
if (defined('__IM__') == false) exit;
?>
<script>
new Ext.form.Panel({
	id:"ModuleConfigForm",
	border:false,
	bodyPadding:10,
	width:700,
	fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:true},
	items:[
		new Ext.form.FieldSet({
			title:Member.getText("admin/configs/form/default_setting"),
			items:[
				Admin.templetField(Member.getText("admin/configs/form/templet"),"templet","member",false)
			]
		}),
		new Ext.form.FieldSet({
			title:Member.getText("admin/configs/form/private"),
			items:[
				new Ext.form.Checkbox({
					fieldLabel:Member.getText("admin/configs/form/private_photo"),
					boxLabel:Member.getText("admin/configs/form/private_photo_help"),
					name:"photo_privacy",
					uncheckedValue:""
				}),
				new Ext.form.Checkbox({
					fieldLabel:Member.getText("admin/configs/form/allow_reset_password"),
					boxLabel:Member.getText("admin/configs/form/allow_reset_password_help"),
					name:"allow_reset_password",
					uncheckedValue:""
				})
			]
		}),
		new Ext.form.FieldSet({
			title:Member.getText("admin/configs/form/signup_setting"),
			items:[
				new Ext.form.Checkbox({
					fieldLabel:Member.getText("admin/configs/form/allow_signup"),
					boxLabel:Member.getText("admin/configs/form/allow_signup_help"),
					name:"allow_signup",
					uncheckedValue:"",
					checked:true
				}),
				new Ext.form.Checkbox({
					fieldLabel:Member.getText("admin/configs/form/approve_signup"),
					boxLabel:Member.getText("admin/configs/form/approve_signup_help"),
					name:"approve_signup",
					uncheckedValue:"",
					checked:true
				}),
				new Ext.form.Checkbox({
					fieldLabel:Member.getText("admin/configs/form/verified_email"),
					boxLabel:Member.getText("admin/configs/form/verified_email_help"),
					name:"verified_email",
					uncheckedValue:"",
					checked:true
				}),
				new Ext.form.FieldContainer({
					layout:"hbox",
					items:[
						new Ext.form.NumberField({
							fieldLabel:Member.getText("admin/configs/form/point"),
							name:"point",
							value:1000,
							flex:1
						}),
						new Ext.form.NumberField({
							fieldLabel:Member.getText("admin/configs/form/exp"),
							name:"exp",
							value:0,
							flex:1
						})
					]
				}),
				new Ext.form.ComboBox({
					fieldLabel:Member.getText("admin/configs/form/label"),
					name:"label",
					store:new Ext.data.JsonStore({
						proxy:{
							type:"ajax",
							simpleSortMode:true,
							url:ENV.getProcessUrl("member","@getLabels"),
							extraParams:{type:"no_label"},
							reader:{type:"json",root:"lists",totalProperty:"totalCount"}
						},
						autoLoad:true,
						remoteSort:false,
						sorters:[{property:"sort",direction:"ASC"}],
						pageSize:0,
						fields:["idx","title"]
					}),
					editable:false,
					displayField:"title",
					valueField:"idx",
					value:"0"
				}),
				new Ext.form.Checkbox({
					fieldLabel:Member.getText("admin/configs/form/universal_login"),
					boxLabel:Member.getText("admin/configs/form/universal_login_help"),
					name:"universal_login",
					uncheckedValue:"",
					checked:true
				})
			]
		})
	]
});
</script>