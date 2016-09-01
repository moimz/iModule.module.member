<script>
var config = new Ext.form.Panel({
	id:"ModuleConfigForm",
	border:false,
	bodyPadding:"0 10 0 10",
	fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:true},
	items:[
		new Ext.form.FieldSet({
			title:Member.getLanguage("admin/config/form/signup"),
			items:[
				new Ext.form.TextField({
					name:"signupText",
					fieldLabel:Member.getLanguage("admin/config/form/signupText")
				}),
				new Ext.form.FieldContainer({
					fieldLabel:Member.getLanguage("admin/config/form/signupStep"),
					layout:"hbox",
					items:[
						new Ext.form.Hidden({
							name:"signupStep",
							listeners:{
								change:function(form,value) {
									if (Ext.getCmp("ModuleMemberSignupStepUsed").getStore().getCount() == 0) {
										var step = value.split(",");
										for (var i=0, loop=step.length;i<loop;i++) {
											Ext.getCmp("ModuleMemberSignupStepAvailable").getStore().removeAt(Ext.getCmp("ModuleMemberSignupStepAvailable").getStore().findExact("step",step[i]));
											Ext.getCmp("ModuleMemberSignupStepUsed").getStore().add({step:step[i],title:Member.getLanguage("admin/config/signupStep/"+step[i]),sort:i});
										}
									}
								}
							}
						}),
						new Ext.grid.Panel({
							id:"ModuleMemberSignupStepAvailable",
							title:Member.getLanguage("admin/config/form/signupStepAvailable"),
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
									text:Member.getLanguage("admin/config/form/addSignupStep"),
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
										Ext.getCmp("ModuleConfigForm").getForm().findField("signupStep").setValue(step.join(","));
									}
								})
							],
							store:new Ext.data.ArrayStore({
								fields:["step","title","sort"],
								sorters:[{property:"sort",direction:"ASC"}],
								data:[["agreement",Member.getLanguage("admin/config/signupStep/agreement"),0],["label",Member.getLanguage("admin/config/signupStep/label"),1],["cert",Member.getLanguage("admin/config/signupStep/cert"),2],["insert",Member.getLanguage("admin/config/signupStep/insert"),3],["verify",Member.getLanguage("admin/config/signupStep/verify"),4],["complete",Member.getLanguage("admin/config/signupStep/complete"),5]]
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
							title:Member.getLanguage("admin/config/form/signupStepUsed"),
							border:true,
							hideHeaders:true,
							margin:"0 0 0 5",
							tbar:[
								new Ext.Button({
									text:Member.getLanguage("admin/config/form/deleteSignupStep"),
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
										Ext.getCmp("ModuleConfigForm").getForm().findField("signupStep").setValue(step.join(","));
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
										Ext.getCmp("ModuleConfigForm").getForm().findField("signupStep").setValue(step.join(","));
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
										
										Ext.getCmp("ModuleConfigForm").getForm().findField("signupStep").setValue(step.join(","));
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