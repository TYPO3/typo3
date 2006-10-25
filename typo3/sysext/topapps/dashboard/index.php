<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 */

$LANG->includeLLFile('EXT:topapps/dashboard/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_topmenubase.php');


/**
 * Main script class for the dashboard overlay
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_topapps
 */
class SC_topapps_dashboard extends t3lib_topmenubase {

	/**
	 * Main function
	 *
	 * @return	void
	 */
	function main()	{
		global $TBE_MODULES,$TBE_TEMPLATE,$MCONF,$LANG;
		
		switch((string)t3lib_div::_GET('cmd'))	{
			case 'menuitem':
				
				echo '<img src="'.t3lib_extMgm::extRelPath('topapps').'dashboard/x_dashboard.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" alt="" onclick="menuItemObjects[\''.$MCONF['name'].'\'].toggle();" />
				<script>
					menuItemObjects[\''.$MCONF['name'].'\'] = {
						active : false,
						toggle: function () {
							if (!this.active)	{
								this.active = true;
								$(\''.$MCONF['name'].'-layer\').style.height = (document.body.clientHeight-50)+\'px\';
								$(\''.$MCONF['name'].'-background\').style.height = (document.body.clientHeight-50)+\'px\';
								Effect.Appear(\''.$MCONF['name'].'-background'.'\',{duration: 0.4, from:0, to:0.7}); 
								Effect.Appear(\''.$MCONF['name'].'-layer'.'\',{duration: 0.4}); 
							} else {
								this.active = false;
								Effect.Fade(\''.$MCONF['name'].'-background'.'\',{duration: 0.4}); 
								Effect.Fade(\''.$MCONF['name'].'-layer'.'\',{duration: 0.4}); 
							}
						}
					}					
				</script>
				
				
				';
				
				$layerContent = '
				<div id="'.$MCONF['name'].'-background" style="position: absolute; display: none; background: black; left: 0px; width: 100%;"></div>
				<div id="'.$MCONF['name'].'-layer" style="position: absolute; display: none; left: 0px; width: 100%;">

					<div class="dashboard-dock" id="dashboard_dock">
						<div class="dashboard-item" id="i1"><p class="handle">H</p>Item 1</div>
						<div class="dashboard-item" id="i2"><p class="handle">H</p>Item 2</div>
						<div class="dashboard-item" id="i3"><p class="handle">H</p>Item 3</div>
					</div><br/>

					<div class="dashboard-col" style="width: 46%;" id="dashboard_col1">
						<br/>

						<div class="dashboard-item" id="i4">
							<div class="dashboard-icon">ICON</div>
							<div class="dashboard-handle handle">My header</div>
							<div class="dashboard-content">
								Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... 
							</div>
						</div>
					</div>
			
					<div class="dashboard-col" style="width: 33%;" id="dashboard_col2">
						<br/>

						<div class="dashboard-item" id="i5">
							<div class="dashboard-icon">ICON</div>
							<div class="dashboard-handle handle">My header</div>
							<div class="dashboard-content">
								Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... 
							</div>
						</div>
					</div>
			
					<div class="dashboard-col" style="width: 20%;" id="dashboard_col3">
						<br/>

						<div class="dashboard-item" id="i6">
							<div class="dashboard-icon">ICON</div>
							<div class="dashboard-handle handle">My header</div>
							<div class="dashboard-content">
								Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... Lots of content... 
							</div>
						</div>
					</div>
			
				</div>
				
				 <script type="text/javascript">
				 // <![CDATA[
				   Sortable.create("dashboard_dock",
				     {
						dropOnEmpty:true,
						containment:["dashboard_dock","dashboard_col1","dashboard_col2","dashboard_col3"],
						constraint:false,
						tag: \'div\',
						handle: \'handle\',
						hoverclass : \'dashboard-item-hover\',
						onUpdate: function (obj,another){ 
							alert(obj.id);
						}
					});
				   Sortable.create("dashboard_col1",
				     {
						dropOnEmpty:true,
						containment:["dashboard_dock","dashboard_col1","dashboard_col2","dashboard_col3"],
						constraint:false,
						tag: \'div\',
						handle: \'handle\',
						onUpdate: function (obj){
							//alert(obj.id);
						}
					});
				   Sortable.create("dashboard_col2",
				     {
						dropOnEmpty:true,
						containment:["dashboard_dock","dashboard_col1","dashboard_col2","dashboard_col3"],
						constraint:false,
						tag: \'div\',
						handle: \'handle\',
						onUpdate: function (obj){
							//alert(obj.id);
						}
					});
				   Sortable.create("dashboard_col3",
				     {
						dropOnEmpty:true,
						containment:["dashboard_dock","dashboard_col1","dashboard_col2","dashboard_col3"],
						constraint:false,
						tag: \'div\',
						handle: \'handle\',
						onUpdate: function (obj){
							//alert(obj.id);
						}
					});

				 // ]]>
				 </script>
								
				
				
				';
				echo $layerContent;
			break;
		}
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/dashboard/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/dashboard/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_topapps_dashboard');
$SOBE->main();
?>