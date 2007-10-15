<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2007 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * Configuration of the htmlArea RTE extension
 *
 * @author	Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 *
 * $Id$  *
 */

if (!defined ("TYPO3_MODE")) 	die ('Access denied.');

if(!$TYPO3_CONF_VARS['BE']['RTEenabled'])  $TYPO3_CONF_VARS['BE']['RTEenabled'] = 1;

// Registering the RTE object
$TYPO3_CONF_VARS['BE']['RTE_reg'][$_EXTKEY] = array('objRef' => 'EXT:'.$_EXTKEY.'/class.tx_rtehtmlarea_base.php:&tx_rtehtmlarea_base');

// Make the extension version number available to the extension scripts
require_once(t3lib_extMgm::extPath($_EXTKEY) . 'ext_emconf.php');
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['version'] = $EM_CONF[$_EXTKEY]['version'];

$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here:

if (strstr($_EXTCONF['defaultConfiguration'],'Minimal')) {
	$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['defaultConfiguration'] = 'Advanced';
} elseif (strstr($_EXTCONF['defaultConfiguration'],'Demo')) {
	$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['defaultConfiguration'] = 'Demo';
} else {
	$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['defaultConfiguration'] = 'Typical';
}
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableImages'] = $_EXTCONF['enableImages'] ? $_EXTCONF['enableImages'] : 0;
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableDAMBrowser'] = $_EXTCONF['enableDAMBrowser'] ? $_EXTCONF['enableDAMBrowser'] : 0;
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableAccessibilityIcons'] = $_EXTCONF['enableAccessibilityIcons'] ? $_EXTCONF['enableAccessibilityIcons'] : 0;
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableClickEnlarge'] = $_EXTCONF['enableClickEnlarge'] ? $_EXTCONF['enableClickEnlarge'] : 0;
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableMozillaExtension'] = $_EXTCONF['enableMozillaExtension'] ? $_EXTCONF['enableMozillaExtension'] : 0;
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['mozAllowClipboardURL'] = $_EXTCONF['mozAllowClipboardURL'] ? $_EXTCONF['mozAllowClipboardURL'] : 'http://typo3.org/fileadmin/dl/allowclipboard_helper/allowclipboard_helper-0.5.5-fx_mz_zm_deLabelFix.xpi';
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['forceCommandMode'] = $_EXTCONF["forceCommandMode"] ? $_EXTCONF["forceCommandMode"] : 0;
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableDebugMode'] = $_EXTCONF['enableDebugMode'] ? $_EXTCONF['enableDebugMode'] : 0;
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableCompressedScripts'] = $_EXTCONF['enableCompressedScripts'] ? $_EXTCONF['enableCompressedScripts'] : 0;
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['dictionaryList'] = $_EXTCONF["dictionaryList"] ? $_EXTCONF["dictionaryList"] : 'en';
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['defaultDictionary'] = $_EXTCONF["defaultDictionary"] ? $_EXTCONF["defaultDictionary"] : 'en';
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['AspellDirectory'] = $_EXTCONF["AspellDirectory"] ? $_EXTCONF["AspellDirectory"] : '/usr/bin/aspell';
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['noSpellCheckLanguages'] = $_EXTCONF["noSpellCheckLanguages"] ? $_EXTCONF["noSpellCheckLanguages"] : 'ja,km,ko,lo,th,zh,b5,gb';
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['HTMLAreaPluginList'] = $_EXTCONF["HTMLAreaPluginList"] ? $_EXTCONF["HTMLAreaPluginList"] : 'TableOperations,SpellChecker,ContextMenu,SelectColor,TYPO3Browsers,InsertSmiley,FindReplace,RemoveFormat,CharacterMap,QuickTag,InlineCSS,DynamicCSS,UserElements,TYPO3HtmlParser';
if ($_EXTCONF['plainImageMaxWidth']) $TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['plainImageMaxWidth'] = intval($_EXTCONF['plainImageMaxWidth']);
if ($_EXTCONF['plainImageMaxHeight']) $TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['plainImageMaxHeight'] = intval($_EXTCONF['plainImageMaxHeight']);

//$TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['safari_test'] = 0;
$TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableInOpera9'] = $_EXTCONF['enableInOpera9'] ? $_EXTCONF['enableInOpera9'] : 0;

	// Add default RTE transformation configuration
t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/res/proc/pageTSConfig.txt">');

	// Add default Page TSonfig RTE configuration
t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/res/' . strtolower($TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['defaultConfiguration']) . '/pageTSConfig.txt">');

	// Add default Page TSonfig RTE configuration for enabling images with the Minimal and Typical default configuration
if ($TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableImages']) {
	t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/res/image/pageTSConfig.txt">');
}

	// Add default Page TSonfig RTE configuration for enabling links accessibility icons
if ($TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableAccessibilityIcons']) {
	t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/res/accessibilityicons/pageTSConfig.txt">');
}

	// Register DAM element browser rendering
if (t3lib_extMgm::isLoaded('dam') && $TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableDAMBrowser']) {
	$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/browse_links.php']['browserRendering'][] = 'EXT:'.$_EXTKEY.'/mod4/class.tx_rtehtmlarea_dam_browse_media.php:&tx_rtehtmlarea_dam_browse_media';
}

	// Add default Page TSonfig RTE configuration for enabling the Click-enlarge feature
if ($TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['enableClickEnlarge']) {
	t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/res/clickenlarge/pageTSConfig.txt">');
}

	// Add default User TSonfig RTE configuration
t3lib_extMgm::addUserTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/res/' . strtolower($TYPO3_CONF_VARS['EXTCONF'][$_EXTKEY]['defaultConfiguration']) . 'userTSConfig.txt">');

	// Configure Lorem Ipsum hook to insert nonsense in wysiwyg mode
if (t3lib_extMgm::isLoaded('lorem_ipsum') && (TYPO3_MODE == 'BE')) {
    $TYPO3_CONF_VARS['EXTCONF']['lorem_ipsum']['RTE_insert'][] = 'tx_rtehtmlarea_base->loremIpsumInsert';
}

?>
