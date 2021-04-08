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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","TYPO3/CMS/Core/Ajax/AjaxRequest","../SvgTree"],(function(e,t,o,r){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.FileStorageTree=void 0,o=__importDefault(o);class i extends r.SvgTree{constructor(){super(),this.settings.defaultProperties={hasChildren:!1,nameSourceField:"title",itemType:"sys_file",prefix:"",suffix:"",locked:!1,loaded:!1,overlayIcon:"",selectable:!0,expanded:!1,checked:!1,backgroundColor:"",class:"",readableRootline:""}}showChildren(e){this.loadChildrenOfNode(e),super.showChildren(e)}getNodeTitle(e){return decodeURIComponent(e.name)}loadChildrenOfNode(e){if(e.loaded)return this.prepareDataForVisibleNodes(),void this.updateVisibleNodes();this.nodesAddPlaceholder(),new o.default(this.settings.dataUrl+"&parent="+e.identifier+"&currentDepth="+e.depth).get({cache:"no-cache"}).then(e=>e.resolve()).then(t=>{let o=Array.isArray(t)?t:[];const r=this.nodes.indexOf(e)+1;o.forEach((e,t)=>{this.nodes.splice(r+t,0,e)}),e.loaded=!0,this.setParametersNode(),this.prepareDataForVisibleNodes(),this.updateVisibleNodes(),this.nodesRemovePlaceholder(),this.switchFocusNode(e)}).catch(e=>{throw this.errorNotification(e,!1),this.nodesRemovePlaceholder(),e})}}t.FileStorageTree=i}));