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
define(["require","exports"],(function(n,o){"use strict";class t{constructor(){this.windows={},this.open=(...n)=>this.localOpen.apply(this,n),this.globalOpen=(...n)=>this.localOpen.apply(this,n)}localOpen(n,o,t="newTYPO3frontendWindow",e=""){if(!n)return null;null===o?o=!window.opener:void 0===o&&(o=!0);const i=this.windows[t];if((i instanceof Window&&!i.closed?i.location.href:null)===n)return i.location.reload(),i;const s=window.open(n,t,e);return this.windows[t]=s,o&&s.focus(),s}}const e=new t;return top.TYPO3.WindowManager||(top.document===window.document?top.TYPO3.WindowManager=e:top.TYPO3.WindowManager=new t),e}));