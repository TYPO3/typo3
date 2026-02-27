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
import d from"@typo3/core/document-service.js";import o from"@typo3/backend/form-engine.js";class u{constructor(){d.ready().then(()=>{this.run()})}run(){const e=document.querySelector('[name$="[shortcut]"]'),t=document.querySelector('[name$="[shortcut_mode]"]'),i=e?.closest("form");!e||!t||!i||t.addEventListener("change",()=>{this.apply(e,i,t)})}async apply(e,t,i){const a=parseInt(i.value,10)===0,n=JSON.parse(e.dataset.formengineValidationRules||"[]"),r=n.findIndex(s=>s.type==="required");a&&r===-1?n.push({type:"required"}):!a&&r!==-1&&n.splice(r,1),e.dataset.formengineValidationRules=JSON.stringify(n),o.reinitialize(),o.Validation.initializeInputFields(),o.Validation.validate(t)}}var c=new u;export{c as default};
