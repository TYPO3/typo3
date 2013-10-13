<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

$tca = array(
	'ctrl' => array(
		'type' => 'type',
		'typeicon_column' => 'type',
		'typeicon_classes' => array(
			'1' => 'mimetypes-text-text',
			'2' => 'mimetypes-media-image',
			'3' => 'mimetypes-media-audio',
			'4' => 'mimetypes-media-video',
			'5' => 'mimetypes-application',
			'default' => 'mimetypes-other-other'
		),
	),
	'types' => array(
		TYPO3\CMS\Core\Resource\File::FILETYPE_UNKNOWN => array('showitem' => '
								fileinfo, title, description, alternative, keywords, caption, download_name,

								--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
									--palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.visibility;10;; ,
									fe_groups,

								--div--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.metadata,
									creator, --palette--;;20;;,
									--palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.geo_location;40;;'),

		TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => array('showitem' => '
								fileinfo, title, description, alternative, keywords, caption, download_name,

								--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
									--palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.visibility;10;; ,
									fe_groups,

								--div--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.metadata,
									creator, --palette--;;20;;,
									--palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.geo_location;40;;,
									language'),

		TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => array('showitem' => '
								fileinfo, title, description, alternative, keywords, caption, download_name,

								--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
									--palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.visibility;10;; ,
									fe_groups,

								--div--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.metadata,
									creator, --palette--;;20;;,
									--palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.geo_location;40;; ,
									--palette--;;30;;,
									--palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.metrics;50;;'),

		TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => array('showitem' => '

								fileinfo, title, description, alternative, keywords, caption, download_name,

								--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
									--palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.visibility;10;; ,
									fe_groups,

								--div--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.metadata,
									duration,
									creator, --palette--;;20;;, language'),

		TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => array('showitem' => '
								fileinfo, title, description, alternative, keywords, caption, download_name,

								--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
									--palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.visibility;10;; ,
									fe_groups,

								--div--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.metadata,
									duration,
									creator, --palette--;;20;;, language'),

		TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => array('showitem' => '
								fileinfo, title, description, alternative, keywords, caption, download_name,

								--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
									--palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.visibility;10;; ,
									fe_groups,

								--div--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.metadata,
									creator, --palette--;;20;;,
									--palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.geo_location;40;; ,
									language, --palette--;LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.content_date;60;;, pages'),
	),
	'palettes' => array(
		'10' => array('showitem' => 'visible, status, ranking', 'canNotCollapse' => '1'),
		'20' => array('showitem' => 'publisher, source', 'canNotCollapse' => '1'),
		'30' => array('showitem' => 'latitude, longitude', 'canNotCollapse' => '1'),
		'40' => array('showitem' => 'location_country, location_region, location_city', 'canNotCollapse' => '1'),
		'50' => array('showitem' => 'width, height, unit, color_space', 'canNotCollapse' => '1'),
		'60' => array('showitem' => 'content_creation_date, content_modification_date', 'canNotCollapse' => '1'),
	),
	'columns' => array(
		'visible' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.visible',
			'config' => array(
				'type' => 'check',
				'default' => '1'
			),
		),
		'status' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.status',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.status.1',
						1,
						\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('filemetadata') . 'Resources/Public/Icons/status_1.png'
					),
					array(
						'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.status.2',
						2,
						\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('filemetadata') . 'Resources/Public/Icons/status_2.png'
					),
					array(
						'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.status.3',
						3,
						\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('filemetadata') . 'Resources/Public/Icons/status_3.png'
					),
				),
			),
		),
		'keywords' => array(
			'exclude' => 1,
			'l10n_mode' => 'prefixLangTitle',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.keywords',
			'config' => array(
				'placeholder' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:placeholder.keywords',
				'type' => 'input',
				'size' => 255,
				'eval' => 'trim'
			),
		),
		'caption' => array(
			'exclude' => 1,
			'l10n_mode' => 'prefixLangTitle',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.caption',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'creator_tool' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.creator_tool',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim'
			),
		),
		'download_name' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.download_name',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'creator' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.creator',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'publisher' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.publisher',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim'
			),
		),
		'source' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.source',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim'
			),
		),
		'location_country' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'l10n_display' => '',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.location_country',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim'
			),
		),
		'location_region' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'l10n_display' => '',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.location_region',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim'
			),
		),
		'location_city' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'l10n_display' => '',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.location_city',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim'
			),
		),
		'latitude' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.latitude',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '30',
				'default' => '0.00000000000000'
			),
		),
		'longitude' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.longitude',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '30',
				'default' => '0.00000000000000'
			),
		),
		'ranking' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.ranking',
			'config' => array(
				'type' => 'select',
				'minitems' => 1,
				'maxitems' => 1,
				'items' => array(
					array(1, 1),
					array(2, 2),
					array(3, 3),
					array(4, 4),
					array(5, 5),
				),
			),
		),
		'content_creation_date' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.content_creation_date',
			'config' => array(
				'type' => 'input',
				'size' => 12,
				'max' => 20,
				'eval' => 'date',
				'checkbox' => 1,
				'default' => time()
			),
		),
		'content_modification_date' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.content_modification_date',
			'config' => array(
				'type' => 'input',
				'size' => 12,
				'max' => 20,
				'eval' => 'date',
				'checkbox' => 1,
				'default' => time()
			),
		),
		'note' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.note',
			'config' => array(
				'type' => 'text',
				'cols' => 40,
				'rows' => 15,
				'eval' => 'trim'
			),
		),
		/*
		 * METRICS ###########################################
		 */
		'unit' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.unit',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', ''),
					array('px', 'px'),
					array('mm', 'mm'),
					array('cm', 'cm'),
					array('m', 'm'),
					array('p', 'p'),
				),
				'default' => '',
				'readOnly' => TRUE,
			),
		),
		'duration' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.duration',
			'config' => array(
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'int',
				'default' => '0'
			)
		),
		'color_space' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.color_space',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', ''),
					array('RGB', 'RGB'),
					array('CMYK', 'CMYK'),
					array('CMY', 'CMY'),
					array('YUV', 'YUV'),
					array('Grey', 'grey'),
					array('indexed', 'indx'),
				),
				'default' => '',
				'readOnly' => TRUE,
			)
		),
		'width' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.width',
			'config' => array(
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'int',
				'default' => '0',
				'readOnly' => TRUE,
			),
		),
		'height' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.height',
			'config' => array(
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'int',
				'default' => '0',
				'readOnly' => TRUE,
			),
		),
		'pages' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.pages',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim',
				'readOnly' => TRUE
			),
		),
		'language' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.language',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			)
		),
		'fe_groups' => array(
			'exclude' => 1,
			'l10n_mode' => 'exclude',
			'label' => 'LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:sys_file_metadata.fe_groups',
			'config' => array(
				'type' => 'select',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 9999,
				'autoSizeMax' => 30,
				'multiple' => 0,
				'foreign_table' => 'fe_groups',
				'MM' => 'sys_file_fegroups_mm',
			),
		),
	),
);


return \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($GLOBALS['TCA']['sys_file_metadata'], $tca);