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
class c{constructor(){typeof HTMLInputElement.prototype.clearable!="function"&&this.registerClearable()}static createCloseButton(t){const l=`
      <span class="t3js-icon icon icon-size-small icon-state-default icon-actions-close" data-identifier="actions-close">
        <span class="icon-markup">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
            <g fill="currentColor">
              <path d="M11.9 5.5 9.4 8l2.5 2.5c.2.2.2.5 0 .7l-.7.7c-.2.2-.5.2-.7 0L8 9.4l-2.5 2.5c-.2.2-.5.2-.7 0l-.7-.7c-.2-.2-.2-.5 0-.7L6.6 8 4.1 5.5c-.2-.2-.2-.5 0-.7l.7-.7c.2-.2.5-.2.7 0L8 6.6l2.5-2.5c.2-.2.5-.2.7 0l.7.7c.2.2.2.5 0 .7z"/>
            </g>
          </svg>
        </span>
      </span>
    `,e=document.createElement("button");return e.type="button",e.tabIndex=-1,e.title=t,e.ariaLabel=t,e.innerHTML=l,e.style.visibility="hidden",e.classList.add("close"),e}registerClearable(){HTMLInputElement.prototype.clearable=async function(t={}){if(this.isClearable)return;if(typeof t!="object")throw new Error("Passed options must be an object, "+typeof t+" given");this.classList.add("form-control-clearable");const l=document.activeElement===this,e=document.createElement("div");e.classList.add("form-control-clearable-wrapper"),this.parentNode.insertBefore(e,this),e.appendChild(this);let i="Clear input";if(this.dataset.clearableLabel)i=this.dataset.clearableLabel;else{const{default:a}=await import("~labels/core.core");i=a.get("labels.inputfield.clearButton.title")}const n=c.createCloseButton(i),s=()=>{n.style.visibility=this.value.length||this.validity.badInput?"visible":"hidden"};n.addEventListener("click",a=>{a.preventDefault(),this.value="",typeof t.onClear=="function"&&t.onClear(this),this.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0})),this.dispatchEvent(new CustomEvent("typo3:internal:clear")),s(),this.focus()}),e.appendChild(n),this.addEventListener("blur",a=>{this.parentNode.contains(a.relatedTarget)&&this.focus()}),this.addEventListener("focus",s),this.addEventListener("keyup",s),s(),this.isClearable=!0,l&&this.focus()}}}var r=new c;export{r as default};
