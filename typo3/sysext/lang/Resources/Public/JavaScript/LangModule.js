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
	 * @var object
	 */
	states: {
		notAvailable: 0,
		available: 1,
		failed: 2,
		ok: 3,
		invalid: 4
	},

	/**
	 * @var jqXHR
	 */
	currentRequest: null,

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
	 * Execute AJAX call for given cell of the translation matrix
	 *
	 * @param mixed cell The cell to process
	 * @param string type Type of the AJAX call
	 * @return void
	 */
	processCell: function(cell, type) {
			// Intialize
		var $cell = jQuery(cell);
		languageModule.toggleEventHandlers('off');
		languageModule.errorCount = 0;
		$cell.html(jQuery('#stateIconChecking').html());

			// Process AJAX call
		languageModule.executeAjaxCall($cell.data(type + 'url'), function(response, error) {
			var state = 'Error';
			if (error === undefined || error === null) {
				var locale = $cell.data('locale');
				if (!response.locales[locale].error) {
					state = parseInt(response.locales[locale].state);
				}
			}
			languageModule.updateCellState($cell, state);
			languageModule.displaySuccess('flashmessage.' + type + 'Complete');
			languageModule.toggleEventHandlers('on');
		});
	},

	/**
	 * Execute AJAX calls for given rows of the translation matrix
	 *
	 * @param mixed rows Rows to process
	 * @param string type Type of the AJAX call
	 * @return void
	 */
	processRows: function(rows, type) {
			// Intialize processing within first run
		if (rows) {
			languageModule.addElementsToStack(rows);
			languageModule.toggleEventHandlers('off');
			languageModule.errorCount = 0;
		}

			// Stop processing if stack is empty
		if (languageModule.isStackEmpty()) {
			languageModule.displaySuccess('flashmessage.' + type + 'Complete');
			languageModule.toggleEventHandlers('on');
			return;
		}

			// Find row to process
		var $row = languageModule.getElementFromStack();
		if ($row === null) {
			languageModule.toggleEventHandlers('on');
			return;
		}

			// Find all cells in row
		var $cells = $row.find('.translationListCell');
		$cells.html(jQuery('#stateIconChecking').html());

			// Process AJAX call
		languageModule.executeAjaxCall($row.data(type + 'url'), function(response, error) {
			if (error !== undefined && error !== null) {
				$cells.html(jQuery('#stateIconError').html());
				if (error === 'abort') {
					languageModule.displayInformation('flashmessage.canceled');
					languageModule.toggleEventHandlers('on');
					return;
				}
			} else {
				$cells.each(function(index, element) {
					var $cell = jQuery(this);
					var state = 'Error';
					if (error === undefined || error === null) {
						var locale = $cell.data('locale');
						if (!response.locales[locale].error) {
							state = parseInt(response.locales[locale].state);
						}
					}
					languageModule.updateCellState($cell, state);
				});
			}
			languageModule.processRows(null, type);
		});
	},

	/**
	 * Update the state of a cell of the translation matrix
	 *
	 * @param mixed cell The cell to process
	 * @param string state Switch to this state
	 * @return void
	 */
	updateCellState: function(cell, state) {
		var $icon = jQuery('#stateIcon' + state);
		if ($icon === undefined) {
			$icon = jQuery('#stateIconError');
		}
		jQuery(cell)
			.removeClass('languageStateNone')
			.addClass('languageState' + state)
			.html($icon.html());
	},

	/**
	 * Execute AJAX call
	 *
	 * @param string url The url to call
	 * @param string callback Callback function
	 * @param boolean ignoreErrors Ignore errors
	 * @return void
	 */
	executeAjaxCall: function(url, callback, ignoreErrors) {
		if (url === undefined || callback === undefined) {
			return;
		}
		languageModule.currentRequest = jQuery.ajax({
			url: url,
			dataType: 'json',
			cache: false,
			success: function(response) {
				callback(response);
			},
			error: function(response, status, error) {
				if (ignoreErrors !== false && error !== 'abort') {
					languageModule.errorCount++;
					if (languageModule.errorCount >= 3) {
						languageModule.displayError('flashmessage.multipleErrors');
					} else {
						languageModule.displayError(error);
					}
				}
				callback(response, error);
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
		var fadeSpeed = 150;
		if (action === 'on') {
			jQuery('.checkItem').on('click', languageModule.checkTranslations).removeClass(className);
			jQuery('.updateItem').on('click', languageModule.updateTranslations).removeClass(className);
			jQuery('.cancelItem').off().fadeOut(fadeSpeed);
			jQuery('.selectionList input, .selectionList label').off().parent().removeClass(className);
			jQuery('.selectionList input:checkbox').on('change', languageModule.submitSelectionForm);
			jQuery('.selectionList tr, .selectionList td').removeClass(className);
			jQuery('.translationList tr, .translationList td').removeClass(className);
			jQuery('.languageState1').on('click', function() {
				languageModule.updateSingleTranslation(this);
			});
			jQuery('.languageStateNone').on('click', function() {
				languageModule.checkSingleTranslation(this);
			});
		} else {
			jQuery('.checkItem').off().addClass(className);
			jQuery('.updateItem').off().addClass(className);
			jQuery('.cancelItem').on('click', languageModule.cancelProcess).fadeIn(fadeSpeed);
			jQuery('.selectionList input:checkbox').off();
			jQuery('.selectionList input, .selectionList label').on('click', function(event) {
				event.preventDefault();
			}).parent().addClass(className);
			jQuery('.selectionList tr, .selectionList td').addClass(className);
			jQuery('.translationList tr, .translationList td').addClass(className);
			jQuery('.languageState1').off();
			jQuery('.languageStateNone').off();
		}
	},

	/**
	 * Display error flash message
	 *
	 * @param string label The label to show
	 * @return void
	 */
	displayError: function(label) {
		if (typeof label !== 'string' || label === '') {
			return;
		}
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
		if (typeof label !== 'string' || label === '') {
			return;
		}
		TYPO3.Flashmessage.display(
			TYPO3.Severity.information,
			TYPO3.l10n.localize('flashmessage.information'),
			TYPO3.l10n.localize(label),
			5
		);
	},

	/**
	 * Display success flash message
	 *
	 * @param string label The label to show
	 * @return void
	 */
	displaySuccess: function(label) {
		if (typeof label !== 'string' || label === '') {
			return;
		}
		TYPO3.Flashmessage.display(
			TYPO3.Severity.ok,
			TYPO3.l10n.localize('flashmessage.success'),
			TYPO3.l10n.localize(label),
			5
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
		languageModule.processRows('.translationListRow', 'check');
	},

	/**
	 * Check for new translation for a single element
	 *
	 * @return void
	 */
	checkSingleTranslation: function(element) {
		languageModule.processCell(element, 'check');
	},

	/**
	 * Update translations
	 *
	 * @return void
	 */
	updateTranslations: function() {
		languageModule.processRows('.translationListRow', 'update');
	},

	/**
	 * Update translation for a single element
	 *
	 * @param mixed element The element to process
	 * @return void
	 */
	updateSingleTranslation: function(element) {
		languageModule.processCell(element, 'update');
	},

	/**
	 * Cancel current process
	 *
	 * @return void
	 */
	cancelProcess: function() {
		languageModule.clearElementStack();
		if (languageModule.currentRequest) {
			languageModule.currentRequest.abort();
		}
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
	 * Clear element stack
	 *
	 * @return void
	 */
	clearElementStack: function() {
		languageModule.elementStack = [];
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