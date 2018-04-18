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
var __values=this&&this.__values||function(e){var t="function"==typeof Symbol&&e[Symbol.iterator],r=0;return t?t.call(e):{next:function(){return e&&r>=e.length&&(e=void 0),{value:e&&e[r++],done:!e}}}};define(["require","exports"],function(e,t){"use strict";return new function(){var e=this;this.keyPrefix="t3-",this.get=function(t){return localStorage.getItem(e.keyPrefix+t)},this.set=function(t,r){localStorage.setItem(e.keyPrefix+t,r)},this.unset=function(t){localStorage.removeItem(e.keyPrefix+t)},this.unsetByPrefix=function(t){t=e.keyPrefix+t;for(var r,n,o=[],i=0;i<localStorage.length;++i)if(localStorage.key(i).substring(0,t.length)===t){var l=localStorage.key(i).substr(e.keyPrefix.length);o.push(l)}try{for(var a=__values(o),u=a.next();!u.done;u=a.next())l=u.value,e.unset(l)}catch(e){r={error:e}}finally{try{u&&!u.done&&(n=a.return)&&n.call(a)}finally{if(r)throw r.error}}},this.clear=function(){localStorage.clear()},this.isset=function(t){var r=e.get(t);return null!=r}}});