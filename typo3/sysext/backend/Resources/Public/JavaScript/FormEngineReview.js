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
define(["require", "exports", "TYPO3/CMS/Backend/FormEngine", "jquery", "bootstrap"], function (require, exports, FormEngine, $) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/FormEngineReview
     * Enables interaction with record fields that need review
     * @exports TYPO3/CMS/Backend/FormEngineReview
     */
    var FormEngineReview = (function () {
        /**
         * The constructor, set the class properties default values
         */
        function FormEngineReview() {
            var _this = this;
            /**
             * Checks if fields have failed validation. In such case, the markup is rendered and the toggle button is unlocked.
             */
            this.checkForReviewableField = function () {
                var me = _this;
                var $invalidFields = FormEngineReview.findInvalidField();
                var $toggleButton = $('.' + _this.toggleButtonClass);
                if ($invalidFields.length > 0) {
                    var $list_1 = $('<div />', { 'class': 'list-group' });
                    $invalidFields.each(function () {
                        var $field = $(this);
                        var $input = $field.find('[data-formengine-validation-rules]');
                        var inputId = $input.attr('id');
                        if (typeof inputId === 'undefined') {
                            inputId = $input.parent().children('[id]').first().attr('id');
                        }
                        $list_1.append($('<a />', {
                            href: '#',
                            'class': 'list-group-item ' + me.fieldListItemClass,
                            'data-field-id': inputId,
                        }).text($field.find(me.labelSelector).text()));
                    });
                    $toggleButton.removeClass('hidden');
                    // Bootstrap has no official API to update the content of a popover w/o destroying it
                    var $popover = $toggleButton.data('bs.popover');
                    if ($popover) {
                        $popover.options.content = $list_1.wrapAll('<div>').parent().html();
                        $popover.setContent();
                        $popover.$tip.addClass($popover.options.placement);
                    }
                }
                else {
                    $toggleButton.addClass('hidden').popover('hide');
                }
            };
            /**
             * Finds the field in the form and focuses it
             *
             * @param {Event} e
             */
            this.switchToField = function (e) {
                e.preventDefault();
                var $listItem = $(e.currentTarget);
                var referenceFieldId = $listItem.data('fieldId');
                var $referenceField = $('#' + referenceFieldId);
                // Iterate possibly nested tab panels
                $referenceField.parents('[id][role="tabpanel"]').each(function () {
                    $('[aria-controls="' + $(this).attr('id') + '"]').tab('show');
                });
                $referenceField.focus();
            };
            this.toggleButtonClass = 't3js-toggle-review-panel';
            this.fieldListItemClass = 't3js-field-item';
            this.labelSelector = '.t3js-formengine-label';
            this.initialize();
        }
        /**
         * Fetches all fields that have a failed validation
         *
         * @return {$}
         */
        FormEngineReview.findInvalidField = function () {
            return $(document).find('.tab-content .' + FormEngine.Validation.errorClass);
        };
        /**
         * Renders an invisible button to toggle the review panel into the least possible toolbar
         *
         * @param {Object} context
         */
        FormEngineReview.attachButtonToModuleHeader = function (context) {
            var $leastButtonBar = $('.t3js-module-docheader-bar-buttons').children().last().find('[role="toolbar"]');
            var $button = $('<a />', {
                'class': 'btn btn-danger btn-sm hidden ' + context.toggleButtonClass,
                href: '#',
                title: TYPO3.lang['buttons.reviewFailedValidationFields'],
            }).append($('<span />', { 'class': 'fa fa-fw fa-info' }));
            $button.popover({
                container: 'body',
                html: true,
                placement: 'bottom',
            });
            $leastButtonBar.prepend($button);
        };
        /**
         * Initialize the events
         */
        FormEngineReview.prototype.initialize = function () {
            var me = this;
            var $document = $(document);
            $(function () {
                FormEngineReview.attachButtonToModuleHeader(me);
            });
            $document.on('click', '.' + this.fieldListItemClass, this.switchToField);
            $document.on('t3-formengine-postfieldvalidation', this.checkForReviewableField);
        };
        return FormEngineReview;
    }());
    return new FormEngineReview();
});
