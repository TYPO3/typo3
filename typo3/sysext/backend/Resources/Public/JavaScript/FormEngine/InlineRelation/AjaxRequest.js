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
define(["require","exports"],function(t,n){"use strict";Object.defineProperty(n,"__esModule",{value:!0});var e=function(){function t(t,n){this.endpoint=null,this.objectGroup=null,this.params="",this.context=null,this.endpoint=t,this.objectGroup=n}return t.prototype.withContext=function(){var t;return void 0!==TYPO3.settings.FormEngineInline.config[this.objectGroup]&&void 0!==TYPO3.settings.FormEngineInline.config[this.objectGroup].context&&(t=TYPO3.settings.FormEngineInline.config[this.objectGroup].context),this.context=t,this},t.prototype.withParams=function(t){for(var n=0;n<t.length;n++)this.params+="&ajax["+n+"]="+encodeURIComponent(t[n]);return this},t.prototype.getEndpoint=function(){return this.endpoint},t.prototype.getOptions=function(){var t=this.params;return this.context&&(t+="&ajax[context]="+encodeURIComponent(JSON.stringify(this.context))),{type:"POST",data:t}},t}();n.AjaxRequest=e});