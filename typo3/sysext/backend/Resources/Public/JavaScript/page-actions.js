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
import r from"@typo3/core/document-service.js";import a from"@typo3/core/event/regular-event.js";import c from"@typo3/backend/storage/persistent.js";var i;(function(s){s.hiddenElements=".t3js-hidden-record"})(i||(i={}));class g{constructor(){r.ready().then(()=>{const t=document.getElementById("pageLayoutToggleShowHidden");t!==null&&new a("click",this.toggleContentElementVisibility).bindTo(t)})}toggleContentElementVisibility(t){const n=t.target,d=document.querySelectorAll(i.hiddenElements),o=n.dataset.dropdowntoggleStatus!=="active";n.disabled=!0;for(const e of d){e.style.display="flow-root";const l=e.scrollHeight;e.style.overflow="clip",o?(e.addEventListener("transitionend",()=>{e.style.display="",e.style.overflow="",e.style.height=""},{once:!0}),e.style.height=l+"px"):(e.addEventListener("transitionend",()=>{e.style.display="none",e.style.overflow=""},{once:!0}),requestAnimationFrame(function(){e.style.height=l+"px",requestAnimationFrame(function(){e.style.height="0px"})}))}n.dataset.dropdowntoggleStatus=o?"active":"inactive",c.set("moduleData.web_layout.showHidden",o?"1":"0").then(()=>{n.disabled=!1})}}var m=new g;export{m as default};
