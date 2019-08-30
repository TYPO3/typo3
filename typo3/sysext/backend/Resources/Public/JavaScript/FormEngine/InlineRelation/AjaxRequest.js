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
define(["require","exports"],function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0});e.AjaxRequest=class{constructor(t,e){this.endpoint=null,this.objectGroup=null,this.params="",this.context=null,this.endpoint=t,this.objectGroup=e}withContext(){let t;return void 0!==TYPO3.settings.FormEngineInline.config[this.objectGroup]&&void 0!==TYPO3.settings.FormEngineInline.config[this.objectGroup].context&&(t=TYPO3.settings.FormEngineInline.config[this.objectGroup].context),this.context=t,this}withParams(t){for(let e=0;e<t.length;e++)this.params+="&ajax["+e+"]="+encodeURIComponent(t[e]);return this}getEndpoint(){return this.endpoint}getOptions(){let t=this.params;return this.context&&(t+="&ajax[context]="+encodeURIComponent(JSON.stringify(this.context))),{type:"POST",data:t}}}});