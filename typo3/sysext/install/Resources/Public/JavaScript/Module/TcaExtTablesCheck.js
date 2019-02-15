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
define(["require","exports","jquery","../Router","../Renderable/ProgressBar","../Renderable/Severity","../Renderable/InfoBox","TYPO3/CMS/Backend/Notification"],function(e,t,n,r,o,i,s,a){"use strict";return new(function(){function e(){this.selectorModalBody=".t3js-modal-body",this.selectorCheckTrigger=".t3js-tcaExtTablesCheck-check",this.selectorOutputContainer=".t3js-tcaExtTablesCheck-output"}return e.prototype.initialize=function(e){var t=this;this.currentModal=e,this.check(),e.on("click",this.selectorCheckTrigger,function(e){e.preventDefault(),t.check()})},e.prototype.check=function(){var e=this,t=this.currentModal.find(this.selectorModalBody),c=n(this.selectorOutputContainer),l=o.render(i.loading,"Loading...","");c.empty().html(l),n.ajax({url:r.getUrl("tcaExtTablesCheck"),cache:!1,success:function(n){if(t.empty().append(n.html),!0===n.success&&Array.isArray(n.status))if(n.status.length>0){var r=s.render(i.warning,"Extensions change TCA in ext_tables.php",'Check for ExtensionManagementUtility and $GLOBALS["TCA"]');t.find(e.selectorOutputContainer).append(r),n.status.forEach(function(e){var n=s.render(e.severity,e.title,e.message);c.append(n),t.append(n)})}else{r=s.render(i.ok,"No TCA changes in ext_tables.php files. Good job!","");t.find(e.selectorOutputContainer).append(r)}else a.error("Something went wrong",'Use "Check for broken extensions"')},error:function(e){r.handleAjaxError(e,t)}})},e}())});