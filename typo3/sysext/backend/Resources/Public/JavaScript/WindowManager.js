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
define(["require","exports"],(function(n,o){"use strict";class e{constructor(){this.windows={},this.open=(...n)=>this._localOpen.apply(this,n),this.globalOpen=(...n)=>this._localOpen.apply(this,n),this.localOpen=(n,o,e="newTYPO3frontendWindow",t="")=>this._localOpen(n,o,e,t)}_localOpen(n,o,e="newTYPO3frontendWindow",t=""){if(!n)return null;null===o?o=!window.opener:void 0===o&&(o=!0);const i=this.windows[e];if((i instanceof Window&&!i.closed?i.location.href:null)===n)return i.location.reload(),i;const s=window.open(n,e,t);return this.windows[e]=s,o&&s.focus(),s}}const t=new e;return top.TYPO3.WindowManager||(top.document===window.document?top.TYPO3.WindowManager=t:top.TYPO3.WindowManager=new e),t}));