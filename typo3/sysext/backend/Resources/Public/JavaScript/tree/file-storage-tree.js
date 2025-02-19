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
import{Tree as t}from"@typo3/backend/tree/tree.js";class s extends t{constructor(){super(),this.settings.defaultProperties={hasChildren:!1,nameSourceField:"title",type:"sys_file",prefix:"",suffix:"",locked:!1,loaded:!1,overlayIcon:"",selectable:!0,expanded:!1,checked:!1}}getNodeTitle(e){return decodeURIComponent(e.name)}}export{s as FileStorageTree};
