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
define(["require","exports"],(function(e,t){"use strict";class n{static createCloseButton(){const e=document.createElement("button");return e.type="button",e.tabIndex=-1,e.innerHTML='<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-close" data-identifier="actions-close">\n        <span class="icon-markup">\n            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">\n                <path\n                    d="M11.9 5.5L9.4 8l2.5 2.5c.2.2.2.5 0\n                    .7l-.7.7c-.2.2-.5.2-.7 0L8 9.4l-2.5 2.5c-.2.2-.5.2-.7\n                    0l-.7-.7c-.2-.2-.2-.5 0-.7L6.6 8 4.1 5.5c-.2-.2-.2-.5\n                    0-.7l.7-.7c.2-.2.5-.2.7 0L8 6.6l2.5-2.5c.2-.2.5-.2.7\n                    0l.7.7c.2.2.2.5 0 .7z"\n                    class="icon-color"/>\n              </svg>\n            </span>\n          </span>',e.style.visibility="hidden",e.classList.add("close"),e}constructor(){"function"!=typeof HTMLInputElement.prototype.clearable&&this.registerClearable()}registerClearable(){HTMLInputElement.prototype.clearable=function(e={}){if(this.dataset.clearable)return;if("object"!=typeof e)throw new Error("Passed options must be an object, "+typeof e+" given");const t=document.createElement("div");t.classList.add("form-control-clearable","form-control"),this.parentNode.insertBefore(t,this),t.appendChild(this);const s=n.createCloseButton(),i=()=>{s.style.visibility=0===this.value.length?"hidden":"visible"};s.addEventListener("click",t=>{t.preventDefault(),this.value="","function"==typeof e.onClear&&e.onClear(this),this.dispatchEvent(new Event("change",{bubbles:!0,cancelable:!0})),i()}),t.appendChild(s),this.addEventListener("focus",i),this.addEventListener("keyup",i),i(),this.dataset.clearable="true"}}}return new n}));