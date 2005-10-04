<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Dynamic configuation of the tt_content table
 * This gets it's own file because it's so huge and central to typical TYPO3 use.
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */



$TCA['tt_content'] = Array (
	'ctrl' => $TCA['tt_content']['ctrl'],
	'interface' => Array (
		'always_description' => 0,
		'showRecordFieldList' => 'CType,header,header_link,bodytext,image,imagewidth,imageorient,media,records,colPos,starttime,endtime,fe_group'
	),
	'columns' => Array (
		'CType' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.0', 'header'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.1', 'text'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.2', 'textpic'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.3', 'image'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.4', 'bullets'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.5', 'table'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.6', 'uploads'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.7', 'multimedia'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.8', 'mailform'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.9', 'search'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.10', 'login'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.11', 'splash'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.12', 'menu'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.13', 'shortcut'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.14', 'list'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.15', 'script'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.16', 'div'),
					Array('LLL:EXT:cms/locallang_ttc.php:CType.I.17', 'html')
				),
				'default' => 'text',
				'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
				'authMode_enforce' => 'strict',
			)
		),
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check'
			)
		),
		'starttime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'endtime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array (
					'upper' => mktime(0,0,0,12,31,2020),
				)
			)
		),
		'fe_group' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
			'config' => Array (
				'type' => 'select',
				'size' => 5,
				'maxitems' => 20,
				'items' => Array (
					Array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
					Array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tt_content',
				'foreign_table_where' => 'AND tt_content.pid=###CURRENT_PID### AND tt_content.sys_language_uid IN (-1,0)',
			)
		),
		'layout' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.layout',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_general.php:LGL.normal', '0'),
					Array('LLL:EXT:cms/locallang_ttc.php:layout.I.1', '1'),
					Array('LLL:EXT:cms/locallang_ttc.php:layout.I.2', '2'),
					Array('LLL:EXT:cms/locallang_ttc.php:layout.I.3', '3')
				),
				'default' => '0'
			)
		),
		'colPos' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:colPos',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:cms/locallang_ttc.php:colPos.I.0', '1'),
					Array('LLL:EXT:lang/locallang_general.php:LGL.normal', '0'),
					Array('LLL:EXT:cms/locallang_ttc.php:colPos.I.2', '2'),
					Array('LLL:EXT:cms/locallang_ttc.php:colPos.I.3', '3')
				),
				'default' => '0'
			)
		),
		'date' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:date',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'header' => Array (
			'l10n_mode' => 'prefixLangTitle',
			'l10n_cat' => 'text',
			'label' => 'LLL:EXT:cms/locallang_ttc.php:header',
			'config' => Array (
				'type' => 'input',
				'max' => '256'
			)
		),
		'header_position' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:header_position',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', ''),
					Array('LLL:EXT:cms/locallang_ttc.php:header_position.I.1', 'center'),
					Array('LLL:EXT:cms/locallang_ttc.php:header_position.I.2', 'right'),
					Array('LLL:EXT:cms/locallang_ttc.php:header_position.I.3', 'left')
				),
				'default' => ''
			)
		),
		'header_link' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:header_link',
			'config' => Array (
				'type' => 'input',
				'size' => '15',
				'max' => '256',
				'checkbox' => '',
				'eval' => 'trim',
				'wizards' => Array(
					'_PADDING' => 2,
					'link' => Array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				),
				'softref' => 'typolink'
			)
		),
		'header_layout' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_general.php:LGL.normal', '0'),
					Array('LLL:EXT:cms/locallang_ttc.php:header_layout.I.1', '1'),
					Array('LLL:EXT:cms/locallang_ttc.php:header_layout.I.2', '2'),
					Array('LLL:EXT:cms/locallang_ttc.php:header_layout.I.3', '3'),
					Array('LLL:EXT:cms/locallang_ttc.php:header_layout.I.4', '4'),
					Array('LLL:EXT:cms/locallang_ttc.php:header_layout.I.5', '5'),
					Array('LLL:EXT:cms/locallang_ttc.php:header_layout.I.6', '100')
				),
				'default' => '0'
			)
		),
		'subheader' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.subheader',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'max' => '256',
				'softref' => 'email[subst]'
			)
		),
		'bodytext' => Array (
			'l10n_mode' => 'prefixLangTitle',
			'l10n_cat' => 'text',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.text',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'wizards' => Array(
					'_PADDING' => 4,
					'RTE' => Array(
						'notNewRecords' => 1,
						'RTEonly' => 1,
						'type' => 'script',
						'title' => 'LLL:EXT:cms/locallang_ttc.php:bodytext.W.RTE',
						'icon' => 'wizard_rte2.gif',
						'script' => 'wizard_rte.php',
					),
					'table' => Array(
						'notNewRecords' => 1,
						'enableByTypeConfig' => 1,
						'type' => 'script',
						'title' => 'Table wizard',
						'icon' => 'wizard_table.gif',
						'script' => 'wizard_table.php',
						'params' => array('xmlOutput' => 0)
					),
					'forms' => Array(
						'notNewRecords' => 1,
						'enableByTypeConfig' => 1,
						'type' => 'script',
#						'hideParent' => array('rows' => 4),
						'title' => 'Forms wizard',
						'icon' => 'wizard_forms.gif',
						'script' => 'wizard_forms.php?special=formtype_mail',
						'params' => array('xmlOutput' => 0)
					)
				),
				'softref' => 'typolink_tag,images,email[subst],url'
			)
		),
		'text_align' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:text_align',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', ''),
					Array('LLL:EXT:cms/locallang_ttc.php:text_align.I.1', 'center'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_align.I.2', 'right'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_align.I.3', 'left')
				),
				'default' => ''
			)
		),
		'text_face' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:text_face',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value', '0'),
					Array('Times', '1'),
					Array('Verdana', '2'),
					Array('Arial', '3')
				),
				'default' => '0'
			)
		),
		'text_size' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:text_size',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value', '0'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_size.I.1', '1'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_size.I.2', '2'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_size.I.3', '3'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_size.I.4', '4'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_size.I.5', '5'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_size.I.6', '10'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_size.I.7', '11')
				),
				'default' => '0'
			)
		),
		'text_color' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:text_color',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value', '0'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.1', '1'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.2', '2'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.3', '200'),
					Array('-----','--div--'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.5', '240'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.6', '241'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.7', '242'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.8', '243'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.9', '244'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.10', '245'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.11', '246'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.12', '247'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.13', '248'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.14', '249'),
					Array('LLL:EXT:cms/locallang_ttc.php:text_color.I.15', '250')
				),
				'default' => '0'
			)
		),
		'text_properties' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:text_properties',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('LLL:EXT:cms/locallang_ttc.php:text_properties.I.0', ''),
					Array('LLL:EXT:cms/locallang_ttc.php:text_properties.I.1', ''),
					Array('LLL:EXT:cms/locallang_ttc.php:text_properties.I.2', ''),
					Array('LLL:EXT:cms/locallang_ttc.php:text_properties.I.3', '')
				),
				'cols' => 4
			)
		),
		'image' => Array (
#			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.images',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => '1000',
				'uploadfolder' => 'uploads/pics',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '200',
				'minitems' => '0',
				'autoSizeMax' => 40,
			)
		),
		'imagewidth' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:imagewidth',
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '999',
					'lower' => '25'
				),
				'default' => 0
			)
		),
		'imageheight' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:imageheight',
			'config' => Array (
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '700',
					'lower' => '25'
				),
				'default' => 0
			)
		),
		'imageorient' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:imageorient',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:cms/locallang_ttc.php:imageorient.I.0', 0, 'selicons/above_center.gif'),
					Array('LLL:EXT:cms/locallang_ttc.php:imageorient.I.1', 1, 'selicons/above_right.gif'),
					Array('LLL:EXT:cms/locallang_ttc.php:imageorient.I.2', 2, 'selicons/above_left.gif'),
					Array('LLL:EXT:cms/locallang_ttc.php:imageorient.I.3', 8, 'selicons/below_center.gif'),
					Array('LLL:EXT:cms/locallang_ttc.php:imageorient.I.4', 9, 'selicons/below_right.gif'),
					Array('LLL:EXT:cms/locallang_ttc.php:imageorient.I.5', 10, 'selicons/below_left.gif'),
					Array('LLL:EXT:cms/locallang_ttc.php:imageorient.I.6', 17, 'selicons/intext_right.gif'),
					Array('LLL:EXT:cms/locallang_ttc.php:imageorient.I.7', 18, 'selicons/intext_left.gif'),
					Array('LLL:EXT:cms/locallang_ttc.php:imageorient.I.8', '--div--'),
					Array('LLL:EXT:cms/locallang_ttc.php:imageorient.I.9', 25, 'selicons/intext_right_nowrap.gif'),
					Array('LLL:EXT:cms/locallang_ttc.php:imageorient.I.10', 26, 'selicons/intext_left_nowrap.gif')
				),
				'selicon_cols' => 6,
				'default' => '8',
				'iconsInOptionTags' => 1,
			)
		),
		'imageborder' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:imageborder',
			'config' => Array (
				'type' => 'check'
			)
		),
		'image_noRows' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:image_noRows',
			'config' => Array (
				'type' => 'check'
			)
		),
		'image_link' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:image_link',
			'config' => Array (
				'type' => 'input',
				'size' => '15',
				'max' => '256',
				'checkbox' => '',
				'eval' => 'trim',
				'wizards' => Array(
					'_PADDING' => 2,
					'link' => Array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				),
				'softref' => 'typolink[linkList]'
			)
		),
		'image_zoom' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:image_zoom',
			'config' => Array (
				'type' => 'check'
			)
		),
		'image_effects' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:image_effects',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:cms/locallang_ttc.php:image_effects.I.0', 0),
					Array('LLL:EXT:cms/locallang_ttc.php:image_effects.I.1', 1),
					Array('LLL:EXT:cms/locallang_ttc.php:image_effects.I.2', 2),
					Array('LLL:EXT:cms/locallang_ttc.php:image_effects.I.3', 3),
					Array('LLL:EXT:cms/locallang_ttc.php:image_effects.I.4', 10),
					Array('LLL:EXT:cms/locallang_ttc.php:image_effects.I.5', 11),
					Array('LLL:EXT:cms/locallang_ttc.php:image_effects.I.6', 20),
					Array('LLL:EXT:cms/locallang_ttc.php:image_effects.I.7', 23),
					Array('LLL:EXT:cms/locallang_ttc.php:image_effects.I.8', 25),
					Array('LLL:EXT:cms/locallang_ttc.php:image_effects.I.9', 26)
				)
			)
		),
		'image_frames' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:image_frames',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:cms/locallang_ttc.php:image_frames.I.0', 0),
					Array('LLL:EXT:cms/locallang_ttc.php:image_frames.I.1', 1),
					Array('LLL:EXT:cms/locallang_ttc.php:image_frames.I.2', 2),
					Array('LLL:EXT:cms/locallang_ttc.php:image_frames.I.3', 3),
					Array('LLL:EXT:cms/locallang_ttc.php:image_frames.I.4', 4),
					Array('LLL:EXT:cms/locallang_ttc.php:image_frames.I.5', 5),
					Array('LLL:EXT:cms/locallang_ttc.php:image_frames.I.6', 6),
					Array('LLL:EXT:cms/locallang_ttc.php:image_frames.I.7', 7),
					Array('LLL:EXT:cms/locallang_ttc.php:image_frames.I.8', 8)
				)
			)
		),
		'image_compression' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:image_compression',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0),
					Array('LLL:EXT:cms/locallang_ttc.php:image_compression.I.1', 1),
					Array('GIF/256', 10),
					Array('GIF/128', 11),
					Array('GIF/64', 12),
					Array('GIF/32', 13),
					Array('GIF/16', 14),
					Array('GIF/8', 15),
					Array('PNG', 39),
					Array('PNG/256', 30),
					Array('PNG/128', 31),
					Array('PNG/64', 32),
					Array('PNG/32', 33),
					Array('PNG/16', 34),
					Array('PNG/8', 35),
					Array('LLL:EXT:cms/locallang_ttc.php:image_compression.I.15', 21),
					Array('LLL:EXT:cms/locallang_ttc.php:image_compression.I.16', 22),
					Array('LLL:EXT:cms/locallang_ttc.php:image_compression.I.17', 24),
					Array('LLL:EXT:cms/locallang_ttc.php:image_compression.I.18', 26),
					Array('LLL:EXT:cms/locallang_ttc.php:image_compression.I.19', 28)
				)
			)
		),
		'imagecols' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:imagecols',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('1', 0),
					Array('2', 2),
					Array('3', 3),
					Array('4', 4),
					Array('5', 5),
					Array('6', 6),
					Array('7', 7),
					Array('8', 8)
				),
				'default' => 0
			)
		),
		'imagecaption' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.caption',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '3',
				'softref' => 'typolink_tag,images,email[subst],url'
			)
		),
		'imagecaption_position' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:imagecaption_position',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', ''),
					Array('LLL:EXT:cms/locallang_ttc.php:imagecaption_position.I.1', 'center'),
					Array('LLL:EXT:cms/locallang_ttc.php:imagecaption_position.I.2', 'right'),
					Array('LLL:EXT:cms/locallang_ttc.php:imagecaption_position.I.3', 'left')
				),
				'default' => ''
			)
		),
		'cols' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:cols',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:cms/locallang_ttc.php:cols.I.0', '0'),
					Array('1', '1'),
					Array('2', '2'),
					Array('3', '3'),
					Array('4', '4'),
					Array('5', '5'),
					Array('6', '6'),
					Array('7', '7'),
					Array('8', '8'),
					Array('9', '9')
				),
				'default' => '0'
			)
		),
		'pages' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.startingpoint',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'pages',
				'size' => '3',
				'maxitems' => '22',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		'recursive' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.recursive',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', '0'),
					Array('LLL:EXT:cms/locallang_ttc.php:recursive.I.1', '1'),
					Array('LLL:EXT:cms/locallang_ttc.php:recursive.I.2', '2'),
					Array('LLL:EXT:cms/locallang_ttc.php:recursive.I.3', '3'),
					Array('LLL:EXT:cms/locallang_ttc.php:recursive.I.4', '4'),
					Array('LLL:EXT:cms/locallang_ttc.php:recursive.I.5', '250')
				),
				'default' => '0'
			)
		),
		'menu_type' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:menu_type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:cms/locallang_ttc.php:menu_type.I.0', '0'),
					Array('LLL:EXT:cms/locallang_ttc.php:menu_type.I.1', '1'),
					Array('LLL:EXT:cms/locallang_ttc.php:menu_type.I.2', '4'),
					Array('LLL:EXT:cms/locallang_ttc.php:menu_type.I.3', '7'),
					Array('LLL:EXT:cms/locallang_ttc.php:menu_type.I.4', '2'),
					Array('LLL:EXT:cms/locallang_ttc.php:menu_type.I.5', '3'),
					Array('LLL:EXT:cms/locallang_ttc.php:menu_type.I.6', '5'),
					Array('LLL:EXT:cms/locallang_ttc.php:menu_type.I.7', '6')
				),
				'default' => '0'
			)
		),
		'list_type' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:list_type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('','')
				),
				'default' => '',
				'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
			)
		),
		'select_key' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.code',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '80',
				'eval' => 'trim'
			)
		),
		'table_bgColor' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:table_bgColor',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value', '0'),
					Array('LLL:EXT:cms/locallang_ttc.php:table_bgColor.I.1', '1'),
					Array('LLL:EXT:cms/locallang_ttc.php:table_bgColor.I.2', '2'),
					Array('LLL:EXT:cms/locallang_ttc.php:table_bgColor.I.3', '200'),
					Array('-----','--div--'),
					Array('LLL:EXT:cms/locallang_ttc.php:table_bgColor.I.5', '240'),
					Array('LLL:EXT:cms/locallang_ttc.php:table_bgColor.I.6', '241'),
					Array('LLL:EXT:cms/locallang_ttc.php:table_bgColor.I.7', '242'),
					Array('LLL:EXT:cms/locallang_ttc.php:table_bgColor.I.8', '243'),
					Array('LLL:EXT:cms/locallang_ttc.php:table_bgColor.I.9', '244')
				),
				'default' => '0'
			)
		),
		'table_border' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:table_border',
			'config' => Array (
				'type' => 'input',
				'size' => '3',
				'max' => '3',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '20',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'table_cellspacing' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:table_cellspacing',
			'config' => Array (
				'type' => 'input',
				'size' => '3',
				'max' => '3',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '200',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'table_cellpadding' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:table_cellpadding',
			'config' => Array (
				'type' => 'input',
				'size' => '3',
				'max' => '3',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '200',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'media' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:media',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => '',	// Must be empty for disallowed to work.
				'disallowed' => 'php,php3',
				'max_size' => '10000',
				'uploadfolder' => 'uploads/media',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '10',
				'minitems' => '0'
			)
		),
		'multimedia' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:multimedia',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'txt,html,htm,class,swf,swa,dcr,wav,avi,au,mov,asf,mpg,wmv,mp3',
				'max_size' => '10000',
				'uploadfolder' => 'uploads/media',
				'size' => '2',
				'maxitems' => '1',
				'minitems' => '0'
			)
		),
		'filelink_size' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:filelink_size',
			'config' => Array (
				'type' => 'check'
			)
		),
		'records' => Array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:records',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_content',
				'size' => '5',
				'maxitems' => '200',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		'spaceBefore' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:spaceBefore',
			'config' => Array (
				'type' => 'input',
				'size' => '3',
				'max' => '3',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '50',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'spaceAfter' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:spaceAfter',
			'config' => Array (
				'type' => 'input',
				'size' => '3',
				'max' => '3',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => Array (
					'upper' => '50',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'section_frame' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:section_frame',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', '0'),
					Array('LLL:EXT:cms/locallang_ttc.php:section_frame.I.1', '1'),
					Array('LLL:EXT:cms/locallang_ttc.php:section_frame.I.2', '5'),
					Array('LLL:EXT:cms/locallang_ttc.php:section_frame.I.3', '6'),
					Array('LLL:EXT:cms/locallang_ttc.php:section_frame.I.4', '10'),
					Array('LLL:EXT:cms/locallang_ttc.php:section_frame.I.5', '11'),
					Array('LLL:EXT:cms/locallang_ttc.php:section_frame.I.6', '12'),
					Array('LLL:EXT:cms/locallang_ttc.php:section_frame.I.7', '20'),
					Array('LLL:EXT:cms/locallang_ttc.php:section_frame.I.8', '21')
				),
				'default' => '0'
			)
		),
		'splash_layout' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:splash_layout',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value', '0'),
					Array('LLL:EXT:cms/locallang_ttc.php:splash_layout.I.1', '1'),
					Array('LLL:EXT:cms/locallang_ttc.php:splash_layout.I.2', '2'),
					Array('LLL:EXT:cms/locallang_ttc.php:splash_layout.I.3', '3'),
					Array('LLL:EXT:cms/locallang_ttc.php:splash_layout.I.4', '--div--'),
					Array('LLL:EXT:cms/locallang_ttc.php:splash_layout.I.5', '20'),
				),
				'default' => '0'
			)
		),
		'sectionIndex' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:sectionIndex',
			'config' => Array (
				'type' => 'check',
				'default' => 1
			)
		),
		'linkToTop' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:linkToTop',
			'config' => Array (
				'type' => 'check'
			)
		),
		'rte_enabled' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:rte_enabled',
			'config' => Array (
				'type' => 'check',
				'showIfRTE' => 1
			)
		),
		'pi_flexform' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.php:pi_flexform',
			'config' => Array (
				'type' => 'flex',
				'ds_pointerField' => 'list_type',
				'ds' => array(
					'default' => '
						<T3DataStructure>
						  <ROOT>
						    <type>array</type>
						    <el>
								<!-- Repeat an element like "xmlTitle" beneath for as many elements you like. Remember to name them uniquely  -->
						      <xmlTitle>
								<TCEforms>
									<label>The Title:</label>
									<config>
										<type>input</type>
										<size>48</size>
									</config>
								</TCEforms>
						      </xmlTitle>
						    </el>
						  </ROOT>
						</T3DataStructure>
					',
				)
			)
		),
		'tx_impexp_origuid' => Array('config'=>array('type'=>'passthrough')),
		'l18n_diffsource' => Array('config'=>array('type'=>'passthrough')),
		't3ver_label' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.versionLabel',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'max' => '30',
			)
		),
	),
	'types' => Array (
		'1' => 	Array('showitem' => 'CType'),
		'header' => 	Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2, subheader;;8'),
		'text' => 		Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2, bodytext;;9;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[flag=rte_enabled|mode=ts];3-3-3, rte_enabled, text_properties'),
		'textpic' => 	Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2, bodytext;;9;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[flag=rte_enabled|mode=ts];3-3-3, rte_enabled, text_properties, --div--, image;;;;4-4-4, imageorient;;2, imagewidth;;13,
			--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgLinks;7,
			--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgOptions;11,
			imagecaption;;5'),
		'rte' => 		Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2, bodytext;;;nowrap:richtext[*]:rte_transform[mode=ts_images-ts_reglinks];3-3-3'),
		'image' => 		Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2, image;;;;4-4-4, imageorient;;2, imagewidth;;13,
			--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgLinks;7,
			--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgOptions;11,
			 imagecaption;;5'),
		'bullets' => 	Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2, layout;;;;3-3-3, bodytext;;9;nowrap, text_properties'),
		'table' => 		Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2, layout;;10;button;3-3-3, cols, bodytext;;9;nowrap:wizards[table], text_properties'),
		'splash' => 	Array('showitem' => 'CType;;4;button;1-1-1, header;LLL:EXT:lang/locallang_general.php:LGL.name;;;2-2-2, splash_layout, bodytext;;;;3-3-3, image;;6'),
		'uploads' => 	Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2, media;;;;5-5-5,
			select_key;LLL:EXT:cms/locallang_ttc.php:select_key.ALT.uploads,
			layout;;10;button, filelink_size,
			imagecaption;LLL:EXT:cms/locallang_ttc.php:imagecaption.ALT.uploads;;nowrap'),
		'multimedia' =>	Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2, multimedia;;;;5-5-5, bodytext;LLL:EXT:lang/locallang_general.php:LGL.parameters;;nowrap'),
		'script' =>		Array('showitem' => 'CType;;4;button;1-1-1, header;LLL:EXT:lang/locallang_general.php:LGL.name;;;2-2-2, select_key;;;;5-5-5, pages;;12, bodytext;LLL:EXT:lang/locallang_general.php:LGL.parameters;;nowrap,
			imagecaption;LLL:EXT:cms/locallang_ttc.php:imagecaption.ALT.script'),
		'menu' => 		Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2, menu_type;;;;5-5-5, pages'),
		'mailform' => 	Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2,
			bodytext;LLL:EXT:cms/locallang_ttc.php:bodytext.ALT.mailform;;nowrap:wizards[forms];5-5-5,
			pages;LLL:EXT:cms/locallang_ttc.php:pages.ALT.mailform,
			subheader;LLL:EXT:cms/locallang_ttc.php:subheader.ALT.mailform'),
		'search' => 	Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2,
			pages;LLL:EXT:cms/locallang_ttc.php:pages.ALT.search;;;5-5-5'),
		'login' => 		Array('showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2,
			pages;LLL:EXT:cms/locallang_ttc.php:pages.ALT.login;;;5-5-5'),
		'shortcut' => 	Array('showitem' => 'CType;;4;button;1-1-1, header;LLL:EXT:lang/locallang_general.php:LGL.name;;;2-2-2, records;;;;5-5-5, layout'),
		'list' => 		Array(
							'showitem' => 'CType;;4;button;1-1-1, header;;3;;2-2-2, --div--, list_type;;;;5-5-5, layout, select_key, pages;;12',
							'subtype_value_field' => 'list_type',
							'subtypes_excludelist' => Array(
								'' => 'layout,select_key,pages',	// When no plugin is selected.
								'3' => 'layout',
//								'4' => 'layout',	// List type forum
								'2' => 'layout',
								'5' => 'layout',
								'9' => 'layout',
								'0' => 'layout',
								'6' => 'layout',
								'7' => 'layout',
								'1' => 'layout',
								'8' => 'layout',
								'indexed_search' => 'layout',
								'11' => 'layout',
								'20' => 'layout',
								'21' => 'layout'
							)
						),
		'div' => 		Array('showitem' => 'CType;;14;button;1-1-1, header;LLL:EXT:lang/locallang_general.php:LGL.name;;;2-2-2'),
		'html' => 		Array('showitem' => 'CType;;4;button;1-1-1, header;LLL:EXT:lang/locallang_general.php:LGL.name;;;2-2-2,
			bodytext;LLL:EXT:cms/locallang_ttc.php:bodytext.ALT.html;;nowrap;3-3-3')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'hidden, starttime, endtime, fe_group'),
		'2' => Array('showitem' => 'imagecols, image_noRows, imageborder'),
		'3' => Array('showitem' => 'header_position, header_layout, header_link, date'),
		'4' => Array('showitem' => 'sys_language_uid, l18n_parent, colPos, spaceBefore, spaceAfter, section_frame, sectionIndex, linkToTop'),
		'5' => Array('showitem' => 'imagecaption_position'),
		'6' => Array('showitem' => 'imagewidth,image_link'),
		'7' => Array('showitem' => 'image_link, image_zoom'),
		'8' => Array('showitem' => 'layout'),
		'9' => Array('showitem' => 'text_align,text_face,text_size,text_color'),
		'10' => Array('showitem' => 'table_bgColor, table_border, table_cellspacing, table_cellpadding'),
		'11' => Array('showitem' => 'image_compression, image_effects, image_frames'),
		'12' => Array('showitem' => 'recursive'),
		'13' => Array('showitem' => 'imageheight'),
		'14' => Array('showitem' => 'sys_language_uid, l18n_parent, colPos')
	)
);



?>
