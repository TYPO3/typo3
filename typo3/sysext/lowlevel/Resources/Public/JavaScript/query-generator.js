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
import"@typo3/backend/input/clearable.js";import s from"@typo3/backend/date-time-picker.js";import a from"@typo3/core/event/regular-event.js";class u{constructor(){this.form=document.querySelector('form[name="queryform"]'),this.searchField=document.querySelector("input#searchField"),this.submitSearch=document.querySelector("button#submitSearch"),this.activeSearch=this.searchField?this.searchField.value!=="":!1,this.limitField=document.querySelector("input#queryLimit");const i=this.searchField,l=this.submitSearch,t=this.form;l&&this.activeSearch&&l.removeAttribute("disabled"),t!==null&&(i&&(new a("search",()=>{i.value===""&&this.activeSearch&&this.doSubmit()}).bindTo(i),new a("input",()=>{i.value===""&&this.activeSearch&&this.doSubmit(),l?.toggleAttribute("disabled",i.value==="")}).bindTo(i),new a("submit",e=>{i.value===""&&!this.activeSearch&&e.preventDefault()}).bindTo(t)),new a("click",e=>{e.preventDefault(),this.doSubmit()}).delegateTo(t,".t3js-submit-click"),new a("change",e=>{e.preventDefault(),this.doSubmit()}).delegateTo(t,".t3js-submit-change"),new a("click",(e,r)=>{e.preventDefault(),this.setLimit(r.value),this.doSubmit()}).delegateTo(t,'.t3js-limit-submit input[type="button"]'),new a("click",(e,r)=>{e.preventDefault(),this.addValueToField(r.dataset.field??"",r.value)}).delegateTo(t,".t3js-addfield"),new a("change",(e,r)=>{const o=t.querySelector('input[name="storeControl[title]"]');r.value!=="0"?o.value=r.options[r.selectedIndex].textContent:o.value=""}).delegateTo(t,"select.t3js-addfield"),document.querySelectorAll('form[name="queryform"] .t3js-clearable').forEach(e=>e.clearable({onClear:()=>{this.doSubmit()}})),document.querySelectorAll('form[name="queryform"] .t3js-datetimepicker').forEach(e=>s.initialize(e)))}doSubmit(){this.form?.submit()}setLimit(i){this.limitField!==null&&(this.limitField.value=i)}addValueToField(i,l){const t=this.form?.querySelector('[name="'+i+'"]');t&&(l=t.value+","+l,t.value=l.split(",").map(e=>e.trim()).filter((e,r,o)=>o.indexOf(e)===r).join(","))}}var c=new u;export{c as default};
