<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * File Abtraction Layer List Registry
 *
 * @todo Andy Grunwald, 01.12.2010, Rename class to match naming conventions? new name tx_fal_mod_extjs_Registry?
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
final class tx_fal_list_Registry {

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined]	$filename	DESCRIPTION
	 * @return	void
	 */
	public static function registerModCssComponent($filename){
		self::registerCssComponent($filename, TRUE, FALSE);
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined]	$filename	DESCRIPTION
	 * @return	void
	 */
	public static function registerEbCssComponent($filename){
		self::registerCssComponent($filename, FALSE, TRUE);
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined]	$filename		DESCRIPTION
	 * @param	boolean			$addToFalList	DESCRIPTION
	 * @param	boolean			$addToEB		DESCRIPTION
	 * @return	void
	 */
	public static function registerCssComponent($filename, $addToFalList = TRUE, $addToEB = TRUE){
		$resolvedFilename = self::resolveFileName($filename);
		if($resolvedFilename !== ''){
			if($addToFalList){
				self::addToConf('cssComponents', 'fallist', $filename);
			}
			if($addToEB){
				self::addToConf('cssComponents', 'elementbrowser', $filename);
			}
		}
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @return	[to be defined]		DESCRIPTION
	 */
	public static function areModCssComponentsRegistered(){
		return self::areCssComponentsRegisteredFor('fallist');
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @return	[to be defined]		DESCRIPTION
	 */
	public static function areEbCssComponentsRegistered(){
		return self::areCssComponentsRegisteredFor('elementbrowser');
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined]		$subType	DESCRIPTION
	 * @return	[to be defined]					DESCRIPTION
	 */
	protected static function areCssComponentsRegisteredFor($subType){
		return is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fal_list']['cssComponents'][$subType]);
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	t3lib_PageRenderer		$pageRenderer	DESCRIPTION
	 * @return	void
	 */
	public static function addModCssComponentsToPage(t3lib_PageRenderer &$pageRenderer){
		if(self::areModCssComponentsRegistered()){
			self::addCssComponentsToPage($pageRenderer, 'fallist');
		}
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	t3lib_PageRenderer		$pageRenderer	DESCRIPTION
	 * @return	void
	 */
	public static function addEbCssComponentsToPage(t3lib_PageRenderer &$pageRenderer){
		if(self::areEbCssComponentsRegistered()){
			self::addCssComponentsToPage($pageRenderer, 'elementbrowser');
		}
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	t3lib_PageRenderer		$pageRenderer	DESCRIPTION
	 * @param	[to be defined]			$subType		DESCRIPTION
	 * @return	void
	 */
	protected static function addCssComponentsToPage(t3lib_PageRenderer &$pageRenderer, $subType = 'fallist'){
		foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fal_list']['cssComponents'][$subType] as $component) {
			$pageRenderer->addCssFile(self::resolveFileName($component));
		}
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined]		$filename	DESCRIPTION
	 * @return	void
	 */
	public static function registerModJsComponent($filename){
		self::registerJsComponent($filename, TRUE, FALSE);
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined]		$filename	DESCRIPTION
	 * @return	void
	 */
	public static function registerEbJsComponent($filename){
		self::registerJsComponent($filename, FALSE, TRUE);
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined]		$filename		DESCRIPTION
	 * @param	boolean				$addToFalList	DESCRIPTION
	 * @param	boolean				$addToEB		DESCRIPTION
	 * @return	void
	 */
	public static function registerJsComponent($filename, $addToFalList = TRUE, $addToEB = TRUE){
		$resolvedFilename = self::resolveFileName($filename);
		if($resolvedFilename !== ''){
			if($addToFalList){
				self::addToConf('jsComponents', 'fallist', $filename);
			}
			if($addToEB){
				self::addToConf('jsComponents', 'elementbrowser', $filename);
			}
		}
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined]		$ns				DESCRIPTION
	 * @param	boolean				$addToFalList	DESCRIPTION
	 * @param	boolean				$addToEB		DESCRIPTION
	 * @return	void
	 */
	public static function registerExtDirectNamespace($ns, $addToFalList = true, $addToEB = true){
		if($addToFalList){
			self::addToConf('extDirectNamespaces', 'fallist', $ns);
		}
		if($addToEB){
			self::addToConf('extDirectNamespaces', 'elementbrowser', $ns);
		}
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined]		$type		DESCRIPTION
	 * @param	[to be defined]		$subtype	DESCRIPTION
	 * @param	[to be defined]		$value		DESCRIPTION
	 * @return	void
	 */
	protected static function addToConf($type, $subtype, $value){
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fal_list'][$type][$subtype][] = $value;
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @return	[to be defined]		DESCRIPTION
	 */
	public static function areModJsComponentsRegistered(){
		return self::areJsComponentsRegisteredFor('fallist');
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @return	[to be defined]		DESCRIPTION
	 */
	public static function areEbJsComponentsRegistered(){
		return self::areJsComponentsRegisteredFor('elementbrowser');
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined]		$subType	DESCRIPTION
	 * @return	[to be defined]		DESCRIPTION
	 */
	protected static function areJsComponentsRegisteredFor($subType){
		return is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fal_list']['jsComponents'][$subType]);
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @return	[to be defined]		DESCRIPTION
	 */
	public static function areModExtDirectNamespacesRegistered(){
		return self::areExtDirectNamespacesRegisteredFor('fallist');
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @return	[to be defined]		DESCRIPTION
	 */
	public static function areEbExtDirectNamespacesRegistered(){
		return self::areExtDirectNamespacesRegisteredFor('elementbrowser');
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined		$subType	DESCRIPTION
	 * @return	[to be defined]					DESCRIPTION
	 */
	protected static function areExtDirectNamespacesRegisteredFor($subType){
		return is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fal_list']['extDirectNamespaces'][$subType]);
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	t3lib_PageRenderer	$pageRenderer	DESCRIPTION
	 * @return	void
	 */
	public static function addModJsComponentsToPage(t3lib_PageRenderer &$pageRenderer){
		if(self::areModJsComponentsRegistered()){
			self::addJsComponentsToPage($pageRenderer, 'fallist');
		}
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	t3lib_PageRenderer	$pageRenderer	DESCRIPTION
	 * @return	void
	 */
	public static function addEbJsComponentsToPage(t3lib_PageRenderer &$pageRenderer){
		if(self::areEbJsComponentsRegistered()){
			self::addJsComponentsToPage($pageRenderer, 'elementbrowser');
		}
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	t3lib_PageRenderer	$pageRenderer	DESCRIPTION
	 * @param	[to be defined]		$subType		DESCRIPTION
	 * @return	void
	 */
	protected static function addJsComponentsToPage(t3lib_PageRenderer &$pageRenderer, $subType = 'fallist'){
		$pageRenderer->addJsFile(self::resolveFileName('EXT:fal/res/js/Application.js'));
		$pageRenderer->addJsFile(self::resolveFileName('EXT:fal/res/js/ComponentRegistry.js'));
		foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fal_list']['jsComponents'][$subType] as $component) {
			$pageRenderer->addJsFile(self::resolveFileName($component));
		}
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	t3lib_PageRenderer	$pageRenderer	DESCRIPTION
	 * @return	void
	 */
	public static function addModExtDirectNamespacesToPage(t3lib_PageRenderer &$pageRenderer){
		if(self::areModExtDirectNamespacesRegistered()){
			self::addExtDirectNamespacesToPage($pageRenderer, 'fallist');
		}
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	t3lib_PageRenderer	$pageRenderer	DESCRIPTION
	 * @return	void
	 */
	public static function addEbExtDirectNamespacesToPage(t3lib_PageRenderer &$pageRenderer){
		if(self::areEbExtDirectNamespacesRegistered()){
			self::addExtDirectNamespacesToPage($pageRenderer, 'elementbrowser');
		}
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	t3lib_PageRenderer		$pageRenderer	DESCRIPTION
	 * @param	[to be defined]			$subType		DESCRIPTION
	 * @return	void
	 */
	public static function addExtDirectNamespacesToPage(t3lib_PageRenderer &$pageRenderer, $subType = 'fallist'){
		foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fal_list']['extDirectNamespaces'][$subType] as $nameSpace) {
			$pageRenderer->addJsFile($pageRenderer->backPath . 'ajax.php?ajaxID=ExtDirect::getAPI&namespace='.$nameSpace);
		}
		$pageRenderer->addExtDirectCode();
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	[to be defined]		$filename		DESCRIPTION
	 * @return	[to be defined]						DESCRIPTION
	 */
	protected static function resolveFileName($filename){
		if (substr($filename,0,4)=='EXT:')	{	// extension
			list($extKey,$local) = explode('/',substr($filename,4),2);
			$filename='';
			if (strcmp($extKey,'') && t3lib_extMgm::isLoaded($extKey) && strcmp($local,''))	{
				$filename = t3lib_extMgm::extRelPath($extKey).$local;
			}
		}
		return $filename;
	}
}
?>