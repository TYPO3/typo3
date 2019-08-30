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
define(["require","exports","./InteractionRequest"],function(e,t,r){"use strict";return class extends r{constructor(e,t=null){super(e,t)}concerns(e){if(this===e)return!0;let t=this;for(;t.parentRequest instanceof r;)if((t=t.parentRequest)===e)return!0;return!1}concernsTypes(e){if(-1!==e.indexOf(this.type))return!0;let t=this;for(;t.parentRequest instanceof r;)if(t=t.parentRequest,-1!==e.indexOf(t.type))return!0;return!1}}});