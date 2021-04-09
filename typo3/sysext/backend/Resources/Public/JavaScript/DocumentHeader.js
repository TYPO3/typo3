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
define(["require","exports","TYPO3/CMS/Core/DocumentService","TYPO3/CMS/Core/Event/ThrottleEvent"],(function(t,e,i,o){"use strict";return new class{constructor(){this.documentHeader=null,this.direction="down",this.reactionRange=300,this.lastPosition=0,this.currentPosition=0,this.changedPosition=0,this.settings={margin:24,offset:100,selectors:{moduleDocumentHeader:".t3js-module-docheader",moduleSearchBar:".t3js-module-docheader-bar-search"}},this.scroll=t=>{this.currentPosition=t.target.scrollTop,this.currentPosition>this.lastPosition?"down"!==this.direction&&(this.direction="down",this.changedPosition=this.currentPosition):this.currentPosition<this.lastPosition&&"up"!==this.direction&&(this.direction="up",this.changedPosition=this.currentPosition),"up"===this.direction&&this.changedPosition-this.reactionRange<this.currentPosition&&this.documentHeader.classList.remove("module-docheader-folded"),"down"===this.direction&&this.changedPosition+this.reactionRange<this.currentPosition&&this.documentHeader.classList.add("module-docheader-folded"),this.lastPosition=this.currentPosition},i.ready().then(()=>{if(this.documentHeader=document.querySelector(this.settings.selectors.moduleDocumentHeader),null===this.documentHeader)return;const t=this.documentHeader.parentElement;new o("scroll",this.scroll,100).bindTo(t)})}}}));