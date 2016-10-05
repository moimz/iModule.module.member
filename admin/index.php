<script>
var panel = new Ext.TabPanel({
	id:"ModuleMember",
	border:false,
	tabPosition:"bottom",
	activeTab:1,
	items:[
		new Ext.grid.Panel({
			id:"ModuleMemberList",
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
				new Ext.form.TextField({
					id:"ModuleMemberListKeyword",
					width:140,
					emptyText:Member.getText("text/keyword"),
					enableKeyEvents:true,
					listeners:{
						keyup:function(form,e) {
							if (e.keyCode == 13) {
								Ext.getCmp("ModuleMemberList").getStore().getProxy().setExtraParam("keyword",Ext.getCmp("ModuleMemberListKeyword").getValue());
								Ext.getCmp("ModuleMemberList").getStore().loadPage(1);
							}
						}
					}
				}),
				new Ext.Button({
					id:"ModuleMemberListSearch",
					iconCls:"mi mi-search",
					handler:function() {
						Ext.getCmp("ModuleMemberList").getStore().getProxy().setExtraParam("keyword",Ext.getCmp("ModuleMemberListKeyword").getValue());
						Ext.getCmp("ModuleMemberList").getStore().loadPage(1);
					}
				}),
				"-",
				new Ext.Button({
					text:Member.getText("admin/list/add_member"),
					iconCls:"fa fa-plus",
					handler:function() {
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
				fields:["status","email","name","nickname","exp","point","reg_date","last_login"],
				listeners:{
					beforeload:function() {
						Ext.getCmp("ModuleMemberListSearch").setIconCls("mi mi-loading").disable();
					},
					load:function(store,records,success,e) {
						if (success == false) {
							if (e.getError()) {
								Ext.Msg.show({title:Admin.getText("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR})
							} else {
								Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("LOAD_DATA_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR})
							}
						}
						Ext.getCmp("ModuleMemberListSearch").setIconCls("mi mi-search").enable();
					}
				}
			}),
			width:"100%",
			columns:[{
				text:Member.getText("admin/list/columns/status"),
				width:80,
				dataIndex:"status",
				align:"center",
				renderer:function(value,p) {
					if (value == "ACTIVE") p.style = "color:blue;";
					else if (value == "DEACTIVE") p.style = "color:red;";
					else if (value == "VERIFYING") p.style = "color:orange;";
					else p.style = "color:gray";
					
					return Member.getText("admin/list/status/"+value);
				}
			},{
				text:Member.getText("admin/list/columns/email"),
				minWidth:150,
				flex:1,
				dataIndex:"email"
			},{
				text:Member.getText("admin/list/columns/nickname"),
				dataIndex:"nickname",
				width:140
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
				width:130,
				dataIndex:"reg_date",
				sortable:true,
				renderer:function(value,p,record) {
					var date = moment.unix(value).format("YYYY.MM.DD, HH:mm");
					return date;
				}
			},{
				text:Member.getText("admin/list/columns/last_login"),
				width:130,
				dataIndex:"last_login",
				sortable:true,
				renderer:function(value,p,record) {
					var date = moment.unix(value).format("YYYY.MM.DD, HH:mm");
					return date;
				}
			}],
			selModel:new Ext.selection.CheckboxModel(),
			bbar:new Ext.PagingToolbar({
				store:null,
				displayInfo:false,
				items:[
					"->",
					{xtype:"tbtext",text:Member.getText("admin/grid_help")}
				],
				listeners:{
					beforerender:function(tool) {
						tool.bindStore(Ext.getCmp("ModuleMemberList").getStore());
					}
				}
			})
		}),
		new Ext.Panel({
			id:"ModuleMemberLabel",
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
									text:Member.getText("admin/label/add_label"),
									iconCls:"fa fa-plus",
									handler:function() {
										Member.addLabel();
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
										Ext.getCmp("ModuleMemberSignUpFormList").disable();
										
										if (success == false) {
											if (e.getError()) {
												Ext.Msg.show({title:Admin.getText("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR})
											} else {
												Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("LOAD_DATA_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR})
											}
										}
									}
								}
							}),
							width:"100%",
							columns:[{
								text:Member.getText("admin/label/columns/title"),
								minWidth:100,
								flex:1,
								dataIndex:"title",
								sortable:true,
								renderer:function(value,p,record) {
									if (record.data.idx == "0") return "["+Member.getText("admin/label/default")+"] "+value;
									return value;
								}
							},{
								text:Member.getText("admin/label/columns/membernum"),
								dataIndex:"membernum",
								sortable:true,
								width:90,
								align:"right",
								renderer:function(value) {
									return Ext.util.Format.number(value,"0,000");
								}
							},{
								text:Member.getText("admin/label/columns/allow_signup"),
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
								text:Member.getText("admin/label/columns/approve_signup"),
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
									text:'<i class="fa fa-caret-up"></i>',
									handler:function() {
										Admin.gridSort(Ext.getCmp("ModuleMemberLabelList"),"sort","up");
										Admin.gridSave(Ext.getCmp("ModuleMemberLabelList"),ENV.getProcessUrl("member","@saveLabelSort"),500);
									}
								}),
								new Ext.Button({
									text:'<i class="fa fa-caret-down"></i>',
									handler:function() {
										Admin.gridSort(Ext.getCmp("ModuleMemberLabelList"),"sort","down");
										Admin.gridSave(Ext.getCmp("ModuleMemberSignUpFormList"),ENV.getProcessUrl("member","@saveSignUpFormSort"),500);
									}
								}),
								"-",
								new Ext.Button({
									text:'<i class="fa fa-refresh"></i>',
									handler:function() {
										Ext.getCmp("ModuleMemberLabelList").getStore().reload();
									}
								}),
								"->",
								{xtype:"tbtext",text:Admin.getText("text/grid_help")}
							],
							listeners:{
								itemdblclick:function(grid,record) {
									Member.addLabel(record.data.idx);
								},
								select:function(grid,record) {
									Ext.getCmp("ModuleMemberSignUpFormList").getStore().getProxy().setExtraParam("label",record.data.idx);
									Ext.getCmp("ModuleMemberSignUpFormList").getStore().reload();
								}
							}
						})
					]
				}),
				new Ext.grid.Panel({
					id:"ModuleMemberSignUpFormList",
					title:Member.getText("admin/label/signup_title"),
					flex:1,
					disabled:true,
					tbar:[
						new Ext.Button({
							text:Member.getText("admin/label/add_field"),
							iconCls:"fa fa-plus",
							handler:function() {
								Member.addField();
							}
						}),
						"->",
						new Ext.toolbar.TextItem({
							id:"ModuleMemberSignUpFormHelp",
							text:Member.getText("admin/label/label_select_first")
						})
					],
					store:new Ext.data.JsonStore({
						proxy:{
							type:"ajax",
							simpleSortMode:true,
							url:ENV.getProcessUrl("member","@getSignUpForms"),
							extraParams:{label:""},
							reader:{type:"json"}
						},
						remoteSort:false,
						sorters:[{property:"sort",direction:"ASC"}],
						autoLoad:false,
						pageSize:50,
						fields:["name","type","input","title","help",{name:"is_required",type:"boolean"}],
						listeners:{
							beforeload:function() {
								Ext.getCmp("ModuleMemberSignUpFormList").disable();
							},
							load:function(store,records,success,e) {
								if (success == true) {
									Ext.getCmp("ModuleMemberSignUpFormList").enable();
									if (store.getProxy().extraParams.label == "0") {
										Ext.getCmp("ModuleMemberSignUpFormHelp").setText(Member.getText("admin/label/default_signup_help"));
									} else {
										Ext.getCmp("ModuleMemberSignUpFormHelp").setText(Member.getText("admin/label/signup_help"));
									}
								} else {
									if (e.getError()) {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR})
									} else {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("LOAD_DATA_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR})
									}
								}
							}
						}
					}),
					width:"100%",
					columns:[{
						text:Member.getText("admin/label/columns/name"),
						width:180,
						dataIndex:"name",
						renderer:function(value,p,record) {
							return "["+Member.getText("admin/label/field_type/"+record.data.type)+"] "+value;
						}
					},{
						text:Member.getText("admin/label/columns/title"),
						dataIndex:"title",
						width:150,
						renderer:function(value,p,record) {
							if (record.data.is_required == true) return "["+Member.getText("admin/label/required")+"] "+value;
							return value;
						}
					},{
						text:Member.getText("admin/label/columns/help"),
						dataIndex:"help",
						minWidth:150,
						flex:1
					},{
						text:Member.getText("admin/label/columns/input"),
						dataIndex:"input",
						width:120,
						renderer:function(value,p) {
							return Member.getText("admin/label/field_input/"+value);
						}
					}],
					selModel:new Ext.selection.CheckboxModel(),
					bbar:[
						new Ext.Button({
							text:'<i class="fa fa-caret-up"></i>',
							handler:function() {
								Admin.gridSort(Ext.getCmp("ModuleMemberSignUpFormList"),"sort","up");
								Admin.gridSave(Ext.getCmp("ModuleMemberSignUpFormList"),ENV.getProcessUrl("member","@saveSignUpFormSort"),500);
							}
						}),
						new Ext.Button({
							text:'<i class="fa fa-caret-down"></i>',
							handler:function() {
								Admin.gridSort(Ext.getCmp("ModuleMemberSignUpFormList"),"sort","down");
								Admin.gridSave(Ext.getCmp("ModuleMemberSignUpFormList"),ENV.getProcessUrl("member","@saveSignUpFormSort"),500);
							}
						}),
						"-",
						new Ext.Button({
							text:'<i class="fa fa-refresh"></i>',
							handler:function() {
								Ext.getCmp("ModuleMemberSignUpFormList").getStore().reload();
							}
						}),
						"->",
						{xtype:"tbtext",text:Admin.getText("text/grid_help")}
					],
					listeners:{
						disable:function() {
							Ext.getCmp("ModuleMemberSignUpFormHelp").setText(Member.getText("admin/label/label_select_first"));
						},
						itemdblclick:function(grid,record) {
							Member.addField(record.data.name);
						}
					}
				})
			]
		})
	]
});
</script>