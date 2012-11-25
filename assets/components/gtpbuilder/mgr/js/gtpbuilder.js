var gtpBuilder = function(config) {
    config = config || {};
    gtpBuilder.superclass.constructor.call(this,config);
};
Ext.extend(gtpBuilder,Ext.Component,{
    page:{},window:{},grid:{},tree:{},panel:{},combo:{},config: {}
});
Ext.reg('gtpbuilder',gtpBuilder);
gtpBuilder = new gtpBuilder();


Ext.onReady(function() {
    MODx.load({ xtype: 'gtpbuilder-page-interface'});
});
 
gtpBuilder.page.Interface = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        components: [{
            xtype: 'gtpbuilder-panel-interface'
            ,renderTo: 'gtpbuilder-cmp-div'
        }]
    });
    gtpBuilder.page.Interface.superclass.constructor.call(this,config);
};
Ext.extend(gtpBuilder.page.Interface,MODx.Component);
Ext.reg('gtpbuilder-page-interface',gtpBuilder.page.Interface);



gtpBuilder.panel.Interface = function(config) {
    config = config || {};
    Ext.apply(config,{
        border: false
        ,baseCls: 'modx-formpanel'
        ,cls: 'container'
        ,items: [{
            html: '<h2>'+_('gtpbuilder')+'</h2>'
            ,border: false
            ,cls: 'modx-page-header'
        },{
            html: '<p>'+_('gtpbuilder.interface_desc')+'</p>'
            ,border: false
            ,bodyCssClass: 'panel-desc'
        },{
            html: '<div id="gtpbuilder-interface-div"></div>'
        },{
            xtype: 'gtpbuilder-interface'
        }]
    });
    gtpBuilder.panel.Interface.superclass.constructor.call(this,config);
};
Ext.extend(gtpBuilder.panel.Interface,MODx.Panel);
Ext.reg('gtpbuilder-panel-interface',gtpBuilder.panel.Interface);




gtpBuilder.window.Interface = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        title: _('gtpbuilder')
        ,url: gtpBuilder.config.connectorUrl
        ,baseParams: {
            action: 'package/build'
            ,register: 'mgr'
            ,topic: '/gtpbuilder/'
        }
        ,saveBtnText: _('gtpbuilder.build_package')
        ,listeners: {
            'beforeSubmit': {fn:this.beforeSubmit,scope:this}
            ,'success': {fn:this.onSuccess,scope:this}
            ,'failure': {fn:this.onFailure,scope:this}
        }
        ,fields: [{
            xtype: 'fieldset'
            ,title: _('gtpbuilder.repo_details')
            ,items:[{
                xtype: 'textfield'
                ,fieldLabel: _('gtpbuilder.repo_owner')
                ,name: 'owner'
                ,anchor: '98%'
                ,allowBlank: false
                ,style: {
                    float: 'left'
                }
            },{
                xtype: 'textfield'
                ,fieldLabel: _('gtpbuilder.repo')
                ,name: 'repo'
                ,anchor: '98%'
                ,allowBlank: false
            },{
                xtype: 'textfield'
                ,fieldLabel: _('gtpbuilder.repo_branch')
                ,name: 'branch'
                ,anchor: '100%'
                ,value: 'master'
                ,allowBlank: false
            }]
        },{
            xtype: 'fieldset'
            ,title: _('gtpbuilder.authentication_details')
            ,cls: 'gtpbuilder-github-auth-fieldset'
            ,defaultType: 'textfield'
            ,items: [{
                fieldLabel: _('gtpbuilder.authentication_username')
                ,name: 'auth_user'
            },{
                fieldLabel: _('gtpbuilder.authentication_password')
                ,name: 'auth_pass'
            }]
        }]
    });
    gtpBuilder.window.Interface.superclass.constructor.call(this,config);
};
Ext.extend(gtpBuilder.window.Interface,MODx.Window,{
    
    beforeSubmit: function(){
        var topic = '/gtpbuilder/';
        var register = 'mgr';
        this.console = MODx.load({
           xtype: 'modx-console'
           ,register: register
           ,topic: topic
           ,show_filename: 0
           ,listeners: {
             'shutdown': {fn:function() {
                     console.log(this.console);
                 if(this.console.isSuccess){
                     document.location.href = '?a=69';
                 }
             },scope:this}
           }
        });
        this.console.show(Ext.getBody());
    }
    
    ,onSuccess: function(){
        MODx.Ajax.request({
            url: MODx.config.connectors_url+'workspace/package/index.php'
            ,params: {
                action: 'scanlocal'
               ,register: 'mgr'
               ,topic: '/gtpbuilder/'
            }
            ,listeners: {
                   'success':{fn:function() {
                       this.console.isSuccess = true;
                       this.console.fireEvent('complete');
                   },scope:this}
               }
        });
    }
    
    ,onFailure: function(){
        console.log('fail');
        this.console.isSuccess = false;
        this.console.fireEvent('complete');
    }
    
    
});
Ext.reg('gtpbuilder-interface',gtpBuilder.window.Interface);

Ext.onReady(function(){
    gtpBuilder.interfaceWindow = MODx.load({
        xtype: 'gtpbuilder-interface'
    });
    
    gtpBuilder.interfaceWindow.show();
})