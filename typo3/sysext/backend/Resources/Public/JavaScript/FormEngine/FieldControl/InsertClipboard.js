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
var __values=this&&this.__values||function(e){var t="function"==typeof Symbol&&e[Symbol.iterator],r=0;return t?t.call(e):{next:function(){return e&&r>=e.length&&(e=void 0),{value:e&&e[r++],done:!e}}}};define(["require","exports","jquery","TYPO3/CMS/Backend/FormEngine"],function(e,t,r,n){"use strict";return function(e){var t=this;this.controlElement=null,this.registerClickHandler=function(e){e.preventDefault();var r,l,o=t.controlElement.dataset.element,i=JSON.parse(t.controlElement.dataset.clipboardItems);try{for(var a=__values(i),c=a.next();!c.done;c=a.next()){var u=c.value;n.setSelectOptionFromExternalSource(o,u.value,u.title,u.title)}}catch(e){r={error:e}}finally{try{c&&!c.done&&(l=a.return)&&l.call(a)}finally{if(r)throw r.error}}},r(function(){t.controlElement=document.querySelector(e),t.controlElement.addEventListener("click",t.registerClickHandler)})}});