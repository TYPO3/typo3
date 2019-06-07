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
var __extends=this&&this.__extends||function(){var t=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(t,e){t.__proto__=e}||function(t,e){for(var r in e)e.hasOwnProperty(r)&&(t[r]=e[r])};return function(e,r){function n(){this.constructor=e}t(e,r),e.prototype=null===r?Object.create(r):(n.prototype=r.prototype,new n)}}();define(["require","exports","./AbstractInteractableModule","jquery","../Router","../Renderable/ProgressBar","../Renderable/Severity","../Renderable/InfoBox","TYPO3/CMS/Backend/Notification","bootstrap"],function(t,e,r,n,o,a,i,s,c){"use strict";return new(function(t){function e(){var e=null!==t&&t.apply(this,arguments)||this;return e.selectorForm="#t3js-mailTest-form",e.selectorOutputContainer=".t3js-mailTest-output",e}return __extends(e,t),e.prototype.initialize=function(t){var e=this;this.currentModal=t,this.getData(),t.on("submit",this.selectorForm,function(t){t.preventDefault(),e.send()})},e.prototype.getData=function(){var t=this.getModalBody();n.ajax({url:o.getUrl("mailTestGetData"),cache:!1,success:function(e){!0===e.success?t.empty().append(e.html):c.error("Something went wrong")},error:function(e){o.handleAjaxError(e,t)}})},e.prototype.send=function(){var t=this.getModuleContent().data("mail-test-token"),e=this.findInModal(this.selectorOutputContainer),r=a.render(i.loading,"Loading...","");e.empty().html(r),n.ajax({url:o.getUrl(),method:"POST",data:{install:{action:"mailTest",token:t,email:this.findInModal(".t3js-mailTest-email").val()}},cache:!1,success:function(t){e.empty(),!0===t.success&&Array.isArray(t.status)?t.status.forEach(function(t){var r=s.render(t.severity,t.title,t.message);e.html(r)}):c.error("Something went wrong")},error:function(){c.error("Something went wrong")}})},e}(r.AbstractInteractableModule))});