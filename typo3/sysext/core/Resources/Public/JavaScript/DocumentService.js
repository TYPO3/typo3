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
define(["require","exports"],(function(e,t){"use strict";return new class{constructor(){this.promise=null}ready(){var e;return null!==(e=this.promise)&&void 0!==e?e:this.promise=this.createPromise()}async createPromise(){return"loading"!==document.readyState||await new Promise(e=>document.addEventListener("DOMContentLoaded",()=>e(),{once:!0})),document}}}));