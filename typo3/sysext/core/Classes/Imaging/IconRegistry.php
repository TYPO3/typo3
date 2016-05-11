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
    protected $icons = array(

        /**
         * Important Information:
         *
         * Icons are maintained in an external repository, if new icons are needed
         * please request them at: https://github.com/wmdbsystems/T3.Icons/issues
         */

        // Actions
        'actions-add' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-add.svg'
            )
        ),
        'actions-close' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-close.svg'
            )
        ),
        'actions-database' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-database.svg'
            )
        ),
        'actions-delete' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-delete.svg'
            )
        ),
        'actions-document-close' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-close.svg'
            )
        ),
        'actions-document-duplicates-select' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-duplicates-select.svg'
            )
        ),
        'actions-document-edit-access' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-edit-access.svg'
            )
        ),
        'actions-document-export-csv' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-export-csv.svg'
            )
        ),
        'actions-document-export-t3d' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-export-t3d.svg'
            )
        ),
        'actions-document-history-open' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-history-open.svg'
            )
        ),
        'actions-document-import-t3d' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-import-t3d.svg'
            )
        ),
        'actions-document-info' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-info.svg'
            )
        ),
        'actions-document-localize' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-localize.svg'
            )
        ),
        'actions-document-move' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-move.svg'
            )
        ),
        'actions-document-new' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-new.svg'
            )
        ),
        'actions-document-open-read-only' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-open-read-only.svg'
            )
        ),
        'actions-document-open' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-open.svg'
            )
        ),
        'actions-document-paste-after' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-paste-after.svg'
            )
        ),
        'actions-document-paste-before' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-paste-before.svg'
            )
        ),
        'actions-document-paste-into' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-paste-into.svg'
            )
        ),
        'actions-document-paste' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-paste.svg'
            )
        ),
        'actions-document-save-cleartranslationcache' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save-cleartranslationcache.svg'
            )
        ),
        'actions-document-save-close' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save-close.svg'
            )
        ),
        'actions-document-save-new' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save-new.svg'
            )
        ),
        'actions-document-save-translation' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save-translation.svg'
            )
        ),
        'actions-document-save-view' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save-view.svg'
            )
        ),
        'actions-document-save' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-save.svg'
            )
        ),
        'actions-document-select' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-select.svg'
            )
        ),
        'actions-document-synchronize' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-synchronize.svg'
            )
        ),
        'actions-document-view' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document-view.svg'
            )
        ),
        'actions-document' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-document.svg'
            )
        ),
        'actions-download' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-download.svg'
            )
        ),
        'actions-edit-add' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-add.svg'
            )
        ),
        'actions-edit-copy-release' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-copy-release.svg'
            )
        ),
        'actions-edit-copy' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-copy.svg'
            )
        ),
        'actions-edit-cut-release' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-cut-release.svg'
            )
        ),
        'actions-edit-cut' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-cut.svg'
            )
        ),
        'actions-edit-delete' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-delete.svg'
            )
        ),
        'actions-edit-download' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-download.svg'
            )
        ),
        'actions-edit-hide' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-hide.svg'
            )
        ),
        'actions-edit-insert-default' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-insert-default.svg'
            )
        ),
        'actions-edit-localize-status-high' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-localize-status-high.svg'
            )
        ),
        'actions-edit-localize-status-low' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-localize-status-low.svg'
            )
        ),
        'actions-edit-merge-localization' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-merge-localization.svg'
            )
        ),
        'actions-edit-pick-date' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-pick-date.svg'
            )
        ),
        'actions-edit-rename' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-rename.svg'
            )
        ),
        'actions-edit-replace' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-replace.svg'
            )
        ),
        'actions-edit-restore' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-restore.svg'
            )
        ),
        'actions-edit-undelete-edit' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-undelete-edit.svg'
            )
        ),
        'actions-edit-undo' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-undo.svg'
            )
        ),
        'actions-edit-unhide' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-unhide.svg'
            )
        ),
        'actions-edit-upload' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-edit-upload.svg'
            )
        ),
        'actions-file-csv' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-file-csv.svg'
            )
        ),
        'actions-file-html' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-file-html.svg'
            )
        ),
        'actions-file-openoffice' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-file-openoffice.svg'
            )
        ),
        'actions-file-pdf' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-file-pdf.svg'
            )
        ),
        'actions-file' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-file.svg'
            )
        ),
        'actions-filter' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-filter.svg'
            )
        ),
        'actions-input-clear' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-input-clear.svg'
            )
        ),
        'actions-insert-record' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-insert-record.svg'
            )
        ),
        'actions-insert-reference' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-insert-reference.svg'
            )
        ),
        'actions-localize' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-localize.svg'
            )
        ),
        'actions-lock' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-lock.svg'
            )
        ),
        'actions-logout' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-logout.svg'
            )
        ),
        'actions-markstate' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-markstate.svg'
            )
        ),
        'actions-merge' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-merge.svg'
            )
        ),
        'actions-message-error-close' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-message-error-close.svg'
            )
        ),
        'actions-message-information-close' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-message-information-close.svg'
            )
        ),
        'actions-message-notice-close' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-message-notice-close.svg'
            )
        ),
        'actions-message-ok-close' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-message-ok-close.svg'
            )
        ),
        'actions-message-warning-close' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-message-warning-close.svg'
            )
        ),
        'actions-move-down' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-down.svg'
            )
        ),
        'actions-move-left' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-left.svg'
            )
        ),
        'actions-move-move' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-move.svg'
            )
        ),
        'actions-move-right' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-right.svg'
            )
        ),
        'actions-move-to-bottom' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-to-bottom.svg'
            )
        ),
        'actions-move-to-top' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-to-top.svg'
            )
        ),
        'actions-move-up' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move-up.svg'
            )
        ),
        'actions-move' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-move.svg'
            )
        ),
        'actions-online-media-add' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-online-media-add.svg'
            )
        ),
        'actions-open' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-open.svg'
            )
        ),
        'actions-page-move' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-page-move.svg'
            )
        ),
        'actions-page-new' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-page-new.svg'
            )
        ),
        'actions-page-open' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-page-open.svg'
            )
        ),
        'actions-pagetree-collapse' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-pagetree-collapse.svg'
            )
        ),
        'actions-pagetree-expand' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-pagetree-expand.svg'
            )
        ),
        'actions-pagetree-mountroot' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-pagetree-mountroot.svg'
            )
        ),
        'actions-preview' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-preview.svg'
            )
        ),
        'actions-refresh' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-refresh.svg'
            )
        ),
        'actions-search' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-search.svg'
            )
        ),
        'actions-selection-delete' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-selection-delete.svg'
            )
        ),
        'actions-swap' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-swap.svg'
            )
        ),
        'actions-synchronize' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-synchronize.svg'
            )
        ),
        'actions-system-backend-user-emulate' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-backend-user-emulate.svg'
            )
        ),
        'actions-system-backend-user-switch' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-backend-user-switch.svg'
            )
        ),
        'actions-system-cache-clear-impact-high' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-cache-clear-impact-high.svg'
            )
        ),
        'actions-system-cache-clear-impact-low' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-cache-clear-impact-low.svg'
            )
        ),
        'actions-system-cache-clear-impact-medium' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-cache-clear-impact-medium.svg'
            )
        ),
        'actions-system-cache-clear-rte' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-cache-clear-rte.svg'
            )
        ),
        'actions-system-cache-clear' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-cache-clear.svg'
            )
        ),
        'actions-system-extension-configure' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-configure.svg'
            )
        ),
        'actions-system-extension-documentation' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-documentation.svg'
            )
        ),
        'actions-system-extension-download' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-download.svg'
            )
        ),
        'actions-system-extension-import' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-import.svg'
            )
        ),
        'actions-system-extension-install' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-install.svg'
            )
        ),
        'actions-system-extension-sqldump' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-sqldump.svg'
            )
        ),
        'actions-system-extension-uninstall' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-uninstall.svg'
            )
        ),
        'actions-system-extension-update-disable' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-update-disable.svg'
            )
        ),
        'actions-system-extension-update' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-extension-update.svg'
            )
        ),
        'actions-system-help-open' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-help-open.svg'
            )
        ),
        'actions-system-list-open' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-list-open.svg'
            )
        ),
        'actions-system-options-view' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-options-view.svg'
            )
        ),
        'actions-system-pagemodule-open' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-pagemodule-open.svg'
            )
        ),
        'actions-system-refresh' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-refresh.svg'
            )
        ),
        'actions-system-shortcut-active' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-shortcut-active.svg'
            )
        ),
        'actions-system-shortcut-new' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-shortcut-new.svg'
            )
        ),
        'actions-system-tree-search-open' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-tree-search-open.svg'
            )
        ),
        'actions-system-typoscript-documentation-open' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-typoscript-documentation-open.svg'
            )
        ),
        'actions-system-typoscript-documentation' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-system-typoscript-documentation.svg'
            )
        ),
        'actions-template-new' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-template-new.svg'
            )
        ),
        'actions-unlock' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-unlock.svg'
            )
        ),
        'actions-unmarkstate' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-unmarkstate.svg'
            )
        ),
        'actions-upload' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-upload.svg'
            )
        ),
        'actions-version-document-remove' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-document-remove.svg'
            )
        ),
        'actions-version-page-open' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-page-open.svg'
            )
        ),
        'actions-version-swap-version' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-swap-version.svg'
            )
        ),
        'actions-version-swap-workspace' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-swap-workspace.svg'
            )
        ),
        'actions-version-workspace-preview' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-workspace-preview.svg'
            )
        ),
        'actions-version-workspace-sendtostage' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-version-workspace-sendtostage.svg'
            )
        ),
        'actions-view-go-back' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-go-back.svg'
            )
        ),
        'actions-view-go-down' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-go-down.svg'
            )
        ),
        'actions-view-go-forward' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-go-forward.svg'
            )
        ),
        'actions-view-go-up' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-go-up.svg'
            )
        ),
        'actions-view-list-collapse' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-list-collapse.svg'
            )
        ),
        'actions-view-list-expand' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-list-expand.svg'
            )
        ),
        'actions-view-paging-first-disabled' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-first-disabled.svg'
            )
        ),
        'actions-view-paging-first' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-first.svg'
            )
        ),
        'actions-view-paging-last-disabled' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-last-disabled.svg'
            )
        ),
        'actions-view-paging-last' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-last.svg'
            )
        ),
        'actions-view-paging-next-disabled' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-next-disabled.svg'
            )
        ),
        'actions-view-paging-next' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-next.svg'
            )
        ),
        'actions-view-paging-previous-disabled' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-previous-disabled.svg'
            )
        ),
        'actions-view-paging-previous' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-paging-previous.svg'
            )
        ),
        'actions-view-table-collapse' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-table-collapse.svg'
            )
        ),
        'actions-view-table-expand' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view-table-expand.svg'
            )
        ),
        'actions-view' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-view.svg'
            )
        ),
        'actions-window-open' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/actions/actions-window-open.svg'
            )
        ),

        // Apps
        'apps-clipboard-images' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-clipboard-images.svg'
            )
        ),
        'apps-clipboard-list' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-clipboard-list.svg'
            )
        ),
        'apps-filetree-folder-add' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-add.svg'
            )
        ),
        'apps-filetree-folder-default' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-default.svg'
            )
        ),
        'apps-filetree-folder-list' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-list.svg'
            )
        ),
        'apps-filetree-folder-locked' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-locked.svg'
            )
        ),
        'apps-filetree-folder-media' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-media.svg'
            )
        ),
        'apps-filetree-folder-news' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-news.svg'
            )
        ),
        'apps-filetree-folder-opened' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-opened.svg'
            )
        ),
        'apps-filetree-folder-recycler' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-recycler.svg'
            )
        ),
        'apps-filetree-folder-temp' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-temp.svg'
            )
        ),
        'apps-filetree-folder-user' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-user.svg'
            )
        ),
        'apps-filetree-folder' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder.svg'
            )
        ),
        'apps-filetree-mount' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-mount.svg'
            )
        ),
        'apps-filetree-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-root.svg'
            )
        ),
        'apps-irre-collapsed' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-irre-collapsed.svg'
            )
        ),
        'apps-irre-expanded' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-irre-expanded.svg'
            )
        ),
        'apps-pagetree-backend-user-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-backend-user-hideinmenu.svg'
            )
        ),
        'apps-pagetree-backend-user' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-backend-user.svg'
            )
        ),
        'apps-pagetree-collapse' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-collapse.svg'
            )
        ),
        'apps-pagetree-drag-copy-above' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-copy-above.svg'
            )
        ),
        'apps-pagetree-drag-copy-below' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-copy-below.svg'
            )
        ),
        'apps-pagetree-drag-move-above' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-move-above.svg'
            )
        ),
        'apps-pagetree-drag-move-below' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-move-below.svg'
            )
        ),
        'apps-pagetree-drag-move-between' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-move-between.svg'
            )
        ),
        'apps-pagetree-drag-move-into' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-move-into.svg'
            )
        ),
        'apps-pagetree-drag-new-between' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-new-between.svg'
            )
        ),
        'apps-pagetree-drag-new-inside' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-new-inside.svg'
            )
        ),
        'apps-pagetree-drag-place-denied' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-drag-place-denied.svg'
            )
        ),
        'apps-pagetree-expand' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-expand.svg'
            )
        ),
        'apps-pagetree-folder-contains-approve' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains-approve.svg'
            )
        ),
        'apps-pagetree-folder-contains-board' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains-board.svg'
            )
        ),
        'apps-pagetree-folder-contains-fe_users' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains-fe_users.svg'
            )
        ),
        'apps-pagetree-folder-contains-news' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains-news.svg'
            )
        ),
        'apps-pagetree-folder-contains-shop' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains-shop.svg'
            )
        ),
        'apps-pagetree-folder-contains' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-contains.svg'
            )
        ),
        'apps-pagetree-folder-default' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-default.svg'
            )
        ),
        'apps-pagetree-folder-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-hideinmenu.svg'
            )
        ),
        'apps-pagetree-folder-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-folder-root.svg'
            )
        ),
        'apps-pagetree-page-advanced-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-advanced-hideinmenu.svg'
            )
        ),
        'apps-pagetree-page-advanced-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-advanced-root.svg'
            )
        ),
        'apps-pagetree-page-advanced' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-advanced.svg'
            )
        ),
        'apps-pagetree-page-backend-user-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-user-hideinmenu.svg'
            )
        ),
        'apps-pagetree-page-backend-user-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-user-root.svg'
            )
        ),
        'apps-pagetree-page-backend-user' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-user.svg'
            )
        ),
        'apps-pagetree-page-backend-users-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-users-hideinmenu.svg'
            )
        ),
        'apps-pagetree-page-backend-users-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-users-root.svg'
            )
        ),
        'apps-pagetree-page-backend-users' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-backend-users.svg'
            )
        ),
        'apps-pagetree-page-content-from-page-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-content-from-page-hideinmenu.svg'
            )
        ),
        'apps-pagetree-page-content-from-page-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-content-from-page-root.svg'
            )
        ),
        'apps-pagetree-page-content-from-page' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-content-from-page.svg'
            )
        ),
        'apps-pagetree-page-default' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-default.svg'
            )
        ),
        'apps-pagetree-page-domain' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-domain.svg'
            )
        ),
        'apps-pagetree-page-frontend-user-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-user-hideinmenu.svg'
            )
        ),
        'apps-pagetree-page-frontend-user-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-user-root.svg'
            )
        ),
        'apps-pagetree-page-frontend-user' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-user.svg'
            )
        ),
        'apps-pagetree-page-frontend-users-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-users-hideinmenu.svg'
            )
        ),
        'apps-pagetree-page-frontend-users-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-users-root.svg'
            )
        ),
        'apps-pagetree-page-frontend-users' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-frontend-users.svg'
            )
        ),
        'apps-pagetree-page-mountpoint-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-mountpoint-hideinmenu.svg'
            )
        ),
        'apps-pagetree-page-mountpoint-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-mountpoint-root.svg'
            )
        ),
        'apps-pagetree-page-mountpoint' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-mountpoint.svg'
            )
        ),
        'apps-pagetree-page-not-in-menu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-not-in-menu.svg'
            )
        ),
        'apps-pagetree-page-recycler-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-recycler-hideinmenu.svg'
            )
        ),
        'apps-pagetree-page-recycler' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-recycler.svg'
            )
        ),
        'apps-pagetree-page-shortcut-external-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut-external-hideinmenu.svg'
            )
        ),
        'apps-pagetree-page-shortcut-external-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut-external-root.svg'
            )
        ),
        'apps-pagetree-page-shortcut-external' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut-external.svg'
            )
        ),
        'apps-pagetree-page-shortcut-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut-hideinmenu.svg'
            )
        ),
        'apps-pagetree-page-shortcut-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut-root.svg'
            )
        ),
        'apps-pagetree-page-shortcut' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page-shortcut.svg'
            )
        ),
        'apps-pagetree-page' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-page.svg'
            )
        ),
        'apps-pagetree-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-root.svg'
            )
        ),
        'apps-pagetree-spacer-hideinmenu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-spacer-hideinmenu.svg'
            )
        ),
        'apps-pagetree-spacer-root' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-spacer-root.svg'
            )
        ),
        'apps-pagetree-spacer' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-pagetree-spacer.svg'
            )
        ),
        'apps-toolbar-menu-actions' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-actions.svg'
            )
        ),
        'apps-toolbar-menu-cache' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-cache.svg'
            )
        ),
        'apps-toolbar-menu-help' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-help.svg'
            )
        ),
        'apps-toolbar-menu-opendocs' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-opendocs.svg'
            )
        ),
        'apps-toolbar-menu-search' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-search.svg'
            )
        ),
        'apps-toolbar-menu-shortcut' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-shortcut.svg'
            )
        ),
        'apps-toolbar-menu-systeminformation' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-systeminformation.svg'
            )
        ),
        'apps-toolbar-menu-workspace' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-toolbar-menu-workspace.svg'
            )
        ),

        // Avatar
        'avatar-default' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/avatar/avatar-default.svg'
            )
        ),

        // Content
        'content-beside-text-img-above-center' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-above-center.svg'
            )
        ),
        'content-beside-text-img-above-left' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-above-left.svg'
            )
        ),
        'content-beside-text-img-above-right' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-above-right.svg'
            )
        ),
        'content-beside-text-img-below-center' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-below-center.svg'
            )
        ),
        'content-beside-text-img-below-left' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-below-left.svg'
            )
        ),
        'content-beside-text-img-below-right' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-below-right.svg'
            )
        ),
        'content-beside-text-img-left' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-left.svg'
            )
        ),
        'content-beside-text-img-right' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-beside-text-img-right.svg'
            )
        ),
        'content-inside-text-img-left' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-inside-text-img-left.svg'
            )
        ),
        'content-inside-text-img-right' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-inside-text-img-right.svg'
            )
        ),
        'content-bullets' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-bullets.svg'
            )
        ),
        'content-elements-login' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-elements-login.svg'
            )
        ),
        'content-elements-mailform' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-elements-mailform.svg'
            )
        ),
        'content-elements-searchform' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-elements-searchform.svg'
            )
        ),
        'content-header' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-header.svg'
            )
        ),
        'content-image' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-image.svg'
            )
        ),
        'content-plugin' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-plugin.svg'
            )
        ),
        'content-special-div' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-div.svg'
            )
        ),
        'content-special-html' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-html.svg'
            )
        ),
        'content-special-indexed_search' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-indexed_search.svg'
            )
        ),
        'content-special-menu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-menu.svg'
            )
        ),
        'content-special-shortcut' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-shortcut.svg'
            )
        ),
        'content-special-uploads' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-special-uploads.svg'
            )
        ),
        'content-table' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-table.svg'
            )
        ),
        'content-text' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-text.svg'
            )
        ),
        'content-textpic' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-textpic.svg'
            )
        ),
        'content-textmedia' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-textmedia.svg'
            )
        ),

        // Default
        'default-not-found' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/default/default-not-found.svg'
            )
        ),

        // Mimetypes
        'mimetypes-application' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-application.svg'
            )
        ),
        'mimetypes-compressed' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-compressed.svg'
            )
        ),
        'mimetypes-excel' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-excel.svg'
            )
        ),
        'mimetypes-media-audio' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-audio.svg'
            )
        ),
        'mimetypes-media-flash' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-flash.svg'
            )
        ),
        'mimetypes-media-image' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-image.svg'
            )
        ),
        'mimetypes-media-video-vimeo' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-video-vimeo.svg'
            )
        ),
        'mimetypes-media-video-youtube' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-video-youtube.svg'
            )
        ),
        'mimetypes-media-video' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-media-video.svg'
            )
        ),
        'mimetypes-open-document-database' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-database.svg'
            )
        ),
        'mimetypes-open-document-drawing' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-drawing.svg'
            )
        ),
        'mimetypes-open-document-formula' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-formula.svg'
            )
        ),
        'mimetypes-open-document-presentation' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-presentation.svg'
            )
        ),
        'mimetypes-open-document-spreadsheet' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-spreadsheet.svg'
            )
        ),
        'mimetypes-open-document-text' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-open-document-text.svg'
            )
        ),
        'mimetypes-other-other' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-other-other.svg'
            )
        ),
        'mimetypes-pdf' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-pdf.svg'
            )
        ),
        'mimetypes-powerpoint' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-powerpoint.svg'
            )
        ),
        'mimetypes-text-css' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-css.svg'
            )
        ),
        'mimetypes-text-csv' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-csv.svg'
            )
        ),
        'mimetypes-text-html' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-html.svg'
            )
        ),
        'mimetypes-text-js' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-js.svg'
            )
        ),
        'mimetypes-text-php' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-php.svg'
            )
        ),
        'mimetypes-text-text' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-text.svg'
            )
        ),
        'mimetypes-text-ts' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-ts.svg'
            )
        ),
        'mimetypes-word' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-word.svg'
            )
        ),
        'mimetypes-x-backend_layout' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-backend_layout.svg'
            )
        ),
        'mimetypes-x-content-divider' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-divider.svg'
            )
        ),
        'mimetypes-x-content-domain' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-domain.svg'
            )
        ),
        'mimetypes-x-content-form-search' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-form-search.svg'
            )
        ),
        'mimetypes-x-content-form' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-form.svg'
            )
        ),
        'mimetypes-x-content-header' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-header.svg'
            )
        ),
        'mimetypes-x-content-html' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-html.svg'
            )
        ),
        'mimetypes-x-content-image' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-image.svg'
            )
        ),
        'mimetypes-x-content-link' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-link.svg'
            )
        ),
        'mimetypes-x-content-list-bullets' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-list-bullets.svg'
            )
        ),
        'mimetypes-x-content-list-files' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-list-files.svg'
            )
        ),
        'mimetypes-x-content-login' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-login.svg'
            )
        ),
        'mimetypes-x-content-menu' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-menu.svg'
            )
        ),
        'mimetypes-x-content-multimedia' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-multimedia.svg'
            )
        ),
        'mimetypes-x-content-page-language-overlay' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-page-language-overlay.svg'
            )
        ),
        'mimetypes-x-content-plugin' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-plugin.svg'
            )
        ),
        'mimetypes-x-content-script' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-script.svg'
            )
        ),
        'mimetypes-x-content-table' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-table.svg'
            )
        ),
        'mimetypes-x-content-template-extension' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-template-extension.svg'
            )
        ),
        'mimetypes-x-content-template-static' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-template-static.svg'
            )
        ),
        'mimetypes-x-content-template' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-template.svg'
            )
        ),
        'mimetypes-x-content-text-picture' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-text-picture.svg'
            )
        ),
        'mimetypes-x-content-text' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-text.svg'
            )
        ),
        'mimetypes-x-index_config' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-index_config.svg'
            )
        ),
        'mimetypes-x-sys_action' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_action.svg'
            )
        ),
        'mimetypes-x-sys_category' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_category.svg'
            )
        ),
        'mimetypes-x-sys_filemounts' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_filemounts.svg'
            )
        ),
        'mimetypes-x-sys_language' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_language.svg'
            )
        ),
        'mimetypes-x-sys_news' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_news.svg'
            )
        ),
        'mimetypes-x-sys_note' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_note.svg'
            )
        ),
        'mimetypes-x-sys_workspace' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-sys_workspace.svg'
            )
        ),
        'mimetypes-x-tx_rtehtmlarea_acronym' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-tx_rtehtmlarea_acronym.svg'
            )
        ),
        'mimetypes-x-tx_scheduler_task_group' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-tx_scheduler_task_group.svg'
            )
        ),
        'mimetypes-x-content-text-media' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-text-picture.svg'
            )
        ),
        'mimetypes-x-sys_file_storage' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:t3skin/icons/gfx/i/_icon_ftp.gif'
            )
        ),

        // Miscellaneous
        'miscellaneous-placeholder' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/miscellaneous/miscellaneous-placeholder.svg'
            )
        ),

        // Module
        'module-web' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'file-o'
            )
        ),
        'module-file' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'image'
            )
        ),
        'module-tools' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'rocket'
            )
        ),
        'module-system' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'plug'
            )
        ),
        'module-help' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'question-circle'
            )
        ),

        // Overlay
        'overlay-advanced' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-advanced.svg'
            )
        ),
        'overlay-approved' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-approved.svg'
            )
        ),
        'overlay-backenduser' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-backenduser.svg'
            )
        ),
        'overlay-backendusers' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-backendusers.svg'
            )
        ),
        'overlay-deleted' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-deleted.svg'
            )
        ),
        'overlay-edit' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-edit.svg'
            )
        ),
        'overlay-external-link' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-external-link.svg'
            )
        ),
        'overlay-frontenduser' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-frontenduser.svg'
            )
        ),
        'overlay-frontendusers' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-frontendusers.svg'
            )
        ),
        'overlay-hidden' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-hidden.svg'
            )
        ),
        'overlay-includes-subpages' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-includes-subpages.svg'
            )
        ),
        'overlay-info' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-info.svg'
            )
        ),
        'overlay-list' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-list.svg'
            )
        ),
        'overlay-locked' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-locked.svg'
            )
        ),
        'overlay-media' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-media.svg'
            )
        ),
        'overlay-missing' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-missing.svg'
            )
        ),
        'overlay-mountpoint' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-mountpoint.svg'
            )
        ),
        'overlay-new' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-new.svg'
            )
        ),
        'overlay-news' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-news.svg'
            )
        ),
        'overlay-readonly' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-readonly.svg'
            )
        ),
        'overlay-restricted' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-restricted.svg'
            )
        ),
        'overlay-scheduled' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-scheduled.svg'
            )
        ),
        'overlay-shop' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-shop.svg'
            )
        ),
        'overlay-shortcut' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-shortcut.svg'
            )
        ),
        'overlay-translated' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-translated.svg'
            )
        ),
        'overlay-warning' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/overlay/overlay-warning.svg'
            )
        ),

        // Spinner
        'spinner-circle-dark' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/spinner/spinner-circle-dark.svg',
                'spinning' => true
            )
        ),
        'spinner-circle-light' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/spinner/spinner-circle-light.svg',
                'spinning' => true
            )
        ),
        'spinner-circle' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/spinner/spinner-circle.svg',
                'spinning' => true
            )
        ),

        // Status
        'status-user-admin' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/status/status-user-admin.svg'
            )
        ),
        'status-user-backend' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/status/status-user-backend.svg'
            )
        ),
        'status-user-frontend' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/status/status-user-frontend.svg'
            )
        ),
        'status-user-group-backend' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/status/status-user-group-backend.svg'
            )
        ),
        'status-user-group-frontend' => array(
            'provider' => SvgIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/T3Icons/status/status-user-group-frontend.svg'
            )
        ),
        'status-dialog-information' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'exclamation-circle'
            )
        ),
        'status-dialog-ok' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'check-circle',
            )
        ),
        'status-dialog-notification' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'exclamation-circle'
            )
        ),
        'status-dialog-warning' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'exclamation-triangle'
            )
        ),
        'status-dialog-error' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'exclamation-circle'
            )
        ),
        'status-warning-lock' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:t3skin/images/icons/status/warning-lock.png'
            )
        ),
        'status-warning-in-use' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:t3skin/images/icons/status/warning-in-use.png'
            )
        ),
        'status-status-checked' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'check',
            )
        ),
        'status-status-current' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'caret-right',
            )
        ),
        'status-status-locked' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'lock',
            )
        ),
        'status-status-reference-hard' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:t3skin/images/icons/status/status-reference-hard.png',
            )
        ),
        'status-status-sorting-asc' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'caret-up',
            )
        ),
        'status-status-sorting-desc' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'caret-down',
            )
        ),
        'status-status-sorting-light-asc' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'caret-up',
            )
        ),
        'status-status-sorting-light-desc' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'caret-down',
            )
        ),
        'status-status-permission-granted' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'check',
            )
        ),
        'status-status-permission-denied' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'times',
            )
        ),
        'status-status-reference-soft' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:t3skin/images/icons/status/status-reference-soft.png',
            )
        ),
        'status-status-edit-read-only' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:t3skin/images/icons/status/status-edit-read-only.png',
            )
        ),

        // Extensions
        'extensions-extensionmanager-update-script' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'refresh',
            )
        ),
        'extensions-scheduler-run-task' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'play-circle',
            )
        ),
        'extensions-workspaces-generatepreviewlink' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:workspaces/Resources/Public/Images/generate-ws-preview-link.png'
            )
        ),

        // Empty
        'empty-empty' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'empty-empty',
            )
        ),

        // System Information
        'sysinfo-php-version' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'code'
            )
        ),
        'sysinfo-database' =>  array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'database'
            )
        ),
        'sysinfo-application-context' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'tasks'
            )
        ),
        'sysinfo-composer-mode' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'music'
            )
        ),
        'sysinfo-git' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'git'
            )
        ),
        'sysinfo-webserver' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'server'
            )
        ),
        'sysinfo-os-linux' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'linux'
            )
        ),
        'sysinfo-os-apple' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'apple'
            )
        ),
        'sysinfo-os-windows' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'windows'
            )
        ),

        // Sysnote
        'sysnote-type-0' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'sticky-note-o'
            )
        ),
        'sysnote-type-1' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'cog'
            )
        ),
        'sysnote-type-2' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'code'
            )
        ),
        'sysnote-type-3' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'thumb-tack'
            )
        ),
        'sysnote-type-4' => array(
            'provider' => FontawesomeIconProvider::class,
            'options' => array(
                'name' => 'check-square'
            )
        ),

        // Flags will be auto-registered after we have the SVG files
        'flags-multiple' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/Flags/multiple.png'
            )
        ),
        'flags-an' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/Flags/an.png'
            )
        ),
        'flags-catalonia' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/Flags/catalonia.png'
            )
        ),
        'flags-cs' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/Flags/cs.png'
            )
        ),
        'flags-en-us-gb' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/Flags/en_us-gb.png'
            )
        ),
        'flags-fam' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/Flags/fam.png'
            )
        ),
        'flags-qc' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/Flags/qc.png'
            )
        ),
        'flags-scotland' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/Flags/scotland.png'
            )
        ),
        'flags-wales' => array(
            'provider' => BitmapIconProvider::class,
            'options' => array(
                'source' => 'EXT:core/Resources/Public/Icons/Flags/wales.png'
            )
        ),
    );

    /**
     * Mapping of file extensions to mimetypes
     *
     * @var string[]
     */
    protected $fileExtensionMapping = array(
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
    );

    /**
     * Mapping of mime types to icons
     *
     * @var string[]
     */
    protected $mimeTypeMapping = array(
        'video/*' => 'mimetypes-media-video',
        'audio/*' => 'mimetypes-media-audio',
        'image/*' => 'mimetypes-media-image',
        'text/*' => 'mimetypes-text-text',
    );

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
    protected $deprecatedIcons = array();

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
    public function registerIcon($identifier, $iconProviderClassName, array $options = array())
    {
        if (!in_array(IconProviderInterface::class, class_implements($iconProviderClassName), true)) {
            throw new \InvalidArgumentException('An IconProvider must implement '
                . IconProviderInterface::class, 1437425803);
        }
        $this->icons[$identifier] = array(
            'provider' => $iconProviderClassName,
            'options' => $options
        );
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
     * @internal
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
     *
     * @return void
     */
    protected function registerTCAIcons()
    {
        $resultArray = array();

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
            $this->icons[$iconIdentifier] = array(
                'provider' => $iconProviderClass,
                'options' => array(
                    'source' => $iconFilePath
                )
            );
        }
        $this->tcaInitialized = true;
    }

    /**
     * Register module icons
     *
     * @return void
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

            $this->icons[$iconIdentifier] = array(
                'provider' => $iconProviderClass,
                'options' => array(
                    'source' => $iconPath
                )
            );
        }
        $this->moduleIconsInitialized = true;
    }

    /**
     * Register flags
     */
    protected function registerFlags()
    {
        $iconFolder = 'EXT:core/Resources/Public/Icons/Flags/SVG/';
        $files = array(
            'AC', 'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
            'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ',
            'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
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
            'QA',
            'RE', 'RO', 'RS', 'RU', 'RW',
            'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ',
            'TA', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ',
            'UA', 'UG', 'UM', 'US-AK', 'US-AL', 'US-AR', 'US-AZ', 'US-CA', 'US-CO', 'US-CT', 'US-DE', 'US-FL', 'US-GA', 'US-HI', 'US-IA', 'US-ID', 'US-IL', 'US-IN', 'US-KS', 'US-KY', 'US-LA', 'US-MA', 'US-MD', 'US-ME', 'US-MI', 'US-MN', 'US-MO', 'US-MS', 'US-MT', 'US-NC', 'US-ND', 'US-NE', 'US-NH', 'US-NJ', 'US-NM', 'US-NV', 'US-NY', 'US-OH', 'US-OK', 'US-OR', 'US-PA', 'US-RI', 'US-SC', 'US-SD', 'US-TN', 'US-TX', 'US-UT', 'US-VA', 'US-VT', 'US-WA', 'US-WI', 'US-WV', 'US-WY', 'US', 'UY', 'UZ',
            'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU',
            'WF', 'WS',
            'XK',
            'YE', 'YT',
            'ZA', 'ZM', 'ZW'
        );
        foreach ($files as $file) {
            $identifier = strtolower($file);
            $this->icons['flags-' . $identifier] = array(
                'provider' => SvgIconProvider::class,
                'options' => array(
                    'source' => $iconFolder . $file . '.svg'
                )
            );
        }
        $this->flagsInitialized = true;
    }

    /**
     * Detect the IconProvider of an icon
     *
     * @param string $iconReference
     * @return string
     */
    protected function detectIconProvider($iconReference)
    {
        if (StringUtility::endsWith(strtolower($iconReference), 'svg')) {
            return SvgIconProvider::class;
        } else {
            return BitmapIconProvider::class;
        }
    }
}
