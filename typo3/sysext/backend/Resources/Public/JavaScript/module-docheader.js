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
import i from"@typo3/core/document-service.js";var e;(function(r){r.wrapper=".module-docheader-wrapper",r.navigation=".t3js-module-docheader-navigation"})(e||(e={}));class o{constructor(){this.wrapper=null,this.navigation=null,this.resizeObserver=new ResizeObserver(()=>this.publish()),i.ready().then(()=>this.observe())}observe(){this.wrapper=document.querySelector(e.wrapper),this.wrapper!==null&&(this.navigation=this.wrapper.querySelector(e.navigation),this.resizeObserver.observe(this.wrapper),this.navigation!==null&&this.resizeObserver.observe(this.navigation))}publish(){const t=document.documentElement.style;t.setProperty("--module-docheader-height",`${this.wrapper.getBoundingClientRect().height}px`),t.setProperty("--module-docheader-navigation-height",`${this.navigation?.getBoundingClientRect().height??0}px`)}}var n=new o;export{n as default};
