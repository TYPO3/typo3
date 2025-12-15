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
import{ScaffoldContentArea as c}from"@typo3/backend/enum/viewport/scaffold-identifier.js";import{AbstractContainer as m}from"@typo3/backend/viewport/abstract-container.js";import l from"@typo3/backend/event/trigger-request.js";import{selector as p}from"@typo3/core/literals.js";class h extends m{constructor(t){super(t),this.activeComponentId=""}get contentNavigation(){return c.getContentNavigation()}get navigationContainer(){return c.getNavigationContainer()}showComponent(t){const n=this.contentNavigation,e=this.navigationContainer;if(!n||!e||(this.show(t),t===this.activeComponentId))return;if(this.activeComponentId!==""){const o=e.querySelector("#navigationComponent-"+this.activeComponentId.replace(/[/@]/g,"_"));o&&(o.style.display="none")}const i="navigationComponent-"+t.replace(/[/@]/g,"_");if(e.querySelectorAll(p`[data-component="${t}"]`).length===1){this.show(t),this.activeComponentId=t;return}import(t+".js").then(o=>{if(typeof o.navigationComponentName=="string"){const r=o.navigationComponentName,s=document.createElement(r);s.setAttribute("id",i),s.dataset.component=t,e.append(s)}else e.insertAdjacentHTML("beforeend",'<div data-component="'+t+'" id="'+i+'"></div>'),Object.values(o)[0].initialize("#"+i);this.show(t),this.activeComponentId=t})}hide(){this.contentNavigation?.hideNavigation()}show(t){const n=this.contentNavigation,e=this.navigationContainer;if(!n||!e)return;e.querySelectorAll("[data-component]").forEach(i=>i.style.display="none"),n.showNavigation();const a=e.querySelector('[data-component="'+t+'"]');a&&(a.style.display=null)}setUrl(t,n){const e=this.consumerScope.invoke(new l("typo3.setUrl",n));return e.then(()=>{this.contentNavigation?.showNavigation()}),e}}export{h as default};
