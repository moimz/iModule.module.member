<script>
var panel = new Ext.TabPanel({
	id:"ModuleMember",
	border:false,
	tabPosition:"bottom",
	items:[
		new Ext.grid.Panel({
			id:"ModuleMemberList",
			title:Member.getLanguage("admin/list/title"),
			border:false,
			tbar:[
				new Ext.Button({
					text:Member.getLanguage("admin/list/addMember"),
					iconCls:"fa fa-plus",
					handler:function() {
					}
				})
			],
			store:new Ext.data.JsonStore({
				proxy:{
					type:"ajax",
					simpleSortMode:true,
					url:ENV.getProcessUrl("member","@getList"),
					reader:{type:"json"}
				},
				remoteSort:true,
				sorters:[{property:"reg_date",direction:"DESC"}],
				autoLoad:true,
				pageSize:50,
				fields:["status","email","nickname","exp","point","reg_date","last_login","display_url","count","image"],
				listeners:{
					load:function(store,records,success,e) {
						if (success == false) {
							if (e.getError()) {
								Ext.Msg.show({title:Admin.getLanguage("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR})
							} else {
								Ext.Msg.show({title:Admin.getLanguage("alert/error"),msg:Admin.getLanguage("error/load"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR})
							}
						}
					}
				}
			}),
			width:"100%",
			columns:[{
				text:Member.getLanguage("admin/list/columns/status"),
				width:80,
				dataIndex:"status",
				align:"center",
				renderer:function(value,p) {
					if (value == "ACTIVE") p.style = "color:blue;";
					else if (value == "DEACTIVE") p.style = "color:red;";
					else if (value == "VERIFYING") p.style = "color:orange;";
					else p.style = "color:gray";
					
					return Member.getLanguage("admin/list/status/"+value);
				}
			},{
				text:Member.getLanguage("admin/list/columns/email"),
				minWidth:150,
				flex:1,
				dataIndex:"email"
			},{
				text:Member.getLanguage("admin/list/columns/nickname"),
				dataIndex:"nickname",
				width:140
			},{
				text:Member.getLanguage("admin/list/columns/exp"),
				dataIndex:"exp",
				sortable:true,
				width:80,
				align:"right",
				renderer:function(value) {
					return Ext.util.Format.number(value,"0,000");
				}
			},{
				text:Member.getLanguage("admin/list/columns/point"),
				dataIndex:"point",
				sortable:true,
				width:100,
				align:"right",
				renderer:function(value) {
					return Ext.util.Format.number(value,"0,000");
				}
			},{
				text:Member.getLanguage("admin/list/columns/reg_date"),
				width:130,
				dataIndex:"reg_date",
				sortable:true,
				renderer:function(value,p,record) {
					var date = moment.unix(value).format("YYYY.MM.DD, HH:mm");
					return date;
				}
			},{
				text:Member.getLanguage("admin/list/columns/last_login"),
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
					{xtype:"tbtext",text:Member.getLanguage("admin/grid_help")}
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
			title:Member.getLanguage("admin/label/title"),
			border:false,
			layout:{type:"hbox",align:"stretch"},
			style:{padding:"5px"},
			items:[
				new Ext.grid.Panel({
					id:"ModuleMemberLabelList",
					title:Member.getLanguage("admin/label/labelTitle"),
					tbar:[
						new Ext.Button({
							text:Member.getLanguage("admin/label/addLabel"),
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
							url:ENV.getProcessUrl("member","@getLabel"),
							reader:{type:"json"}
						},
						remoteSort:true,
						sorters:[{property:"title",direction:"ASC"}],
						autoLoad:true,
						pageSize:50,
						fields:["idx","title","membernum"],
						listeners:{
							load:function(store,records,success,e) {
								if (success == false) {
									if (e.getError()) {
										Ext.Msg.show({title:Admin.getLanguage("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR})
									} else {
										Ext.Msg.show({title:Admin.getLanguage("alert/error"),msg:Admin.getLanguage("error/load"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR})
									}
								}
							}
						}
					}),
					width:"100%",
					columns:[{
						text:Member.getLanguage("admin/label/columns/title"),
						minWidth:100,
						flex:1,
						dataIndex:"title",
						sortable:true,
						renderer:function(value,p,record) {
							if (record.data.idx == 0) return Member.getLanguage("admin/label/default");
							else return value;
						}
					},{
						text:Member.getLanguage("admin/label/columns/membernum"),
						dataIndex:"membernum",
						sortable:true,
						width:90,
						align:"right",
						renderer:function(value) {
							return Ext.util.Format.number(value,"0,000");
						}
					}],
					selModel:new Ext.selection.CheckboxModel(),
					bbar:[
						new Ext.Button({
							text:'<i class="fa fa-refresh"></i>',
							handler:function() {
								Ext.getCmp("ModuleMemberLabelList").getStore().reload();
							}
						}),
						"->",
						{xtype:"tbtext",text:Member.getLanguage("admin/grid_help")}
					],
					listeners:{
						itemdblclick:function(grid,record) {
							Member.addLabel(record.data.idx);
						}
					}
				})
			]
		})
	]
});
</script>