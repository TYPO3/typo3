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
import{Tree as o}from"@typo3/backend/tree/tree.js";class i extends o{constructor(){super(),this.settings.defaultProperties={hasChildren:!1,nameSourceField:"title",type:"sys_file",prefix:"",suffix:"",locked:!1,loaded:!1,overlayIcon:"",selectable:!0,expanded:!1,checked:!1}}getNodeTitle(e){let t=decodeURIComponent(e.name);const s=this.getNodeLabels(e);s.length&&(t+="; "+s.map(l=>l.label).join("; "));const a=this.getNodeStatusInformation(e);return a.length&&(t+="; "+a.map(l=>l.label).join("; ")),t}}export{i as FileStorageTree};
