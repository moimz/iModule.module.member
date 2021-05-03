<?php
/**
 * 이 파일은 iModule 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 회원모듈 관리자패널을 구성한다.
 * 
 * @file /modules/member/admin/index.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.1.0
 * @modified 2021. 5. 3.
 */
if (defined('__IM__') == false) exit;
?>
<script>
Ext.onReady(function () { Ext.getCmp("iModuleAdminPanel").add(
	new Ext.TabPanel({
		id:"ModuleMember",
		border:false,
		tabPosition:"bottom",
		activeTab:0,
		items:[
			new Ext.grid.Panel({
				id:"ModuleMemberList",
				iconCls:"mi mi-group",
				title:Member.getText("admin/list/title"),
				border:false,
				tbar:[
					new Ext.form.ComboBox({
						id:"ModuleMemberListLabel",
						store:new Ext.data.JsonStore({
							proxy:{
								type:"ajax",
								url:ENV.getProcessUrl("member","@getLabels"),
								extraParams:{type:"all_label"},
								reader:{type:"json"}
							},
							remoteSort:false,
							fields:["idx","title"]
						}),
						width:140,
						autoLoadOnValue:true,
						editable:false,
						displayField:"title",
						valueField:"idx",
						value:"0",
						listeners:{
							change:function(form,value) {
								Ext.getCmp("ModuleMemberList").getStore().getProxy().setExtraParam("label",value);
								Ext.getCmp("ModuleMemberList").getStore().loadPage(1);
							}
						}
					}),
					Admin.searchField("ModuleMemberListKeyword",180,Member.getText("text/keyword"),function(keyword) {
						Ext.getCmp("ModuleMemberList").getStore().getProxy().setExtraParam("keyword",keyword);
						Ext.getCmp("ModuleMemberList").getStore().loadPage(1);
					}),
					"-",
					new Ext.Button({
						text:Member.getText("admin/list/add_member"),
						iconCls:"mi mi-plus",
						handler:function() {
							Member.list.add();
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
					sorters:[{property:"reg_date",direction:"DESC"}],
					autoLoad:true,
					pageSize:50,
					fields:["status","email","name","nickname","exp","point","reg_date","latest_login"],
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
					text:Member.getText("admin/list/columns/status"),
					width:80,
					dataIndex:"status",
					align:"center",
					renderer:function(value,p) {
						if (value == "ACTIVATED") p.style = "color:blue;";
						else if (value == "DEACTIVATED") p.style = "color:red;";
						else if (value == "VERIFYING") p.style = "color:orange;";
						else p.style = "color:gray";
						
						return Member.getText("status/"+value);
					}
				},{
					text:Member.getText("admin/list/columns/name"),
					dataIndex:"name",
					width:100,
					sortable:true
				},{
					text:Member.getText("admin/list/columns/nickname"),
					dataIndex:"nickname",
					width:150,
					sortable:true,
					renderer:function(value,p,record) {
						return '<i style="width:24px; height:24px; float:left; display:block; background:url('+record.data.photo+'); background-size:cover; background-repeat:no-repeat; border:1px solid #ccc; border-radius:50%; margin:-3px 5px -3px -5px;"></i>'+value;
					}
				},{
					text:Member.getText("admin/list/columns/type"),
					width:100,
					dataIndex:"type",
					renderer:function(value) {
						return Member.getText("type/"+value);
					}
				},{
					text:Member.getText("admin/list/columns/email"),
					minWidth:150,
					flex:1,
					dataIndex:"email",
					renderer:function(value) {
						if (value) return '<a href="mailto:'+value+'">'+value+'</a>';
					}
				},{
					text:Member.getText("admin/list/columns/cellphone"),
					dataIndex:"cellphone",
					width:130,
					renderer:function(value) {
						return value;
					}
				},{
					text:Member.getText("admin/list/columns/exp"),
					dataIndex:"exp",
					sortable:true,
					width:80,
					align:"right",
					renderer:function(value) {
						return Ext.util.Format.number(value,"0,000");
					}
				},{
					text:Member.getText("admin/list/columns/point"),
					dataIndex:"point",
					sortable:true,
					width:100,
					align:"right",
					renderer:function(value) {
						return Ext.util.Format.number(value,"0,000");
					}
				},{
					text:Member.getText("admin/list/columns/reg_date"),
					width:150,
					dataIndex:"reg_date",
					sortable:true,
					renderer:function(value,p,record) {
						return moment(value * 1000).locale($("html").attr("lang")).format("YYYY.MM.DD(dd) HH:mm");
					}
				},{
					text:Member.getText("admin/list/columns/latest_login"),
					width:150,
					dataIndex:"latest_login",
					sortable:true,
					renderer:function(value,p,record) {
						return moment(value * 1000).locale($("html").attr("lang")).format("YYYY.MM.DD(dd) HH:mm");
					}
				}],
				selModel:new Ext.selection.CheckboxModel(),
				bbar:new Ext.PagingToolbar({
					store:null,
					displayInfo:false,
					items:[
						"->",
						{xtype:"tbtext",text:Admin.getText("text/grid_help")}
					],
					listeners:{
						beforerender:function(tool) {
							tool.bindStore(Ext.getCmp("ModuleMemberList").getStore());
						}
					}
				}),
				listeners:{
					itemdblclick:function(grid,record) {
						Member.list.show(record.data.idx);
					},
					itemcontextmenu:function(grid,record,item,index,e) {
						var menu = new Ext.menu.Menu();
						
						menu.addTitle(record.data.name);
						
						menu.add({
							iconCls:"xi xi-form",
							text:Member.getText("admin/list/modify_member"),
							handler:function() {
								Member.list.show(record.data.idx);
							}
						});
						
						menu.add("-");
						
						menu.add({
							iconCls:"xi xi-wallet",
							text:Member.getText("admin/point/history"),
							handler:function() {
								Member.point.history(record.data.idx);
							}
						});
						
						menu.add({
							iconCls:"xi xi-piggy-bank",
							text:Member.getText("admin/point/add"),
							handler:function() {
								Member.point.add(record.data.idx);
							}
						});
						
						menu.add("-");
						
						menu.add({
							iconCls:"xi xi-user-lock",
							text:Member.getText("admin/login"),
							handler:function() {
								Member.login(record.data.idx,record.data.name);
							}
						});
						
						menu.add({
							iconCls:"mi mi-trash",
							text:Member.getText("admin/list/deactive_member"),
							handler:function() {
								Member.list.deactive();
							}
						});
						
						e.stopEvent();
						menu.showAt(e.getXY());
					}
				}
			}),
			new Ext.Panel({
				id:"ModuleMemberLabel",
				iconCls:"xi xi-form",
				title:Member.getText("admin/label/title"),
				border:false,
				layout:{type:"hbox",align:"stretch"},
				style:{padding:"5px"},
				items:[
					new Ext.Panel({
						width:450,
						border:false,
						padding:"0 5 0 0",
						layout:"fit",
						items:[
							new Ext.grid.Panel({
								id:"ModuleMemberLabelList",
								title:Member.getText("admin/label/label_title"),
								tbar:[
									new Ext.Button({
										text:Member.getText("admin/label/add"),
										iconCls:"mi mi-plus",
										handler:function() {
											Member.label.add();
										}
									}),
									new Ext.Button({
										text:Member.getText("admin/label/delete"),
										iconCls:"mi mi-trash",
										handler:function() {
											Member.label.delete();
										}
									})
								],
								store:new Ext.data.JsonStore({
									proxy:{
										type:"ajax",
										simpleSortMode:true,
										url:ENV.getProcessUrl("member","@getLabels"),
										extraParams:{type:"title"},
										reader:{type:"json"}
									},
									remoteSort:false,
									sorters:[{property:"sort",direction:"ASC"}],
									autoLoad:true,
									fields:["idx","title","membernum",{name:"allow_signup",type:"boolean"},{name:"approve_signup",type:"boolean"},{name:"is_change",type:"boolean"},{name:"is_unique",type:"boolean"},{name:"sort",type:"int"}],
									listeners:{
										load:function(store,records,success,e) {
											Ext.getCmp("ModuleMemberSignUpFieldList").disable();
											
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
									text:Member.getText("admin/label/column/title"),
									minWidth:100,
									flex:1,
									dataIndex:"title",
									sortable:true,
									renderer:function(value,p,record) {
										if (record.data.idx == "0") return "["+Member.getText("admin/label/default")+"] "+value;
										return value;
									}
								},{
									text:Member.getText("admin/label/column/membernum"),
									dataIndex:"membernum",
									sortable:true,
									width:90,
									align:"right",
									renderer:function(value) {
										return Ext.util.Format.number(value,"0,000");
									}
								},{
									text:Member.getText("admin/label/column/allow_signup"),
									dataIndex:"allow_signup",
									sortable:true,
									width:90,
									align:"center",
									renderer:function(value,p) {
										if (value == true) p.style = "color:blue;";
										else p.style = "color:red;";
										return Member.getText("admin/label/allow_signup/"+(value == true ? "TRUE" : "FALSE"));
									}
								},{
									text:Member.getText("admin/label/column/approve_signup"),
									dataIndex:"approve_signup",
									sortable:true,
									width:90,
									align:"center",
									renderer:function(value,p) {
										if (value == true) p.style = "color:red;";
										else p.style = "color:blue;";
										return Member.getText("admin/label/approve_signup/"+(value == true ? "TRUE" : "FALSE"));
									}
								}],
								selModel:new Ext.selection.RowModel(),
								bbar:[
									new Ext.Button({
										iconCls:"fa fa-caret-up",
										handler:function() {
											Admin.gridSort(Ext.getCmp("ModuleMemberLabelList"),"sort","up");
											Admin.gridSave(Ext.getCmp("ModuleMemberLabelList"),ENV.getProcessUrl("member","@saveLabelSort"),500);
										}
									}),
									new Ext.Button({
										iconCls:"fa fa-caret-down",
										handler:function() {
											Admin.gridSort(Ext.getCmp("ModuleMemberLabelList"),"sort","down");
											Admin.gridSave(Ext.getCmp("ModuleMemberLabelList"),ENV.getProcessUrl("member","@saveLabelSort"),500);
										}
									}),
									"-",
									new Ext.Button({
										iconCls:"x-tbar-loading",
										handler:function() {
											Ext.getCmp("ModuleMemberLabelList").getStore().reload();
										}
									}),
									"->",
									{xtype:"tbtext",text:Admin.getText("text/grid_help")}
								],
								listeners:{
									itemdblclick:function(grid,record) {
										Member.label.add(record.data.idx);
									},
									select:function(grid,record) {
										Ext.getCmp("ModuleMemberSignUpFieldList").getStore().getProxy().setExtraParam("label",record.data.idx);
										Ext.getCmp("ModuleMemberSignUpFieldList").getStore().reload();
									},
									itemcontextmenu:function(grid,record,item,index,e) {
										var menu = new Ext.menu.Menu();
										
										menu.addTitle(record.data.title);
										
										menu.add({
											iconCls:"xi xi-form",
											text:Member.getText("admin/label/modify"),
											handler:function() {
												Member.label.add(record.data.name);
											}
										});
										
										if (record.data.idx != 0) {
											menu.add({
												iconCls:"mi mi-trash",
												text:Member.getText("admin/label/delete"),
												handler:function() {
													Member.label.delete();
												}
											});
										}
										
										e.stopEvent();
										menu.showAt(e.getXY());
									}
								}
							})
						]
					}),
					new Ext.grid.Panel({
						id:"ModuleMemberSignUpFieldList",
						title:Member.getText("admin/label/signup_title"),
						flex:1,
						disabled:true,
						tbar:[
							new Ext.Button({
								text:Member.getText("admin/field/add"),
								iconCls:"mi mi-plus",
								handler:function() {
									Member.field.add();
								}
							}),
							new Ext.Button({
								text:Member.getText("admin/field/delete"),
								iconCls:"mi mi-trash",
								handler:function() {
									Member.field.delete();
								}
							}),
							"->",
							new Ext.toolbar.TextItem({
								id:"ModuleMemberSignUpFieldHelp",
								text:Member.getText("admin/label/label_select_first")
							})
						],
						store:new Ext.data.JsonStore({
							proxy:{
								type:"ajax",
								simpleSortMode:true,
								url:ENV.getProcessUrl("member","@getSignUpFields"),
								extraParams:{label:""},
								reader:{type:"json"}
							},
							remoteSort:false,
							sorters:[{property:"sort",direction:"ASC"}],
							autoLoad:false,
							pageSize:50,
							fields:["name","type","input","title","help",{name:"is_required",type:"boolean"},{name:"sort",type:"int"}],
							listeners:{
								beforeload:function() {
									Ext.getCmp("ModuleMemberSignUpFieldList").disable();
								},
								load:function(store,records,success,e) {
									if (success == true) {
										Ext.getCmp("ModuleMemberSignUpFieldList").enable();
										if (store.getProxy().extraParams.label == "0") {
											Ext.getCmp("ModuleMemberSignUpFieldHelp").setText(Member.getText("admin/label/default_signup_help"));
										} else {
											Ext.getCmp("ModuleMemberSignUpFieldHelp").setText(Member.getText("admin/label/signup_help"));
										}
									} else {
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
							text:Member.getText("admin/field/column/name"),
							width:180,
							dataIndex:"name",
							renderer:function(value,p,record) {
								return "["+Member.getText("admin/field/type/"+record.data.type)+"] "+value;
							}
						},{
							text:Member.getText("admin/field/column/title"),
							dataIndex:"title",
							width:150,
							renderer:function(value,p,record) {
								if (record.data.is_required == true) return "["+Member.getText("admin/label/required")+"] "+value;
								return value;
							}
						},{
							text:Member.getText("admin/field/column/help"),
							dataIndex:"help",
							minWidth:150,
							flex:1
						},{
							text:Member.getText("admin/field/column/input"),
							dataIndex:"input",
							width:120,
							renderer:function(value,p) {
								return Member.getText("admin/field/input/"+value);
							}
						}],
						selModel:new Ext.selection.CheckboxModel(),
						bbar:[
							new Ext.Button({
								iconCls:"fa fa-caret-up",
								handler:function() {
									Admin.gridSort(Ext.getCmp("ModuleMemberSignUpFieldList"),"sort","up");
									Admin.gridSave(Ext.getCmp("ModuleMemberSignUpFieldList"),ENV.getProcessUrl("member","@saveSignUpFieldSort"),500);
								}
							}),
							new Ext.Button({
								iconCls:"fa fa-caret-down",
								handler:function() {
									Admin.gridSort(Ext.getCmp("ModuleMemberSignUpFieldList"),"sort","down");
									Admin.gridSave(Ext.getCmp("ModuleMemberSignUpFieldList"),ENV.getProcessUrl("member","@saveSignUpFieldSort"),500);
								}
							}),
							"-",
							new Ext.Button({
								iconCls:"x-tbar-loading",
								handler:function() {
									Ext.getCmp("ModuleMemberSignUpFieldList").getStore().reload();
								}
							}),
							"->",
							{xtype:"tbtext",text:Admin.getText("text/grid_help")}
						],
						listeners:{
							disable:function() {
								Ext.getCmp("ModuleMemberSignUpFieldHelp").setText(Member.getText("admin/label/label_select_first"));
							},
							itemdblclick:function(grid,record) {
								Member.field.add(record.data.name);
							},
							itemcontextmenu:function(grid,record,item,index,e) {
								var menu = new Ext.menu.Menu();
								
								menu.addTitle(record.data.title);
								
								menu.add({
									iconCls:"xi xi-form",
									text:Member.getText("admin/field/modify"),
									handler:function() {
										Member.field.add(record.data.name);
									}
								});
								
								if ($.inArray(record.data.name,["email","password","nickname"]) == -1) {
									menu.add({
										iconCls:"mi mi-trash",
										text:Member.getText("admin/field/delete"),
										handler:function() {
											Member.field.delete();
										}
									});
								}
								
								e.stopEvent();
								menu.showAt(e.getXY());
							}
						}
					})
				]
			}),
			new Ext.grid.Panel({
				id:"ModuleMemberPointList",
				title:Member.getText("admin/point/title"),
				iconCls:"xi xi-wallet",
				border:false,
				tbar:[
					Admin.searchField("ModuleMemberPointKeyword",200,Member.getText("admin/point/keyword"),function(keyword) {
						Ext.getCmp("ModuleMemberPointList").getStore().getProxy().setExtraParam("keyword",keyword);
						Ext.getCmp("ModuleMemberPointList").getStore().loadPage(1);
					})
				],
				store:new Ext.data.JsonStore({
					proxy:{
						type:"ajax",
						simpleSortMode:true,
						url:ENV.getProcessUrl("member","@getPoints"),
						reader:{type:"json"}
					},
					remoteSort:true,
					sorters:[{property:"reg_date",direction:"DESC"}],
					autoLoad:true,
					pageSize:50,
					fields:[""],
					listeners:{
						load:function(store,records,success,e) {
							if (success == false) {
								if (e.getError()) {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								} else {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								}
							}
						}
					}
				}),
				columns:[{
					text:Member.getText("admin/point/columns/module"),
					width:120,
					dataIndex:"module_title"
				},{
					text:Member.getText("admin/point/columns/code"),
					width:140,
					dataIndex:"code"
				},{
					text:Member.getText("admin/point/columns/content"),
					minWidth:200,
					flex:1,
					dataIndex:"content"
				},{
					text:Member.getText("admin/point/columns/member"),
					width:140,
					dataIndex:"member",
					renderer:function(value,p,record) {
						return '<i style="display:inline-block; width:26px; height:26px; vertical-align:middle; background:url('+record.data.photo+') no-repeat 50% 50%; background-size:cover; border-radius:50%; border:1px solid #ccc; box-sizing:border-box; margin:-4px 5px -3px -5px;"></i>' + value;
					}
				},{
					text:Member.getText("admin/point/columns/reg_date"),
					width:140,
					dataIndex:"reg_date",
					renderer:function(value) {
						return moment(value).locale($("html").attr("lang")).format("YYYY.MM.DD(dd) HH:mm");
					}
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
				selModel:new Ext.selection.CheckboxModel(),
				bbar:new Ext.PagingToolbar({
					store:null,
					displayInfo:false,
					items:[
						"->",
						{xtype:"tbtext",text:Member.getText("admin/point/grid_help")}
					],
					listeners:{
						beforerender:function(tool) {
							tool.bindStore(Ext.getCmp("ModuleMemberPointList").getStore());
						}
					}
				}),
				listeners:{
					itemdblclick:function(grid,record) {
						Member.point.history(record.data.midx);
					},
					itemcontextmenu:function(grid,record,item,index,e) {
						var menu = new Ext.menu.Menu();
						
						menu.add('<div class="x-menu-title">'+record.data.member+'</div>');
						
						menu.add({
							iconCls:"xi xi-form",
							text:Member.getText("admin/point/history"),
							handler:function() {
								Member.point.history(record.data.midx);
							}
						});
						
						e.stopEvent();
						menu.showAt(e.getXY());
					}
				}
			})
		]
	})
); });
</script>