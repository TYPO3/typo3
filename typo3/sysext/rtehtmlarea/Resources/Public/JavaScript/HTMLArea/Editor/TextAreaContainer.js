/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/TextAreaContainer
 * The container of the textarea within the editor framework
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
	function (Util, Dom, Event) {

	/**
	 * Status bar constructor
	 *
	 * @param {Object} config
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/Editor/TextAreaContainer
	 */
	var TextAreaContainer = function (config) {
		Util.apply(this, config);
	};

	TextAreaContainer.prototype = {

		/**
		 * Render the textarea container (called by framework rendering)
		 *
		 * @param object container: the container into which to insert the status bar (that is the framework)
		 * @return void
		 */
		render: function (container) {
			this.el = document.createElement('div');
			if (this.id) {
				this.el.setAttribute('id', this.id);
			}
			if (this.cls) {
				this.el.setAttribute('class', this.cls);
			}
			this.el = container.appendChild(this.el);
			this.swallow(this.textArea);
			this.rendered = true;
		},

		/**
		 * Get the element to which the textarea container is rendered
		 */
		getEl: function () {
			return this.el;
		},

		/**
		 * editorId should be set in config
		 */
		editorId: null,

		/**
		 * Get a reference to the editor
		 */
		getEditor: function() {
			return RTEarea[this.editorId].editor;
		},

		/**
		 * Let the textarea container swallow the textarea
		 */
		swallow: function (textarea) {
			this.originalParent = textarea.parentNode;
			this.getEl().appendChild(textarea);
		},

		/**
		 * Show the texarea container
		 */
		show: function () {
			this.getEl().style.display = '';
			Event.trigger(this, 'HTMLAreaEventTextAreaContainerShow');
		},

		/**
		 * Hide the texarea container
		 */
		hide: function () {
			this.getEl().style.display = 'none';
		},

		/**
		 * Throw back the texarea (called by framework)
		 */
		onBeforeDestroy: function() {
			this.originalParent.appendChild(this.textArea);
			Event.off(this);
			this.el = null;
		}
	};

	return TextAreaContainer;

});
