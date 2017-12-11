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
define(["require", "exports", "TYPO3/CMS/Backend/Modal", "TYPO3/CMS/Backend/Severity"], function (require, exports, Modal, Severity) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/Wizard/NewContentElement
     * NewContentElement JavaScript
     * @exports TYPO3/CMS/Backend/Wizard/NewContentElement
     */
    var NewContentElement = (function () {
        function NewContentElement() {
        }
        NewContentElement.wizard = function (url, title) {
            Modal.advanced({
                callback: function (currentModal) {
                    currentModal.find('.t3js-modal-body').addClass('t3-new-content-element-wizard-window');
                },
                content: url,
                severity: Severity.notice,
                size: Modal.sizes.medium,
                title: title,
                type: Modal.types.ajax,
            });
        };
        return NewContentElement;
    }());
    return NewContentElement;
});
