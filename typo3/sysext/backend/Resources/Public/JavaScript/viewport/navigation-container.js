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
import{ScaffoldIdentifierEnum as i}from"@typo3/backend/enum/viewport/scaffold-identifier.js";import{AbstractContainer as r}from"@typo3/backend/viewport/abstract-container.js";import l from"@typo3/backend/event/trigger-request.js";import{selector as m}from"@typo3/core/literals.js";class p extends r{constructor(t){super(t),this.activeComponentId=""}get parent(){return document.querySelector(i.scaffold)}get container(){return document.querySelector(i.contentNavigation)}showComponent(t){const e=this.container;if(this.show(t),t===this.activeComponentId)return;if(this.activeComponentId!==""){const n=e.querySelector("#navigationComponent-"+this.activeComponentId.replace(/[/@]/g,"_"));n&&(n.style.display="none")}const a="navigationComponent-"+t.replace(/[/@]/g,"_");if(e.querySelectorAll(m`[data-component="${t}"]`).length===1){this.show(t),this.activeComponentId=t;return}import(t+".js").then(n=>{if(typeof n.navigationComponentName=="string"){const c=n.navigationComponentName,s=document.createElement(c);s.setAttribute("id",a),s.classList.add("scaffold-content-navigation-component"),s.dataset.component=t,e.append(s)}else e.insertAdjacentHTML("beforeend",'<div class="scaffold-content-navigation-component" data-component="'+t+'" id="'+a+'"></div>'),Object.values(n)[0].initialize("#"+a);this.show(t),this.activeComponentId=t})}hide(){const t=this.parent;t.classList.remove("scaffold-content-navigation-expanded"),t.classList.remove("scaffold-content-navigation-available")}show(t){const e=this.parent,o=this.container;o.querySelectorAll(i.contentNavigationDataComponent).forEach(n=>n.style.display="none"),e.classList.add("scaffold-content-navigation-expanded"),e.classList.add("scaffold-content-navigation-available");const a=o.querySelector('[data-component="'+t+'"]');a&&(a.style.display=null)}setUrl(t,e){const o=this.consumerScope.invoke(new l("typo3.setUrl",e));return o.then(()=>{this.parent.classList.add("scaffold-content-navigation-expanded")}),o}}export{p as default};
