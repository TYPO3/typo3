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
import"@typo3/backend/input/clearable.js";import o from"@typo3/backend/date-time-picker.js";import i from"@typo3/core/event/regular-event.js";class u{constructor(){this.form=document.querySelector('form[name="queryform"]'),this.searchField=document.querySelector("input#searchField"),this.submitSearch=document.querySelector("button#submitSearch"),this.activeSearch=this.searchField?this.searchField.value!=="":!1,this.limitField=document.querySelector("input#queryLimit"),this.submitSearch&&this.activeSearch&&this.submitSearch.removeAttribute("disabled"),this.searchField&&(new i("search",()=>{this.searchField.value===""&&this.activeSearch&&this.doSubmit()}).bindTo(this.searchField),new i("input",()=>{this.searchField.value===""&&this.activeSearch&&this.doSubmit(),this.submitSearch.toggleAttribute("disabled",this.searchField.value==="")}).bindTo(this.searchField),new i("submit",e=>{this.searchField.value===""&&!this.activeSearch&&e.preventDefault()}).bindTo(this.form)),new i("click",e=>{e.preventDefault(),this.doSubmit()}).delegateTo(this.form,".t3js-submit-click"),new i("change",e=>{e.preventDefault(),this.doSubmit()}).delegateTo(this.form,".t3js-submit-change"),new i("click",(e,t)=>{e.preventDefault(),this.setLimit(t.value),this.doSubmit()}).delegateTo(this.form,'.t3js-limit-submit input[type="button"]'),new i("click",(e,t)=>{e.preventDefault(),this.addValueToField(t.dataset.field,t.value)}).delegateTo(this.form,".t3js-addfield"),new i("change",(e,t)=>{const r=this.form.querySelector('input[name="storeControl[title]"]');t.value!=="0"?r.value=t.querySelector("option:selected").textContent:r.value=""}).delegateTo(this.form,"select.t3js-addfield"),document.querySelectorAll('form[name="queryform"] .t3js-clearable').forEach(e=>e.clearable({onClear:()=>{this.doSubmit()}})),document.querySelectorAll('form[name="queryform"] .t3js-datetimepicker').forEach(e=>o.initialize(e))}doSubmit(){this.form.submit()}setLimit(e){this.limitField.value=e}addValueToField(e,t){const r=this.form.querySelector('[name="'+e+'"]');t=r.value+","+t,r.value=t.split(",").map(a=>a.trim()).filter((a,s,l)=>l.indexOf(a)===s).join(",")}}var c=new u;export{c as default};
