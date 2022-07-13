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
var __importDefault=this&&this.__importDefault||function(n){return n&&n.__esModule?n:{default:n}};define(["require","exports","TYPO3/CMS/Backend/Utility"],(function(n,o,e){"use strict";e=__importDefault(e);class t{constructor(){this.windows={},this.open=(...n)=>this._localOpen.apply(this,n),this.globalOpen=(...n)=>this._localOpen.apply(this,n),this.localOpen=(n,o,e="newTYPO3frontendWindow",t="")=>this._localOpen(n,o,e,t)}_localOpen(n,o,t="newTYPO3frontendWindow",i=""){var l;if(!n)return null;null===o?o=!window.opener:void 0===o&&(o=!0);const r=null!==(l=this.windows[t])&&void 0!==l?l:window.open("",t,i),s="Window"===r.constructor.name&&!r.closed?r.location.href:null;if(e.default.urlsPointToSameServerSideResource(n,s))return r.location.replace(n),r.location.reload(),r;const a=window.open(n,t,i);return this.windows[t]=a,o&&a.focus(),a}}const i=new t;return top.TYPO3.WindowManager||(top.document===window.document?top.TYPO3.WindowManager=i:top.TYPO3.WindowManager=new t),i}));