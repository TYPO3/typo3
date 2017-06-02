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
define(["require", "exports", "jquery", "TYPO3/CMS/Recordlist/LinkBrowser", "TYPO3/CMS/Backend/Modal"], function (require, exports, $, LinkBrowser, Modal) {
    "use strict";
    var RteLinkBrowser = (function () {
        function RteLinkBrowser() {
            this.plugin = null;
            this.CKEditor = null;
            this.siteUrl = '';
        }
        /**
         * @param {string} editorId Id of CKEditor
         */
        RteLinkBrowser.prototype.initialize = function (editorId) {
            var _this = this;
            var callerWindow;
            if (typeof top.TYPO3.Backend !== 'undefined' && typeof top.TYPO3.Backend.ContentContainer.get() !== 'undefined') {
                callerWindow = top.TYPO3.Backend.ContentContainer.get();
            }
            else {
                callerWindow = window.parent;
            }
            $.each(callerWindow.CKEDITOR.instances, function (name, editor) {
                if (editor.id === editorId) {
                    _this.CKEditor = editor;
                }
            });
            // siteUrl etc are added as data attributes to the body tag
            $.extend(RteLinkBrowser, $('body').data());
            $('.t3js-removeCurrentLink').on('click', function (event) {
                event.preventDefault();
                _this.CKEditor.execCommand('unlink');
                Modal.dismiss();
            });
        };
        /**
         * Store the final link
         *
         * @param {stringify} link The select element or anything else which identifies
         * the link (e.g. "page:<pageUid>" or "file:<uid>")
         */
        RteLinkBrowser.prototype.finalizeFunction = function (link) {
            var linkElement = this.CKEditor.document.createElement('a');
            var attributes = LinkBrowser.getLinkAttributeValues();
            var params = attributes.params ? attributes.params : '';
            if (attributes.target) {
                linkElement.setAttribute('target', attributes.target);
            }
            if (attributes.class) {
                linkElement.setAttribute('class', attributes.class);
            }
            if (attributes.title) {
                linkElement.setAttribute('title', attributes.title);
            }
            delete attributes.title;
            delete attributes.class;
            delete attributes.target;
            delete attributes.params;
            $.each(attributes, function (attrName, attrValue) {
                linkElement.setAttribute(attrName, attrValue);
            });
            linkElement.setAttribute('href', link + params);
            var selection = this.CKEditor.getSelection();
            if (selection && selection.getSelectedText() === '') {
                selection.selectElement(selection.getStartElement());
            }
            if (selection && selection.getSelectedText()) {
                linkElement.setText(selection.getSelectedText());
            }
            else {
                linkElement.setText(linkElement.getAttribute('href'));
            }
            this.CKEditor.insertElement(linkElement);
            Modal.dismiss();
        };
        return RteLinkBrowser;
    }());
    return new RteLinkBrowser();
});
