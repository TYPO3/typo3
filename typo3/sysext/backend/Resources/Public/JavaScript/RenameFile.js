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
define(["require", "exports", "TYPO3/CMS/Backend/Modal", "TYPO3/CMS/Backend/Severity", "jquery"], function (require, exports, Modal, Severity, $) {
    "use strict";
    /**
     * Module: TYPO3/CMS/Backend/RenameFile
     * Modal to pick the required conflict strategy for colliding filenames
     * @exports TYPO3/CMS/Backend/RenameFile
     */
    var RenameFile = (function () {
        function RenameFile() {
            this.initialize();
        }
        RenameFile.prototype.initialize = function () {
            $('.t3js-submit-file-rename').on('click', this.checkForDuplicate);
        };
        RenameFile.prototype.checkForDuplicate = function (e) {
            e.preventDefault();
            var form = $(e.currentTarget).closest('form');
            var fileNameField = form.find('input[name="file[rename][0][target]"]');
            var conflictModeField = form.find('input[name="file[rename][0][conflictMode]"]');
            var ajaxUrl = TYPO3.settings.ajaxUrls.file_exists;
            $.ajax({
                cache: false,
                data: {
                    fileName: fileNameField.val(),
                    fileTarget: form.find('input[name="file[rename][0][destination]"]').val(),
                },
                success: function (response) {
                    var fileExists = response !== false;
                    var originalFileName = fileNameField.data('original');
                    var newFileName = fileNameField.val();
                    if (fileExists && originalFileName !== newFileName) {
                        var description = TYPO3.lang['file_rename.exists.description']
                            .replace('{0}', originalFileName).replace('{1}', newFileName);
                        var modal = Modal.confirm(TYPO3.lang['file_rename.exists.title'], description, Severity.warning, [
                            {
                                active: true,
                                btnClass: 'btn-default',
                                name: 'cancel',
                                text: TYPO3.lang['file_rename.actions.cancel'],
                            },
                            {
                                btnClass: 'btn-primary',
                                name: 'rename',
                                text: TYPO3.lang['file_rename.actions.rename'],
                            },
                            {
                                btnClass: 'btn-default',
                                name: 'replace',
                                text: TYPO3.lang['file_rename.actions.override'],
                            },
                        ]);
                        modal.on('button.clicked', function (event) {
                            conflictModeField.val(event.target.name);
                            form.submit();
                            Modal.dismiss();
                        });
                    }
                    else {
                        form.submit();
                    }
                },
                url: ajaxUrl,
            });
        };
        ;
        return RenameFile;
    }());
    return new RenameFile();
});
