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
define(["require","exports","jquery","../Router","../Renderable/ProgressBar","../Renderable/FlashMessage","../Renderable/Severity","../Renderable/InfoBox"],function(e,t,r,n,o,i,s,a){"use strict";return new(function(){function e(){this.selectorModalBody=".t3js-modal-body",this.selectorCheckTrigger=".t3js-tcaMigrationsCheck-check",this.selectorOutputContainer=".t3js-tcaMigrationsCheck-output"}return e.prototype.initialize=function(e){var t=this;this.currentModal=e,this.check(),e.on("click",this.selectorCheckTrigger,function(e){e.preventDefault(),t.check()})},e.prototype.check=function(){var e=this,t=r(this.selectorOutputContainer),c=this.currentModal.find(this.selectorModalBody),l=o.render(s.loading,"Loading...","");t.empty().html(l),r.ajax({url:n.getUrl("tcaMigrationsCheck"),cache:!1,success:function(t){if(c.empty().append(t.html),!0===t.success&&Array.isArray(t.status))if(t.status.length>0){var r=a.render(s.warning,"TCA migrations need to be applied","Check the following list and apply needed changes.");c.find(e.selectorOutputContainer).empty(),c.find(e.selectorOutputContainer).append(r),t.status.forEach(function(t){var r=a.render(t.severity,t.title,t.message);c.find(e.selectorOutputContainer).append(r)})}else{var n=a.render(s.ok,"No TCA migrations need to be applied","Your TCA looks good.");c.find(e.selectorOutputContainer).append(n)}else{var o=i.render(s.error,"Something went wrong",'Use "Check for broken extensions"');c.find(e.selectorOutputContainer).append(o)}},error:function(e){n.handleAjaxError(e,c)}})},e}())});