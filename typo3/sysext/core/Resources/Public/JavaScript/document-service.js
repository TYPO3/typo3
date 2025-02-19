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
class t{constructor(){this.promise=null}ready(){return this.promise??(this.promise=this.createPromise())}async createPromise(){return document.readyState!=="loading"||await new Promise(e=>document.addEventListener("DOMContentLoaded",()=>e(),{once:!0})),document}}const r=new t;export{r as default};
