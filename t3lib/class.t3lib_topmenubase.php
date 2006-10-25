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
 * Base class for scripts delivering content to the top menu bar/icon panel.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */




/**
 * Base class for scripts delivering content to the top menu bar/icon panel.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class t3lib_topmenubase {

	function menuItems($menuItems)	{
		$output = '';
		
			// Traverse items:
		foreach($menuItems as $item)	{
			
				// Divider has no other options:
			if ($item['title']=='--div--')	{
				$output.= '<div class="menuLayerItem_divider" onmouseover="menuOpenSub(this);"></div>';
			} else {
			
				$itemCode = '';
				$onClick = '';
			
					// Render subitems if any:
				if (is_array($item['subitems']))	{
					$itemCode.= $this->menuLayer($item['subitems'],$item['id']);
				}
			
					// Render state icon if any:
				switch ($item['state'])	{
					case 'checked';
						$itemCode.= '<img src="gfx/x_state_checked.png" width="16" class="menulayerItemIcon">';
					break;
					default:
					$itemCode.= '<img src="gfx/clear.gif" width="16" class="menulayerItemIcon">';
					break;
				}
			
					// Render icon if any:
				if ($item['icon'])	{
					if (is_array($item['icon']))	{
						$itemCode.= '<img '.t3lib_iconWorks::skinImg('',$item['icon'][0],$item['icon'][1]).' class="menulayerItemIcon" alt="" />';
					} else {
						$itemCode.= $item['icon'];
					}
				}
			
					// Title:
				$itemCode.= htmlspecialchars($item['title']).'&nbsp;&nbsp;';
			
					// if subitems, show arrow pointing right:
				$itemCode.= is_array($item['subitems']) ? '<img src="gfx/x_thereismore.png" class="menulayerItemIcon" style="padding-left:40px;">' : ''; 
			
					// Set onclick handlers:
				$onClick.= $item['xurl'] ? "if (Event.element(event)==this){openUrlInWindow('".$item['xurl']."','aWindow');}" : '';
				$onClick.= $item['url'] ? "if (Event.element(event)==this){content.document.location='".$item['url']."';}" : '';
				$onClick.= $item['onclick'] ? $item['onclick'] : $item['onclick'];
		
					// Wrap it all up:
				$output.= '<div '.($item['id'] ? 'id="'.htmlspecialchars($item['id']).'"' : '').'class="menuLayerItem" onmouseover="menuOpenSub(this);"'.($onClick ? ' onclick="'.htmlspecialchars($onClick).'"' : '').'>'.$itemCode.'</div>';
				$output.= $item['html'];
			}
		}

		return $output;
	}
	/**
	 *
	 */
	function menuLayer($menuItems,$baseid='')	{
		$output = $this->menuItems($menuItems);
			
			// Encapsulate in menu layer:
		return $this->simpleLayer($output,$baseid?$baseid.'-layer':'');
	}
	
	function simpleLayer($output,$id='',$class='menulayer')	{
		return '<div class="'.$class.'" style="display: none;"'.($id?' id="'.htmlspecialchars($id).'"':'').'>'.$output.'</div>';
	}
	
	function menuItemLayer($id,$content,$onclick='')	{
		return '<div id="'.$id.'" class="menuItems menu-normal" style="float: left;" onclick="menuToggleState(\''.$id.'\');'.$onclick.'" onmouseover="menuMouseOver(\''.$id.'\');" onmouseout="menuMouseOut(\''.$id.'\');">'.$content.'</div>';
	}
	function menuItemObject($id,$functionContent)	{
		return '
		<script>
			menuItemObjects[\''.$id.'\'] = {
				'.$functionContent.'
			}		
		</script>		
		';
	}
}

?>