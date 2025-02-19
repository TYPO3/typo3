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
import r from"@typo3/backend/event/interaction-request.js";class n extends r{constructor(t,e=null){super(t,e)}concerns(t){if(this===t)return!0;for(let e=this.parentRequest;e instanceof r;e=e.parentRequest)if(e===t)return!0;return!1}concernsTypes(t){if(t.includes(this.type))return!0;for(let e=this.parentRequest;e instanceof r;e=e.parentRequest)if(t.includes(e.type))return!0;return!1}}export{n as default};
