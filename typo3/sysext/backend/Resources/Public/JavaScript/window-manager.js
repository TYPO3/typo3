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
import p from"@typo3/backend/utility.js";class a{constructor(){this.windows={},this.localOpen=(n,o,t="newTYPO3frontendWindow",l="")=>this._localOpen(n,o,t,l)}open(...n){return this._localOpen.apply(null,n)}globalOpen(...n){return this._localOpen.apply(null,n)}_localOpen(n,o,t="newTYPO3frontendWindow",l=""){if(!n)return null;o===null?o=!window.opener:o===void 0&&(o=!0);const e=this.windows[t]??window.open("",t,l);let r=!1;try{r=e.constructor.name==="Window"}catch{}const s=r&&!e.closed?e.location.href:null;if(p.urlsPointToSameServerSideResource(n,s))return e.location.replace(n),e.location.reload(),e.focus(),e;const i=window.open(n,t,l);return this.windows[t]=i,o&&i.focus(),i}}const d=new a;top.TYPO3.WindowManager||(top.document===window.document?top.TYPO3.WindowManager=d:top.TYPO3.WindowManager=new a);export{d as default};
