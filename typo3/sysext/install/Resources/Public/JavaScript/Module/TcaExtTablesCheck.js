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
var __extends=this&&this.__extends||function(){var e=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(e,t){e.__proto__=t}||function(e,t){for(var n in t)t.hasOwnProperty(n)&&(e[n]=t[n])};return function(t,n){function r(){this.constructor=t}e(t,n),t.prototype=null===n?Object.create(n):(r.prototype=n.prototype,new r)}}();define(["require","exports","./AbstractInteractableModule","jquery","../Router","../Renderable/ProgressBar","../Renderable/Severity","../Renderable/InfoBox","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification"],function(e,t,n,r,o,a,s,i,c,u){"use strict";return new(function(e){function t(){var t=null!==e&&e.apply(this,arguments)||this;return t.selectorCheckTrigger=".t3js-tcaExtTablesCheck-check",t.selectorOutputContainer=".t3js-tcaExtTablesCheck-output",t}return __extends(t,e),t.prototype.initialize=function(e){var t=this;this.currentModal=e,this.check(),e.on("click",this.selectorCheckTrigger,function(e){e.preventDefault(),t.check()})},t.prototype.check=function(){var e=this,t=this.getModalBody(),n=r(this.selectorOutputContainer),l=a.render(s.loading,"Loading...","");n.empty().html(l),r.ajax({url:o.getUrl("tcaExtTablesCheck"),cache:!1,success:function(r){if(t.empty().append(r.html),c.setButtons(r.buttons),!0===r.success&&Array.isArray(r.status))if(r.status.length>0){var o=i.render(s.warning,"Extensions change TCA in ext_tables.php",'Check for ExtensionManagementUtility and $GLOBALS["TCA"]');t.find(e.selectorOutputContainer).append(o),r.status.forEach(function(e){var r=i.render(e.severity,e.title,e.message);n.append(r),t.append(r)})}else{o=i.render(s.ok,"No TCA changes in ext_tables.php files. Good job!","");t.find(e.selectorOutputContainer).append(o)}else u.error("Something went wrong",'Use "Check for broken extensions"')},error:function(e){o.handleAjaxError(e,t)}})},t}(n.AbstractInteractableModule))});