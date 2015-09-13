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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class IconRegistry, which makes it possible to register custom icons
 * from within an extension.
 */
class IconRegistry implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Registered icons
	 *
	 * @var array
	 */
	protected $icons = array(
		// Default icon, fallback
		'default-not-found' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Default/default-not-found.svg',
			)
		),

		// Action icons
		'apps-filetree-folder-default' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/apps/filetree-folder-default.png',
			)
		),
		'apps-filetree-mount' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/App/apps-filetree-mount.png'
			)
		),
		'apps-filetree-root' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/apps/filetree-root.png',
			)
		),
		'apps-toolbar-menu-cache' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'bolt',
			)
		),
		'apps-toolbar-menu-shortcut' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'star',
			)
		),
		'apps-toolbar-menu-workspace' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'th-large',
			)
		),
		'apps-toolbar-menu-actions' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'cog',
			)
		),
		'apps-pagetree-page-domain' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/apps/pagetree-page-domain.png',
			)
		),
		'apps-pagetree-page-default' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/apps/pagetree-page-default.png',
			)
		),
		'apps-pagetree-root' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/App/apps-pagetree-root.svg',
			)
		),
		'apps-toolbar-menu-opendocs' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'file',
			)
		),
		'apps-toolbar-menu-search' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/App/apps-toolbar-menu-search.svg',
			)
		),
		'actions-document-close' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'close',
			)
		),
		'actions-actions-document-duplicates-select' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/document-duplicates-select.png',
			)
		),
		'actions-document-edit-access' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'clock-o',
			)
		),
		'actions-document-export-t3d' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'download',
			)
		),
		'actions-document-history-open' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'history',
			)
		),
		'actions-document-info' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'info-circle',
			)
		),
		'actions-document-import-t3d' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'upload',
			)
		),
		'actions-document-localize' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/document-localize.png',
			)
		),
		'actions-document-move' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'arrows',
			)
		),
		'actions-document-new' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'plus-square',
			)
		),
		'actions-document-open' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'pencil',
			)
		),
		'actions-document-paste' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-document-paste.svg',
			)
		),
		'actions-document-paste-after' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-document-paste-after.svg',
			)
		),
		'actions-document-paste-before' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-document-paste-before.svg',
			)
		),
		'actions-document-paste-into' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-document-paste-into.svg',
			)
		),
		'actions-document-select' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'check-square-o',
			)
		),
		'actions-document-save' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-document-save.svg',
			)
		),
		'actions-document-save-cleartranslationcache' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-document-save-cleartranslationcache.svg',
			)
		),
		'actions-document-save-close' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-document-save-close.svg',
			)
		),
		'actions-document-save-new' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-document-save-new.svg',
			)
		),
		'actions-document-save-translation' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-document-save-translation.svg',
			)
		),
		'actions-document-save-view' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-document-save-view.svg',
			)
		),
		'actions-document-synchronize' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/document-synchronize.png'
			)
		),
		'actions-document-view' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'desktop',
			)
		),
		'actions-edit-copy' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'copy',
			)
		),
		'actions-edit-copy-release' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'copy',
			)
		),
		'actions-edit-cut' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'scissors',
			)
		),
		'actions-edit-cut-release' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'scissors',
			)
		),
		'actions-edit-download' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'download',
			)
		),
		'actions-edit-add' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'plus-circle',
			)
		),
		'actions-edit-delete' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'trash',
			)
		),
		'actions-edit-localize-status-low' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/edit-localize-status-low.png',
			)
		),
		'actions-edit-localize-status-high' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/edit-localize-status-high.png',
			)
		),
		'actions-edit-merge-localization' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/edit-merge-localization.png',
			)
		),
		'actions-edit-pick-date' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'calendar',
			)
		),
		'actions-edit-rename' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'quote-right',
			)
		),
		'actions-edit-hide' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'toggle-on',
			)
		),
		'actions-edit-replace' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'retweet',
			)
		),
		'actions-edit-restore' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/edit-restore.png',
			)
		),
		'actions-edit-restore-edit' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/edit-undelete-edit.png',
			)
		),
		'actions-edit-undo' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'undo',
			)
		),
		'actions-edit-unhide' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'toggle-off',
			)
		),
		'actions-edit-upload' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'upload',
			)
		),
		'actions-filter' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-filter.svg',
			)
		),
		'actions-insert-record' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/insert-record.png',
			)
		),
		'actions-insert-reference' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/insert-reference.png',
			)
		),
		'actions-markstate' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'square-o',
			)
		),
		'actions-page-new' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-page-new.svg',
			)
		),
		'actions-page-move' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-page-move.svg',
			),
		),
		'actions-move' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'arrows',
			)
		),
		'actions-move-down' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'angle-down',
			)
		),
		'actions-move-left' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'angle-left',
			)
		),
		'actions-move-move' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'bars',
			)
		),
		'actions-move-right' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'angle-right',
			)
		),
		'actions-move-to-bottom' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'angle-double-down',
			)
		),
		'actions-move-to-top' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'angle-double-up',
			)
		),
		'actions-move-up' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'angle-up',
			)
		),
		'actions-page-open' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-page-open.svg',
			)
		),
		'actions-pagetree-collapse' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'caret-right',
			)
		),
		'actions-pagetree-expand' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'caret-down',
			)
		),
		'actions-pagetree-mountroot' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'anchor',
			)
		),
		'actions-refresh' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-refresh.svg',
			)
		),
		'actions-selection-delete' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'trash',
			)
		),
		'actions-search' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-search.svg',
			)
		),
		'actions-system-backend-user-switch'  => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'sign-out',
			)
		),
		'actions-system-cache-clear' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'bolt',
			)
		),
		'actions-system-cache-clear-impact-low' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-system-cache-clear-impact-low.svg',
			)
		),
		'actions-system-cache-clear-impact-medium' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-system-cache-clear-impact-medium.svg',
			)
		),
		'actions-system-cache-clear-impact-high' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-system-cache-clear-impact-high.svg',
			)
		),
		'actions-system-help-open' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-system-help-open.svg',
			)
		),
		'actions-system-extension-configure' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'gear',
			)
		),
		'actions-system-extension-download' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'download',
			)
		),
		'actions-system-extension-install' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'plus-circle',
			)
		),
		'actions-system-extension-import' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'cloud-download',
			)
		),
		'actions-system-extension-uninstall' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'minus-square',
			)
		),
		'actions-system-extension-sqldump' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'database',
			)
		),
		'actions-system-extension-update' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'refresh',
			)
		),
		'actions-system-list-open' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'list-alt',
			)
		),
		'actions-system-shortcut-new' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'star',
			)
		),
		'actions-version-open' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'refresh',
			)
		),
		'actions-system-tree-search-open' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Action/actions-system-tree-search-open.svg',
			)
		),
		'actions-version-swap-version' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'exchange',
			)
		),
		'actions-unmarkstate' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'check-square-o',
			)
		),
		'actions-view-list-collapse' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'chevron-up',
			)
		),
		'actions-view-list-expand' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'chevron-down',
			)
		),
		'actions-view-go-back' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'angle-double-left',
			)
		),
		'actions-view-go-forward' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'angle-double-right',
			)
		),
		'actions-view-go-up' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'level-up',
			)
		),
		'actions-view-paging-first' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'step-backward',
			)
		),
		'actions-view-paging-last' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'step-forward',
			)
		),
		'actions-view-paging-previous' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'arrow-left',
			)
		),
		'actions-view-paging-next' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'arrow-right',
			)
		),
		'actions-view-table-collapse' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'chevron-left',
			)
		),
		'actions-view-table-expand' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'chevron-right',
			)
		),
		'actions-window-open' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'arrows-alt',
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

		// specials
		'empty-empty' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'empty-empty',
			)
		),

		// Miscellaneous icons
		'miscellaneous-placeholder' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Miscellaneous/miscellaneous-placeholder.svg',
			)
		),

		// Status
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
		'status-dialog-ok' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'check-circle',
			)
		),
		'status-dialog-notification' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'exclamation-circle',
			)
		),
		'status-dialog-warning' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'exclamation-triangle',
			)
		),
		'status-dialog-error' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'exclamation-circle',
			)
		),
		'status-warning-lock' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/status/warning-lock.png',
			)
		),
		'status-warning-in-use' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/status/warning-in-use.png',
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

		// Mimetypes
		'mimetypes-text-html' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'file-text-o',
			)
		),
		'mimetypes-text-csv' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/mimetypes/text-csv.png',
			)
		),
		'mimetypes-word' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'file-word-o',
			)
		),
		'mimetypes-x-content-page-language-overlay' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/mimetypes/x-content-page-language-overlay.gif',
			)
		),
		'mimetypes-x-content-template-static' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/mimetypes/x-content-template-static.png',
			)
		),

		// Spinner
		'spinner-circle-dark' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Spinner/spinner-circle-dark.svg',
				'spinning' => TRUE
			)
		),
		'spinner-circle-light' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Spinner/spinner-circle-light.svg',
				'spinning' => TRUE
			)
		),

		// Overlays
		'overlay-deleted' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Overlay/overlay-deleted.svg'
			)
		),
		'overlay-hidden' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Overlay/overlay-hidden.svg'
			)
		),
		'overlay-includes-subpages' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Overlay/overlay-includes-subpages.svg'
			)
		),
		'overlay-locked' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Overlay/overlay-locked.svg'
			)
		),
		'overlay-readonly' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Overlay/overlay-readonly.svg',
			)
		),
		'overlay-restricted' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Overlay/overlay-restricted.svg'
			)
		),
		'overlay-scheduled' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Overlay/overlay-scheduled.svg'
			)
		),
		'overlay-translated' => array(
			'provider' => SvgIconProvider::class,
			'options' => array(
				'source' => 'EXT:backend/Resources/Public/Icons/Overlay/overlay-translated.svg'
			)
		)
	);

	/**
	 * Array of deprecated icons, add deprecated icons to this array and remove it from registry
	 * - Index of this array contains the deprecated icon
	 * - Value of each entry must contain the deprecation message and can contain an identifier which replaces the old identifier
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
	protected $deprecatedIcons = array(
		'actions-system-refresh' => array(
			'replacement' => 'actions-refresh',
			'message' => '%s is deprecated since TYPO3 CMS 7, this icon will be removed in TYPO3 CMS 8'
		)
	);

	/**
	 * @var string
	 */
	protected $defaultIconIdentifier = 'default-not-found';

	/**
	 * @param string $identifier
	 *
	 * @return bool
	 */
	public function isRegistered($identifier) {
		return isset($this->icons[$identifier]);
	}

	/**
	 * @param string $identifier
	 *
	 * @return bool
	 */
	public function isDeprecated($identifier) {
		return isset($this->deprecatedIcons[$identifier]);
	}

	/**
	 * @return string
	 */
	public function getDefaultIconIdentifier() {
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
	public function registerIcon($identifier, $iconProviderClassName, array $options = array()) {
		if (!in_array(IconProviderInterface::class, class_implements($iconProviderClassName), TRUE)) {
			throw new \InvalidArgumentException('An IconProvider must implement ' . IconProviderInterface::class, 1437425803);
		}
		$this->icons[$identifier] = array(
			'provider' => $iconProviderClassName,
			'options' => $options
		);
	}

	/**
	 * Fetches the configuration provided by registerIcon()
	 *
	 * @param string $identifier the icon identifier
	 * @return mixed
	 * @throws Exception
	 */
	public function getIconConfigurationByIdentifier($identifier) {
		if (!$this->isRegistered($identifier)) {
			throw new Exception('Icon with identifier "' . $identifier . '" is not registered"', 1437425804);
		}
		if ($this->isDeprecated($identifier)) {
			$deprecationSettings = $this->getDeprecationSettings($identifier);
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
	 */
	public function getDeprecationSettings($identifier) {
		if (!$this->isDeprecated($identifier)) {
			throw new Exception('Icon with identifier "' . $identifier . '" is not deprecated"', 1437425804);
		}
		return $this->deprecatedIcons[$identifier];
	}

	/**
	 * @return array
	 * @internal
	 */
	public function getAllRegisteredIconIdentifiers() {
		return array_keys($this->icons);
	}

}
