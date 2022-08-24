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
class InteractionRequestMap{constructor(){this.assignments=[]}attachFor(e,t){let s=this.getFor(e);null===s&&(s={request:e,deferreds:[]},this.assignments.push(s)),s.deferreds.push(t)}detachFor(e){const t=this.getFor(e);this.assignments=this.assignments.filter((e=>e===t))}getFor(e){let t=null;return this.assignments.some((s=>s.request===e&&(t=s,!0))),t}resolveFor(e){const t=this.getFor(e);return null!==t&&(t.deferreds.forEach((e=>e.resolve())),this.detachFor(e),!0)}rejectFor(e){const t=this.getFor(e);return null!==t&&(t.deferreds.forEach((e=>e.reject())),this.detachFor(e),!0)}}export default new InteractionRequestMap;