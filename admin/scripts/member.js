var Member = {
	addLabel:function(idx) {
		new Ext.Window({
			id:"ModuleMemberAddLabelWindow",
			title:(idx ? Member.getLanguage("admin/label/window/modify") : Member.getLanguage("admin/label/window/add")),
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
						new Ext.form.TextField({
							fieldLabel:Member.getLanguage("admin/label/form/title"),
							name:"title"
						}),
						new Ext.form.Checkbox({
							fieldLabel:Member.getLanguage("admin/label/form/allow_signup"),
							name:"allow_signup",
							checked:true,
							boxLabel:Member.getLanguage("admin/label/form/allow_signup_help"),
							afterBodyEl:(idx == 0 ? '<div class="x-form-help">'+Member.getLanguage("admin/label/form/allow_signup_help_default")+'</div>' : "")
						}),
						new Ext.form.Checkbox({
							fieldLabel:Member.getLanguage("admin/label/form/auto_active"),
							name:"auto_active",
							checked:true,
							boxLabel:Member.getLanguage("admin/label/form/auto_active_help"),
							afterBodyEl:(idx == 0 ? '<div class="x-form-help">'+Member.getLanguage("admin/label/form/auto_active_help_default")+'</div>' : "")
						}),
						new Ext.form.Checkbox({
							fieldLabel:Member.getLanguage("admin/label/form/is_change"),
							name:"is_change",
							checked:true,
							boxLabel:Member.getLanguage("admin/label/form/is_change_help"),
							afterBodyEl:(idx == 0 ? '<div class="x-form-help">'+Member.getLanguage("admin/label/form/is_change_help_default")+'</div>' : "")
						}),
						new Ext.form.Checkbox({
							fieldLabel:Member.getLanguage("admin/label/form/is_unique"),
							name:"is_unique",
							checked:true,
							boxLabel:Member.getLanguage("admin/label/form/is_unique_help"),
							afterBodyEl:(idx == 0 ? '<div class="x-form-help">'+Member.getLanguage("admin/label/form/is_unique_help_default")+'</div>' : "")
						})
					]
				})
			],
			listeners:{
				show:function() {
					if (idx !== undefined) {
						Ext.getCmp("ModuleMemberAddLabelForm").getForm().load({
							url:ENV.getProcessUrl("member","@getLabel"),
							params:{idx:idx},
							waitTitle:Admin.getLanguage("action/wait"),
							waitMsg:Admin.getLanguage("action/loading"),
							success:function(form,action) {
								
							},
							failure:function(form,action) {
								if (action.result && action.result.message) {
									Ext.Msg.show({title:Admin.getLanguage("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								} else {
									Ext.Msg.show({title:Admin.getLanguage("alert/error"),msg:Admin.getLanguage("error/load"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								}
								Ext.getCmp("ModuleMemberAddLabelWindow").close();
							}
						});
					}
				}
			}
		}).show();
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
				step.add({step:"insert",title:Member.getLanguage("admin/config/signupStep/insert"),sort:step.getCount()});
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
		console.log(stepSort);
	}
};