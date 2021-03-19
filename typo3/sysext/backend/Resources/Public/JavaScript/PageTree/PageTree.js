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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","TYPO3/CMS/Core/Ajax/AjaxRequest","../SvgTree"],(function(e,t,r,o){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.PageTree=void 0,r=__importDefault(r);class s extends o.SvgTree{constructor(){super(),this.networkErrorTitle=TYPO3.lang.pagetree_networkErrorTitle,this.networkErrorMessage=TYPO3.lang.pagetree_networkErrorDesc,this.settings.defaultProperties={hasChildren:!1,nameSourceField:"title",itemType:"pages",prefix:"",suffix:"",locked:!1,loaded:!1,overlayIcon:"",selectable:!0,expanded:!1,checked:!1,backgroundColor:"",stopPageTree:!1,class:"",readableRootline:"",isMountPoint:!1}}showChildren(e){this.loadChildrenOfNode(e),super.showChildren(e)}nodesUpdate(e){return(e=super.nodesUpdate(e)).append("text").text("+").attr("class","node-stop").attr("dx",30).attr("dy",5).attr("visibility",e=>e.stopPageTree&&0!==e.depth?"visible":"hidden").on("click",(e,t)=>{document.dispatchEvent(new CustomEvent("typo3:pagetree:mountPoint",{detail:{pageId:parseInt(t.identifier,10)}}))}),e}loadChildrenOfNode(e){e.loaded||(this.nodesAddPlaceholder(),new r.default(this.settings.dataUrl+"&pid="+e.identifier+"&mount="+e.mountPoint+"&pidDepth="+e.depth).get({cache:"no-cache"}).then(e=>e.resolve()).then(t=>{let r=Array.isArray(t)?t:[];r.shift();const o=this.nodes.indexOf(e)+1;r.forEach((e,t)=>{this.nodes.splice(o+t,0,e)}),e.loaded=!0,this.setParametersNode(),this.prepareDataForVisibleNodes(),this.updateVisibleNodes(),this.nodesRemovePlaceholder(),this.switchFocusNode(e)}).catch(e=>{throw this.errorNotification(e,!1),this.nodesRemovePlaceholder(),e}))}appendTextElement(e){return super.appendTextElement(e).attr("dx",e=>{let t=this.textPosition;return e.stopPageTree&&0!==e.depth&&(t+=15),e.locked&&(t+=15),t})}}t.PageTree=s}));