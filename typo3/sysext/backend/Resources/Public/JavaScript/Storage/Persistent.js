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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery"],(function(t,e,s){"use strict";s=__importDefault(s);return new class{constructor(){this.data=!1,this.get=t=>{const e=this;if(!1===this.data){let s;return this.loadFromServer().done(()=>{s=e.getRecursiveDataByDeepKey(e.data,t.split("."))}),s}return this.getRecursiveDataByDeepKey(this.data,t.split("."))},this.set=(t,e)=>(!1!==this.data&&(this.data=this.setRecursiveDataByDeepKey(this.data,t.split("."),e)),this.storeOnServer(t,e)),this.addToList=(t,e)=>{const a=this;return s.default.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"addToList",key:t,value:e},method:"post"}).done(t=>{a.data=t})},this.removeFromList=(t,e)=>{const a=this;return s.default.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"removeFromList",key:t,value:e},method:"post"}).done(t=>{a.data=t})},this.unset=t=>{const e=this;return s.default.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"unset",key:t},method:"post"}).done(t=>{e.data=t})},this.clear=()=>{s.default.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"clear"},method:"post"}),this.data=!1},this.isset=t=>{const e=this.get(t);return null!=e},this.load=t=>{this.data=t},this.loadFromServer=()=>{const t=this;return s.default.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{async:!1,data:{action:"getAll"}}).done(e=>{t.data=e})},this.storeOnServer=(t,e)=>{const a=this;return s.default.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"set",key:t,value:e},method:"post"}).done(t=>{a.data=t})},this.getRecursiveDataByDeepKey=(t,e)=>{if(1===e.length)return(t||{})[e[0]];const s=e.shift();return this.getRecursiveDataByDeepKey(t[s]||{},e)},this.setRecursiveDataByDeepKey=(t,e,s)=>{if(1===e.length)(t=t||{})[e[0]]=s;else{const a=e.shift();t[a]=this.setRecursiveDataByDeepKey(t[a]||{},e,s)}return t}}}}));