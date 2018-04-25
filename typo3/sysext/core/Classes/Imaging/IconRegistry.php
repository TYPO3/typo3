<?php
namespace TYPO3\CMS\Core\Imaging;

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

use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Class IconRegistry, which makes it possible to register custom icons
 * from within an extension.
 */
class IconRegistry implements SingletonInterface
{
    /**
     * @var bool
     */
    protected $fullInitialized = false;

    /**
     * @var bool
     */
    protected $tcaInitialized = false;

    /**
     * @var bool
     */
    protected $flagsInitialized = false;

    /**
     * @var bool
     */
    protected $moduleIconsInitialized = false;

    /**
     * Registered icons
     *
     * @var array
     */
    protected $icons = [

        /**
         * Important Information:
         *
         * Icons are maintained in an external repository, if new icons are needed
         * please request them at: https://github.com/wmdbsystems/T3.Icons/issues
         */

        // Actions
        'actions-add' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-add.svg'
            ]
        ],
        'actions-check' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-check.svg'
            ]
        ],
        'actions-close' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-close.svg'
            ]
        ],
        'actions-cloud' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-cloud.svg'
            ]
        ],
        'actions-database-export' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-database-export.svg'
            ]
        ],
        'actions-database-import' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-database-import.svg'
            ]
        ],
        'actions-database-reload' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-database-reload.svg'
            ]
        ],
        'actions-database' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-database.svg'
            ]
        ],
        'actions-delete' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-delete.svg'
            ]
        ],
        'actions-document-close' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-close.svg'
            ]
        ],
        'actions-document-duplicates-select' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-duplicates-select.svg'
            ]
        ],
        'actions-document-edit-access' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-edit-access.svg'
            ]
        ],
        'actions-document-export-csv' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-export-csv.svg'
            ]
        ],
        'actions-document-export-t3d' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-export-t3d.svg'
            ]
        ],
        'actions-document-history-open' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-history-open.svg'
            ]
        ],
        'actions-document-import-t3d' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-import-t3d.svg'
            ]
        ],
        'actions-document-info' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-info.svg'
            ]
        ],
        'actions-document-localize' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-localize.svg'
            ]
        ],
        'actions-document-move' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-move.svg'
            ]
        ],
        'actions-document-new' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-new.svg'
            ]
        ],
        'actions-document-open-read-only' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-open-read-only.svg'
            ]
        ],
        'actions-document-open' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-open.svg'
            ]
        ],
        'actions-document-paste-after' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-paste-after.svg'
            ]
        ],
        'actions-document-paste-before' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-paste-before.svg'
            ]
        ],
        'actions-document-paste-into' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-paste-into.svg'
            ]
        ],
        'actions-document-paste' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-paste.svg'
            ]
        ],
        'actions-document-save-cleartranslationcache' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save-cleartranslationcache.svg'
            ]
        ],
        'actions-document-save-close' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save-close.svg'
            ]
        ],
        'actions-document-save-new' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save-new.svg'
            ]
        ],
        'actions-document-save-translation' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save-translation.svg'
            ]
        ],
        'actions-document-save-view' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save-view.svg'
            ]
        ],
        'actions-document-save' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save.svg'
            ]
        ],
        'actions-document-select' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-select.svg'
            ]
        ],
        'actions-document-synchronize' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-synchronize.svg'
            ]
        ],
        'actions-document-view' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-view.svg'
            ]
        ],
        'actions-document' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document.svg'
            ]
        ],
        'actions-download' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-download.svg'
            ]
        ],
        'actions-duplicates' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-duplicates.svg'
            ]
        ],
        'actions-edit-add' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-add.svg'
            ]
        ],
        'actions-edit-copy-release' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-copy-release.svg'
            ]
        ],
        'actions-edit-copy' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-copy.svg'
            ]
        ],
        'actions-edit-cut-release' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-cut-release.svg'
            ]
        ],
        'actions-edit-cut' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-cut.svg'
            ]
        ],
        'actions-edit-delete' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-delete.svg'
            ]
        ],
        'actions-edit-download' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-download.svg'
            ]
        ],
        'actions-edit-hide' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-hide.svg'
            ]
        ],
        'actions-edit-insert-default' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-insert-default.svg'
            ]
        ],
        'actions-edit-localize-status-high' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-localize-status-high.svg'
            ]
        ],
        'actions-edit-localize-status-low' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-localize-status-low.svg'
            ]
        ],
        'actions-edit-merge-localization' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-merge-localization.svg'
            ]
        ],
        'actions-edit-pick-date' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-pick-date.svg'
            ]
        ],
        'actions-edit-rename' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-rename.svg'
            ]
        ],
        'actions-edit-replace' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-replace.svg'
            ]
        ],
        'actions-edit-restore' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-restore.svg'
            ]
        ],
        'actions-edit-undelete-edit' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-undelete-edit.svg'
            ]
        ],
        'actions-edit-undo' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-undo.svg'
            ]
        ],
        'actions-edit-unhide' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-unhide.svg'
            ]
        ],
        'actions-edit-upload' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-upload.svg'
            ]
        ],
        'actions-file-csv' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-file-csv.svg'
            ]
        ],
        'actions-file-html' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-file-html.svg'
            ]
        ],
        'actions-file-openoffice' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-file-openoffice.svg'
            ]
        ],
        'actions-file-pdf' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-file-pdf.svg'
            ]
        ],
        'actions-file' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-file.svg'
            ]
        ],
        'actions-filter' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-filter.svg'
            ]
        ],
        'actions-folder' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-folder.svg'
            ]
        ],
        'actions-input-clear' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-input-clear.svg'
            ]
        ],
        'actions-insert-record' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-insert-record.svg'
            ]
        ],
        'actions-insert-reference' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-insert-reference.svg'
            ]
        ],
        'actions-localize' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-localize.svg'
            ]
        ],
        'actions-lock' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-lock.svg'
            ]
        ],
        'actions-logout' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-logout.svg'
            ]
        ],
        'actions-markstate' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-markstate.svg'
            ]
        ],
        'actions-menu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-menu.svg'
            ]
        ],
        'actions-merge' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-merge.svg'
            ]
        ],
        'actions-message-error-close' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-message-error-close.svg'
            ]
        ],
        'actions-message-information-close' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-message-information-close.svg'
            ]
        ],
        'actions-message-notice-close' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-message-notice-close.svg'
            ]
        ],
        'actions-message-ok-close' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-message-ok-close.svg'
            ]
        ],
        'actions-message-warning-close' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-message-warning-close.svg'
            ]
        ],
        'actions-move-down' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-down.svg'
            ]
        ],
        'actions-move-left' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-left.svg'
            ]
        ],
        'actions-move-move' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-move.svg'
            ]
        ],
        'actions-move-right' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-right.svg'
            ]
        ],
        'actions-move-to-bottom' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-to-bottom.svg'
            ]
        ],
        'actions-move-to-top' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-to-top.svg'
            ]
        ],
        'actions-move-up' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-up.svg'
            ]
        ],
        'actions-move' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move.svg'
            ]
        ],
        'actions-online-media-add' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-online-media-add.svg'
            ]
        ],
        'actions-open' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-open.svg'
            ]
        ],
        'actions-page-move' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-page-move.svg'
            ]
        ],
        'actions-page-new' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-page-new.svg'
            ]
        ],
        'actions-page-open' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-page-open.svg'
            ]
        ],
        'actions-pagetree-collapse' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-pagetree-collapse.svg'
            ]
        ],
        'actions-pagetree-expand' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-pagetree-expand.svg'
            ]
        ],
        'actions-pagetree-mountroot' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-pagetree-mountroot.svg'
            ]
        ],
        'actions-pagetree' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-pagetree.svg'
            ]
        ],
        'actions-preview' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-preview.svg'
            ]
        ],
        'actions-refresh' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-refresh.svg'
            ]
        ],
        'actions-remove' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-remove.svg'
            ]
        ],
        'actions-rename' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-rename.svg'
            ]
        ],
        'actions-replace' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-replace.svg'
            ]
        ],
        'actions-save' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-save.svg'
            ]
        ],
        'actions-search' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-search.svg'
            ]
        ],
        'actions-selection-delete' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-selection-delete.svg'
            ]
        ],
        'actions-swap' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-swap.svg'
            ]
        ],
        'actions-synchronize' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-synchronize.svg'
            ]
        ],
        'actions-system-backend-user-emulate' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-backend-user-emulate.svg'
            ]
        ],
        'actions-system-backend-user-switch' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-backend-user-switch.svg'
            ]
        ],
        'actions-system-cache-clear-impact-high' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-cache-clear-impact-high.svg'
            ]
        ],
        'actions-system-cache-clear-impact-low' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-cache-clear-impact-low.svg'
            ]
        ],
        'actions-system-cache-clear-impact-medium' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-cache-clear-impact-medium.svg'
            ]
        ],
        'actions-system-cache-clear-rte' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-cache-clear-rte.svg'
            ]
        ],
        'actions-system-cache-clear' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-cache-clear.svg'
            ]
        ],
        'actions-system-extension-configure' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-configure.svg'
            ]
        ],
        'actions-system-extension-documentation' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-documentation.svg'
            ]
        ],
        'actions-system-extension-download' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-download.svg'
            ]
        ],
        'actions-system-extension-import' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-import.svg'
            ]
        ],
        'actions-system-extension-install' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-install.svg'
            ]
        ],
        'actions-system-extension-sqldump' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-sqldump.svg'
            ]
        ],
        'actions-system-extension-uninstall' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-uninstall.svg'
            ]
        ],
        'actions-system-extension-update-disable' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-update-disable.svg'
            ]
        ],
        'actions-system-extension-update' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-update.svg'
            ]
        ],
        'actions-system-help-open' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-help-open.svg'
            ]
        ],
        'actions-system-list-open' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-list-open.svg'
            ]
        ],
        'actions-system-options-view' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-options-view.svg'
            ]
        ],
        'actions-system-pagemodule-open' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-pagemodule-open.svg'
            ]
        ],
        'actions-system-refresh' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-refresh.svg'
            ]
        ],
        'actions-system-shortcut-active' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-shortcut-active.svg'
            ]
        ],
        'actions-system-shortcut-new' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-shortcut-new.svg'
            ]
        ],
        'actions-system-tree-search-open' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-tree-search-open.svg'
            ]
        ],
        'actions-system-typoscript-documentation-open' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-typoscript-documentation-open.svg'
            ]
        ],
        'actions-system-typoscript-documentation' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-typoscript-documentation.svg'
            ]
        ],
        'actions-template-new' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-template-new.svg'
            ]
        ],
        'actions-unlock' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-unlock.svg'
            ]
        ],
        'actions-unmarkstate' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-unmarkstate.svg'
            ]
        ],
        'actions-upload' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-upload.svg'
            ]
        ],
        'actions-version-document-remove' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-document-remove.svg'
            ]
        ],
        'actions-version-page-open' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-page-open.svg'
            ]
        ],
        'actions-version-swap-version' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-swap-version.svg'
            ]
        ],
        'actions-version-swap-workspace' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-swap-workspace.svg'
            ]
        ],
        'actions-version-workspace-preview' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-workspace-preview.svg'
            ]
        ],
        'actions-version-workspace-sendtoprevstage' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-workspace-sendtoprevstage.svg'
            ]
        ],
        'actions-version-workspace-sendtostage' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-workspace-sendtostage.svg'
            ]
        ],
        'actions-version-workspaces-preview-link' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-workspaces-preview-link.svg'
            ]
        ],
        'actions-view-go-back' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-go-back.svg'
            ]
        ],
        'actions-view-go-down' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-go-down.svg'
            ]
        ],
        'actions-view-go-forward' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-go-forward.svg'
            ]
        ],
        'actions-view-go-up' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-go-up.svg'
            ]
        ],
        'actions-view-list-collapse' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-list-collapse.svg'
            ]
        ],
        'actions-view-list-expand' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-list-expand.svg'
            ]
        ],
        'actions-view-paging-first-disabled' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-first-disabled.svg'
            ]
        ],
        'actions-view-paging-first' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-first.svg'
            ]
        ],
        'actions-view-paging-last-disabled' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-last-disabled.svg'
            ]
        ],
        'actions-view-paging-last' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-last.svg'
            ]
        ],
        'actions-view-paging-next-disabled' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-next-disabled.svg'
            ]
        ],
        'actions-view-paging-next' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-next.svg'
            ]
        ],
        'actions-view-paging-previous-disabled' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-previous-disabled.svg'
            ]
        ],
        'actions-view-paging-previous' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-previous.svg'
            ]
        ],
        'actions-view-table-collapse' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-table-collapse.svg'
            ]
        ],
        'actions-view-table-expand' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-table-expand.svg'
            ]
        ],
        'actions-view' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view.svg'
            ]
        ],
        'actions-window-open' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-window-open.svg'
            ]
        ],
        'actions-wizard-link' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'link'
            ]
        ],
        'actions-wizard-rte' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'arrows-alt'
            ]
        ],
        'actions-add-placeholder' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-add-placeholder.svg'
            ]
        ],
        'actions-view-page' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-page.svg'
            ]
        ],
        // Apps
        'apps-clipboard-images' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-clipboard-images.svg'
            ]
        ],
        'apps-clipboard-list' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-clipboard-list.svg'
            ]
        ],
        'apps-filetree-folder-add' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-add.svg'
            ]
        ],
        'apps-filetree-folder-default' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-default.svg'
            ]
        ],
        'apps-filetree-folder-list' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-list.svg'
            ]
        ],
        'apps-filetree-folder-locked' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-locked.svg'
            ]
        ],
        'apps-filetree-folder-media' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-media.svg'
            ]
        ],
        'apps-filetree-folder-news' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-news.svg'
            ]
        ],
        'apps-filetree-folder-opened' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-opened.svg'
            ]
        ],
        'apps-filetree-folder-recycler' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-recycler.svg'
            ]
        ],
        'apps-filetree-folder-temp' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-temp.svg'
            ]
        ],
        'apps-filetree-folder-user' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-user.svg'
            ]
        ],
        'apps-filetree-folder' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder.svg'
            ]
        ],
        'apps-filetree-mount' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-mount.svg'
            ]
        ],
        'apps-filetree-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-root.svg'
            ]
        ],
        'apps-irre-collapsed' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-irre-collapsed.svg'
            ]
        ],
        'apps-irre-expanded' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-irre-expanded.svg'
            ]
        ],
        'apps-pagetree-backend-user-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-backend-user-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-backend-user' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-backend-user.svg'
            ]
        ],
        'apps-pagetree-category-collapse-all' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-category-collapse-all.svg'
            ]
        ],
        'apps-pagetree-category-expand-all' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-category-expand-all.svg'
            ]
        ],
        'apps-pagetree-collapse' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-collapse.svg'
            ]
        ],
        'apps-pagetree-drag-copy-above' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-copy-above.svg'
            ]
        ],
        'apps-pagetree-drag-copy-below' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-copy-below.svg'
            ]
        ],
        'apps-pagetree-drag-move-above' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-move-above.svg'
            ]
        ],
        'apps-pagetree-drag-move-below' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-move-below.svg'
            ]
        ],
        'apps-pagetree-drag-move-between' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-move-between.svg'
            ]
        ],
        'apps-pagetree-drag-move-into' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-move-into.svg'
            ]
        ],
        'apps-pagetree-drag-new-between' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-new-between.svg'
            ]
        ],
        'apps-pagetree-drag-new-inside' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-new-inside.svg'
            ]
        ],
        'apps-pagetree-drag-place-denied' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-place-denied.svg'
            ]
        ],
        'apps-pagetree-expand' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-expand.svg'
            ]
        ],
        'apps-pagetree-folder-contains-approve' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains-approve.svg'
            ]
        ],
        'apps-pagetree-folder-contains-board' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains-board.svg'
            ]
        ],
        'apps-pagetree-folder-contains-fe_users' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains-fe_users.svg'
            ]
        ],
        'apps-pagetree-folder-contains-news' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains-news.svg'
            ]
        ],
        'apps-pagetree-folder-contains-shop' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains-shop.svg'
            ]
        ],
        'apps-pagetree-folder-contains' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains.svg'
            ]
        ],
        'apps-pagetree-folder-default' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-default.svg'
            ]
        ],
        'apps-pagetree-folder-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-folder-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-root.svg'
            ]
        ],
        'apps-pagetree-page-advanced-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-advanced-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-page-advanced-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-advanced-root.svg'
            ]
        ],
        'apps-pagetree-page-advanced' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-advanced.svg'
            ]
        ],
        'apps-pagetree-page-backend-user-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-user-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-page-backend-user-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-user-root.svg'
            ]
        ],
        'apps-pagetree-page-backend-user' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-user.svg'
            ]
        ],
        'apps-pagetree-page-backend-users-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-users-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-page-backend-users-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-users-root.svg'
            ]
        ],
        'apps-pagetree-page-backend-users' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-users.svg'
            ]
        ],
        'apps-pagetree-page-content-from-page-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-content-from-page-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-page-content-from-page-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-content-from-page-root.svg'
            ]
        ],
        'apps-pagetree-page-content-from-page' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-content-from-page.svg'
            ]
        ],
        'apps-pagetree-page-default' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-default.svg'
            ]
        ],
        'apps-pagetree-page-domain' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-domain.svg'
            ]
        ],
        'apps-pagetree-page-frontend-user-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-user-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-page-frontend-user-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-user-root.svg'
            ]
        ],
        'apps-pagetree-page-frontend-user' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-user.svg'
            ]
        ],
        'apps-pagetree-page-frontend-users-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-users-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-page-frontend-users-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-users-root.svg'
            ]
        ],
        'apps-pagetree-page-frontend-users' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-users.svg'
            ]
        ],
        'apps-pagetree-page-mountpoint-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-mountpoint-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-page-mountpoint-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-mountpoint-root.svg'
            ]
        ],
        'apps-pagetree-page-mountpoint' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-mountpoint.svg'
            ]
        ],
        'apps-pagetree-page-not-in-menu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-not-in-menu.svg'
            ]
        ],
        'apps-pagetree-page-recycler-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-recycler-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-page-recycler' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-recycler.svg'
            ]
        ],
        'apps-pagetree-page-shortcut-external-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut-external-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-page-shortcut-external-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut-external-root.svg'
            ]
        ],
        'apps-pagetree-page-shortcut-external' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut-external.svg'
            ]
        ],
        'apps-pagetree-page-shortcut-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-page-shortcut-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut-root.svg'
            ]
        ],
        'apps-pagetree-page-shortcut' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut.svg'
            ]
        ],
        'apps-pagetree-page' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page.svg'
            ]
        ],
        'apps-pagetree-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-root.svg'
            ]
        ],
        'apps-pagetree-spacer-hideinmenu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-spacer-hideinmenu.svg'
            ]
        ],
        'apps-pagetree-spacer-root' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-spacer-root.svg'
            ]
        ],
        'apps-pagetree-spacer' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-spacer.svg'
            ]
        ],
        'apps-toolbar-menu-actions' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-actions.svg'
            ]
        ],
        'apps-toolbar-menu-cache' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-cache.svg'
            ]
        ],
        'apps-toolbar-menu-help' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-help.svg'
            ]
        ],
        'apps-toolbar-menu-opendocs' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-opendocs.svg'
            ]
        ],
        'apps-toolbar-menu-search' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-search.svg'
            ]
        ],
        'apps-toolbar-menu-shortcut' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-shortcut.svg'
            ]
        ],
        'apps-toolbar-menu-systeminformation' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-systeminformation.svg'
            ]
        ],
        'apps-toolbar-menu-workspace' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-workspace.svg'
            ]
        ],
        'apps-pagetree-category-toggle-hide-checked' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'check-square'
            ]
        ],

        // Avatar
        'avatar-default' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/avatar/avatar-default.svg'
            ]
        ],

        // Content
        'content-accordion' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-accordion.svg'
            ]
        ],
        'content-audio' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-audio.svg'
            ]
        ],
        'content-beside-text-img-above-center' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-above-center.svg'
            ]
        ],
        'content-beside-text-img-above-left' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-above-left.svg'
            ]
        ],
        'content-beside-text-img-above-right' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-above-right.svg'
            ]
        ],
        'content-beside-text-img-below-center' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-below-center.svg'
            ]
        ],
        'content-beside-text-img-below-left' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-below-left.svg'
            ]
        ],
        'content-beside-text-img-below-right' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-below-right.svg'
            ]
        ],
        'content-beside-text-img-left' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-left.svg'
            ]
        ],
        'content-beside-text-img-right' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-right.svg'
            ]
        ],
        'content-briefcase' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-briefcase.svg'
            ]
        ],
        'content-bullets' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-bullets.svg'
            ]
        ],
        'content-carousel-header' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-carousel-header.svg'
            ]
        ],
        'content-carousel-html' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-carousel-html.svg'
            ]
        ],
        'content-carousel-image' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-carousel-image.svg'
            ]
        ],
        'content-carousel' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-carousel.svg'
            ]
        ],
        'content-coffee' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-coffee.svg'
            ]
        ],
        'content-elements-login' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-elements-login.svg'
            ]
        ],
        'content-elements-mailform' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-elements-mailform.svg'
            ]
        ],
        'content-elements-searchform' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-elements-searchform.svg'
            ]
        ],
        'content-form' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-form.svg'
            ]
        ],
        'content-header' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-header.svg'
            ]
        ],
        'content-idea' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-idea.svg'
            ]
        ],
        'content-image' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-image.svg'
            ]
        ],
        'content-info' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-info.svg'
            ]
        ],
        'content-inside-text-img-left' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-inside-text-img-left.svg'
            ]
        ],
        'content-inside-text-img-right' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-inside-text-img-right.svg'
            ]
        ],
        'content-media' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-media.svg'
            ]
        ],
        'content-menu-abstract' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-menu-abstract.svg'
            ]
        ],
        'content-menu-categorized' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-menu-categorized.svg'
            ]
        ],
        'content-menu-pages' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-menu-pages.svg'
            ]
        ],
        'content-menu-recently-updated' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-menu-recently-updated.svg'
            ]
        ],
        'content-menu-related' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-menu-related.svg'
            ]
        ],
        'content-menu-section' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-menu-section.svg'
            ]
        ],
        'content-menu-sitemap-pages' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-menu-sitemap-pages.svg'
            ]
        ],
        'content-menu-sitemap' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-menu-sitemap.svg'
            ]
        ],
        'content-menu-thumbnail' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-menu-thumbnail.svg'
            ]
        ],
        'content-news' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-news.svg'
            ]
        ],
        'content-panel' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-panel.svg'
            ]
        ],
        'content-plugin' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-plugin.svg'
            ]
        ],
        'content-quote' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-quote.svg'
            ]
        ],
        'content-special-div' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-div.svg'
            ]
        ],
        'content-special-html' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-html.svg'
            ]
        ],
        'content-special-indexed_search' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-indexed_search.svg'
            ]
        ],
        'content-special-menu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-menu.svg'
            ]
        ],
        'content-special-shortcut' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-shortcut.svg'
            ]
        ],
        'content-special-uploads' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-uploads.svg'
            ]
        ],
        'content-tab-item' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-tab-item.svg'
            ]
        ],
        'content-tab' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-tab.svg'
            ]
        ],
        'content-table' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-table.svg'
            ]
        ],
        'content-text-columns' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-text-columns.svg'
            ]
        ],
        'content-text-teaser' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-text-teaser.svg'
            ]
        ],
        'content-text' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-text.svg'
            ]
        ],
        'content-textmedia' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-textmedia.svg'
            ]
        ],
        'content-textpic' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-textpic.svg'
            ]
        ],

        // Default
        'default-not-found' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/default/default-not-found.svg'
            ]
        ],

        // Mimetypes
        'mimetypes-application' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-application.svg'
            ]
        ],
        'mimetypes-compressed' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-compressed.svg'
            ]
        ],
        'mimetypes-excel' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-excel.svg'
            ]
        ],
        'mimetypes-media-audio' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-audio.svg'
            ]
        ],
        'mimetypes-media-flash' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-flash.svg'
            ]
        ],
        'mimetypes-media-image' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-image.svg'
            ]
        ],
        'mimetypes-media-video-vimeo' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-video-vimeo.svg'
            ]
        ],
        'mimetypes-media-video-youtube' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-video-youtube.svg'
            ]
        ],
        'mimetypes-media-video' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-video.svg'
            ]
        ],
        'mimetypes-open-document-database' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-database.svg'
            ]
        ],
        'mimetypes-open-document-drawing' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-drawing.svg'
            ]
        ],
        'mimetypes-open-document-formula' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-formula.svg'
            ]
        ],
        'mimetypes-open-document-presentation' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-presentation.svg'
            ]
        ],
        'mimetypes-open-document-spreadsheet' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-spreadsheet.svg'
            ]
        ],
        'mimetypes-open-document-text' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-text.svg'
            ]
        ],
        'mimetypes-other-other' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-other-other.svg'
            ]
        ],
        'mimetypes-pdf' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-pdf.svg'
            ]
        ],
        'mimetypes-powerpoint' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-powerpoint.svg'
            ]
        ],
        'mimetypes-text-css' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-css.svg'
            ]
        ],
        'mimetypes-text-csv' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-csv.svg'
            ]
        ],
        'mimetypes-text-html' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-html.svg'
            ]
        ],
        'mimetypes-text-js' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-js.svg'
            ]
        ],
        'mimetypes-text-php' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-php.svg'
            ]
        ],
        'mimetypes-text-text' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-text.svg'
            ]
        ],
        'mimetypes-text-ts' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-ts.svg'
            ]
        ],
        'mimetypes-text-typoscript' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-typoscript.svg'
            ]
        ],
        'mimetypes-word' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-word.svg'
            ]
        ],
        'mimetypes-x-backend_layout' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-backend_layout.svg'
            ]
        ],
        'mimetypes-x-content-divider' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-divider.svg'
            ]
        ],
        'mimetypes-x-content-domain' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-domain.svg'
            ]
        ],
        'mimetypes-x-content-form-search' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-form-search.svg'
            ]
        ],
        'mimetypes-x-content-form' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-form.svg'
            ]
        ],
        'mimetypes-x-content-header' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-header.svg'
            ]
        ],
        'mimetypes-x-content-html' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-html.svg'
            ]
        ],
        'mimetypes-x-content-image' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-image.svg'
            ]
        ],
        'mimetypes-x-content-link' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-link.svg'
            ]
        ],
        'mimetypes-x-content-list-bullets' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-list-bullets.svg'
            ]
        ],
        'mimetypes-x-content-list-files' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-list-files.svg'
            ]
        ],
        'mimetypes-x-content-login' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-login.svg'
            ]
        ],
        'mimetypes-x-content-menu' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-menu.svg'
            ]
        ],
        'mimetypes-x-content-multimedia' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-multimedia.svg'
            ]
        ],
        'mimetypes-x-content-page-language-overlay' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-page-language-overlay.svg'
            ]
        ],
        'mimetypes-x-content-plugin' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-plugin.svg'
            ]
        ],
        'mimetypes-x-content-script' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-script.svg'
            ]
        ],
        'mimetypes-x-content-table' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-table.svg'
            ]
        ],
        'mimetypes-x-content-template-extension' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-template-extension.svg'
            ]
        ],
        'mimetypes-x-content-template-static' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-template-static.svg'
            ]
        ],
        'mimetypes-x-content-template' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-template.svg'
            ]
        ],
        'mimetypes-x-content-text-media' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-text-media.svg'
            ]
        ],
        'mimetypes-x-content-text-picture' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-text-picture.svg'
            ]
        ],
        'mimetypes-x-content-text' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-text.svg'
            ]
        ],
        'mimetypes-x-index_config' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-index_config.svg'
            ]
        ],
        'mimetypes-x-sys_action' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_action.svg'
            ]
        ],
        'mimetypes-x-sys_category' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_category.svg'
            ]
        ],
        'mimetypes-x-sys_filemounts' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_filemounts.svg'
            ]
        ],
        'mimetypes-x-sys_file_storage' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_file_storage.svg'
            ]
        ],
        'mimetypes-x-sys_language' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_language.svg'
            ]
        ],
        'mimetypes-x-sys_news' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_news.svg'
            ]
        ],
        'mimetypes-x-sys_note' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_note.svg'
            ]
        ],
        'mimetypes-x-sys_workspace' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_workspace.svg'
            ]
        ],
        'mimetypes-x-tx_rtehtmlarea_acronym' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-tx_rtehtmlarea_acronym.svg'
            ]
        ],
        'mimetypes-x-tx_scheduler_task_group' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-tx_scheduler_task_group.svg'
            ]
        ],

        // Miscellaneous
        'miscellaneous-placeholder' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/miscellaneous/miscellaneous-placeholder.svg'
            ]
        ],

        // Module
        'module-web' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'file-o'
            ]
        ],
        'module-file' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'image'
            ]
        ],
        'module-tools' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'rocket'
            ]
        ],
        'module-system' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'plug'
            ]
        ],
        'module-help' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'question-circle'
            ]
        ],
        'module-about' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-about.svg'
            ]
        ],
        'module-aboutmodules' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-aboutmodules.svg'
            ]
        ],
        'module-belog' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-belog.svg'
            ]
        ],
        'module-beuser' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-beuser.svg'
            ]
        ],
        'module-config' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-config.svg'
            ]
        ],
        'module-cshmanual' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-cshmanual.svg'
            ]
        ],
        'module-dbal' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-dbal.svg'
            ]
        ],
        'module-dbint' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-dbint.svg'
            ]
        ],
        'module-documentation' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-documentation.svg'
            ]
        ],
        'module-extensionmanager' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-extensionmanager.svg'
            ]
        ],
        'module-filelist' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-filelist.svg'
            ]
        ],
        'module-form' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-form.svg'
            ]
        ],
        'module-func' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-func.svg'
            ]
        ],
        'module-help' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-help.svg'
            ]
        ],
        'module-indexed_search' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-indexed_search.svg'
            ]
        ],
        'module-info' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-info.svg'
            ]
        ],
        'module-install-environment' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-install-environment.svg'
            ]
        ],
        'module-install-maintenance' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-install-maintenance.svg'
            ]
        ],
        'module-install-settings' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-install-settings.svg'
            ]
        ],
        'module-install-upgrade' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-install-upgrade.svg'
            ]
        ],
        'module-install' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-install.svg'
            ]
        ],
        'module-lang' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-lang.svg'
            ]
        ],
        'module-list' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-list.svg'
            ]
        ],
        'module-page' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-page.svg'
            ]
        ],
        'module-permission' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-permission.svg'
            ]
        ],
        'module-recycler' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-recycler.svg'
            ]
        ],
        'module-reports' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-reports.svg'
            ]
        ],
        'module-scheduler' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-scheduler.svg'
            ]
        ],
        'module-setup' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-setup.svg'
            ]
        ],
        'module-taskcenter' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-taskcenter.svg'
            ]
        ],
        'module-tstemplate' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-tstemplate.svg'
            ]
        ],
        'module-version' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-version.svg'
            ]
        ],
        'module-viewpage' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-viewpage.svg'
            ]
        ],
        'module-workspaces' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/module/module-workspaces.svg'
            ]
        ],

        // Overlay
        'overlay-advanced' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-advanced.svg'
            ]
        ],
        'overlay-approved' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-approved.svg'
            ]
        ],
        'overlay-backenduser' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-backenduser.svg'
            ]
        ],
        'overlay-backendusers' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-backendusers.svg'
            ]
        ],
        'overlay-deleted' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-deleted.svg'
            ]
        ],
        'overlay-edit' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-edit.svg'
            ]
        ],
        'overlay-external-link' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-external-link.svg'
            ]
        ],
        'overlay-frontenduser' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-frontenduser.svg'
            ]
        ],
        'overlay-frontendusers' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-frontendusers.svg'
            ]
        ],
        'overlay-hidden' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-hidden.svg'
            ]
        ],
        'overlay-endtime' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-endtime.svg'
            ]
        ],
        'overlay-includes-subpages' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-includes-subpages.svg'
            ]
        ],
        'overlay-info' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-info.svg'
            ]
        ],
        'overlay-list' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-list.svg'
            ]
        ],
        'overlay-locked' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-locked.svg'
            ]
        ],
        'overlay-media' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-media.svg'
            ]
        ],
        'overlay-missing' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-missing.svg'
            ]
        ],
        'overlay-mountpoint' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-mountpoint.svg'
            ]
        ],
        'overlay-new' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-new.svg'
            ]
        ],
        'overlay-news' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-news.svg'
            ]
        ],
        'overlay-readonly' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-readonly.svg'
            ]
        ],
        'overlay-restricted' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-restricted.svg'
            ]
        ],
        'overlay-scheduled' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-scheduled.svg'
            ]
        ],
        'overlay-endtime' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-endtime.svg'
            ]
        ],
        'overlay-shop' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-shop.svg'
            ]
        ],
        'overlay-shortcut' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-shortcut.svg'
            ]
        ],
        'overlay-translated' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-translated.svg'
            ]
        ],
        'overlay-warning' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-warning.svg'
            ]
        ],

        // Spinner
        'spinner-circle-dark' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/spinner/spinner-circle-dark.svg',
                'spinning' => true
            ]
        ],
        'spinner-circle-light' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/spinner/spinner-circle-light.svg',
                'spinning' => true
            ]
        ],
        'spinner-circle' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/spinner/spinner-circle.svg',
                'spinning' => true
            ]
        ],

        // Status
        'status-user-admin' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/status/status-user-admin.svg'
            ]
        ],
        'status-user-backend' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/status/status-user-backend.svg'
            ]
        ],
        'status-user-frontend' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/status/status-user-frontend.svg'
            ]
        ],
        'status-user-group-backend' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/status/status-user-group-backend.svg'
            ]
        ],
        'status-user-group-frontend' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/status/status-user-group-frontend.svg'
            ]
        ],
        'status-dialog-information' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'exclamation-circle'
            ]
        ],
        'status-dialog-ok' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'check-circle',
            ]
        ],
        'status-dialog-notification' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'exclamation-circle'
            ]
        ],
        'status-dialog-warning' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'exclamation-triangle'
            ]
        ],
        'status-dialog-error' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'exclamation-circle'
            ]
        ],
        'status-warning-lock' => [
            'provider' => BitmapIconProvider::class,
            'options' => [
                'source' => 'EXT:backend/Resources/Public/Icons/warning-lock.png'
            ]
        ],
        'status-warning-in-use' => [
            'provider' => BitmapIconProvider::class,
            'options' => [
                'source' => 'EXT:backend/Resources/Public/Icons/warning-in-use.png'
            ]
        ],
        'status-status-checked' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'check',
            ]
        ],
        'status-status-current' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'caret-right',
            ]
        ],
        'status-status-reference-hard' => [
            'provider' => BitmapIconProvider::class,
            'options' => [
                'source' => 'EXT:impexp/Resources/Public/Icons/status-reference-hard.png',
            ]
        ],
        'status-status-sorting-asc' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'caret-up',
            ]
        ],
        'status-status-sorting-desc' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'caret-down',
            ]
        ],
        'status-status-sorting-light-asc' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'caret-up',
            ]
        ],
        'status-status-sorting-light-desc' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'caret-down',
            ]
        ],
        'status-status-permission-granted' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'check',
            ]
        ],
        'status-status-permission-denied' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'times',
            ]
        ],
        'status-status-reference-soft' => [
            'provider' => BitmapIconProvider::class,
            'options' => [
                'source' => 'EXT:impexp/Resources/Public/Icons/status-reference-soft.png',
            ]
        ],
        'status-status-edit-read-only' => [
            'provider' => BitmapIconProvider::class,
            'options' => [
                'source' => 'EXT:backend/Resources/Public/Icons/status-edit-read-only.png',
            ]
        ],

        // Extensions
        'extensions-extensionmanager-update-script' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'refresh',
            ]
        ],
        'extensions-scheduler-run-task' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'play-circle',
            ]
        ],
        'extensions-scheduler-run-task-cron' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'clock-o',
            ]
        ],
        'extensions-workspaces-generatepreviewlink' => [
            'provider' => BitmapIconProvider::class,
            'options' => [
                'source' => 'EXT:workspaces/Resources/Public/Images/generate-ws-preview-link.png'
            ]
        ],

        // Empty
        'empty-empty' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'empty-empty',
            ]
        ],

        // System Information
        'sysinfo-php-version' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'code'
            ]
        ],
        'sysinfo-database' =>  [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'database'
            ]
        ],
        'sysinfo-application-context' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'tasks'
            ]
        ],
        'sysinfo-composer-mode' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'music'
            ]
        ],
        'sysinfo-git' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'git'
            ]
        ],
        'sysinfo-webserver' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'server'
            ]
        ],
        'sysinfo-os-linux' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'linux'
            ]
        ],
        'sysinfo-os-apple' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'apple'
            ]
        ],
        'sysinfo-os-windows' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'windows'
            ]
        ],
        'sysinfo-os-unknown' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/sysinfo/sysinfo-os-unknown.svg'
            ]
        ],
        'sysinfo-typo3-version' => [
            'provider' => SvgIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/sysinfo/sysinfo-typo3-version.svg'
            ]
        ],

        // Sysnote
        'sysnote-type-0' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'sticky-note-o'
            ]
        ],
        'sysnote-type-1' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'cog'
            ]
        ],
        'sysnote-type-2' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'code'
            ]
        ],
        'sysnote-type-3' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'thumb-tack'
            ]
        ],
        'sysnote-type-4' => [
            'provider' => FontawesomeIconProvider::class,
            'options' => [
                'name' => 'check-square'
            ]
        ],

        // Flags will be auto-registered after we have the SVG files
        'flags-multiple' => [
            'provider' => BitmapIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/Flags/multiple.png'
            ]
        ],
        'flags-catalonia' => [
            'provider' => BitmapIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/Flags/catalonia.png'
            ]
        ],
        'flags-en-us-gb' => [
            'provider' => BitmapIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/Flags/en_us-gb.png'
            ]
        ],
        'flags-scotland' => [
            'provider' => BitmapIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/Flags/scotland.png'
            ]
        ],
        'flags-wales' => [
            'provider' => BitmapIconProvider::class,
            'options' => [
                'source' => 'EXT:core/Resources/Public/Icons/Flags/wales.png'
            ]
        ],
    ];

    /**
     * Mapping of file extensions to mimetypes
     *
     * @var string[]
     */
    protected $fileExtensionMapping = [
        'htm' => 'mimetypes-text-html',
        'html' => 'mimetypes-text-html',
        'css' => 'mimetypes-text-css',
        'js' => 'mimetypes-text-js',
        'csv' => 'mimetypes-text-csv',
        'php' => 'mimetypes-text-php',
        'php6' => 'mimetypes-text-php',
        'php5' => 'mimetypes-text-php',
        'php4' => 'mimetypes-text-php',
        'php3' => 'mimetypes-text-php',
        'inc' => 'mimetypes-text-php',
        'ts' => 'mimetypes-text-ts',
        'typoscript' => 'mimetypes-text-typoscript',
        'txt' => 'mimetypes-text-text',
        'class' => 'mimetypes-text-text',
        'tmpl' => 'mimetypes-text-text',
        'jpg' => 'mimetypes-media-image',
        'jpeg' => 'mimetypes-media-image',
        'gif' => 'mimetypes-media-image',
        'png' => 'mimetypes-media-image',
        'bmp' => 'mimetypes-media-image',
        'tif' => 'mimetypes-media-image',
        'tiff' => 'mimetypes-media-image',
        'tga' => 'mimetypes-media-image',
        'psd' => 'mimetypes-media-image',
        'eps' => 'mimetypes-media-image',
        'ai' => 'mimetypes-media-image',
        'svg' => 'mimetypes-media-image',
        'pcx' => 'mimetypes-media-image',
        'avi' => 'mimetypes-media-video',
        'mpg' => 'mimetypes-media-video',
        'mpeg' => 'mimetypes-media-video',
        'mov' => 'mimetypes-media-video',
        'vimeo' => 'mimetypes-media-video-vimeo',
        'youtube' => 'mimetypes-media-video-youtube',
        'wav' => 'mimetypes-media-audio',
        'mp3' => 'mimetypes-media-audio',
        'ogg' => 'mimetypes-media-audio',
        'flac' => 'mimetypes-media-audio',
        'opus' => 'mimetypes-media-audio',
        'mid' => 'mimetypes-media-audio',
        'swf' => 'mimetypes-media-flash',
        'swa' => 'mimetypes-media-flash',
        'exe' => 'mimetypes-application',
        'com' => 'mimetypes-application',
        't3x' => 'mimetypes-compressed',
        't3d' => 'mimetypes-compressed',
        'zip' => 'mimetypes-compressed',
        'tgz' => 'mimetypes-compressed',
        'gz' => 'mimetypes-compressed',
        'pdf' => 'mimetypes-pdf',
        'doc' => 'mimetypes-word',
        'dot' => 'mimetypes-word',
        'docm' => 'mimetypes-word',
        'docx' => 'mimetypes-word',
        'dotm' => 'mimetypes-word',
        'dotx' => 'mimetypes-word',
        'sxw' => 'mimetypes-word',
        'rtf' => 'mimetypes-word',
        'xls' => 'mimetypes-excel',
        'xlsm' => 'mimetypes-excel',
        'xlsx' => 'mimetypes-excel',
        'xltm' => 'mimetypes-excel',
        'xltx' => 'mimetypes-excel',
        'sxc' => 'mimetypes-excel',
        'pps' => 'mimetypes-powerpoint',
        'ppsx' => 'mimetypes-powerpoint',
        'ppt' => 'mimetypes-powerpoint',
        'pptm' => 'mimetypes-powerpoint',
        'pptx' => 'mimetypes-powerpoint',
        'potm' => 'mimetypes-powerpoint',
        'potx' => 'mimetypes-powerpoint',
        'mount' => 'apps-filetree-mount',
        'folder' => 'apps-filetree-folder-default',
        'default' => 'mimetypes-other-other',
    ];

    /**
     * Mapping of mime types to icons
     *
     * @var string[]
     */
    protected $mimeTypeMapping = [
        'video/*' => 'mimetypes-media-video',
        'audio/*' => 'mimetypes-media-audio',
        'image/*' => 'mimetypes-media-image',
        'text/*' => 'mimetypes-text-text',
    ];

    /**
     * Array of deprecated icons, add deprecated icons to this array and remove it from registry
     * - Index of this array contains the deprecated icon
     * - Value of each entry must contain the deprecation message and can contain an identifier
     *   which replaces the old identifier
     *
     * Example:
     * array(
     *   'deprecated-icon-identifier' => array(
     *      'message' => '%s is deprecated since TYPO3 CMS 7, this icon will be removed in TYPO3 CMS 8',
     *      'replacement' => 'alternative-icon-identifier' // must be registered
     *   )
     * )
     *
     * @var array
     */
    protected $deprecatedIcons = [
        'actions-document-close' => [
            'message' => '%s is deprecated since TYPO3 CMS 8, this icon will be removed in TYPO3 CMS 9',
            'replacement' => 'actions-close'
        ],
        'actions-edit-add' => [
            'message' => '%s is deprecated since TYPO3 CMS 8, this icon will be removed in TYPO3 CMS 9',
            'replacement' => 'actions-add'
        ]
    ];

    /**
     * @var string
     */
    protected $defaultIconIdentifier = 'default-not-found';

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialize the registry
     * This method can be called multiple times, depending on initialization status.
     * In some cases e.g. TCA is not available, the method must be called multiple times.
     */
    protected function initialize()
    {
        if (!$this->tcaInitialized && !empty($GLOBALS['TCA'])) {
            $this->registerTCAIcons();
        }
        if (!$this->moduleIconsInitialized && !empty($GLOBALS['TBE_MODULES'])) {
            $this->registerModuleIcons();
        }
        if (!$this->flagsInitialized) {
            $this->registerFlags();
        }
        if ($this->tcaInitialized && $this->moduleIconsInitialized && $this->flagsInitialized) {
            $this->fullInitialized = true;
        }
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function isRegistered($identifier)
    {
        if (!$this->fullInitialized) {
            $this->initialize();
        }
        return isset($this->icons[$identifier]);
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function isDeprecated($identifier)
    {
        return isset($this->deprecatedIcons[$identifier]);
    }

    /**
     * @return string
     */
    public function getDefaultIconIdentifier()
    {
        return $this->defaultIconIdentifier;
    }

    /**
     * Registers an icon to be available inside the Icon Factory
     *
     * @param string $identifier
     * @param string $iconProviderClassName
     * @param array $options
     *
     * @throws \InvalidArgumentException
     */
    public function registerIcon($identifier, $iconProviderClassName, array $options = [])
    {
        if (!in_array(IconProviderInterface::class, class_implements($iconProviderClassName), true)) {
            throw new \InvalidArgumentException('An IconProvider must implement '
                . IconProviderInterface::class, 1437425803);
        }
        $this->icons[$identifier] = [
            'provider' => $iconProviderClassName,
            'options' => $options
        ];
    }

    /**
     * Register an icon for a file extension
     *
     * @param string $fileExtension
     * @param string $iconIdentifier
     */
    public function registerFileExtension($fileExtension, $iconIdentifier)
    {
        $this->fileExtensionMapping[$fileExtension] = $iconIdentifier;
    }

    /**
     * Register an icon for a mime-type
     *
     * @param string $mimeType
     * @param string $iconIdentifier
     */
    public function registerMimeTypeIcon($mimeType, $iconIdentifier)
    {
        $this->mimeTypeMapping[$mimeType] = $iconIdentifier;
    }

    /**
     * Fetches the configuration provided by registerIcon()
     *
     * @param string $identifier the icon identifier
     * @return mixed
     * @throws Exception
     */
    public function getIconConfigurationByIdentifier($identifier)
    {
        if (!$this->fullInitialized) {
            $this->initialize();
        }
        if (!$this->isRegistered($identifier)) {
            throw new Exception('Icon with identifier "' . $identifier . '" is not registered"', 1437425804);
        }
        if ($this->isDeprecated($identifier)) {
            $deprecationSettings = $this->deprecatedIcons[$identifier];
            GeneralUtility::deprecationLog(sprintf($deprecationSettings['message'], $identifier));
            if (!empty($deprecationSettings['replacement'])) {
                $identifier = $deprecationSettings['replacement'];
            }
        }
        return $this->icons[$identifier];
    }

    /**
     * @param string $identifier
     *
     * @return array
     * @throws Exception
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getDeprecationSettings($identifier)
    {
        GeneralUtility::logDeprecatedFunction();
        if (!$this->isDeprecated($identifier)) {
            throw new Exception('Icon with identifier "' . $identifier . '" is not deprecated"', 1460976527);
        }
        return $this->deprecatedIcons[$identifier];
    }

    /**
     * @return array
     */
    public function getAllRegisteredIconIdentifiers()
    {
        if (!$this->fullInitialized) {
            $this->initialize();
        }
        return array_keys($this->icons);
    }

    /**
     * @param string $fileExtension
     * @return string
     */
    public function getIconIdentifierForFileExtension($fileExtension)
    {
        // If the file extension is not valid use the default one
        if (!isset($this->fileExtensionMapping[$fileExtension])) {
            $fileExtension = 'default';
        }
        return $this->fileExtensionMapping[$fileExtension];
    }

    /**
     * Get iconIdentifier for given mimeType
     *
     * @param string $mimeType
     * @return string|null Returns null if no icon is registered for the mimeType
     */
    public function getIconIdentifierForMimeType($mimeType)
    {
        if (!isset($this->mimeTypeMapping[$mimeType])) {
            return null;
        }
        return $this->mimeTypeMapping[$mimeType];
    }

    /**
     * Load icons from TCA for each table and add them as "tcarecords-XX" to $this->icons
     */
    protected function registerTCAIcons()
    {
        $resultArray = [];

        $tcaTables = array_keys($GLOBALS['TCA']);
        // check every table in the TCA, if an icon is needed
        foreach ($tcaTables as $tableName) {
            // This method is only needed for TCA tables where typeicon_classes are not configured
            if (is_array($GLOBALS['TCA'][$tableName])) {
                $tcaCtrl = $GLOBALS['TCA'][$tableName]['ctrl'];
                $iconIdentifier = 'tcarecords-' . $tableName . '-default';
                if (isset($this->icons[$iconIdentifier])) {
                    continue;
                }
                if (isset($tcaCtrl['iconfile'])) {
                    $resultArray[$iconIdentifier] = $tcaCtrl['iconfile'];
                }
            }
        }

        foreach ($resultArray as $iconIdentifier => $iconFilePath) {
            $iconProviderClass = $this->detectIconProvider($iconFilePath);
            $this->icons[$iconIdentifier] = [
                'provider' => $iconProviderClass,
                'options' => [
                    'source' => $iconFilePath
                ]
            ];
        }
        $this->tcaInitialized = true;
    }

    /**
     * Register module icons
     */
    protected function registerModuleIcons()
    {
        $moduleConfiguration = $GLOBALS['TBE_MODULES']['_configuration'];
        foreach ($moduleConfiguration as $moduleKey => $singleModuleConfiguration) {
            $iconIdentifier = !empty($singleModuleConfiguration['iconIdentifier'])
                ? $singleModuleConfiguration['iconIdentifier']
                : null;

            if ($iconIdentifier !== null) {
                // iconIdentifier found, icon is registered, continue
                continue;
            }

            $iconPath = !empty($singleModuleConfiguration['icon'])
                ? $singleModuleConfiguration['icon']
                : null;
            $iconProviderClass = $this->detectIconProvider($iconPath);
            $iconIdentifier = 'module-icon-' . $moduleKey;

            $this->icons[$iconIdentifier] = [
                'provider' => $iconProviderClass,
                'options' => [
                    'source' => $iconPath
                ]
            ];
        }
        $this->moduleIconsInitialized = true;
    }

    /**
     * Register flags
     */
    protected function registerFlags()
    {
        $iconFolder = 'EXT:core/Resources/Public/Icons/Flags/PNG/';
        $files = [
            'AC', 'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AN', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
            'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ',
            'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CR', 'CS', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
            'DE', 'DG', 'DJ', 'DK', 'DM', 'DO', 'DZ',
            'EA', 'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET', 'EU',
            'FI', 'FJ', 'FK', 'FM', 'FO', 'FR',
            'GA', 'GB-ENG', 'GB-NIR', 'GB-SCT', 'GB-WLS', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY',
            'HK', 'HM', 'HN', 'HR', 'HT', 'HU',
            'IC', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT',
            'JE', 'JM', 'JO', 'JP',
            'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ',
            'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY',
            'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
            'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ',
            'OM',
            'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY',
            'QA', 'QC',
            'RE', 'RO', 'RS', 'RU', 'RW',
            'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ',
            'TA', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ',
            'UA', 'UG', 'UM', 'US', 'UY', 'UZ',
            'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU',
            'WF', 'WS',
            'XK',
            'YE', 'YT',
            'ZA', 'ZM', 'ZW'
        ];
        foreach ($files as $file) {
            $identifier = strtolower($file);
            $this->icons['flags-' . $identifier] = [
                'provider' => BitmapIconProvider::class,
                'options' => [
                    'source' => $iconFolder . $file . '.png'
                ]
            ];
        }
        $this->flagsInitialized = true;
    }

    /**
     * Detect the IconProvider of an icon
     *
     * @param string $iconReference
     * @return string
     */
    public function detectIconProvider($iconReference)
    {
        if (StringUtility::endsWith(strtolower($iconReference), 'svg')) {
            return SvgIconProvider::class;
        }
        return BitmapIconProvider::class;
    }
}
