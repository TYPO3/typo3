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
var __importDefault=this&&this.__importDefault||function(n){return n&&n.__esModule?n:{default:n}};define(["require","exports","TYPO3/CMS/Backend/Utility"],(function(n,o,t){"use strict";t=__importDefault(t);class e{constructor(){this.windows={},this.open=(...n)=>this._localOpen.apply(this,n),this.globalOpen=(...n)=>this._localOpen.apply(this,n),this.localOpen=(n,o,t="newTYPO3frontendWindow",e="")=>this._localOpen(n,o,t,e)}_localOpen(n,o,e="newTYPO3frontendWindow",i=""){var l;if(!n)return null;null===o?o=!window.opener:void 0===o&&(o=!0);const r=null!==(l=this.windows[e])&&void 0!==l?l:window.open("",e,i);let s=!1;try{s="Window"===r.constructor.name}catch(n){}const a=s&&!r.closed?r.location.href:null;if(t.default.urlsPointToSameServerSideResource(n,a))return r.location.replace(n),r.location.reload(),r;const c=window.open(n,e,i);return this.windows[e]=c,o&&c.focus(),c}}const i=new e;return top.TYPO3.WindowManager||(top.document===window.document?top.TYPO3.WindowManager=i:top.TYPO3.WindowManager=new e),i}));