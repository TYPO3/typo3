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
var __extends=this&&this.__extends||function(){var t=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(t,e){t.__proto__=e}||function(t,e){for(var n in e)e.hasOwnProperty(n)&&(t[n]=e[n])};return function(e,n){function r(){this.constructor=e}t(e,n),e.prototype=null===n?Object.create(n):(r.prototype=n.prototype,new r)}}();define(["require","exports","./AbstractInteractableModule","jquery","../Router","../Renderable/ProgressBar","../Renderable/Severity","../Renderable/InfoBox","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Notification","bootstrap"],function(t,e,n,r,o,a,s,i,c,u){"use strict";return new(function(t){function e(){var e=null!==t&&t.apply(this,arguments)||this;return e.selectorOutputContainer=".t3js-mailTest-output",e.selectorMailTestButton=".t3js-mailTest-execute",e}return __extends(e,t),e.prototype.initialize=function(t){var e=this;this.currentModal=t,this.getData(),t.on("click",this.selectorMailTestButton,function(t){t.preventDefault(),e.send()})},e.prototype.getData=function(){var t=this.getModalBody();r.ajax({url:o.getUrl("mailTestGetData"),cache:!1,success:function(e){!0===e.success?(t.empty().append(e.html),c.setButtons(e.buttons)):u.error("Something went wrong")},error:function(e){o.handleAjaxError(e,t)}})},e.prototype.send=function(){var t=this.getModuleContent().data("mail-test-token"),e=this.findInModal(this.selectorOutputContainer),n=a.render(s.loading,"Loading...","");e.empty().html(n),r.ajax({url:o.getUrl(),method:"POST",data:{install:{action:"mailTest",token:t,email:this.findInModal(".t3js-mailTest-email").val()}},cache:!1,success:function(t){e.empty(),!0===t.success&&Array.isArray(t.status)?t.status.forEach(function(t){var n=i.render(t.severity,t.title,t.message);e.html(n)}):u.error("Something went wrong")},error:function(){u.error("Something went wrong")}})},e}(n.AbstractInteractableModule))});