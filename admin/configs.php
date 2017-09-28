<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 회원모듈 설정을 위한 설정폼을 생성한다.
 * 
 * @file /modules/member/admin/configs.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160910
 */

if (defined('__IM__') == false) exit;
?>
<script>
new Ext.form.Panel({
	id:"ModuleConfigForm",
	border:false,
	bodyPadding:10,
	fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:true},
	items:[
		new Ext.form.FieldSet({
			title:Member.getText("admin/configs/form/default_setting"),
			items:[
				Admin.templetField(Member.getText("admin/configs/form/templet"),"templet","member",false)
			]
		}),
		new Ext.form.FieldSet({
			title:"보안설정",
			items:[
				new Ext.form.Checkbox({
					boxLabel:"로그인을 하지 않은 경우 모든 회원들의 사진을 기본사진으로 보이게 설정합니다.",
					name:"photo_privacy",
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
				}),
				new Ext.form.FieldContainer({
					fieldLabel:Member.getText("admin/configs/form/signup_step"),
					layout:"hbox",
					items:[
						new Ext.form.Hidden({
							name:"signup_step",
							listeners:{
								change:function(form,value) {
									if (Ext.getCmp("ModuleMemberSignupStepUsed").getStore().getCount() == 0) {
										var step = value.split(",");
										for (var i=0, loop=step.length;i<loop;i++) {
											Ext.getCmp("ModuleMemberSignupStepAvailable").getStore().removeAt(Ext.getCmp("ModuleMemberSignupStepAvailable").getStore().findExact("step",step[i]));
											Ext.getCmp("ModuleMemberSignupStepUsed").getStore().add({step:step[i],title:Member.getText("text/signup_step/"+step[i]),sort:i});
										}
									}
								}
							}
						}),
						new Ext.grid.Panel({
							id:"ModuleMemberSignupStepAvailable",
							title:Member.getText("admin/configs/form/signup_step_available"),
							border:true,
							hideHeaders:true,
							tbar:[
								new Ext.Button({
									iconCls:"fa fa-check-square-o",
									handler:function() {
										Ext.getCmp("ModuleMemberSignupStepAvailable").getSelectionModel().selectAll();
									}
								}),
								"->",
								new Ext.Button({
									text:Member.getText("admin/configs/form/add_signup_step"),
									iconCls:"fa fa-arrow-right",
									iconAlign:"right",
									handler:function() {
										var checked = Ext.getCmp("ModuleMemberSignupStepAvailable").getSelectionModel().getSelection();
										for (var i=0, loop=checked.length;i<loop;i++) {
											checked[i].set("sort",Ext.getCmp("ModuleMemberSignupStepUsed").getStore().getCount());
											Ext.getCmp("ModuleMemberSignupStepUsed").getStore().add(checked[i]);
										}
										Ext.getCmp("ModuleMemberSignupStepAvailable").getStore().remove(checked);
										
										Member.checkSignupStep();
										
										var step = [];
										for (var i=0, loop=Ext.getCmp("ModuleMemberSignupStepUsed").getStore().getCount();i<loop;i++) {
											step.push(Ext.getCmp("ModuleMemberSignupStepUsed").getStore().getAt(i).get("step"));
										}
										Ext.getCmp("ModuleConfigForm").getForm().findField("signup_step").setValue(step.join(","));
									}
								})
							],
							store:new Ext.data.ArrayStore({
								fields:["step","title","sort"],
								sorters:[{property:"sort",direction:"ASC"}],
								data:[["agreement",Member.getText("text/signup_step/agreement"),0],["label",Member.getText("text/signup_step/label"),1],["cert",Member.getText("text/signup_step/cert"),2],["insert",Member.getText("text/signup_step/insert"),3],["verify",Member.getText("text/signup_step/verify"),4],["complete",Member.getText("text/signup_step/complete"),5]]
							}),
							flex:1,
							height:300,
							columns:[{
								flex:1,
								dataIndex:"title"
							}],
							selModel:new Ext.selection.CheckboxModel()
						}),
						new Ext.grid.Panel({
							id:"ModuleMemberSignupStepUsed",
							title:Member.getText("admin/configs/form/signup_step_used"),
							border:true,
							hideHeaders:true,
							margin:"0 0 0 5",
							tbar:[
								new Ext.Button({
									text:Member.getText("admin/configs/form/delete_signup_step"),
									iconCls:"fa fa-arrow-left",
									handler:function() {
										var sort = ["agreement","label","cert","insert","verify","complete"];
										var checked = Ext.getCmp("ModuleMemberSignupStepUsed").getSelectionModel().getSelection();
										for (var i=0, loop=checked.length;i<loop;i++) {
											if (checked[i].get("step") == "insert") {
												Ext.getCmp("ModuleMemberSignupStepUsed").getSelectionModel().deselect(checked[i]);
											} else {
												checked[i].set("sort",$.inArray(checked[i].get("sort"),sort));
												Ext.getCmp("ModuleMemberSignupStepAvailable").getStore().add(checked[i]);
											}
										}
										checked = Ext.getCmp("ModuleMemberSignupStepUsed").getSelectionModel().getSelection();
										Ext.getCmp("ModuleMemberSignupStepUsed").getStore().remove(checked);
										
										Member.checkSignupStep();
										
										var step = [];
										for (var i=0, loop=Ext.getCmp("ModuleMemberSignupStepUsed").getStore().getCount();i<loop;i++) {
											step.push(Ext.getCmp("ModuleMemberSignupStepUsed").getStore().getAt(i).get("step"));
										}
										Ext.getCmp("ModuleConfigForm").getForm().findField("signup_step").setValue(step.join(","));
									}
								}),
								"-",
								new Ext.Button({
									iconCls:"fa fa-caret-up",
									handler:function() {
										Admin.gridSort(Ext.getCmp("ModuleMemberSignupStepUsed"),"sort","up");
										Member.checkSignupStep();
										var step = [];
										for (var i=0, loop=Ext.getCmp("ModuleMemberSignupStepUsed").getStore().getCount();i<loop;i++) {
											step.push(Ext.getCmp("ModuleMemberSignupStepUsed").getStore().getAt(i).get("step"));
										}
										Ext.getCmp("ModuleConfigForm").getForm().findField("signup_step").setValue(step.join(","));
									}
								}),
								new Ext.Button({
									iconCls:"fa fa-caret-down",
									handler:function() {
										Admin.gridSort(Ext.getCmp("ModuleMemberSignupStepUsed"),"sort","down");
										Member.checkSignupStep();
										var step = [];
										for (var i=0, loop=Ext.getCmp("ModuleMemberSignupStepUsed").getStore().getCount();i<loop;i++) {
											step.push(Ext.getCmp("ModuleMemberSignupStepUsed").getStore().getAt(i).get("step"));
										}
										
										Ext.getCmp("ModuleConfigForm").getForm().findField("signup_step").setValue(step.join(","));
									}
								})
							],
							store:new Ext.data.ArrayStore({
								fields:["step","title","sort"],
								sorters:[{property:"sort",direction:"ASC"}],
								data:[]
							}),
							flex:1,
							height:300,
							columns:[{
								flex:1,
								dataIndex:"title"
							}],
							selModel:new Ext.selection.CheckboxModel()
						})
					]
				})
			]
		})
	]
});
</script>