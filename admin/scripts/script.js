/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 회원모듈 관리자 기능 처리
 * 
 * @file /modules/member/admin/scripts/script.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 29.
 */
var Member = {
	/**
	 * 회원라벨관리
	 */
	label:{
		add:function(idx) {
			new Ext.Window({
				id:"ModuleMemberAddLabelWindow",
				title:(idx ? Member.getText("admin/label/window/label_modify") : Member.getText("admin/label/window/label_add")),
				modal:true,
				width:600,
				border:false,
				autoScroll:true,
				items:[
					new Ext.form.Panel({
						id:"ModuleMemberAddLabelForm",
						border:false,
						bodyPadding:"10 10 0 10",
						fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:true},
						items:[
							new Ext.form.Hidden({
								name:"idx",
								disabled:idx !== undefined ? false : true
							}),
							new Ext.form.FieldContainer({
								fieldLabel:Member.getText("admin/label/form/title"),
								layout:{type:"vbox",align:"stretch"},
								items:[
									new Ext.form.FieldContainer({
										layout:"hbox",
										items:[
											new Ext.form.TextField({
												name:"title",
												flex:1
											}),
											new Ext.form.Checkbox({
												name:"is_default_language_setting",
												boxLabel:Member.getText("admin/label/form/default_language_setting"),
												hidden:idx !== 0,
												style:{marginLeft:"5px"},
												listeners:{
													change:function(form,checked) {
														Ext.getCmp("ModuleMemberAddLabelForm").getForm().findField("title").setDisabled(checked);
													}
												}
											})
										]
									}),
									Admin.languageFieldSet("ModuleMemberAddLabelLanguages",Member.getText("admin/label/form/title"),"codes","titles")
								]
							}),
							new Ext.form.Checkbox({
								fieldLabel:Member.getText("admin/label/form/allow_signup"),
								name:"allow_signup",
								checked:true,
								boxLabel:Member.getText("admin/label/form/allow_signup_help"),
								afterBodyEl:(idx == 0 ? '<div class="x-form-help">'+Member.getText("admin/label/form/allow_signup_help_default")+'</div>' : "")
							}),
							new Ext.form.Checkbox({
								fieldLabel:Member.getText("admin/label/form/approve_signup"),
								name:"approve_signup",
								checked:false,
								boxLabel:Member.getText("admin/label/form/approve_signup_help"),
								afterBodyEl:(idx == 0 ? '<div class="x-form-help">'+Member.getText("admin/label/form/approve_signup_help_default")+'</div>' : "")
							}),
							new Ext.form.Checkbox({
								fieldLabel:Member.getText("admin/label/form/is_change"),
								name:"is_change",
								checked:true,
								readOnly:idx == 0,
								boxLabel:Member.getText("admin/label/form/is_change_help"),
								afterBodyEl:(idx == 0 ? '<div class="x-form-help">'+Member.getText("admin/label/form/is_change_help_default")+'</div>' : "")
							}),
							new Ext.form.Checkbox({
								fieldLabel:Member.getText("admin/label/form/is_unique"),
								name:"is_unique",
								checked:false,
								readOnly:idx == 0,
								boxLabel:Member.getText("admin/label/form/is_unique_help"),
								afterBodyEl:(idx == 0 ? '<div class="x-form-help">'+Member.getText("admin/label/form/is_unique_help_default")+'</div>' : "")
							})
						]
					})
				],
				buttons:[
					new Ext.Button({
						text:Member.getText("button/confirm"),
						handler:function() {
							Ext.getCmp("ModuleMemberAddLabelForm").getForm().submit({
								url:ENV.getProcessUrl("member","@saveLabel"),
								submitEmptyText:false,
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/saving"),
								success:function(form,action) {
									Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/saved"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function(button) {
										Ext.getCmp("ModuleMemberAddLabelWindow").close();
										Ext.getCmp("ModuleMemberLabelList").getStore().load(function() {
											console.log("reload");
										});
									}});
								},
								failure:function(form,action) {
									if (action.result) {
										if (action.result.message) {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										} else {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_SAVE_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										}
									} else {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("INVALID_FORM_DATA"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									}
								}
							});
						}
					}),
					new Ext.Button({
						text:Member.getText("button/cancel"),
						handler:function() {
							Ext.getCmp("ModuleMemberAddLabelWindow").close();
						}
					})
				],
				listeners:{
					show:function() {
						if (idx !== undefined) {
							Ext.getCmp("ModuleMemberAddLabelForm").getForm().load({
								url:ENV.getProcessUrl("member","@getLabel"),
								params:{idx:idx},
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/loading"),
								success:function(form,action) {
									if (action.result.data) Admin.parseLanguageFieldValue("ModuleMemberAddLabelLanguages",action.result.data.languages);
								},
								failure:function(form,action) {
									if (action.result && action.result.message) {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									} else {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									}
									Ext.getCmp("ModuleMemberAddLabelWindow").close();
								}
							});
						}
					}
				}
			}).show();
		},
		delete:function() {
			var selected = Ext.getCmp("ModuleMemberList").getSelectionModel().getSelection();
			if (selected.length == 0) {
				Ext.Msg.show({title:Admin.getText("alert/error"),msg:"삭제할 라벨을 선택하여 주십시오.",buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
				return;
			}
			
			var idxes = [];
			for (var i=0, loop=selected.length;i<loop;i++) {
				idxes.push(selected[i].get("idx"));
				if (selected[i].get("idx") == 0) {
					Ext.Msg.show({title:Admin.getText("alert/error"),msg:"기본라벨은 삭제할 수 없습니다.",buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
					return;
				}
			}
			
			Ext.Msg.show({title:Admin.getText("alert/info"),msg:"선택한 라벨을 삭제하시겠습니까?<br>해당 라벨에 속한 회원은 삭제되지 않습니다.",buttons:Ext.Msg.OKCANCEL,icon:Ext.Msg.QUESTION,fn:function(button) {
				if (button == "ok") {
					Ext.Msg.wait(Admin.getText("action/working"),Admin.getText("action/wait"));
					$.send(ENV.getProcessUrl("member","@deleteLabel"),{idxes:idxes.join(",")},function(result) {
						if (result.success == true) {
							Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/worked"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function() {
								Ext.getCmp("ModuleMemberList").getStore().reload();
							}});
						}
					});
				}
			}});
		}
	},
	/**
	 * 회원가입필드 처리
	 */
	field:{
		add:function(oName) {
			var label = Ext.getCmp("ModuleMemberSignUpFieldList").getStore().getProxy().extraParams.label;
			
			new Ext.Window({
				id:"ModuleMemberAddFieldWindow",
				title:(oName ? Member.getText("admin/field/window/modify") : Member.getText("admin/field/window/add")),
				modal:true,
				width:800,
				border:false,
				autoScroll:true,
				items:[
					new Ext.form.Panel({
						id:"ModuleMemberAddFieldForm",
						border:false,
						bodyPadding:"10 10 0 10",
						fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:true},
						items:[
							new Ext.form.Hidden({
								name:"label",
								value:label
							}),
							new Ext.form.Hidden({
								name:"oName",
								value:oName,
								disabled:oName ? false : true
							}),
							new Ext.form.FieldContainer({
								fieldLabel:Member.getText("admin/field/form/name"),
								layout:"hbox",
								items:[
									new Ext.form.ComboBox({
										name:"name",
										disabled:oName ? true : false,
										store:new Ext.data.ArrayStore({
											fields:["display","value"],
											data:[
												[Member.getText("admin/field/type/agreement"),"agreement"],
												[Member.getText("admin/field/type/privacy"),"privacy"],
												[Member.getText("admin/field/type/email"),"email"],
												[Member.getText("admin/field/type/password"),"password"],
												[Member.getText("admin/field/type/name"),"name"],
												[Member.getText("admin/field/type/nickname"),"nickname"],
												[Member.getText("admin/field/type/birthday"),"birthday"],
												[Member.getText("admin/field/type/telephone"),"telephone"],
												[Member.getText("admin/field/type/cellphone"),"cellphone"],
												[Member.getText("admin/field/type/homepage"),"homepage"],
												[Member.getText("admin/field/type/gender"),"gender"],
												[Member.getText("admin/field/type/address"),"address"],
												[Member.getText("admin/field/type/etc"),"etc"]
											]
										}),
										editable:false,
										displayField:"display",
										valueField:"value",
										value:"etc",
										width:150,
										margin:"0 5 0 0",
										listeners:{
											change:function(form,value) {
												var form = Ext.getCmp("ModuleMemberAddFieldForm").getForm();
												if (value == "etc") {
													form.findField("name_etc").enable();
													form.findField("input").setValue("text");
													form.findField("input").enable();
												} else {
													form.findField("name_etc").disable();
													form.findField("input").setValue("system");
													form.findField("input").disable();
												}
												
												if ($.inArray(value,["email","password","name","nickname","agreement","privacy"]) == -1) {
													form.findField("is_required").setValue(false).enable();
												} else {
													form.findField("is_required").setValue(true).disable();
												}
												
												if ($.inArray(value,["agreement","privacy"]) == -1) {
													Ext.getCmp("ModuleMemberAddFieldContent").hide();
												} else {
													Ext.getCmp("ModuleMemberAddFieldContent").show();
												}
											}
										}
									}),
									new Ext.form.TextField({
										name:"name_etc",
										flex:1,
										disabled:oName ? true : false,
										emptyText:Member.getText("admin/field/form/name_etc_help")
									})
								],
								afterBodyEl:'<div class="x-form-help">'+Member.getText("admin/field/form/name_help")+'</div>'
							}),
							new Ext.form.ComboBox({
								name:"input",
								fieldLabel:Member.getText("admin/field/form/input"),
								store:new Ext.data.ArrayStore({
									fields:["display","value"],
									data:[
										[Member.getText("admin/field/input/text"),"text"],
										[Member.getText("admin/field/input/select"),"select"],
										[Member.getText("admin/field/input/radio"),"radio"],
										[Member.getText("admin/field/input/checkbox"),"checkbox"],
										[Member.getText("admin/field/input/email"),"email"],
										[Member.getText("admin/field/input/password"),"password"],
										[Member.getText("admin/field/input/date"),"date"],
										[Member.getText("admin/field/input/url"),"url"],
										[Member.getText("admin/field/input/tel"),"tel"],
										[Member.getText("admin/field/input/textarea"),"textarea"],
										[Member.getText("admin/field/input/address"),"address"]
									]
								}),
								editable:false,
								displayField:"display",
								valueField:"value",
								value:"text",
								listeners:{
									change:function(form,value) {
										var form = Ext.getCmp("ModuleMemberAddFieldForm").getForm();
										
										if ($.inArray(value,["select","radio","checkbox"]) == -1) {
											Ext.getCmp("ModuleMemberAddFieldOptions").disable().hide();
										} else {
											Ext.getCmp("ModuleMemberAddFieldOptions").enable().show();
											
											if (value == "checkbox") {
												form.findField("max").enable();
											} else {
												form.findField("max").disable();
											}
										}
										
										if (value == "system") {
											setTimeout(function(form) { form.findField("input").setRawValue(Member.getText("admin/field/input/system")) },100,form);
										}
									}
								}
							}),
							new Ext.form.FieldContainer({
								fieldLabel:Member.getText("admin/field/form/title"),
								layout:{type:"vbox",align:"stretch"},
								items:[
									new Ext.form.TextField({
										name:"title"
									}),
									Admin.languageFieldSet("ModuleMemberAddFieldTitleLanguages",Member.getText("admin/label/form/title"),"title_codes","title_languages")
								],
								afterBodyEl:'<div class="x-form-help">'+Member.getText("admin/field/form/title_help")+'</div>'
							}),
							new Ext.form.FieldContainer({
								fieldLabel:Member.getText("admin/field/form/help"),
								layout:{type:"vbox",align:"stretch"},
								items:[
									new Ext.form.TextField({
										name:"help"
									}),
									Admin.languageFieldSet("ModuleMemberAddFieldHelpLanguages",Member.getText("admin/label/form/title"),"help_codes","help_languages")
								],
								afterBodyEl:'<div class="x-form-help">'+Member.getText("admin/field/form/help_help")+'</div>'
							}),
							new Ext.form.FieldContainer({
								id:"ModuleMemberAddFieldContent",
								hidden:true,
								fieldLabel:Member.getText("admin/field/form/content"),
								layout:"hbox",
								items:[
									Admin.wysiwygField("","content")
								],
								afterBodyEl:'<div class="x-form-help">'+Member.getText("admin/field/form/content_help")+'</div>'
							}),
							new Ext.form.FieldContainer({
								id:"ModuleMemberAddFieldOptions",
								fieldLabel:Member.getText("admin/field/form/options"),
								layout:{type:"vbox",align:"stretch"},
								disabled:true,
								hidden:true,
								items:[
									new Ext.form.FieldContainer({
										layout:"hbox",
										items:[
											new Ext.form.NumberField({
												name:"max",
												value:0,
												width:80
											}),
											new Ext.form.DisplayField({
												value:"("+Member.getText("admin/label/form/max")+")",
												margin:"0 5 0 5",
												flex:1
											})
										]
									}),
									new Ext.form.FieldSet({
										id:"ModuleMemberAddFieldOptionItems",
										title:Member.getText("admin/field/form/options_item"),
										collapsible:true,
										collapsed:false,
										items:[
											new Ext.form.FieldContainer({
												layout:"hbox",
												fieldDefaults:{labelAlign:"left"},
												margin:"0 0 0 0",
												items:[
													new Ext.form.DisplayField({
														fieldLabel:Member.getText("admin/field/form/options_value"),
														width:200,
														margin:"0 5 0 0"
													}),
													new Ext.form.DisplayField({
														fieldLabel:Member.getText("admin/field/form/options_display"),
														flex:1,
														margin:"0 0 0 0"
													})
												]
											}),
											new Ext.form.FieldContainer({
												layout:"hbox",
												items:[
													new Ext.form.Hidden({
														name:"options[]",
														allowBlank:false
													}),
													new Ext.form.TextField({
														width:200,
														margin:"0 5 0 0",
														readOnly:true,
														allowBlank:false,
														listeners:{
															focus:function(form) {
																Member.field.addOption(form.ownerCt);
															}
														}
													}),
													new Ext.form.TextField({
														flex:2,
														margin:"0 5 0 0",
														readOnly:true,
														allowBlank:false,
														listeners:{
															focus:function(form) {
																Member.field.addOption(form.ownerCt);
															}
														}
													}),
													new Ext.Button({
														iconCls:"mi mi-plus",
														margin:"0 5 0 0",
														handler:function() {
															Member.field.addOption()
														}
													}),
													new Ext.Button({
														iconCls:"mi mi-minus",
														handler:function(button) {
															button.ownerCt.items.items[0].setValue("");
															button.ownerCt.items.items[1].setValue("");
															button.ownerCt.items.items[2].setValue("");
														}
													})
												]
											})
										]
									})
								],
								afterBodyEl:'<div class="x-form-help">'+Member.getText("admin/field/form/options_help")+'</div>'
							}),
							new Ext.form.Checkbox({
								fieldLabel:Member.getText("admin/field/form/is_required"),
								name:"is_required",
								checked:false,
								boxLabel:Member.getText("admin/field/form/is_required_help")
							})
						]
					})
				],
				buttons:[
					new Ext.Button({
						text:Member.getText("button/confirm"),
						handler:function() {
							Ext.getCmp("ModuleMemberAddFieldForm").getForm().submit({
								url:ENV.getProcessUrl("member","@saveSignUpField"),
								submitEmptyText:false,
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/saving"),
								success:function(form,action) {
									Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/saved"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function(button) {
										Ext.getCmp("ModuleMemberAddFieldWindow").close();
										Ext.getCmp("ModuleMemberSignUpFieldList").getStore().load();
									}});
								},
								failure:function(form,action) {
									if (action.result) {
										if (action.result.message) {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										} else {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_SAVE_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										}
									} else {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("INVALID_FORM_DATA"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									}
								}
							});
						}
					}),
					new Ext.Button({
						text:Member.getText("button/cancel"),
						handler:function() {
							Ext.getCmp("ModuleMemberAddFieldWindow").close();
						}
					})
				],
				listeners:{
					show:function() {
						if (oName) {
							Ext.getCmp("ModuleMemberAddFieldForm").getForm().load({
								url:ENV.getProcessUrl("member","@getSignUpField"),
								params:{label:label,name:oName},
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/loading"),
								success:function(form,action) {
									if (action.result.data) {
										Admin.parseLanguageFieldValue("ModuleMemberAddFieldTitleLanguages",action.result.data.title_languages);
										Admin.parseLanguageFieldValue("ModuleMemberAddFieldHelpLanguages",action.result.data.help_languages);
										
										if ($.inArray(action.result.data.input,["select","radio","checkbox"]) > -1) {
											var options = action.result.data.options;
											for (var i=0, loop=options.length;i<loop;i++) {
												if (i == 0) {
													Ext.getCmp("ModuleMemberAddFieldOptionItems").items.items[1].items.items[0].setValue(JSON.stringify(options[i]));
													Ext.getCmp("ModuleMemberAddFieldOptionItems").items.items[1].items.items[1].setValue(options[i].display);
													Ext.getCmp("ModuleMemberAddFieldOptionItems").items.items[1].items.items[2].setValue(options[i].value);
												} else {
													Ext.getCmp("ModuleMemberAddFieldOptionItems").add(
														new Ext.form.FieldContainer({
															layout:"hbox",
															items:[
																new Ext.form.Hidden({
																	name:"options[]",
																	allowBlank:false,
																	value:JSON.stringify(options[i])
																}),
																new Ext.form.TextField({
																	width:200,
																	margin:"0 5 0 0",
																	readOnly:true,
																	allowBlank:false,
																	value:options[i].value,
																	listeners:{
																		focus:function(form) {
																			Member.field.addOption(form.ownerCt);
																		}
																	}
																}),
																new Ext.form.TextField({
																	flex:2,
																	margin:"0 5 0 0",
																	readOnly:true,
																	allowBlank:false,
																	value:options[i].display,
																	listeners:{
																		focus:function(form) {
																			Member.field.addOption(form.ownerCt);
																		}
																	}
																}),
																new Ext.Button({
																	iconCls:"mi mi-minus",
																	handler:function(button) {
																		button.ownerCt.remove();
																	}
																})
															]
														})
													);
												}
											}
										}
									}
									
									Ext.getCmp("ModuleMemberAddFieldWindow").center();
								},
								failure:function(form,action) {
									if (action.result && action.result.message) {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									} else {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									}
									Ext.getCmp("ModuleMemberAddFieldWindow").close();
								}
							});
						}
					}
				}
			}).show();
		},
		addOption:function(object) {
			var object = object ? object : null;
			var data = object != null && object.items.items[0].getValue() ? JSON.parse(object.items.items[0].getValue()) : null;
			
			new Ext.Window({
				id:"ModuleMemberAddFieldOptionWindow",
				title:(data ? Member.getText("admin/field/window/option_modify") : Member.getText("admin/field/window/option_add")),
				modal:true,
				width:600,
				border:false,
				autoScroll:true,
				items:[
					new Ext.form.Panel({
						id:"ModuleMemberAddFieldOptionForm",
						border:false,
						bodyPadding:"10 10 0 10",
						fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:true},
						items:[
							new Ext.form.TextField({
								fieldLabel:Member.getText("admin/field/form/options_value"),
								name:"value",
								allowBlank:false,
								afterBodyEl:'<div class="x-form-help">'+Member.getText("admin/field/form/options_value_help")+'</div>'
							}),
							new Ext.form.FieldContainer({
								fieldLabel:Member.getText("admin/field/form/options_display"),
								layout:{type:"vbox",align:"stretch"},
								items:[
									new Ext.form.TextField({
										name:"display",
										allowBlank:false
									}),
									Admin.languageFieldSet("ModuleMemberAddFieldOptionDisplayLanguages",Member.getText("admin/field/form/options_display"),"codes","values")
								],
								afterBodyEl:'<div class="x-form-help">'+Member.getText("admin/field/form/options_display_help")+'</div>'
							})
						]
					})
				],
				buttons:[
					new Ext.Button({
						text:Member.getText("button/confirm"),
						handler:function() {
							var form = Ext.getCmp("ModuleMemberAddFieldOptionForm").getForm();
							if (form.isValid() == true) {
								var option = {};
								option.value = form.findField("value").getValue();
								option.display = form.findField("display").getValue();
								option.languages = {};
								
								for (var i=1, loop=Ext.getCmp("ModuleMemberAddFieldOptionDisplayLanguages").items.length;i<loop;i++) {
									var language = Ext.getCmp("ModuleMemberAddFieldOptionDisplayLanguages").items.items[i];
									var languageCode = language.items.items[0].items.items[0].getValue();
									var languageValue = language.items.items[1].getValue();
									
									if (languageCode.length > 0 && languageValue.length > 0) {
										option.languages[languageCode] = languageValue;
									}
								}
								
								if (object == null) {
									Ext.getCmp("ModuleMemberAddFieldOptionItems").add(
										new Ext.form.FieldContainer({
											layout:"hbox",
											items:[
												new Ext.form.Hidden({
													name:"options[]",
													allowBlank:false,
													value:JSON.stringify(option)
												}),
												new Ext.form.TextField({
													width:200,
													margin:"0 5 0 0",
													readOnly:true,
													allowBlank:false,
													value:option.value,
													listeners:{
														focus:function(form) {
															Member.field.addOption(form.ownerCt);
														}
													}
												}),
												new Ext.form.TextField({
													flex:2,
													margin:"0 5 0 0",
													readOnly:true,
													allowBlank:false,
													value:option.display,
													listeners:{
														focus:function(form) {
															Member.field.addOption(form.ownerCt);
														}
													}
												}),
												new Ext.Button({
													iconCls:"mi mi-minus",
													handler:function(button) {
														button.ownerCt.remove();
													}
												})
											]
										})
									);
								} else {
									object.items.items[0].setValue(JSON.stringify(option));
									object.items.items[1].setValue(option.value);
									object.items.items[2].setValue(option.display);
								}
								
								Ext.getCmp("ModuleMemberAddFieldOptionWindow").close();
							}
						}
					}),
					new Ext.Button({
						text:Member.getText("button/cancel"),
						handler:function() {
							Ext.getCmp("ModuleMemberAddFieldOptionWindow").close();
						}
					})
				],
				listeners:{
					show:function() {
						if (data != null) {
							var form = Ext.getCmp("ModuleMemberAddFieldOptionForm").getForm();
							form.findField("display").setValue(data.display);
							form.findField("value").setValue(data.value);
							Admin.parseLanguageFieldValue("ModuleMemberAddFieldOptionDisplayLanguages",data.languages);
							Ext.getCmp("ModuleMemberAddFieldOptionWindow").center();
						}
					}
				}
			}).show();
		},
		delete:function() {
			var selected = Ext.getCmp("ModuleMemberSignUpFieldList").getSelectionModel().getSelection();
			if (selected.length == 0) {
				Ext.Msg.show({title:Admin.getText("alert/error"),msg:"삭제할 필드를 선택하여 주십시오.",buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
				return;
			}
			
			var fields = [];
			for (var i=0, loop=selected.length;i<loop;i++) {
				fields.push({label:selected[i].get("label"),name:selected[i].get("name")});
				if (selected[i].get("name") == "email" || selected[i].get("name") == "password" || selected[i].get("name") == "nickname") {
					Ext.Msg.show({title:Admin.getText("alert/error"),msg:"기본필드(이메일, 패스워드, 닉네임)는 삭제할 수 없습니다.",buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
					return;
				}
			}
			
			Ext.Msg.show({title:Admin.getText("alert/info"),msg:"선택한 필드를 삭제하시겠습니까?",buttons:Ext.Msg.OKCANCEL,icon:Ext.Msg.QUESTION,fn:function(button) {
				if (button == "ok") {
					Ext.Msg.wait(Admin.getText("action/working"),Admin.getText("action/wait"));
					$.send(ENV.getProcessUrl("member","@deleteSignUpField"),{fields:JSON.stringify(fields)},function(result) {
						if (result.success == true) {
							Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/worked"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function() {
								Ext.getCmp("ModuleMemberSignUpFieldList").getStore().reload();
							}});
						}
					});
				}
			}});
		}
	},
	checkSignupStep:function() {
		var checked = Ext.getCmp("ModuleMemberSignupStepUsed").getSelectionModel().getSelection();
		
		while (true) {
			Ext.getCmp("ModuleMemberSignupStepUsed").getStore().sort("sort","ASC");
			var step = Ext.getCmp("ModuleMemberSignupStepUsed").getStore();
			var stepSort = {};
			for (var i=0, loop=step.getCount();i<loop;i++) {
				step.getAt(i).set("sort",i);
				stepSort[step.getAt(i).get("step")] = step.getAt(i).get("sort");
			}
			
			if (stepSort.insert === undefined) {
				step.add({step:"insert",title:Member.getText("admin/configs/signup_step/insert"),sort:step.getCount()});
				continue;
			}
			
			if (stepSort.agreement !== undefined && stepSort.agreement > stepSort.insert) {
				step.getAt(step.findExact("step","agreement")).set("sort",stepSort.insert - 0.5);
				continue;
			}
			
			if (stepSort.cert !== undefined && stepSort.cert > stepSort.insert) {
				step.getAt(step.findExact("step","cert")).set("sort",stepSort.insert - 0.5);
				continue;
			}
			
			if (stepSort.label !== undefined && stepSort.label > stepSort.insert) {
				step.getAt(step.findExact("step","label")).set("sort",stepSort.insert - 0.5);
				continue;
			}
			
			if (stepSort.verify !== undefined && stepSort.verify < stepSort.insert) {
				step.getAt(step.findExact("step","verify")).set("sort",stepSort.insert + 0.5);
				continue;
			}
			
			if (stepSort.complete !== undefined && stepSort.complete < step.getCount() - 1) {
				step.getAt(step.findExact("step","complete")).set("sort",step.getCount());
				continue;
			}
//			if (stepSort.agreement !== undefined)
			break;
		}
		
		Ext.getCmp("ModuleMemberSignupStepUsed").getSelectionModel().deselectAll();
		for (var i=0, loop=checked.length;i<loop;i++) {
			Ext.getCmp("ModuleMemberSignupStepUsed").getSelectionModel().select(Ext.getCmp("ModuleMemberSignupStepUsed").getStore().findExact("step",checked[i].get("step")),true);
		}
	}
};