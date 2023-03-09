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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{SvgTree}from"@typo3/backend/svg-tree.js";export class PageTree extends SvgTree{constructor(){super(),this.networkErrorTitle=TYPO3.lang.pagetree_networkErrorTitle,this.networkErrorMessage=TYPO3.lang.pagetree_networkErrorDesc,this.settings.defaultProperties={hasChildren:!1,nameSourceField:"title",itemType:"pages",prefix:"",suffix:"",locked:!1,loaded:!1,overlayIcon:"",selectable:!0,expanded:!1,checked:!1,backgroundColor:"",stopPageTree:!1,class:"",readableRootline:"",isMountPoint:!1}}showChildren(e){this.loadChildrenOfNode(e),super.showChildren(e)}nodesUpdate(e){const t=(e=super.nodesUpdate(e)).append("svg").attr("class","node-stop").attr("y",super.settings.icon.size/2*-1).attr("x",super.settings.icon.size/2*-1).attr("height",super.settings.icon.size).attr("width",super.settings.icon.size).attr("visibility",(e=>e.stopPageTree&&0!==e.depth?"visible":"hidden")).on("click",((e,t)=>{document.dispatchEvent(new CustomEvent("typo3:pagetree:mountPoint",{detail:{pageId:parseInt(t.identifier,10)}}))}));return t.append("rect").attr("height",super.settings.icon.size).attr("width",super.settings.icon.size).attr("fill","rgba(0,0,0,0)"),t.append("use").attr("transform-origin","50% 50%").attr("href","#icon-actions-caret-right"),e}getToggleVisibility(e){return e.stopPageTree&&0!==e.depth?"hidden":e.hasChildren?"visible":"hidden"}loadChildrenOfNode(e){e.loaded||(this.nodesAddPlaceholder(),new AjaxRequest(this.settings.dataUrl+"&pid="+e.identifier+"&mount="+e.mountPoint+"&pidDepth="+e.depth).get({cache:"no-cache"}).then((e=>e.resolve())).then((t=>{const s=Array.isArray(t)?t:[];s.shift();const i=this.nodes.indexOf(e)+1;s.forEach(((e,t)=>{this.nodes.splice(i+t,0,e)})),e.loaded=!0,this.setParametersNode(),this.prepareDataForVisibleNodes(),this.updateVisibleNodes(),this.nodesRemovePlaceholder(),this.focusNode(e)})).catch((e=>{throw this.errorNotification(e,!1),this.nodesRemovePlaceholder(),e})))}}