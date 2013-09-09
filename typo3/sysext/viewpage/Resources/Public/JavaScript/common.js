// IIFE for faster access to $ and save $ use
(function ($, Ext) {

	// make sure $ and ExtDirect is loaded
	var extReady = false;
	var $Ready = false;

	$(document).ready(function ($) {
		$Ready = true;
		librariesReady();
	});
	Ext.onReady(function () {
		extReady = true;
		librariesReady();
	});

	var librariesReady = function () {
		if (!$Ready || !extReady) {
			return
		}

		var resizableContainer = $('#resizeable');
		var widthSelector = $('#width');

		//save states in BE_USER->uc
		Ext.state.Manager.setProvider(new TYPO3.state.ExtDirectProvider({
			key: 'moduleData.web_view.States',
			autoRead: false
		}));
		// load states
		if (Ext.isObject(TYPO3.settings.web_view.States)) {
			Ext.state.Manager.getProvider().initState(TYPO3.settings.web_view.States);
		}

		// Add event to width selector
		widthSelector.on('change', function () {
			var jsonObject = JSON.parse(widthSelector.val());
			var height = jsonObject['height'] ? jsonObject['height'] : '100%';
			resizableContainer.animate({
				'width': jsonObject['width'],
				'height': height
			});
			Ext.state.Manager.set('widthSelectorValue', widthSelector.val());
		});

		// use stored states
		// restore custom selector
		var storedCustomWidth = Ext.state.Manager.get('widthSelectorCustomValue', false);
		if (storedCustomWidth) {
			// add custom selector if stored value is not there
			if (widthSelector.find('option[value="' + storedCustomWidth + '"]').length === 0) {
				addCustomWidthOption(storedCustomWidth);
			}
		}

		// select stored value
		var storedWidth = Ext.state.Manager.get('widthSelectorValue', false);
		if (storedWidth) {
			// select it
			widthSelector.val(storedWidth).change();
		}


		// $ UI Resizable plugin
		// initialize
		resizableContainer.resizable({
			handles: 'e, se, s'
		});

		// create and select custom option
		resizableContainer.on('resizestart', function (event, ui) {

			// check custom option is there and add
			if (widthSelector.find('#custom').length === 0) {
				addCustomWidthOption('{}');
			}
			// select it
			widthSelector.find('#custom').prop('selected', true);

			// add iframe overlay to prevent loosing the mouse focus to the iframe while resizing fast
			$(this).append('<div id="iframeCover" style="zindex:99;position:absolute;width:100%;top:0px;left:0px;height:100%;"></div>');

		});

		resizableContainer.on('resize', function (event, ui) {
			// update custom option
			var value = JSON.stringify({
				width: ui.size.width,
				height: ui.size.height
			});
			var label = getOptionLabel(value);
			widthSelector.find('#custom').text(label).val(value);
		});

		resizableContainer.on('resizestop', function (event, ui) {
			Ext.state.Manager.set('widthSelectorCustomValue', widthSelector.val());
			// TODO: remove setTimeout workaround after bug #51998 in
			// TYPO3\CMS\Backend\InterfaceState\ExtDirect\DataProvider->setState() was fixed
			setTimeout(function () {
					Ext.state.Manager.set('widthSelectorValue', widthSelector.val())
				},
				1000);

			// remove iframe overlay
			$('#iframeCover').remove();
		});

		function addCustomWidthOption(value) {
			label = getOptionLabel(value);

			var customOption = "<option id='custom' value='" + value + "'>" + label + "</option>";
			widthSelector.prepend(customOption);
		}

		function getOptionLabel(data) {
			var jsonObject = JSON.parse(data);
			var height = jsonObject['height'] ? ' × ' + jsonObject['height'] + 'px ' : '';
			return jsonObject['width'] + 'px ' + height + TYPO3.lang['customWidth'];
		}
	};
}(jQuery, Ext));