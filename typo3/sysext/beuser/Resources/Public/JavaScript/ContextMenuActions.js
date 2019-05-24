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
define(["require","exports"],function(e,t){"use strict";return new function(){var e=this;this.openPermissionsModule=function(t,n){"pages"===t&&top.TYPO3.Backend.ContentContainer.setUrl(top.TYPO3.settings.AccessPermissions.moduleUrl+"&id="+n+"&tx_beuser_system_beusertxpermission[action]=edit&tx_beuser_system_beusertxpermission[controller]=Permission&returnUrl="+e.getReturnUrl())},this.getReturnUrl=function(){return encodeURIComponent(top.list_frame.document.location.pathname+top.list_frame.document.location.search)}}});