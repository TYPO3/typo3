<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Class for generation of the module menu.
 * Will make the vertical, horizontal, selectorbox based menus AND the "about modules" display.
 * Basically it traverses the module structure and generates output based on that.
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML compliant content
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   73: class alt_menu_functions
 *   94:     function topMenu($theModules,$dontLink=0,$backPath='',$descr=0)
 *  473:     function getNavFramePrefix($moduleInfo, $subModuleInfo=array())
 *  504:     function mIconFile($Ifilename,$backPath)
 *  519:     function mIconFilename($Ifilename,$backPath)
 *  531:     function topButtons()
 *  544:     function adminButtons()
 *  563:     function adminFunctions($backPath)
 *  596:     function wrapLinkWithAB($link)
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */









/**
 * Class with menu functions
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class alt_menu_functions {

		// Internal
	var $fsMod = array();

	/**
	 * Creates the menu of modules.
	 *
	 * $descr determines the type of menu made:
	 * 		0 = Ordinary vertical menu
	 * 		1 = Descriptions for 'About modules' display
	 * 		2 = selector-box menu
	 * 		3 = topmenu - horizontal line of icons!
	 * 		4 = part of JavaScript switch contruct for alt_main.php frameset.
	 *
	 * @param	array		$theModules is the output from load_modules class ($this->loadModules->modules)
	 * @param	boolean		$dontLink == true will prevent the output from being linked with A-tags (used in the 'beuser' extension)
	 * @param	string		$backPath must be the 'backPath' to PATH_typo3 from where the menu is displayed.
	 * @param	integer		$descr determines the type of menu made (see above)
	 * @return	string		The menu HTML
	 */
	function topMenu($theModules,$dontLink=0,$backPath='',$descr=0)	{

			// By default module sections are collapsable, only if they are explicitly turned off via TSconfig, they are not:
		$tmpArr = $GLOBALS['BE_USER']->getTSConfig('options.moduleMenuCollapsable');
		$collapsable = (isset($tmpArr['value']) && $tmpArr['value']==0) ? 0 : 1;
		unset($tmpArr);

			// Initialize vars:
		$final='';
		$menuCode='';
		$descrCode='';
		$collection=array();
		$menuCode_sub='';
		$selectItems=array();
		$mIcons=array();
		$mJScmds=array();
		$onBlur=$GLOBALS['CLIENT']['FORMSTYLE'] ? 'this.blur();' : '';

		$selectItems[] = '<option value="">[ '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:buttons.selMenu_modules',1).' ]</option>';
		$mC=0;

			// Get collapsed configuration
		if ($collapsable == 1) {
			$config = is_array ($GLOBALS['BE_USER']->uc['moduleData']['alt_menu.php']) ? $GLOBALS['BE_USER']->uc['moduleData']['alt_menu.php'] : array();
			$collapsedOverride = t3lib_div::_GP('collapsedOverride');
			if (is_array ($collapsedOverride)) {
				$config = array_merge ($config, $collapsedOverride);
			}

			if (t3lib_div::_GP('collapsableExpandAll') == 1) {
				$config['expandAll'] = t3lib_div::_GP('expandAll');
			}

			if ($config['expandAll'] && is_array($collapsedOverride)) {
				$config = $collapsedOverride;
			}

			$GLOBALS['BE_USER']->uc['moduleData']['alt_menu.php'] = $config;
			$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);

				// all items have to be expanded when expandAll is set
			if($config['expandAll'] == 1) {
				foreach($config as $key => $value) {
					if($key != 'expandAll')
						$config[$key] = 0;
				}
			}
		}

			// Traverse array with modules
		foreach ($theModules as $moduleName => $moduleInfo) {
			$mC++;

			$prefix = $this->getNavFramePrefix($moduleInfo);
			if ($prefix) {
				$this->fsMod[]='fsMod.recentIds["'.$moduleName.'"]="";';
			}

				// If there are submodules:
			if (is_array($moduleInfo['sub']))	{
					// Finding the default module to display
				if ($moduleInfo['defaultMod'])	{
					$link = $moduleInfo['sub'][$moduleInfo['defaultMod']]['script'];
				} else {
					reset($moduleInfo['sub']);
					$subTemp = current($moduleInfo['sub']);
					$link = $subTemp['script'];
				}
				$link_sub = 1;	// Tells that the main modules links to a submodule
				$link = '';	// Does not link to submodules...
			} else {
				$link = $moduleInfo['script'];
				$link_sub = 0;
			}

			$link = t3lib_div::resolveBackPath($link);

			$moduleKey = $moduleName.'_tab';
			$moduleCSSId = 'ID_'.t3lib_div::md5int($moduleName);

			$collection[$moduleKey] = array(
				'moduleName' => $moduleName,
				'title' => $GLOBALS['LANG']->moduleLabels['tabs'][$moduleKey],
				'onclick' => 'top.goToModule(\''.$moduleName.'\');',
			);

				// Creating image icon
			$image = @getimagesize($this->mIconFile($GLOBALS['LANG']->moduleLabels['tabs_images'][$moduleKey],$backPath));
			$imageCode='';
			$descr3_title = $GLOBALS['LANG']->moduleLabels['tabs'][$moduleKey].' ';
			if ($image)	{
				$Ifilename = $this->mIconFilename($GLOBALS['LANG']->moduleLabels['tabs_images'][$moduleKey],$backPath);
				$collection[$moduleKey]['icon'] = array($Ifilename, $image[3]);
				$imageCode = '<img src="'.$Ifilename.'" '.$image[3].' alt="" />';
				$descr3_imageCode = '<img src="'.$Ifilename.'" '.$image[3].' title="'.htmlspecialchars($descr3_title).'" alt="" />';
			} else {
				$descr3_imageCode = '<img'.t3lib_iconWorks::skinImg($backPath,'gfx/dummy_module.gif','width="14" height="12"').' title="'.htmlspecialchars($descr3_title).'" alt="" />';
			}

				// Creating the various links:
			$label = $GLOBALS['LANG']->moduleLabels['tabs'][$moduleKey];
			if ($link && $prefix)	$link=$prefix.rawurlencode($link);
			if ($link && !$dontLink)	{
				$label = '<a href="#" onclick="top.goToModule(\''.$moduleName.'\');'.$onBlur.'return false;">'.$label.'</a>';	//  && !$link_sub

				$mIcons[] = '<a href="#" onclick="top.goToModule(\''.$moduleName.'\');'.$onBlur.'return false;" class="c-mainitem" id="'.$moduleCSSId.'">'.$descr3_imageCode.'</a>';

				$JScmd = '
						top.content.location=top.getModuleUrl(top.TS.PATH_typo3+"'.$this->wrapLinkWithAB($link).'"+additionalGetVariables);
						top.highlightModuleMenuItem("'.$moduleCSSId.'",1);';
				$mJScmds[] = "case '".$moduleName."': \n ".$JScmd." \n break;";
			}

			$selectItems[] = '<option value="top.goToModule(\''.$moduleName.'\');">'.htmlspecialchars($GLOBALS['LANG']->moduleLabels['tabs'][$moduleKey]).'</option>';
			$label='&nbsp;<strong>'.$label.'</strong>&nbsp;';


				// make menu collapsable
			if($collapsable == 1 && is_array($moduleInfo['sub'])) {
				$collapseJS = 'onclick="window.location.href=\'alt_menu.php?collapsedOverride['.$moduleName.']='.($config[$moduleName] ? '0' : '1').'\'"';
				$collapseIcon = t3lib_iconWorks::getSpriteIcon('actions-view-table-' . ($config[$moduleName] ? 'expand' : 'collapse'), array('class' => 'c-iconCollapse'));
			} else {
				$collapseJS = $collapseIcon = '';
			}

				// Creating a main item for the vertical menu (descr=0)
			$menuCode.='
						<tr class="c-mainitem" id="'.$moduleCSSId.'">
							<td colspan="3" '.$collapseJS.' >'.$imageCode.'<span class="c-label">'.$label.'</span>'.$collapseIcon.'</td>
						</tr>';

				// Code for "About modules"
			$descrCode.='
						<tr class="c-mainitem">
							<td colspan="3">'.$imageCode.$label.'</td>
						</tr>';


				// Hide submodules when collapsed:
			if($collapsable == 1 && $config[$moduleName] == 1 && $descr == 0 && $config['expandAll'] != 1) {
				unset($moduleInfo['sub']);
			}

				// Traversing submodules
			$subCode='';
			if (is_array($moduleInfo['sub']))	{
				$collection[$moduleKey]['subitems'] = array();
				$c=0;
				foreach ($moduleInfo['sub'] as $subName => $subInfo) {
					if ($c==0)		{
						$subCode.='
								<tr class="c-first">
									<td colspan="3"></td>
								</tr>';
						$descrCode.='
								<tr class="c-first">
									<td colspan="3"></td>
								</tr>';
					}

					$link = t3lib_div::resolveBackPath($subInfo['script']);
					$prefix = $this->getNavFramePrefix($moduleInfo, $subInfo);

					$subKey = $moduleName.'_'.$subName.'_tab';
					$moduleCSSId = 'ID_'.t3lib_div::md5int($moduleName.'_'.$subName);

					$collection[$moduleKey]['subitems'][$subKey] = array(
						'moduleName' => $moduleName.'_'.$subName,
						'title' => $GLOBALS['LANG']->moduleLabels['tabs'][$subKey],
						'onclick' => 'top.goToModule(\''.$moduleName.'_'.$subName.'\');',
					);

						// Creating image icon
					$image = @getimagesize($this->mIconFile($GLOBALS['LANG']->moduleLabels['tabs_images'][$subKey],$backPath));
					$imageCode='';
					$descr3_title = $GLOBALS['LANG']->moduleLabels['tabs'][$subKey].': '.$GLOBALS['LANG']->moduleLabels['labels'][$subKey.'label'];
					if ($image)	{
						$Ifilename = $this->mIconFilename($GLOBALS['LANG']->moduleLabels['tabs_images'][$subKey],$backPath);
						$collection[$moduleKey]['subitems'][$subKey]['icon'] = array($Ifilename, $image[3]);
						$imageCode = '<img src="'.$Ifilename.'" '.$image[3].' title="'.htmlspecialchars($GLOBALS['LANG']->moduleLabels['labels'][$subKey.'label']).'" alt="" />';
						$descr3_imageCode = '<img src="'.$Ifilename.'" '.$image[3].' title="'.htmlspecialchars($descr3_title).'" alt="" />';
					} else {
						$descr3_imageCode = '<img'.t3lib_iconWorks::skinImg($backPath,'gfx/dummy_module.gif','width="14" height="12"').' title="'.htmlspecialchars($descr3_title).'" alt="" />';
					}

						// Label for submodule:
					$label = $GLOBALS['LANG']->moduleLabels['tabs'][$subKey];
					$label_descr = ' title="'.htmlspecialchars($GLOBALS['LANG']->moduleLabels['labels'][$subKey.'label']).'"';
					$flabel = htmlspecialchars($label);
					$origLink = $link;
					if ($link && $prefix)	$link=$prefix.rawurlencode($link);

						// Setting additional JavaScript if frameset script:
					$addJS = '';
					if ($moduleInfo['navFrameScript'])	{$addJS = "+'&id='+top.rawurlencodeAndRemoveSiteUrl(top.fsMod.recentIds['" . $moduleName . "'])";}

						// If there is a script to link to (and linking is not disabled.
					if ($link && !$dontLink)	{
							// For condensed mode, send &cMR parameter to frameset script.
						if ($addJS && $GLOBALS['BE_USER']->uc['condensedMode'])	{$addJS.=  "+(cMR?'&cMR=1':'')";}

							// Command for the selector box:
						$JScmd = '
								top.content.location=top.getModuleUrl(top.TS.PATH_typo3+"'.$this->wrapLinkWithAB($link).'"'.$addJS.'+additionalGetVariables);
								top.fsMod.currentMainLoaded="'.$moduleName.'";
								';

						if ($subInfo['navFrameScript'])	{
							$JScmd.='
								top.currentSubScript="'.$origLink.'";';
						}

							// If there is a frameset script in place:
						if (!$GLOBALS['BE_USER']->uc['condensedMode'] && $moduleInfo['navFrameScript'])	{

								// use special nav script from sub module, otherwise from the main module
							$subNavFrameScript = $subInfo['navFrameScript'] ? $subInfo['navFrameScript'] : $moduleInfo['navFrameScript'];
							$subNavFrameScript = t3lib_div::resolveBackPath($subNavFrameScript);

								// add GET params for sub module to the nav script
							$subNavFrameScript = $this->wrapLinkWithAB($subNavFrameScript).$subInfo['navFrameScriptParam'];

							$JScmd = '
								if (top.content.list_frame && top.fsMod.currentMainLoaded=="'.$moduleName.'") {
									top.currentSubScript="'.$origLink.'";
									top.content.list_frame.location=top.getModuleUrl(top.TS.PATH_typo3+"'.$this->wrapLinkWithAB($origLink).'"'.$addJS.'+additionalGetVariables);
									if(top.currentSubNavScript!="'.$subNavFrameScript.'") {
										top.currentSubNavScript="'.$subNavFrameScript.'";
										top.content.nav_frame.location=top.getModuleUrl(top.TS.PATH_typo3+"'.$subNavFrameScript.'");
									}
								} else {
									top.content.location=top.TS.PATH_typo3+(
										top.nextLoadModuleUrl?
										"'.($prefix?$this->wrapLinkWithAB($link).'&exScript=':'').'listframe_loader.php":
										"'.$this->wrapLinkWithAB($link).'"'.$addJS.'+additionalGetVariables
									);
									top.fsMod.currentMainLoaded="'.$moduleName.'";
									top.currentSubScript="'.$origLink.'";
								}
								';
						}
						$selectItems[] = '<option value="top.goToModule(\''.$moduleName.'_'.$subName.'\');">'.htmlspecialchars('- '.$label).'</option>';
						$onClickString = htmlspecialchars('top.goToModule(\''.$moduleName.'_'.$subName.'\');'.$onBlur.'return false;');

						$flabel = '<a href="#" onclick="'.$onClickString.'"'.$label_descr.'>'.htmlspecialchars($label).'</a>';

						$mIcons[] = '<a href="#" onclick="'.$onClickString.'"'.$label_descr.' class="c-subitem" id="'.$moduleCSSId.'">'.$descr3_imageCode.'</a>';

						$JScmd.= '
								top.highlightModuleMenuItem("'.$moduleCSSId.'");';
						$mJScmds[] = "case '".$moduleName.'_'.$subName."': \n ".$JScmd." \n break;";
					}

					$subCode.='
							<tr class="c-subitem-row" id="'.$moduleCSSId.'">
								<td></td>
								<td align="center">'.(!$GLOBALS['BE_USER']->uc['hideSubmoduleIcons']?$imageCode:'').'</td>
								<td class="c-subitem-label">'.$flabel.'</td>
							</tr>';

						// For "About modules":
					$moduleLabel = htmlspecialchars($GLOBALS['LANG']->moduleLabels['labels'][$subKey.'label']);
					$moduleLabelHtml = !empty($moduleLabel) ? '<strong>' . $moduleLabel . '</strong><br />' : '';
					$moduleDescription = $GLOBALS['LANG']->moduleLabels['labels'][$subKey.'descr'];

					$descrCode.='
							<tr class="c-subitem-row">
								<td align="center">'.$imageCode.'</td>
								<td>'.$flabel.'&nbsp;&nbsp;</td>';

					if (!empty($moduleLabel) || !empty($moduleDescription)) {
						$descrCode .= '
								<td class="module-description">' . $moduleLabelHtml . $moduleDescription . '</td>';
					} else {
						$descrCode .= '
								<td>&nbsp;</td>';
					}

					$descrCode .= '
							</tr>';

						// Possibly adding a divider line
					$c++;
					if ($c<count($moduleInfo['sub']))	{
							// Divider
						$subCode.='
							<tr class="c-divrow">
								<td colspan="3"><img'.t3lib_iconWorks::skinImg($backPath,'gfx/altmenuline.gif','width="105" height="3"').' alt="" /></td>
							</tr>';
					}
				}
					// Spacer gif for top menu:
				if (count($theModules)>$mC)	{
					$mIcons[]='<img src="'.$backPath.'gfx/acm_spacer2.gif" width="8" height="12" hspace="3" alt="" />';
				}
			}

			if (!empty($subCode)) {
					// Add spacers after each main section:
				$subCode .= '
						<tr class="c-endrow">
							<td colspan="3"></td>
						</tr>';
				$descrCode .= '
						<tr class="c-endrow">
							<td colspan="3"></td>
						</tr>';

					// Add sub-code:
				$menuCode .= $subCode;
			}
		}
			// $descr==0:	Ordinary vertical menu
		if ($menuCode)	{
			if($collapsable == 1 || $config['expandAll'] == 1) {
				$collapseAllHTML = '<tr class="c-endrow">
						<td></td>
						<td align="center">
								<form action="alt_menu.php" method="get">
									<input type="hidden" name="collapsableExpandAll" value="1" />
									<input type="checkbox" name="expandAll" id="expandall" value="1" onclick="this.form.submit();" '.($config['expandAll']?'checked="checked"':'').' />
								</form>
						</td>
						<td class="c-subitem-label"><label for="expandall">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.expandAll',1).'</label></td>
					</tr>';
			} else {
				$collapseAllHTML = '';
			}

			$final = '


				<!--
					Vertical module menu, shown in left frame of backend.
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-vmenu">
					'.$menuCode.'
					<tr class="c-endrow">
						<td colspan="3">'.t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'menu_modules', $GLOBALS['BACK_PATH']).'</td></tr>
					'.$collapseAllHTML.'
				</table>';
		}

			// Output for the "About modules" module
		if ($descr==1)	{
			$descrCode = '


				<!--
					Listing of modules, for Help > About modules
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-about-modules">
					'.$descrCode.'
				</table>';
			$final = $descrCode;
		}

			// selector-box menu
		if ($descr==2)	{

				// Add admin-functions for clearing caches:
			if ($GLOBALS['BE_USER']->isAdmin())	{
				$functionArray = $this->adminFunctions($backPath);
				if (count($functionArray))	{
					$selectItems[] = '<option value=""></option>';
					foreach($functionArray as $fAoptions)	{
						$selectItems[] = '<option value="'.htmlspecialchars("window.location.href='".$fAoptions['href']."';").'">[ '.htmlspecialchars($fAoptions['title']).' ]</option>';
					}
				}
			}

				// Logout item:
			$selectItems[] = '<option value=""></option>';
			$selectItems[] = '<option value="'.htmlspecialchars("top.location='logout.php';").'">[ '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:buttons.logout',1).' ]</option>';
			$final = '
				<select name="menuselector" onchange="eval(this.options[this.selectedIndex].value);">
					'.implode('
					',$selectItems).'
				</select>';
		}
			// topmenu - horizontal line of icons!
		if ($descr==3)	{
			$final = ''.implode('',$mIcons).'';
		}

			// Output for the goToModules() function in main frameset.
		if ($descr==4)	{
			$final = chr(10).implode(chr(10),$mJScmds).chr(10);
		}

			// Output for TOPMENU BAR drop downs (but basically this is an array with which you can do many interesting things...)
		if ($descr==5)	{
			$final = $collection;
		}

			// Return result:
		return $final;
	}

	/**
	 * Returns a prefix used to call the navframe with parameters to call the scripts defined in the modules info array.
	 *
	 * @param	string		Module info array
	 * @param	string		Submodule info array
	 * @return	string		Result url string
	 */
	function getNavFramePrefix($moduleInfo, $subModuleInfo=array()) {
		$prefix = '';
		$navFrameScript = $subModuleInfo['navFrameScript'] ? $subModuleInfo['navFrameScript'] : $moduleInfo['navFrameScript'];
		$navFrameScriptParam = isset($subModuleInfo['navFrameScriptParam']) ? $subModuleInfo['navFrameScriptParam'] : $moduleInfo['navFrameScriptParam'];
		if ($navFrameScript)	{
			$navFrameScript = t3lib_div::resolveBackPath($navFrameScript);
			$navFrameScript = $this->wrapLinkWithAB($navFrameScript);

			if ($GLOBALS['BE_USER']->uc['condensedMode'])	{
				$prefix=$navFrameScript.$navFrameScriptParam.'&currentSubScript=';
			} else {
				$prefix='alt_mod_frameset.php?'.
					'fW="+top.TS.navFrameWidth+"'.
					'&nav="+top.TS.PATH_typo3+"'.rawurlencode($navFrameScript.$navFrameScriptParam).
					'&script=';
			}
		}
		return $prefix;
	}

	/**
	 * Returns $Ifilename readable for script in PATH_typo3.
	 * That means absolute names are just returned while relative names are prepended with $backPath (pointing back to typo3/ dir)
	 *
	 * @param	string		Icon filename
	 * @param	string		Back path
	 * @return	string		Result
	 * @see mIconFilename()
	 */
	function mIconFile($Ifilename,$backPath)	{
		if (t3lib_div::isAbsPath($Ifilename))	{
			return $Ifilename;
		}
		return $backPath.$Ifilename;
	}

	/**
	 * Returns relative filename to the $Ifilename (for use in img-tags)
	 *
	 * @param	string		Icon filename
	 * @param	string		Back path
	 * @return	string		Result
	 * @see mIconFile()
	 */
	function mIconFilename($Ifilename,$backPath)	{
		if (t3lib_div::isAbsPath($Ifilename))	{
			$Ifilename = '../'.substr($Ifilename,strlen(PATH_site));
		}
		return $backPath.$Ifilename;
	}

	/**
	 * Returns logout button.
	 *
	 * @return	string
	 */
	function topButtons()	{
		$label = $GLOBALS['BE_USER']->user['ses_backuserid'] ? 'LLL:EXT:lang/locallang_core.php:buttons.exit' : 'LLL:EXT:lang/locallang_core.php:buttons.logout';
		$out = '<form action="logout.php" target="_top"><input type="submit" value="'.$GLOBALS['LANG']->sL($label,1).'" /></form>';
		return $out;
	}

	/**
	 * Returns logout button.
	 *
	 * @return	string
	 */
	function adminButtons()	{
		$functionArray = $this->adminFunctions('');

		$icons=array();
		foreach($functionArray as $fAoptions)	{
			$icons[]= '<a href="'.htmlspecialchars($fAoptions['href']).'">'.$fAoptions['icon'].'</a>';
		}

		return implode('',$icons);
	}

	/**
	 * Returns array with parts from which the admin functions can be constructed.
	 *
	 * @param	string		Backpath.
	 * @return	array
	 */
	function adminFunctions($backPath)	{
		$functions=array();

			// Clearing of cache-files in typo3conf/ + menu
		if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extCache'])	{
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_allTypo3Conf');
			$functions[]=array(
				'id' => 'temp_CACHED',
				'title' => $title,
				'href' => $backPath .
						'tce_db.php?vC=' . $GLOBALS['BE_USER']->veriCode() .
						'&redirect=' . rawurlencode(t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT')) .
						'&cacheCmd=temp_CACHED' .
						t3lib_BEfunc::getUrlToken('tceAction'),
				'icon' => '<img'.t3lib_iconWorks::skinImg($backPath,'gfx/clear_cache_files_in_typo3c.gif','width="21" height="18"').' title="'.htmlspecialchars($title).'" alt="" />'
			);
		}

			// Clear all page cache
		$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_all');
		$functions[]=array(
			'id' => 'all',
			'title' => $title,
			'href' => $backPath . 'tce_db.php?vC=' . $GLOBALS['BE_USER']->veriCode() .
					'&redirect=' . rawurlencode(t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT')) .
					'&cacheCmd=all' .
					t3lib_BEfunc::getUrlToken('tceAction'),
			'icon' => '<img'.t3lib_iconWorks::skinImg($backPath,'gfx/clear_all_cache.gif','width="21" height="18"').' title="'.htmlspecialchars($title).'" alt="" />'
		);

			// Return functions
		return $functions;
	}

	/**
	 * Appends a '?' if there is none in the string already
	 *
	 * @param	string		Link URL
	 * @return	string
	 */
	function wrapLinkWithAB($link)	{
		if (!strstr($link,'?'))	{
			return $link.'?';
		} else return $link;
	}

	/**
	 * Generates some JavaScript code for the frame.
	 *
	 * @return	string	goToModule javascript function
	 */
	function generateMenuJScode($loadedModules, $menuType = 4)	{
		global $TBE_TEMPLATE;

		$goToModuleSwitch = $this->topMenu($loadedModules, 0, '', $menuType);

		$jsCode = '
	/**
	 * Function used to switch switch module.
	 */
	var currentModuleLoaded = "";
	function goToModule(modName,cMR_flag,addGetVars)	{	//
		var additionalGetVariables = "";
		if (addGetVars)	additionalGetVariables = addGetVars;

		var cMR = 0;
		if (cMR_flag)	cMR = 1;

		currentModuleLoaded = modName;

		switch(modName)	{'.$goToModuleSwitch.'
		}
	}';

		return $jsCode;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.alt_menu_functions.inc'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.alt_menu_functions.inc']);
}
?>
