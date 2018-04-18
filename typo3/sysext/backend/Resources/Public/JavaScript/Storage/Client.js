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
var __values=this&&this.__values||function(e){var t="function"==typeof Symbol&&e[Symbol.iterator],r=0;return t?t.call(e):{next:function(){return e&&r>=e.length&&(e=void 0),{value:e&&e[r++],done:!e}}}};define(["require","exports"],function(e,t){"use strict";return new(function(){function e(){var t=this;this.keyPrefix="t3-",this.get=function(r){return e.isCapable()?localStorage.getItem(t.keyPrefix+r):null},this.set=function(r,n){e.isCapable()&&localStorage.setItem(t.keyPrefix+r,n)},this.unset=function(r){e.isCapable()&&localStorage.removeItem(t.keyPrefix+r)},this.unsetByPrefix=function(r){if(e.isCapable()){r=t.keyPrefix+r;for(var n,a,i=[],l=0;l<localStorage.length;++l)if(localStorage.key(l).substring(0,r.length)===r){var o=localStorage.key(l).substr(t.keyPrefix.length);i.push(o)}try{for(var u=__values(i),s=u.next();!s.done;s=u.next()){o=s.value;t.unset(o)}}catch(e){n={error:e}}finally{try{s&&!s.done&&(a=u.return)&&a.call(u)}finally{if(n)throw n.error}}}},this.clear=function(){e.isCapable()&&localStorage.clear()},this.isset=function(r){if(e.isCapable()){var n=t.get(r);return null!=n}return!1}}return e.isCapable=function(){return null!==localStorage},e}())});