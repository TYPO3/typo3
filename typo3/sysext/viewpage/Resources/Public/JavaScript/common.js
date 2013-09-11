    // make sure jQuery and ExtDirect is loaded
var extReady = false;
var jQueryReady = false;

jQuery(document).ready(function($) {
    jQueryReady = true;
    LibrariesReady();
});
Ext.onReady(function () {
    extReady = true;
    LibrariesReady();
});

var LibrariesReady = function () {
    if (jQueryReady && extReady) {

        var resizableContainer = jQuery("#resizeable");
        var widthSelector = jQuery("#width");

        //save states in BE_USER->uc
        Ext.state.Manager.setProvider(new TYPO3.state.ExtDirectProvider({
            key: 'moduleData.viewpage.States',
            autoRead: false
        }));
        // load states
        if (Ext.isObject(TYPO3.settings.viewpage.States)) {
            Ext.state.Manager.getProvider().initState(TYPO3.settings.viewpage.States);
        }

         // Add event to width selector
        widthSelector.on('change', function() {
            resizableContainer.animate({
                'width': widthSelector.val()
            });
            Ext.state.Manager.set('widthSelectorValue', widthSelector.val())
        });

        // use stored states
        var storedWidth = false;
        if (typeof Ext.state.Manager.get('widthSelectorValue') != "undefined") {
            storedWidth = Ext.state.Manager.get('widthSelectorValue');
        }

        if (storedWidth) {
            // add custom selector if stored value is not there
            if (widthSelector.find( 'option[value="' + storedWidth + '"]').length === 0 ) {
                addCustomWidthSelector(storedWidth);
            }
            // select it
            widthSelector.val(storedWidth).change();
        }


        // jQuery UI Resizable plugin
        // initialize
        resizableContainer.resizable({ handles: "e, se, s" });

        // create and select custom option
        resizableContainer.on("resizestart", function(event, ui) {

            // check custom option is there and add
            if (widthSelector.find( "option.custom").length === 0) {
                addCustomWidthSelector('');
            }
            // select it
            widthSelector.find("option.custom").prop("selected", true);
       } );

        resizableContainer.on( "resize", function( event, ui ) {
            // update custom option
            var label = ui.size.width + 'px ' +  TYPO3.lang['customWidth']
            widthSelector.find(".custom").text(label).val(ui.size.width);
        });

        resizableContainer.on("resizestop", function(event, ui) {
            Ext.state.Manager.set('widthSelectorValue', widthSelector.val())
        } );


        function addCustomWidthSelector(value){
            label = value + 'px ' + TYPO3.lang['customWidth'];

            var customOption = "<option class='custom ' value='" + value + "'>" + label + "</option>";
            widthSelector.prepend(customOption);
        }
    }
};