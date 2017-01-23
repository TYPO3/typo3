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
 * Module: TYPO3/CMS/Version/ContextMenuActions
 *
 * JavaScript to handle Version actions from context menu
 * @exports TYPO3/CMS/Version/ContextMenuActions
 */
define(function () {
    'use strict';

    /**
     * @exports TYPO3/CMS/Version/ContextMenuActions
     */
    var ContextMenuActions = {};

    ContextMenuActions.openVersionModule = function (table, uid) {
        var $anchorElement = $(this);
        var actionUrl = $anchorElement.data('actionUrl');
        top.TYPO3.Backend.ContentContainer.setUrl(
            actionUrl +
            '&redirect=' + top.rawurlencode(top.list_frame.document.location.pathname + top.list_frame.document.location.search)
        );
    };

    return ContextMenuActions;
});
