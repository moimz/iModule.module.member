/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 회원모듈 관리자 기능 처리
 * 
 * @file /modules/member/admin/scripts/script.js
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.1.0
 * @modified 2021. 5. 12.
 */
var Member = {
	login:function(midx,name) {
		Ext.Msg.show({title:Admin.getText("alert/info"),msg:name+"님 계정으로 로그인을 하시겠습니까?<br>로그인 계정을 변경할 경우 현 관리자권한을 잃게됩니다.",buttons:Ext.Msg.OKCANCEL,icon:Ext.Msg.QUESTION,fn:function(button) {
			if (button == "ok") {
				$.send(ENV.getProcessUrl("member","@login"),{idx:midx},function(result) {
					if (result.success == true) {
						location.replace(ENV.getUrl(false));
					}
				});
			}
		}});
	},
	/**
	 * 회원관리
	 */
	list:{
		add:function(labels,fields) {
			if (labels === undefined || fields === undefined) {
				new Ext.Window({
					id:"ModuleMemberAddWindow",
					title:Member.getText("admin/list/add_member"),
					width:400,
					modal:true,
					autoScroll:true,
					border:false,
					items:[
						new Ext.form.Panel({
							id:"ModuleMemberAddForm",
							border:false,
							bodyPadding:"10 10 5 10",
							items:[
								new Ext.form.FieldSet({
									id:"ModuleMemberAddFieldSet",
									title:Member.getText("text/select_label"),
									items:[
										new Ext.form.Checkbox({
											name:"labels[]",
											inputValue:0,
											boxLabel:Member.getText("text/default_label_title"),
											margin:"0 0 5 0",
											readOnly:true,
											checked:true
										})
									]
								})
							]
						})
					],
					buttons:[
						new Ext.Button({
							text:Admin.getText("button/confirm"),
							handler:function() {
								Ext.getCmp("ModuleMemberAddForm").getForm().submit({
									url:ENV.getProcessUrl("member","@getMemberFields"),
									submitEmptyText:false,
									waitTitle:Admin.getText("action/wait"),
									waitMsg:Admin.getText("action/saving"),
									success:function(form,action) {
										Ext.getCmp("ModuleMemberAddWindow").close();
										Member.list.add(action.result.labels,action.result.fields);
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
							text:Admin.getText("button/cancel"),
							handler:function() {
								Ext.getCmp("ModuleMemberAddWindow").close();
							}
						})
					],
					listeners:{
						show:function() {
							$.send(ENV.getProcessUrl("member","@getLabels"),function(result) {
								if (result.success == true) {
									for (var i=0, loop=result.lists.length;i<loop;i++) {
										Ext.getCmp("ModuleMemberAddFieldSet").add(
											new Ext.form.Checkbox({
												name:"labels[]",
												inputValue:result.lists[i].idx,
												boxLabel:result.lists[i].title,
												margin:"0 0 5 0"
											})
										);
									}
									Ext.getCmp("ModuleMemberAddWindow").center();
								}
							});
						}
					}
				}).show();
			} else {
				new Ext.Window({
					id:"ModuleMemberAddWindow",
					title:Member.getText("admin/list/add_member"),
					width:600,
					modal:true,
					autoScroll:true,
					border:false,
					items:[
						new Ext.form.Panel({
							id:"ModuleMemberAddForm",
							border:false,
							bodyPadding:"10 10 5 10",
							fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:false},
							items:[
								new Ext.form.Hidden({
									name:"domain"
								}),
								new Ext.form.Hidden({
									name:"language"
								}),
								new Ext.form.FieldSet({
									title:Member.getText("admin/list/form/default"),
									items:(function(fields) {
										var items = [];
										
										items.push(
											new Ext.form.ComboBox({
												name:"site",
												fieldLabel:Member.getText("admin/list/form/target_site"),
												store:new Ext.data.JsonStore({
													proxy:{
														type:"ajax",
														simpleSortMode:true,
														url:ENV.getProcessUrl("admin","@getSites"),
														extraParams:{is_sitemap:"true"},
														reader:{type:"json"}
													},
													remoteSort:false,
													sorters:[{property:"sort",direction:"ASC"}],
													autoLoad:true,
													pageSize:0,
													fields:["display","value"],
													listeners:{
														load:function(store,records,success,e) {
															if (success == true) {
																if (store.getCount() > 0) {
																	Ext.getCmp("ModuleMemberAddForm").getForm().findField("site").setValue(store.getAt(0).get("value"));
																}
															} else {
																if (e.getError()) {
																	Ext.Msg.show({title:Admin.getText("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
																} else {
																	Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
																}
															}
														}
													}
												}),
												autoLoadOnValue:true,
												editable:false,
												displayField:"display",
												valueField:"value",
												width:400,
												listeners:{
													change:function(form,value) {
														if (value) {
															var temp = value.split("@");
															form.getForm().findField("domain").setValue(temp[0]);
															form.getForm().findField("language").setValue(temp[1]);
														}
													}
												}
											})
										);
										
										for (var i=0, loop=labels.length;i<loop;i++) {
											items.push(
												new Ext.form.Hidden({
													name:"labels[]",
													value:labels[i]
												})
											);
										}
										
										for (var i=0, loop=fields.length;i<loop;i++) {
											items.push(Member.field.get(fields[i]));
										}
										
										return items;
									})(fields.defaults)
								}),
								(function(fields) {
									if (fields.length == 0) return null;
									
									return new Ext.form.FieldSet({
										title:Member.getText("admin/list/form/extra"),
										items:(function(fields) {
											var items = [];
											
											for (var i=0, loop=fields.length;i<loop;i++) {
												items.push(Member.field.get(fields[i]));
											}
											
											return items;
										})(fields)
									})
								})(fields.extras)
							]
						})
					],
					buttons:[
						new Ext.Button({
							text:Admin.getText("button/confirm"),
							handler:function() {
								Ext.getCmp("ModuleMemberAddForm").getForm().submit({
									url:ENV.getProcessUrl("member","@saveMember"),
									submitEmptyText:false,
									waitTitle:Admin.getText("action/wait"),
									waitMsg:Admin.getText("action/saving"),
									success:function(form,action) {
										Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/saved"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function() {
											Ext.getCmp("ModuleMemberList").getStore().reload();
											Ext.getCmp("ModuleMemberAddWindow").close();
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
							text:Admin.getText("button/cancel"),
							handler:function() {
								Ext.getCmp("ModuleMemberAddWindow").close();
							}
						})
					]
				}).show();
			}
		},
		/**
		 * 회원추가
		 */
		update:function(idx,fields) {
			new Ext.Window({
				id:"ModuleMemberUpdateWindow",
				title:Member.getText("admin/list/modify_member"),
				width:600,
				modal:true,
				autoScroll:true,
				border:false,
				items:[
					new Ext.form.Panel({
						id:"ModuleMemberUpdateForm",
						border:false,
						bodyPadding:"10 10 5 10",
						fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:false},
						items:[
							new Ext.form.Hidden({
								name:"idx"
							}),
							new Ext.form.FieldSet({
								title:Member.getText("admin/list/form/default"),
								items:(function(fields) {
									var items = [];
									
									for (var i=0, loop=fields.length;i<loop;i++) {
										items.push(Member.field.get(fields[i]));
									}
									
									return items;
								})(fields.defaults)
							}),
							(function(fields) {
								if (fields.length == 0) return null;
								
								return new Ext.form.FieldSet({
									title:Member.getText("admin/list/form/extra"),
									items:(function(fields) {
										var items = [];
										
										for (var i=0, loop=fields.length;i<loop;i++) {
											items.push(Member.field.get(fields[i]));
										}
										
										return items;
									})(fields)
								})
							})(fields.extras)
						]
					})
				],
				buttons:[
					new Ext.Button({
						text:Admin.getText("button/confirm"),
						handler:function() {
							Ext.getCmp("ModuleMemberUpdateForm").getForm().submit({
								url:ENV.getProcessUrl("member","@saveMember"),
								submitEmptyText:false,
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/saving"),
								success:function(form,action) {
									Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/saved"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function() {
										Ext.getCmp("ModuleMemberList").getStore().reload();
										Ext.getCmp("ModuleMemberUpdateWindow").close();
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
						text:Admin.getText("button/cancel"),
						handler:function() {
							Ext.getCmp("ModuleMemberUpdateWindow").close();
						}
					})
				],
				listeners:{
					show:function() {
						Ext.getCmp("ModuleMemberUpdateForm").getForm().load({
							url:ENV.getProcessUrl("member","@getMember"),
							params:{idx:idx},
							waitTitle:Admin.getText("action/wait"),
							waitMsg:Admin.getText("action/loading"),
							success:function(form,action) {
								var data = action.result.data;
								var fields = action.result.fields;
								for (var i=0, loop=fields.length;i<loop;i++) {
									if (data[fields[i].name] === undefined) continue;
									
									if (fields[i].type == "address" || fields[i].input == "address") {
										if (data[fields[i].name]) {
											form.findField(fields[i].name+"_zipcode").setValue(data[fields[i].name].zipcode);
											form.findField(fields[i].name+"_address1").setValue(data[fields[i].name].address1);
											form.findField(fields[i].name+"_address2").setValue(data[fields[i].name].address2);
											form.findField(fields[i].name+"_city").setValue(data[fields[i].name].city);
											form.findField(fields[i].name+"_state").setValue(data[fields[i].name].state);
										}
									}
									
									if (fields[i].input == "checkbox" && typeof data[fields[i].name] == "object") {
										var checkboxes = form.findField(fields[i].name+"[]").ownerCt.items.items;
										for (var checkbox in checkboxes) {
											if (data[fields[i].name] && $.inArray(checkboxes[checkbox].inputValue,data[fields[i].name]) > -1 || $.inArray(parseInt(checkboxes[checkbox].inputValue,10),data[fields[i].name]) > -1) {
												checkboxes[checkbox].setValue(true);
											}
										}
									}
								}
							},
							failure:function(form,action) {
								if (action.result && action.result.message) {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								} else {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								}
								Ext.getCmp("ModuleMemberUpdateWindow").close();
							}
						});
					}
				}
			}).show();
		},
		/**
		 * 회원정보보기
		 */
		show:function(idx) {
			Ext.Msg.wait(Admin.getText("action/working"),Admin.getText("action/wait"));
			$.send(ENV.getProcessUrl("member","@getMemberFields"),{midx:idx},function(result) {
				if (result.success == true) {
					Member.list.update(idx,result.fields);
					Ext.Msg.close();
				}
			});
		},
		/**
		 * 회원비활성화
		 */
		deactive:function() {
			var selected = Ext.getCmp("ModuleMemberList").getSelectionModel().getSelection();
			if (selected.length == 0) return;
			
			var idxes = [];
			for (var i=0, loop=selected.length;i<loop;i++) {
				idxes.push(selected[i].get("idx"));
			}
			
			Ext.Msg.show({title:Admin.getText("alert/info"),msg:Member.getText("admin/list/deactive_confirm"),buttons:Ext.Msg.OKCANCEL,icon:Ext.Msg.QUESTION,fn:function(button) {
				if (button == "ok") {
					Ext.Msg.wait(Admin.getText("action/working"),Admin.getText("action/wait"));
					$.send(ENV.getProcessUrl("member","@deactiveMember"),{idxes:idxes.join(",")},function(result) {
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
	 * 포인트내역
	 */
	point:{
		add:function(idx) {
			new Ext.Window({
				id:"ModuleMemberPointAddWindow",
				title:Member.getText("admin/point/add"),
				width:500,
				modal:true,
				border:false,
				layout:"fit",
				items:[
					new Ext.form.Panel({
						id:"ModuleMemberPointAddForm",
						border:false,
						bodyPadding:"10 10 0 10",
						fieldDefaults:{labelAlign:"right",labelWidth:90,anchor:"100%",allowBlank:true},
						items:[
							new Ext.form.Hidden({
								name:"idx",
								value:idx
							}),
							new Ext.form.DisplayField({
								fieldLabel:Member.getText("text/name"),
								name:"name",
								value:""
							}),
							new Ext.form.DisplayField({
								fieldLabel:Member.getText("text/point"),
								name:"current",
								value:""
							}),
							new Ext.form.FieldContainer({
								fieldLabel:Member.getText("admin/point/columns/point"),
								layout:"hbox",
								items:[
									new Ext.form.NumberField({
										name:"point",
										value:0,
										step:1,
										width:100
									}),
									new Ext.form.DisplayField({
										value:"마이너스 숫자입력시 기존적립내역에서 차감할 수 있습니다.",
										flex:1,
										fieldStyle:{textAlign:"right",color:"#666",fontSize:"11px"}
									})
								]
							}),
							new Ext.form.TextField({
								fieldLabel:"적립사유",
								name:"content"
							})
						]
					})
				],
				buttons:[
					new Ext.Button({
						text:Admin.getText("button/confirm"),
						handler:function() {
							Ext.getCmp("ModuleMemberPointAddForm").getForm().submit({
								url:ENV.getProcessUrl("member","@savePoint"),
								submitEmptyText:false,
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/saving"),
								success:function(form,action) {
									Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/saved"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function(button) {
										Ext.getCmp("ModuleMemberList").getStore().reload();
										if (Ext.getCmp("ModuleMemberPointHistoryList")) Ext.getCmp("ModuleMemberPointHistoryList").getStore().loadPage(1);
										Ext.getCmp("ModuleMemberPointAddWindow").close();
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
						text:Admin.getText("button/cancel"),
						handler:function() {
							Ext.getCmp("ModuleMemberPointAddWindow").close();
						}
					})
				],
				listeners:{
					show:function() {
						Ext.getCmp("ModuleMemberPointAddForm").getForm().load({
							url:ENV.getProcessUrl("member","@getMember"),
							params:{idx:idx},
							waitTitle:Admin.getText("action/wait"),
							waitMsg:Admin.getText("action/loading"),
							success:function(form,action) {
								form.findField("current").setValue(Ext.util.Format.number(action.result.data.point,"0,000"));
								form.findField("point").setValue(0);
							},
							failure:function(form,action) {
								Ext.getCmp("ModuleMemberPointAddWindow").close();
							}
						});
					}
				}
			}).show();
		},
		history:function(idx) {
			new Ext.Window({
				id:"ModuleMemberPointHistoryWindow",
				title:Member.getText("admin/point/history"),
				width:700,
				height:500,
				modal:true,
				border:false,
				layout:"fit",
				items:[
					new Ext.grid.Panel({
						id:"ModuleMemberPointHistoryList",
						border:false,
						tbar:[
							new Ext.Button({
								iconCls:"xi xi-piggy-bank",
								text:Member.getText("admin/point/add"),
								handler:function() {
									Member.point.add(idx);
								}
							})
						],
						store:new Ext.data.JsonStore({
							proxy:{
								type:"ajax",
								url:ENV.getProcessUrl("member","@getPoints"),
								extraParams:{idx:idx},
								reader:{type:"json"}
							},
							remoteSort:true,
							sorters:[{property:"reg_date",direction:"DESC"}],
							autoLoad:true,
							pageSize:50,
							fields:[{name:"reg_date",type:"int"},"content",{name:"point",type:"int"},{name:"accumulation",type:"int"}],
							listeners:{
								load:function(store,records,success,e) {
									if (success == false) {
										if (e.getError()) {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										} else {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("LOAD_DATA_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										}
									}
								}
							}
						}),
						columns:[{
							text:Member.getText("admin/point/columns/reg_date"),
							dataIndex:"reg_date",
							width:145,
							sortable:true,
							align:"center",
							renderer:function(value) {
								return moment(value).locale($("html").attr("lang")).format("YYYY.MM.DD(dd) HH:mm");
							}
						},{
							text:Member.getText("admin/point/columns/content"),
							dataIndex:"content",
							minWidth:200,
							flex:1
						},{
							text:Member.getText("admin/point/columns/point"),
							width:100,
							dataIndex:"point",
							align:"right",
							renderer:function(value) {
								return Ext.util.Format.number(value,"0,000");
							}
						},{
							text:Member.getText("admin/point/columns/accumulation"),
							width:100,
							dataIndex:"accumulation",
							align:"right",
							renderer:function(value) {
								return Ext.util.Format.number(value,"0,000");
							}
						}],
						bbar:new Ext.PagingToolbar({
							store:null,
							displayInfo:false,
							listeners:{
								beforerender:function(tool) {
									tool.bindStore(Ext.getCmp("ModuleMemberPointHistoryList").getStore());
								}
							}
						})
					})
				]
			}).show();
		}
	},
	/**
	 * 활동내역
	 */
	activity:{
		history:function(idx) {
			new Ext.Window({
				id:"ModuleMemberActivityHistoryWindow",
				title:Member.getText("admin/activity/history"),
				width:700,
				height:500,
				modal:true,
				border:false,
				layout:"fit",
				items:[
					new Ext.grid.Panel({
						id:"ModuleMemberActivityHistoryList",
						border:false,
						store:new Ext.data.JsonStore({
							proxy:{
								type:"ajax",
								url:ENV.getProcessUrl("member","@getActivities"),
								extraParams:{idx:idx},
								reader:{type:"json"}
							},
							remoteSort:true,
							sorters:[{property:"reg_date",direction:"DESC"}],
							autoLoad:true,
							pageSize:50,
							fields:[{name:"reg_date",type:"int"},"content",{name:"exp",type:"int"},{name:"accumulation",type:"int"},"ip"],
							listeners:{
								load:function(store,records,success,e) {
									if (success == false) {
										if (e.getError()) {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										} else {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("LOAD_DATA_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										}
									}
								}
							}
						}),
						columns:[{
							text:Member.getText("admin/activity/columns/reg_date"),
							dataIndex:"reg_date",
							width:145,
							sortable:true,
							align:"center",
							renderer:function(value) {
								return moment(value).locale($("html").attr("lang")).format("YYYY.MM.DD(dd) HH:mm");
							}
						},{
							text:Member.getText("admin/activity/columns/content"),
							dataIndex:"content",
							minWidth:200,
							flex:1
						},{
							text:Member.getText("admin/activity/columns/exp"),
							width:100,
							dataIndex:"exp",
							align:"right",
							renderer:function(value) {
								return Ext.util.Format.number(value,"0,000");
							}
						},{
							text:Member.getText("admin/activity/columns/accumulation"),
							width:100,
							dataIndex:"accumulation",
							align:"right",
							renderer:function(value) {
								return Ext.util.Format.number(value,"0,000");
							}
						},{
							text:Member.getText("admin/activity/columns/ip"),
							width:120,
							dataIndex:"ip"
						}],
						bbar:new Ext.PagingToolbar({
							store:null,
							displayInfo:false,
							listeners:{
								beforerender:function(tool) {
									tool.bindStore(Ext.getCmp("ModuleMemberActivityHistoryList").getStore());
								}
							}
						})
					})
				]
			}).show();
		}
	},
	/**
	 * 회원라벨관리
	 */
	label:{
		add:function(idx) {
			new Ext.Window({
				id:"ModuleMemberUpdateLabelWindow",
				title:(idx ? Member.getText("admin/label/window/label_modify") : Member.getText("admin/label/window/label_add")),
				modal:true,
				width:600,
				border:false,
				autoScroll:true,
				items:[
					new Ext.form.Panel({
						id:"ModuleMemberUpdateLabelForm",
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
														Ext.getCmp("ModuleMemberUpdateLabelForm").getForm().findField("title").setDisabled(checked);
													}
												}
											})
										]
									}),
									Admin.languageFieldSet("ModuleMemberUpdateLabelLanguages",Member.getText("admin/label/form/title"),"codes","titles")
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
							Ext.getCmp("ModuleMemberUpdateLabelForm").getForm().submit({
								url:ENV.getProcessUrl("member","@saveLabel"),
								submitEmptyText:false,
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/saving"),
								success:function(form,action) {
									Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/saved"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function(button) {
										Ext.getCmp("ModuleMemberUpdateLabelWindow").close();
										Ext.getCmp("ModuleMemberLabelList").getStore().load();
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
							Ext.getCmp("ModuleMemberUpdateLabelWindow").close();
						}
					})
				],
				listeners:{
					show:function() {
						if (idx !== undefined) {
							Ext.getCmp("ModuleMemberUpdateLabelForm").getForm().load({
								url:ENV.getProcessUrl("member","@getLabel"),
								params:{idx:idx},
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/loading"),
								success:function(form,action) {
									if (action.result.data) Admin.parseLanguageFieldValue("ModuleMemberUpdateLabelLanguages",action.result.data.languages);
								},
								failure:function(form,action) {
									if (action.result && action.result.message) {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									} else {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									}
									Ext.getCmp("ModuleMemberUpdateLabelWindow").close();
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
		get:function(field) {
			if (field.input == "system") {
				if (field.name == "birthday") {
					return new Ext.form.DateField({
						name:field.name,
						fieldLabel:field.title,
						format:"Y-m-d",
						allowBlank:field.is_required === false,
						afterBodyEl:field.help ? '<div class="x-form-help">' + field.help + '</div>' : null
					});
				} else if (field.name == "gender") {
					return new Ext.form.ComboBox({
						name:field.name,
						fieldLabel:field.title,
						store:new Ext.data.ArrayStore({
							fields:["display","value"],
							data:[["선택안함","NONE"],["남성","MALE"],["여성","FEMALE"]]
						}),
						displayField:"display",
						valueField:"value",
						value:"NONE",
						allowBlank:field.is_required === false,
						afterBodyEl:field.help ? '<div class="x-form-help">' + field.help + '</div>' : null
					});
				} else if (field.name == "address") {
					return new Ext.form.FieldContainer({
						fieldLabel:field.title,
						layout:{type:"vbox",align:"stretch"},
						items:[
							new Ext.form.FieldContainer({
								layout:"hbox",
								items:[
									new Ext.form.TextField({
										name:field.name+"_zipcode",
										width:100,
										emptyText:"(우편번호)",
										allowBlank:field.is_required === false
									}),
									new Ext.form.TextField({
										name:field.name+"_address1",
										style:{marginLeft:"5px"},
										flex:1,
										emptyText:"(주소)",
										allowBlank:field.is_required === false
									})
								]
							}),
							new Ext.form.TextField({
								name:field.name+"_address2",
								emptyText:"(상세주소)",
								allowBlank:true
							}),
							new Ext.form.FieldContainer({
								layout:"hbox",
								items:[
									new Ext.form.TextField({
										name:field.name+"_city",
										emptyText:"(도/시)",
										flex:1,
										allowBlank:true
									}),
									new Ext.form.TextField({
										name:field.name+"_state",
										style:{marginLeft:"5px"},
										emptyText:"(구/군)",
										flex:1,
										allowBlank:true
									})
								]
							})
						],
						afterBodyEl:field.help ? '<div class="x-form-help">' + field.help + '</div>' : null
					});
				} else {
					return new Ext.form.TextField({
						name:field.name,
						fieldLabel:field.title,
						allowBlank:field.is_required === false,
						afterBodyEl:field.help ? '<div class="x-form-help">' + field.help + '</div>' : null
					});
				}
			} else {
				if (field.input == "radio") {
					return new Ext.form.RadioGroup({
						fieldLabel:field.title,
						columns:1,
						allowBlank:field.is_required === false,
						items:(function(options) {
							var items = [];
							for (var value in options) {
								items.push(new Ext.form.Radio({
									boxLabel:options[value],
									name:field.name,
									inputValue:value
								}));
							}
							
							return items;
						})(field.options),
						afterBodyEl:field.help ? '<div class="x-form-help">' + field.help + '</div>' : null
					});
				} else if (field.input == "checkbox") {
					return new Ext.form.CheckboxGroup({
						fieldLabel:field.title,
						columns:1,
						allowBlank:field.is_required === false,
						items:(function(options) {
							var items = [];
							for (var value in options) {
								items.push(new Ext.form.Checkbox({
									boxLabel:options[value],
									name:field.name+"[]",
									inputValue:value
								}));
							}
							
							return items;
						})(field.options),
						afterBodyEl:field.help ? '<div class="x-form-help">' + field.help + '</div>' : null
					});
				} else if (field.input == "select") {
					return new Ext.form.ComboBox({
						name:field.name,
						fieldLabel:field.title,
						store:new Ext.data.ArrayStore({
							fields:["display","value"],
							data:(function(options) {
								var datas = [];
								for (var value in options) {
									datas.push([options[value],value]);
								}
								return datas;
							})(field.options)
						}),
						displayField:"display",
						valueField:"value",
						allowBlank:field.is_required === false,
						afterBodyEl:field.help ? '<div class="x-form-help">' + field.help + '</div>' : null
					});
				} else if (field.input == "address") {
					return new Ext.form.FieldContainer({
						fieldLabel:field.title,
						layout:{type:"vbox",align:"stretch"},
						items:[
							new Ext.form.FieldContainer({
								layout:"hbox",
								items:[
									new Ext.form.TextField({
										name:field.name+"_zipcode",
										width:100,
										emptyText:"(우편번호)",
										allowBlank:field.is_required === false
									}),
									new Ext.form.TextField({
										name:field.name+"_address1",
										style:{marginLeft:"5px"},
										flex:1,
										emptyText:"(주소)",
										allowBlank:field.is_required === false
									})
								]
							}),
							new Ext.form.TextField({
								name:field.name+"_address2",
								emptyText:"(상세주소)",
								allowBlank:true
							}),
							new Ext.form.FieldContainer({
								layout:"hbox",
								items:[
									new Ext.form.TextField({
										name:field.name+"_city",
										emptyText:"(도/시)",
										flex:1,
										allowBlank:true
									}),
									new Ext.form.TextField({
										name:field.name+"_state",
										style:{marginLeft:"5px"},
										emptyText:"(구/군)",
										flex:1,
										allowBlank:true
									})
								]
							})
						],
						afterBodyEl:field.help ? '<div class="x-form-help">' + field.help + '</div>' : null
					});
				} else if (field.input == "textarea") {
					return new Ext.form.TextArea({
						name:field.name,
						fieldLabel:field.title,
						allowBlank:field.is_required === false,
						afterBodyEl:field.help ? '<div class="x-form-help">' + field.help + '</div>' : null
					});
				} else {
					return new Ext.form.TextField({
						name:field.name,
						fieldLabel:field.title,
						allowBlank:field.is_required === false,
						afterBodyEl:field.help ? '<div class="x-form-help">' + field.help + '</div>' : null
					});
				}
			}
			
			return null;
		},
		add:function(oName) {
			var label = Ext.getCmp("ModuleMemberSignUpFieldList").getStore().getProxy().extraParams.label;
			
			new Ext.Window({
				id:"ModuleMemberUpdateFieldWindow",
				title:(oName ? Member.getText("admin/field/window/modify") : Member.getText("admin/field/window/add")),
				modal:true,
				width:800,
				border:false,
				layout:"fit",
				items:[
					new Ext.form.Panel({
						id:"ModuleMemberUpdateFieldForm",
						border:false,
						bodyPadding:"10 10 0 10",
						autoScroll:true,
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
											data:(function() {
												var datas = [];
												for (var type in Member.getText("admin/field/type")) {
													datas.push([Member.getText("admin/field/type/"+type),type]);
												}
												
												return datas;
											})()
										}),
										editable:false,
										displayField:"display",
										valueField:"value",
										value:"etc",
										width:150,
										margin:"0 5 0 0",
										listeners:{
											change:function(form,value) {
												var form = Ext.getCmp("ModuleMemberUpdateFieldForm").getForm();
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
													Ext.getCmp("ModuleMemberUpdateFieldContent").hide();
												} else {
													Ext.getCmp("ModuleMemberUpdateFieldContent").show();
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
									data:(function() {
										var datas = [];
										for (var type in Member.getText("admin/field/input")) {
											datas.push([Member.getText("admin/field/input/"+type),type]);
										}
										
										return datas;
									})()
								}),
								editable:false,
								displayField:"display",
								valueField:"value",
								value:"text",
								listeners:{
									change:function(form,value) {
										var form = Ext.getCmp("ModuleMemberUpdateFieldForm").getForm();
										
										if ($.inArray(value,["select","radio","checkbox"]) == -1) {
											Ext.getCmp("ModuleMemberUpdateFieldOptions").disable().hide();
										} else {
											Ext.getCmp("ModuleMemberUpdateFieldOptions").enable().show();
											
											if (value == "checkbox") {
												Ext.getCmp("ModuleMemberUpdateFieldOptionsMax").enable();
												Ext.getCmp("ModuleMemberUpdateFieldOptionsMax").show();
											} else {
												Ext.getCmp("ModuleMemberUpdateFieldOptionsMax").disable();
												Ext.getCmp("ModuleMemberUpdateFieldOptionsMax").hide();
											}
										}
										
										if (value == "system") {
											setTimeout(function(form) { form.findField("input").setRawValue(Member.getText("admin/field/input/system")); },100,form);
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
									Admin.languageFieldSet("ModuleMemberUpdateFieldTitleLanguages",Member.getText("admin/label/form/title"),"title_codes","title_languages")
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
									Admin.languageFieldSet("ModuleMemberUpdateFieldHelpLanguages",Member.getText("admin/label/form/title"),"help_codes","help_languages")
								],
								afterBodyEl:'<div class="x-form-help">'+Member.getText("admin/field/form/help_help")+'</div>'
							}),
							new Ext.form.FieldContainer({
								id:"ModuleMemberUpdateFieldContent",
								hidden:true,
								fieldLabel:Member.getText("admin/field/form/content"),
								layout:"hbox",
								items:[
									Admin.wysiwygField("","content",{allowBlank:true})
								],
								afterBodyEl:'<div class="x-form-help">'+Member.getText("admin/field/form/content_help")+'</div>'
							}),
							new Ext.form.FieldContainer({
								id:"ModuleMemberUpdateFieldOptions",
								fieldLabel:Member.getText("admin/field/form/options"),
								layout:{type:"vbox",align:"stretch"},
								disabled:true,
								hidden:true,
								items:[
									new Ext.form.FieldContainer({
										id:"ModuleMemberUpdateFieldOptionsMax",
										layout:"hbox",
										items:[
											new Ext.form.NumberField({
												name:"max",
												value:0,
												width:80
											}),
											new Ext.form.DisplayField({
												value:Member.getText("admin/field/form/max"),
												margin:"0 5 0 5",
												flex:1
											})
										]
									}),
									new Ext.form.FieldSet({
										id:"ModuleMemberUpdateFieldOptionItems",
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
							Ext.getCmp("ModuleMemberUpdateFieldForm").getForm().submit({
								url:ENV.getProcessUrl("member","@saveSignUpField"),
								submitEmptyText:false,
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/saving"),
								success:function(form,action) {
									Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/saved"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function(button) {
										Ext.getCmp("ModuleMemberUpdateFieldWindow").close();
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
							Ext.getCmp("ModuleMemberUpdateFieldWindow").close();
						}
					})
				],
				listeners:{
					show:function() {
						if (oName) {
							Ext.getCmp("ModuleMemberUpdateFieldForm").getForm().load({
								url:ENV.getProcessUrl("member","@getSignUpField"),
								params:{label:label,name:oName},
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/loading"),
								success:function(form,action) {
									if (action.result.data) {
										Admin.parseLanguageFieldValue("ModuleMemberUpdateFieldTitleLanguages",action.result.data.title_languages);
										Admin.parseLanguageFieldValue("ModuleMemberUpdateFieldHelpLanguages",action.result.data.help_languages);
										
										if ($.inArray(action.result.data.input,["select","radio","checkbox"]) > -1) {
											var options = action.result.data.options;
											for (var i=0, loop=options.length;i<loop;i++) {
												if (i == 0) {
													Ext.getCmp("ModuleMemberUpdateFieldOptionItems").items.items[1].items.items[0].setValue(JSON.stringify(options[i]));
													Ext.getCmp("ModuleMemberUpdateFieldOptionItems").items.items[1].items.items[1].setValue(options[i].value);
													Ext.getCmp("ModuleMemberUpdateFieldOptionItems").items.items[1].items.items[2].setValue(options[i].display);
												} else {
													Ext.getCmp("ModuleMemberUpdateFieldOptionItems").add(
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
																		button.ownerCt.ownerCt.remove(button.ownerCt);
																	}
																})
															]
														})
													);
												}
											}
										}
									}
									
									Ext.getCmp("ModuleMemberUpdateFieldWindow").center();
								},
								failure:function(form,action) {
									if (action.result && action.result.message) {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									} else {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									}
									Ext.getCmp("ModuleMemberUpdateFieldWindow").close();
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
				id:"ModuleMemberUpdateFieldOptionWindow",
				title:(data ? Member.getText("admin/field/window/option_modify") : Member.getText("admin/field/window/option_add")),
				modal:true,
				width:600,
				border:false,
				autoScroll:true,
				items:[
					new Ext.form.Panel({
						id:"ModuleMemberUpdateFieldOptionForm",
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
									Admin.languageFieldSet("ModuleMemberUpdateFieldOptionDisplayLanguages",Member.getText("admin/field/form/options_display"),"codes","values")
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
							var form = Ext.getCmp("ModuleMemberUpdateFieldOptionForm").getForm();
							if (form.isValid() == true) {
								var option = {};
								option.value = form.findField("value").getValue();
								option.display = form.findField("display").getValue();
								option.languages = {};
								
								for (var i=1, loop=Ext.getCmp("ModuleMemberUpdateFieldOptionDisplayLanguages").items.length;i<loop;i++) {
									var language = Ext.getCmp("ModuleMemberUpdateFieldOptionDisplayLanguages").items.items[i];
									var languageCode = language.items.items[0].items.items[0].getValue();
									var languageValue = language.items.items[1].getValue();
									
									if (languageCode.length > 0 && languageValue.length > 0) {
										option.languages[languageCode] = languageValue;
									}
								}
								
								if (object == null) {
									Ext.getCmp("ModuleMemberUpdateFieldOptionItems").add(
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
														button.ownerCt.ownerCt.remove(button.ownerCt);
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
								
								Ext.getCmp("ModuleMemberUpdateFieldOptionWindow").close();
							}
						}
					}),
					new Ext.Button({
						text:Member.getText("button/cancel"),
						handler:function() {
							Ext.getCmp("ModuleMemberUpdateFieldOptionWindow").close();
						}
					})
				],
				listeners:{
					show:function() {
						if (data != null) {
							var form = Ext.getCmp("ModuleMemberUpdateFieldOptionForm").getForm();
							form.findField("display").setValue(data.display);
							form.findField("value").setValue(data.value);
							Admin.parseLanguageFieldValue("ModuleMemberUpdateFieldOptionDisplayLanguages",data.languages);
							Ext.getCmp("ModuleMemberUpdateFieldOptionWindow").center();
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
			
			break;
		}
		
		Ext.getCmp("ModuleMemberSignupStepUsed").getSelectionModel().deselectAll();
		for (var i=0, loop=checked.length;i<loop;i++) {
			Ext.getCmp("ModuleMemberSignupStepUsed").getSelectionModel().select(Ext.getCmp("ModuleMemberSignupStepUsed").getStore().findExact("step",checked[i].get("step")),true);
		}
	},
	/**
	 * 회원검색패널을 불러온다.
	 */
	search:function(callback) {
		new Ext.Window({
			id:"ModuleMemberSearchWindow",
			title:Member.getText("admin/search/title"),
			width:700,
			height:500,
			modal:true,
			autoScroll:true,
			border:false,
			layout:"fit",
			items:[
				new Ext.grid.Panel({
					id:"ModuleMemberSearchResult",
					border:false,
					tbar:[
						new Ext.form.TextField({
							id:"ModuleMemberSearchKeyword",
							width:140,
							emptyText:Member.getText("admin/list/columns/name") + " / " + Member.getText("admin/list/columns/nickname") + " / " + Member.getText("admin/list/columns/email"),
							enableKeyEvents:true,
							flex:1,
							listeners:{
								keyup:function(form,e) {
									if (e.keyCode == 13) {
										Ext.getCmp("ModuleMemberSearchResult").getStore().getProxy().setExtraParam("keyword",Ext.getCmp("ModuleMemberSearchKeyword").getValue());
										Ext.getCmp("ModuleMemberSearchResult").getStore().loadPage(1);
									}
								}
							}
						}),
						new Ext.Button({
							iconCls:"mi mi-search",
							handler:function() {
								Ext.getCmp("ModuleMemberSearchResult").getStore().getProxy().setExtraParam("keyword",Ext.getCmp("ModuleMemberSearchKeyword").getValue());
								Ext.getCmp("ModuleMemberSearchResult").getStore().loadPage(1);
							}
						})
					],
					store:new Ext.data.JsonStore({
						proxy:{
							type:"ajax",
							simpleSortMode:true,
							url:ENV.getProcessUrl("member","@getMembers"),
							reader:{type:"json"}
						},
						remoteSort:true,
						sorters:[{property:"idx",direction:"ASC"}],
						autoLoad:false,
						pageSize:50,
						fields:["idx","name","nickname","email","type"],
						listeners:{
							load:function(store,records,success,e) {
								if (success == false) {
									if (e.getError()) {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									} else {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("LOAD_DATA_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									}
								}
							}
						}
					}),
					columns:[{
						text:Member.getText("admin/list/columns/name"),
						width:100,
						dataIndex:"name",
						sortable:true
					},{
						text:Member.getText("admin/list/columns/nickname"),
						width:100,
						dataIndex:"nickname",
						sortable:true
					},{
						text:Member.getText("admin/list/columns/type"),
						width:80,
						dataIndex:"type",
						sortable:true,
						renderer:function(value) {
							return Member.getText("type/"+value);
						}
					},{
						text:Member.getText("admin/list/columns/email"),
						minWidth:150,
						dataIndex:"email",
						flex:1,
						sortable:true
					}],
					selModel:new Ext.selection.CheckboxModel({mode:"SINGLE"}),
					bbar:new Ext.PagingToolbar({
						store:null,
						displayInfo:false,
						items:[
							"->",
							{xtype:"tbtext",text:Member.getText("admin/search/help")}
						],
						listeners:{
							beforerender:function(tool) {
								tool.bindStore(Ext.getCmp("ModuleMemberSearchResult").getStore());
							}
						}
					}),
					listeners:{
						itemdblclick:function(grid,record) {
							callback(record.data);
							Ext.getCmp("ModuleMemberSearchWindow").close();
						}
					}
				})
			],
			buttons:[
				new Ext.Button({
					text:Admin.getText("button/confirm"),
					handler:function() {
						if (Ext.getCmp("ModuleMemberSearchResult").getSelectionModel().getSelection().length == 0) {
							Ext.Msg.show({title:Admin.getText("alert/error"),msg:Member.getErrorText("NOT_SELECTED_MEMBER"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
						} else {
							callback(Ext.getCmp("ModuleMemberSearchResult").getSelectionModel().getSelection().pop().data);
							Ext.getCmp("ModuleMemberSearchWindow").close();
						}
					}
				}),
				new Ext.Button({
					text:Admin.getText("button/close"),
					handler:function() {
						Ext.getCmp("ModuleMemberSearchWindow").close();
					}
				})
			]
		}).show();
	}
};