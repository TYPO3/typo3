<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addUserTSConfig('
	options.saveDocView = 1
	options.saveDocNew = 1
	options.saveDocNew.pages = 0
	options.saveDocNew.pages_language_overlay = 0
');

t3lib_extMgm::addPageTSConfig('
mod.wizards.newContentElement.wizardItems {
	common.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common
	common.elements {
		text {
			icon = gfx/c_wiz/regular_text.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_regularText_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_regularText_description
			tt_content_defValues {
				CType = text
			}
		}
		textpic {
			icon = gfx/c_wiz/text_image_right.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_textImage_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_textImage_description
			tt_content_defValues {
				CType = textpic
				imageorient = 17
			}
		}
		image {
			icon = gfx/c_wiz/images_only.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_imagesOnly_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_imagesOnly_description
			tt_content_defValues {
				CType = image
				imagecols = 2
			}
		}
		bullets {
			icon = gfx/c_wiz/bullet_list.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_bulletList_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_bulletList_description
			tt_content_defValues {
				CType = bullets
			}
		}
		table {
			icon = gfx/c_wiz/table.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_table_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:common_table_description
			tt_content_defValues {
				CType = table
			}
		}

	}
	common.show = text,textpic,image,bullets,table

	special.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special
	special.elements {
		uploads {
			icon = gfx/c_wiz/filelinks.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_filelinks_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_filelinks_description
			tt_content_defValues {
				CType = uploads
			}
		}
		multimedia {
			icon = gfx/c_wiz/multimedia.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_multimedia_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_multimedia_description
			tt_content_defValues {
				CType = multimedia
			}
		}
		media {
			icon = gfx/c_wiz/multimedia.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_media_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_media_description
			tt_content_defValues {
				CType = media
			}
		}
		menu {
			icon = gfx/c_wiz/sitemap2.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_sitemap_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_sitemap_description
			tt_content_defValues {
				CType = menu
				menu_type = 2
			}
		}
		html {
			icon = gfx/c_wiz/html.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_plainHTML_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_plainHTML_description
			tt_content_defValues {
				CType = html
			}
		}
		div {
		 	icon = gfx/c_wiz/div.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_divider_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:special_divider_description
			tt_content_defValues {
				CType = div
			}
		}

	}
	special.show = uploads,media,menu,html,div

	forms.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms
	forms.elements {
		mailform {
			icon = gfx/c_wiz/mailform.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_mail_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_mail_description
			tt_content_defValues {
				CType = mailform
				bodytext (
# Example content:
Name: | *name = input,40 | Enter your name here
Email: | *email=input,40 |
Address: | address=textarea,40,5 |
Contact me: | tv=check | 1

|formtype_mail = submit | Send form!
|html_enabled=hidden | 1
|subject=hidden| This is the subject
				)
			}
		}
		search {
			icon = gfx/c_wiz/searchform.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_search_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_search_description
			tt_content_defValues {
				CType = search
			}
		}
		login {
			icon = gfx/c_wiz/login_form.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_login_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:forms_login_description
			tt_content_defValues {
				CType = login
			}
		}

	}
	forms.show = mailform,search,login

	plugins.header = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:plugins
	plugins.elements {
		general {
			icon = gfx/c_wiz/user_defined.gif
			title = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:plugins_general_title
			description = LLL:EXT:cms/layout/locallang_db_new_content_el.xml:plugins_general_description
			tt_content_defValues.CType = list
		}
	}
	plugins.show = *
}

');

$TYPO3_CONF_VARS['SYS']['contentTable'] = 'tt_content';
$TYPO3_CONF_VARS['FE']['eID_include']['tx_cms_showpic'] = 'EXT:cms/tslib/showpic.php';

$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['compat_version']['cms'] = array(
	'title' => 'CMS Frontend',
	'version' => 4000000,
	'description' => '<ul>' .
					'<li><p>Word separator character for simulateStaticDocument is changed from ' .
					'underscore (_) to hyphen (-) to make URLs more friendly for search engines' .
					'URLs that are already existing (e.g. external links to your site) will still work like before.</p>' .
					'<p>You can set the separator character back to an underscore by putting the following line into the '.
					'<strong>Setup</strong> section of your Page TypoScript template:</p>' .
					'<p style="margin-top: 5px; white-space: nowrap;"><code>config.simulateStaticDocuments_replacementChar = _</code></p></li>'.
					'<li><p>CSS Stylesheets and JavaScript are put into an external file by default.</p>'.
					'<p>Technically, that means that the default value of "config.inlineStyle2TempFile" is now set to "1" and that of "config.removeDefaultJS" to "external"</p></li>'.
					'</ul>',
);


	// registering hooks for the treelist cache
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:cms/tslib/hooks/class.tx_cms_treelistcacheupdate.php:&tx_cms_treelistCacheUpdate';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]  = 'EXT:cms/tslib/hooks/class.tx_cms_treelistcacheupdate.php:&tx_cms_treelistCacheUpdate';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][]     = 'EXT:cms/tslib/hooks/class.tx_cms_treelistcacheupdate.php:&tx_cms_treelistCacheUpdate';

if (TYPO3_MODE === 'FE') {
		// Register the core media wizard provider
	tslib_mediaWizardManager::registerMediaWizardProvider('tslib_mediaWizardCoreProvider');
		// register eID provider for ExtDirect for the frontend
	$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['ExtDirect'] = PATH_tslib . 'extdirecteid.php';
}

	// register search keys
$GLOBALS ['TYPO3_CONF_VARS']['SYS']['livesearch']['page'] = 'pages';
$GLOBALS ['TYPO3_CONF_VARS']['SYS']['livesearch']['content'] = 'tt_content';

?>