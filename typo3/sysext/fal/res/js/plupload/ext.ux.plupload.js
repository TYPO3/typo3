/*!
 * Ext.ux.PluploadPanel
 * Ext.ux.PluploadButton
 * (c) http://adtim.ru/extjs/ux_plupload
 * 2010-04-28 v0.1
 */

Ext.ux.PluploadPanel = Ext.extend(Ext.Panel, {
    constructor: function(config) {

        this.autoScroll = false;
        this.bodyCssClass = 'x-plupload-body';

        this.success = [];
        this.failed = [];

        this.viewTpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<dl id="{id}">',
                    '<dt style="width: 50%">{name}</dt>',
                    '<dt style="width: 15%">{size:fileSize}</dt>',
                    '<tpl exec="this.statusValue(status, percent, msg)"></tpl><dt style="width: 35%">{this.statusText}</dt>',
                    '<div class="x-clear"></div>',
                '</dl>',
            '</tpl>',
            {   compiled: true,
                statusText: '-',
                statusTextQueued: config.statusQueuedText || 'Queued',
                statusTextUploading: config.statusUploadingText || 'Uploaded ({0}%)',
                statusTextFailed: config.statusFailedText || 'FAILED',
                statusTextDone: config.statusDoneText || 'DONE',
                statusValue: function (status, percent, msg) {
                    if ( status == 1 ) {
                        this.statusText = this.statusTextQueued;
                    }
                    else if ( status == 2 ) {
                        this.statusText = String.format( this.statusTextUploading, percent );
                    }
                    else if ( status == 4 ) {
                        this.statusText = msg || this.statusTextFailed;
                    }
                    else if ( status == 5 ) {
                        this.statusText = this.statusTextDone;
                    }
                }
            }
        );

        this.store = new Ext.data.JsonStore({
            fields: [ 'id', 'loaded', 'name', 'size', 'percent', 'status', 'msg' ],
            listeners: {
                load: this.onStoreLoad,
                remove: this.onStoreRemove,
                update: this.onStoreUpdate,
                scope: this
            }
        });

        this.pbar = new Ext.ProgressBar({ flex: 1 });

        this.bbar = new Ext.Toolbar({
            layout: 'hbox',
            style: { paddingLeft: '5px' },
            items: [
                this.pbar,
                new Ext.Toolbar.TextItem({
                    text: '<i>uploader not initialized</i>',
                    itemId: 'status'
                })
            ]
        });
    
        this.tbar = new Ext.Toolbar({
            enableOverflow: true,
              items: [
                new Ext.Button({
                    text: config.addButtonText || 'Add files',
                    itemId: 'addButton',
                    iconCls: config.addButtonCls,
                    disabled: true
                }),
                new Ext.Button({
                    text: config.uploadButtonText || 'Upload',
                    handler: this.onStart,
                    scope: this,
                    disabled: true,
                    itemId: 'start',
                    iconCls: config.uploadButtonCls
                }),
                new Ext.Button({
                    text: config.cancelButtonText || 'Cancel',
                    handler: this.onCancel,
                    scope: this,
                    disabled: true,
                    itemId: 'cancel',
                    iconCls: config.cancelButtonCls
                }),
                new Ext.SplitButton({
                    text: config.deleteButtonText || 'Remove',
                    handler: this.onDeleteSelected,
                    menu: new Ext.menu.Menu({
                        items: [
                            {text: config.deleteSelectedText || '<b>Remove selected</b>', handler: this.onDeleteSelected, scope: this },
                            '-',
                            {text: config.deleteUploadedText || 'Remove uploaded', handler: this.onDeleteUploaded, scope: this },
                            '-',
                            {text: config.deleteAllText || 'Remove all', handler: this.onDeleteAll, scope: this }
                        ]
                    }),
                    scope: this,
                    disabled: true,
                    itemId: 'delete',
                    iconCls: config.deleteButtonCls
                })
            ]
        });

        this.view = new Ext.DataView({
            store: this.store,
            tpl: this.viewTpl,
            multiSelect: true,
            overClass: 'plupload_over',
            selectedClass: 'plupload_selected',
            itemSelector: 'dl',
            emptyText: config.emptyText || '<div class="plupload_emptytext"><span>Queue is empty</span></div>',
            emptyDropText: config.emptyDropText || '<div class="plupload_emptytext"><span>Drop files here</span></div>',
            deferEmptyText: false,
            plugins: Ext.DataView.DragSelector ? new Ext.DataView.DragSelector() : ''
        });

        this.items = this.view;

        Ext.ux.PluploadPanel.superclass.constructor.apply(this, arguments);
    },
    initComponent: function() {
        Ext.ux.PluploadPanel.superclass.initComponent.apply(this, arguments);
    },
    afterRender: function() {
        Ext.ux.PluploadPanel.superclass.afterRender.apply(this, arguments);

        this.initialize_uploader();
    },
    onDeleteSelected: function () {
        Ext.each( this.view.getSelectedRecords(), 
            function (record) {
                this.remove_file( record.get( 'id' ) );
            }, this
        );
    },
    onDeleteAll: function () {
        this.store.each(
            function (record) {
                this.remove_file( record.get( 'id' ) );
            }, this
        );
    },
    onDeleteUploaded: function () {
        this.store.each(
            function (record) {
                if ( record.get( 'status' ) == 5 ) {
                    this.remove_file( record.get( 'id' ) );
                }
            }, this
        );
    },
    onCancel: function () {
        this.uploader.stop();
    },
    onStart: function () {
        this.fireEvent('beforestart', this);
        if ( this.multipart_params ) {
            this.uploader.settings.multipart_params = this.multipart_params;
        }
        this.uploader.start();
    },
    initialize_uploader: function () {
        var runtimes = 'gears,browserplus,html5';
        if ( this.flash_swf_url ) {
            runtimes = "flash," + runtimes; 
        }
        if ( this.silverlight_xap_url ) {
            runtimes = "silverlight," + runtimes; 
        }
        this.uploader = new plupload.Uploader({
            url: this.url,
            runtimes: this.runtimes || runtimes,
            browse_button: this.getTopToolbar().getComponent('addButton').getEl().dom.id,
            container: this.getTopToolbar().getEl().dom.id,
            max_file_size: this.max_file_size || '10mb',
            resize: this.resize || '',
            flash_swf_url: this.flash_swf_url || '',
            silverlight_xap_url: this.silverlight_xap_url || '',
            filters : this.filters || [],
            chunk_size: this.chunk_size,
            unique_names: this.unique_names,
            multipart: this.multipart,
            multipart_params: this.multipart_params,
            file_data_name: this.file_data_name,
            drop_element: this.body.dom.id,
            required_features: this.required_features
        });
        Ext.each(['Init', 'ChunkUploaded', 'FilesAdded', 'FilesRemoved', 'FileUploaded', 'PostInit',
                  'QueueChanged', 'Refresh', 'StateChanged', 'UploadFile', 'UploadProgress', 'Error',  ],
                 function (v) { this.uploader.bind(v, eval("this." + v), this); }, this
                );
        this.uploader.init();
    },
    remove_file: function (id) {
        var fileObj = this.uploader.getFile( id );
        if ( fileObj ) {
            this.uploader.removeFile( fileObj );
        }
        else {
            this.store.remove( this.store.getById( id ) );
        }
    },
    update_pbar: function () {
        var t = this.uploader.total;
        var speed = Ext.util.Format.fileSize(t.bytesPerSec);
        var total = this.store.data.length;
        var failed = this.failed.length; 
        var success = this.success.length;
        var sent = failed + success;
        var queued = total - success - failed;
//console.log('Sent', sent, 'Total', total, 'Success', success, 'Failed', failed, 'Queued', queued, 'Speed', speed);
        if ( total ) {
            this.progressText = '';
            var pbarText = String.format( this.progressText || '{2} of {1} uploaded ({5}/s)', sent, total, success, failed, queued, speed );
            var percent = t.percent / 100;

            // flash and html4 runtime fix (uploader.total contains outdated info about queue)
            if ( this.runtime == 'flash' || this.runtime == 'html4' ) { 
                if ( total == sent || this.store.find('status', /1|2/) == -1 ) { //find queued or uploading status
                    percent = 1  
                }
            }
            // end fix

            this.pbar.updateProgress(percent, pbarText);
        }
        else {
            this.pbar.updateProgress(0, ' ');
        }
    },
    update_store: function (v) {
        if ( !v.msg ) { v.msg = ''; }
        var data = this.store.getById(v.id);
        if ( data ) {
            data.data = v;
            data.commit();
        }
        else {
            this.store.loadData(v, true);
        }
    },
    onStoreLoad: function (store, record, operation) {
        this.update_pbar();
    },
    onStoreRemove: function (store, record, operation) {
        if ( ! store.data.length ) {
            this.getTopToolbar().getComponent('delete').setDisabled(true);
            this.getTopToolbar().getComponent('start').setDisabled(true);
            this.uploader.total.reset();
        }
        var id = record.get( 'id' );

        Ext.each( this.success, 
            function (v) {
                if ( v && v.id == id ) {
                    this.success.remove(v);
                }
            }, this
        );

        Ext.each( this.failed, 
            function (v) {
                if ( v && v.id == id ) {
                    this.failed.remove(v);
                }
            }, this
        );
        this.update_pbar();
    },
    onStoreUpdate: function (store, record, operation) {
        this.update_pbar();
    },
    Init: function(uploader, data) {
        var bbar = this.getBottomToolbar();
        var statusCmp = bbar.getComponent('status');
        this.runtime = data.runtime;
        if ( this.runtime_visible == true ) {
            statusCmp.setText(" Uploader runtime: " + this.runtime);
        }
        else {
            statusCmp.setText('');
        }

        bbar.syncSize();

        if ( this.uploader.features.dragdrop ) {
            var v = this.view;
            v.emptyText = this.emptyDropText;
            if ( v.rendered ) {
                v.refresh();
            }
        }
        this.getTopToolbar().getComponent('addButton').setDisabled(false);
    },
    ChunkUploaded: function() {
    },
    FilesAdded: function(uploader, files) {
        this.getTopToolbar().getComponent('delete').setDisabled(false);
        this.getTopToolbar().getComponent('start').setDisabled(false);
        Ext.each(files, 
            function (v) {
                this.update_store( v );
            }, this
        );
    },
    FilesRemoved: function(uploader, files) {
        Ext.each(files, 
            function (file) {
                this.store.remove( this.store.getById( file.id ) );
            }, this
        );
    },
    FileUploaded: function(uploader, file, status) {
        var response = Ext.util.JSON.decode( status.response );
        if ( response.success == true ) {
            file.server_error = 0;
            this.success.push(file);
        }
        else {
            if ( response.message ) {
                file.msg = '<span style="color: red">' + response.message + '</span>';
            }
            file.server_error = 1;
            this.failed.push(file);
        }
        this.update_store( file );
    },
    PostInit: function() {

    },
    QueueChanged: function(uploader) {
    },
    Refresh: function(uploader) {
        Ext.each(uploader.files, 
            function (v) {
                this.update_store( v );
            }, this
        );
    },
    StateChanged: function(uploader) {
        if ( uploader.state == 2 ) {
            this.fireEvent('uploadstarted', this);
            this.getTopToolbar().getComponent('cancel').setDisabled(false);
            this.getTopToolbar().getComponent('start').setDisabled(true);
        }
        else {
            this.fireEvent('uploadcomplete', this, this.success, this.failed);
            this.getTopToolbar().getComponent('cancel').setDisabled(true);
            this.getTopToolbar().getComponent('start').setDisabled(false);
        }
    },
    UploadFile: function(uploader, file) {
        this.fireEvent('uploadfile', this, uploader, file);
    },
    UploadProgress: function(uploader, file) {
        if ( file.server_error ) {
            file.status = 4;
        }
        this.update_store( file );
    },
    Error: function (uploader, data) {
        data.file.status = 4;
        if ( data.code == -600 ) {
            data.file.msg = String.format( '<span style="color: red">{0}</span>', this.statusInvalidSizeText || 'Too big' );
        }
        else if ( data.code == -700 ) {
            data.file.msg = String.format( '<span style="color: red">{0}</span>', this.statusInvalidExtensionText || 'Invalid file type' );
        }
        else {
            data.file.msg = String.format( '<span style="color: red">{2} ({0}: {1})</span>', data.code, data.details, data.message );
        }
        this.update_store( data.file );
    }
});

Ext.reg('pluploadpanel', Ext.ux.PluploadPanel);

Ext.ux.PluploadButton = Ext.extend(Ext.Button, {
    constructor: function(config) {

        this.uploadpanel = new Ext.ux.PluploadPanel(config.upload_config);

        this.window = new Ext.Window({ 
            title: config.window_title || config.text || 'Upload files',
            width: config.window_width || 640, 
            height: config.window_height || 380, 
            layout: 'fit', 
            items: this.uploadpanel, 
            closeAction: 'hide',
            listeners: {
                hide: function (window) {
                    if ( this.clearOnClose ) {
                        this.uploadpanel.onDeleteAll();
                    }
                },
                scope: this
            }
        });

        this.handler = function () { 
            this.window.show(); 
            this.uploadpanel.doLayout();
        };
        
        Ext.ux.PluploadButton.superclass.constructor.apply(this, arguments);
    }
});
Ext.reg('pluploadbutton', Ext.ux.PluploadButton);
