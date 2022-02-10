/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
import $ from"jquery";class ContextMenuActions{exportT3d(t,e){const n=$(this).data("action-url");"pages"===t?top.TYPO3.Backend.ContentContainer.setUrl(n+"&id="+e+"&tx_impexp[pagetree][id]="+e+"&tx_impexp[pagetree][levels]=0&tx_impexp[pagetree][tables][]=_ALL"):top.TYPO3.Backend.ContentContainer.setUrl(n+"&tx_impexp[record][]="+t+":"+e+"&tx_impexp[external_ref][tables][]=_ALL")}importT3d(t,e){const n=$(this).data("action-url");top.TYPO3.Backend.ContentContainer.setUrl(n+"&id="+e+"&table="+t)}}export default new ContextMenuActions;