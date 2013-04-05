<?php
return array(
	'ctrl' => array(
		'label' => 'domainName',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'title' => 'LLL:EXT:cms/locallang_tca.xlf:sys_domain',
		'iconfile' => 'domain.gif',
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-content-domain'
		),
		'searchFields' => 'domainName,redirectTo'
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden,domainName,redirectTo'
	),
	'columns' => array(
		'domainName' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_domain.domainName',
			'config' => array(
				'type' => 'input',
				'size' => '35',
				'max' => '80',
				'eval' => 'required,unique,lower,trim,domainname',
				'softref' => 'substitute'
			)
		),
		'redirectTo' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_domain.redirectTo',
			'config' => array(
				'type' => 'input',
				'size' => '35',
				'max' => '255',
				'default' => '',
				'eval' => 'trim',
				'softref' => 'substitute'
			)
		),
		'redirectHttpStatusCode' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_domain.redirectHttpStatusCode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:cms/locallang_tca.xlf:sys_domain.redirectHttpStatusCode.301', '301'),
					array('LLL:EXT:cms/locallang_tca.xlf:sys_domain.redirectHttpStatusCode.302', '302'),
					array('LLL:EXT:cms/locallang_tca.xlf:sys_domain.redirectHttpStatusCode.303', '303'),
					array('LLL:EXT:cms/locallang_tca.xlf:sys_domain.redirectHttpStatusCode.307', '307')
				),
				'size' => 1,
				'maxitems' => 1
			)
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'prepend_params' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_domain.prepend_params',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'forced' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:sys_domain.forced',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '1'
			)
		)
	),
	'types' => array(
		'1' => array('showitem' => 'hidden;;;;1-1-1,domainName;;1;;3-3-3,prepend_params,forced;;;;4-4-4')
	),
	'palettes' => array(
		'1' => array('showitem' => 'redirectTo, redirectHttpStatusCode')
	)
);
?>