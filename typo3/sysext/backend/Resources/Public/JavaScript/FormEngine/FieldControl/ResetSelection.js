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
var __values=this&&this.__values||function(e){var t="function"==typeof Symbol&&e[Symbol.iterator],n=0;return t?t.call(e):{next:function(){return e&&n>=e.length&&(e=void 0),{value:e&&e[n++],done:!e}}}};define(["require","exports","jquery"],(function(e,t,n){"use strict";return function(e){var t=this;this.controlElement=null,this.registerClickHandler=function(e){e.preventDefault();var n,r,l=t.controlElement.dataset.itemName,o=JSON.parse(t.controlElement.dataset.selectedIndices),c=document.forms.namedItem("editform").querySelector('[name="'+l+'[]"]');c.selectedIndex=-1;try{for(var i=__values(o),a=i.next();!a.done;a=i.next()){var u=a.value;c.options[u].selected=!0}}catch(e){n={error:e}}finally{try{a&&!a.done&&(r=i.return)&&r.call(i)}finally{if(n)throw n.error}}},n((function(){t.controlElement=document.querySelector(e),null!==t.controlElement&&t.controlElement.addEventListener("click",t.registerClickHandler)}))}}));