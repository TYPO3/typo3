var languageModule = {

	/**
	 * @var array
	 */
	elementStack: [],

	/**
	 * @var integer
	 */
	errorCount: 0,

	/**
	 * Initialize the language module
	 *
	 * @return void
	 */
	initialize: function() {
			// Initialize dataTable for selection and translation list
		jQuery('.selectionList, .translationList').dataTable({
			iDisplayLength: 1000,
			bPaginate: false,
			bSearch: false,
			bInfo: false,
			bFilter: false,
			// bSort: false,
			sScrollY: '390px',
			aoColumnDefs: [{
				bSortable: false,
				aTargets: ['notSortable']
			}]
		});

			// Prevent "jumping" style of the tables while generating
		jQuery('.languageSelectionListContainer').css('visibility', 'visible');
		jQuery('.translationUpdateListContainer').css('visibility', 'visible');

			// Enable event handlers
		languageModule.toggleEventHandlers('on');
	},

	/**
	 * Execute AJAX calls to fetch translation states
	 *
	 * @param mixed elements Elements to push into stack
	 * @param string type Type of the AJAX call
	 * @return void
	 */
	processElements: function(elements, type) {
			// Intialize processing within first run
		if (elements) {
			jQuery(elements).html(jQuery('#stateIconNone').html());
			languageModule.addElementsToStack(elements);
			languageModule.toggleEventHandlers('off');
		}

			// Stop processing if stack is empty
		if (languageModule.isStackEmpty()) {
			languageModule.displayInformation('flashmessage.' + type + 'Complete');
			languageModule.toggleEventHandlers('on');
			return;
		}

			// Find element to process
		var $element = languageModule.getElementFromStack();
		if ($element === null) {
			languageModule.toggleEventHandlers('on');
			return;
		}
		$element.html(jQuery('#stateIconChecking').html());

			// Execute AJAX call
		jQuery.ajax({
			url: $element.data(type + 'url'),
			dataType: 'json',
			cache: false,
			success: function(response) {
				var $icon = jQuery('#stateIcon' + parseInt(response.state));
				if ($icon === undefined || response.error) {
					$icon = jQuery('#stateIconError');
				}
				$element.html($icon.html());
				languageModule.processElements(null, type);
			},
			error: function(xhr, status, error) {
				languageModule.errorCount++;
				$element.html(jQuery('#stateIconError').html());
				if (languageModule.errorCount >= 3) {
					languageModule.displayError('flashmessage.multipleErrors');
					languageModule.toggleEventHandlers('on');
					return;
				}
				languageModule.displayError(error);
				languageModule.processElements(null, type);
			}
		});
	},

	/**
	 * Bind / unbind event handlers
	 *
	 * @param string action The value "on" or "off"
	 * @return void
	 */
	toggleEventHandlers: function(action) {
		var className = 'waiting';
		if (action === 'on') {
			jQuery('.checkItem').on('click', languageModule.checkTranslations).removeClass(className);
			jQuery('.updateItem').on('click', languageModule.updateTranslations).removeClass(className);
			jQuery('.selectionList input, .selectionList label').off().removeClass(className);
			jQuery('.selectionList input:checkbox').on('change', languageModule.submitSelectionForm);
		} else {
			jQuery('.checkItem').off().addClass(className);
			jQuery('.updateItem').off().addClass(className);
			jQuery('.selectionList input:checkbox').off();
			jQuery('.selectionList input, .selectionList label').on('click', function(event) {
				event.preventDefault();
			}).addClass(className);
		}
	},

	/**
	 * Display error flash message
	 *
	 * @param string label The label to show
	 * @return void
	 */
	displayError: function(label) {
		TYPO3.Flashmessage.display(
			TYPO3.Severity.error,
			TYPO3.l10n.localize('flashmessage.error'),
			TYPO3.l10n.localize(label),
			5
		);
	},

	/**
	 * Display information flash message
	 *
	 * @param string label The label to show
	 * @return void
	 */
	displayInformation: function(label) {
		TYPO3.Flashmessage.display(
			TYPO3.Severity.information,
			TYPO3.l10n.localize('flashmessage.information'),
			TYPO3.l10n.localize(label),
			3
		);
	},

	/**
	 * Submit language selection form
	 *
	 * @return void
	 */
	submitSelectionForm: function() {
		jQuery('form[name="languageSelectionForm"]').submit();
	},

	/**
	 * Check for new translations
	 *
	 * @return void
	 */
	checkTranslations: function() {
		languageModule.processElements('.languageState', 'check');
	},

	/**
	 * Update translations
	 *
	 * @return void
	 */
	updateTranslations: function() {
		languageModule.processElements('.languageState', 'update');
	},

	/**
	 * Fill call stack
	 *
	 * @param string elements Element identificator
	 * @return void
	 */
	addElementsToStack: function(elements) {
		jQuery(elements).each(function(i, element) {
			languageModule.elementStack.push(element);
		});
	},

	/**
	 * Get and remove first element from stack
	 *
	 * @return object The element
	 */
	getElementFromStack: function() {
		var element = languageModule.elementStack.shift();
		if (element !== undefined) {
			return jQuery(element);
		}
		return null;
	},

	/**
	 * Check if stack contains elements
	 *
	 * @return boolean False if empty
	 */
	isStackEmpty: function() {
		return languageModule.elementStack.length ? false : true;
	}

}


/**
 * Initialize when DOM is ready
 */
jQuery(document).ready(function($) {
	languageModule.initialize();
});