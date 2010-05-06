<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
					array('LLL:EXT:cms/locallang_ttc.xml:CType.div.standard', '--div--'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.0', 'header', 'i/tt_content_header.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.1', 'text', 'i/tt_content.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.2', 'textpic', 'i/tt_content_textpic.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.3', 'image', 'i/tt_content_image.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.div.lists', '--div--'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.4', 'bullets', 'i/tt_content_bullets.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.5', 'table', 'i/tt_content_table.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.6', 'uploads', 'i/tt_content_uploads.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.div.forms', '--div--'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.8', 'mailform', 'i/tt_content_form.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.9', 'search', 'i/tt_content_search.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.10', 'login', 'i/tt_content_login.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.div.special', '--div--'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.7', 'multimedia', 'i/tt_content_mm.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.18', 'media', 'i/tt_content_mm.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.11', 'splash', 'i/tt_content_news.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.12', 'menu', 'i/tt_content_menu.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.13', 'shortcut', 'i/tt_content_shortcut.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.14', 'list', 'i/tt_content_list.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.15', 'script', 'i/tt_content_script.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.16', 'div', 'i/tt_content_div.gif'),
					array('LLL:EXT:cms/locallang_ttc.xml:CType.I.17', 'html', 'i/tt_content_html.gif')
				),
				'default' => 'text',
				'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
				'authMode_enforce' => 'strict',
				'iconsInOptionTags' => 1,
				'noIconsBelowSelect' => 1,
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
				'exclusiveKeys' => '-1,-2',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'ORDER BY fe_groups.title',
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
			'exclude' => 1,
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
			'exclude' => 1,
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
					'_VALIGN' => 'middle',
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
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
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
				'default' => '0',
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
					Array('1', 1),
					Array('2', 2),
					Array('3', 3),
					Array('4', 4),
					Array('5', 5),
					Array('6', 6),
					Array('7', 7),
					Array('8', 8)
				),
				'default' => 1
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
		'altText' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:image_altText',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '3'
			)
		),
		'titleText' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:image_titleText',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '3'
			)
		),
		'longdescURL' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.php:image_longdescURL',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '3'
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
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					),
				),
			),
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
					Array('', '', '')
				),
				'default' => '',
				'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
				'iconsInOptionTags' => 1,
				'noIconsBelowSelect' => 1,
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
				'disallowed' => PHP_EXTENSIONS_DEFAULT,
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
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
				'allowed' => 'txt,html,htm,class,swf,swa,dcr,wav,avi,au,mov,asf,mpg,wmv,mp3,mp4,m4v',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
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
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					),
				),
			),
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
			'l10n_display' => 'hideDiff',
			'label' => 'LLL:EXT:cms/locallang_ttc.php:pi_flexform',
			'config' => Array (
				'type' => 'flex',
				'ds_pointerField' => 'list_type,CType',
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
					',media' => file_get_contents(t3lib_extMgm::extPath('cms') . 'flexform_media.xml'),
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
				'max' => '255',
			)
		),
	),
	'types' => Array (
		'1' => 	Array('showitem' => 'CType'),
		'header' => 	Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, subheader;;8, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'text' => 		Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.1, bodytext;;9;richtext:rte_transform[flag=rte_enabled|mode=ts_css];3-3-3, rte_enabled, text_properties,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group',
		),
		'textpic' => 	Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.text, bodytext;;9;richtext:rte_transform[flag=rte_enabled|mode=ts_css];3-3-3, rte_enabled, text_properties,
							--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.media, image;;;;5-5-5, imageorient;;2,
							--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgDimensions;13,
							--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgLinks;7;;6-6-6,
							imagecaption;;5,altText;;;;7-7-7, titleText, longdescURL,
							--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgOptions;11;;8-8-8,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'rte' => 		Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2,
							--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.text, bodytext;;;nowrap:richtext[*]:rte_transform[mode=ts_images-ts_reglinks];3-3-3,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'image' => 		Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.media, image;;;;4-4-4, imageorient;;2,
							--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgDimensions;13,
							--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgLinks;7;;5-5-5,
							imagecaption;;5, altText;;;;6-6-6, titleText, longdescURL,
							--palette--;LLL:EXT:cms/locallang_ttc.php:ALT.imgOptions;11;;7-7-7,
			 				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'bullets' => 	Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;4-4-4,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.4, layout;;;;3-3-3, bodytext;;9;nowrap, text_properties,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'table' => 		Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;4-4-4,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.5, layout;;10;;3-3-3, cols, bodytext;;9;nowrap:wizards[table], text_properties,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'splash' => 	Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;LLL:EXT:lang/locallang_general.php:LGL.name;;;2-2-2,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.11, splash_layout, bodytext;;;;3-3-3, image;;6,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'uploads' => 	Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.6, media;;;;3-3-3,
							select_key;LLL:EXT:cms/locallang_ttc.php:select_key.ALT.uploads,
							layout;;10, filelink_size,
							imagecaption;LLL:EXT:cms/locallang_ttc.php:imagecaption.ALT.uploads;;nowrap,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'multimedia' =>	Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.7, multimedia;;;;3-3-3, bodytext;LLL:EXT:lang/locallang_general.php:LGL.parameters;;nowrap,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'media' =>	Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.18, pi_flexform;;;;3-3-3,
							bodytext;LLL:EXT:cms/locallang_ttc.xml:media.alternativeContent;9;richtext:rte_transform[flag=rte_enabled|mode=ts_css];4-4-4,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'script' =>		Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;LLL:EXT:lang/locallang_general.php:LGL.name;;;2-2-2,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.15, select_key;;;;3-3-3, pages;;12, bodytext;LLL:EXT:lang/locallang_general.php:LGL.parameters;;nowrap,
							imagecaption;LLL:EXT:cms/locallang_ttc.php:imagecaption.ALT.script, linkToTop;;;;4-4-4,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'menu' => 		Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.12, menu_type;;;;4-4-4, pages,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'mailform' => 	Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.8, bodytext;LLL:EXT:cms/locallang_ttc.php:bodytext.ALT.mailform;;nowrap:wizards[forms];3-3-3,
							pages;LLL:EXT:cms/locallang_ttc.php:pages.ALT.mailform,
							subheader;LLL:EXT:cms/locallang_ttc.php:subheader.ALT.mailform,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'search' => 	Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.9, pages;LLL:EXT:cms/locallang_ttc.php:pages.ALT.search,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'login' => 		Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.10, pages;LLL:EXT:cms/locallang_ttc.php:pages.ALT.login,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'shortcut' => 	Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;LLL:EXT:lang/locallang_general.php:LGL.name;;;2-2-2,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.13, records;;;;5-5-5, layout,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'list' => 		Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.plugin, list_type;;;;3-3-3, layout, select_key, pages;;12,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group',
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
								'11' => 'layout',
								'20' => 'layout',
								'21' => 'layout'
							)
						),
		'div' => 		Array(
			'showitem' => 'CType;;14;;1-1-1, hidden, header;LLL:EXT:lang/locallang_general.php:LGL.name;;;2-2-2, linkToTop,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		),
		'html' => 		Array(
			'showitem' => 'CType;;4;;1-1-1, hidden, header;LLL:EXT:lang/locallang_general.php:LGL.name;;;2-2-2, linkToTop;;;;3-3-3,
							--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.17, bodytext;LLL:EXT:cms/locallang_ttc.php:bodytext.ALT.html;;nowrap,
							--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group'
		)
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'starttime, endtime'),
		'2' => Array('showitem' => 'imagecols, image_noRows, imageborder'),
		'3' => Array('showitem' => 'header_position, header_layout, header_link, date'),
		'4' => Array('showitem' => 'sys_language_uid, l18n_parent, colPos, spaceBefore, spaceAfter, section_frame, sectionIndex'),
		'5' => Array('showitem' => 'imagecaption_position'),
		'6' => Array('showitem' => 'imagewidth,image_link'),
		'7' => Array('showitem' => 'image_link, image_zoom','canNotCollapse' => 1),
		'8' => Array('showitem' => 'layout'),
		'9' => Array('showitem' => 'text_align,text_face,text_size,text_color'),
		'10' => Array('showitem' => 'table_bgColor, table_border, table_cellspacing, table_cellpadding'),
		'11' => Array('showitem' => 'image_compression, image_effects, image_frames','canNotCollapse' => 1),
		'12' => Array('showitem' => 'recursive'),
		'13' => Array('showitem' => 'imagewidth, imageheight','canNotCollapse' => 1),
		'14' => Array('showitem' => 'sys_language_uid, l18n_parent, colPos'),
	)
);


?>