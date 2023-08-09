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
import InteractionRequest from"@typo3/backend/event/interaction-request.js";class TriggerRequest extends InteractionRequest{constructor(e,t=null){super(e,t)}concerns(e){if(this===e)return!0;for(let t=this.parentRequest;t instanceof InteractionRequest;t=t.parentRequest)if(t===e)return!0;return!1}concernsTypes(e){if(e.includes(this.type))return!0;for(let t=this.parentRequest;t instanceof InteractionRequest;t=t.parentRequest)if(e.includes(t.type))return!0;return!1}}export default TriggerRequest;