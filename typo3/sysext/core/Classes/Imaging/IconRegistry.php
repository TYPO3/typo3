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
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'times-circle',
			)
		),

		// Action icons
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
		'actions-document-paste-after' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'clipboard',
			)
		),
		'actions-document-select' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'check-square-o',
			)
		),
		'actions-document-paste-into' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'clipboard',
			)
		),
		'actions-document-save' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/document-save.png'
			)
		),
		'actions-document-save-close' => array(
			'provider' => BitmapIconProvider::class,
			'options' => array(
				'source' => 'EXT:t3skin/images/icons/actions/document-save-close.png'
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
		'actions-edit-delete' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'trash',
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
		'actions-page-open' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'pencil-square-o',
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
		'actions-version-open' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'refresh',
			)
		),
		'actions-system-refresh' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'refresh',
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
		'actions-view-go-back' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'angle-double-left',
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

		// Status
		'status-status-current' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'caret-right',
			)
		),

		// Overlays
		'overlay-read-only' => array(
			'provider' => FontawesomeIconProvider::class,
			'options' => array(
				'name' => 'minus-circle',
			)
		),
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
		return !empty($this->icons[$identifier]);
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
		return $this->icons[$identifier];
	}

	/**
	 * @return array
	 * @internal
	 */
	public function getAllRegisteredIconIdentifiers() {
		return array_keys($this->icons);
	}
}
