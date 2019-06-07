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
var __extends=this&&this.__extends||function(){var e=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(e,t){e.__proto__=t}||function(e,t){for(var r in t)t.hasOwnProperty(r)&&(e[r]=t[r])};return function(t,r){function n(){this.constructor=t}e(t,r),t.prototype=null===r?Object.create(r):(n.prototype=r.prototype,new n)}}();define(["require","exports","./AbstractInteractableModule","jquery","../Router","../Renderable/ProgressBar","../Renderable/FlashMessage","../Renderable/Severity","../Renderable/InfoBox"],function(e,t,r,n,o,i,a,s,c){"use strict";return new(function(e){function t(){var t=null!==e&&e.apply(this,arguments)||this;return t.selectorCheckTrigger=".t3js-tcaMigrationsCheck-check",t.selectorOutputContainer=".t3js-tcaMigrationsCheck-output",t}return __extends(t,e),t.prototype.initialize=function(e){var t=this;this.currentModal=e,this.check(),e.on("click",this.selectorCheckTrigger,function(e){e.preventDefault(),t.check()})},t.prototype.check=function(){var e=this,t=n(this.selectorOutputContainer),r=this.getModalBody(),u=i.render(s.loading,"Loading...","");t.empty().html(u),n.ajax({url:o.getUrl("tcaMigrationsCheck"),cache:!1,success:function(t){if(r.empty().append(t.html),!0===t.success&&Array.isArray(t.status))if(t.status.length>0){var n=c.render(s.warning,"TCA migrations need to be applied","Check the following list and apply needed changes.");r.find(e.selectorOutputContainer).empty(),r.find(e.selectorOutputContainer).append(n),t.status.forEach(function(t){var n=c.render(t.severity,t.title,t.message);r.find(e.selectorOutputContainer).append(n)})}else{var o=c.render(s.ok,"No TCA migrations need to be applied","Your TCA looks good.");r.find(e.selectorOutputContainer).append(o)}else{var i=a.render(s.error,"Something went wrong",'Use "Check for broken extensions"');r.find(e.selectorOutputContainer).append(i)}},error:function(e){o.handleAjaxError(e,r)}})},t}(r.AbstractInteractableModule))});