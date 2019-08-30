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
define(["require","exports","../AbstractInteractableModule","jquery","../../Router","TYPO3/CMS/Backend/Notification"],function(e,t,r,a,n,s){"use strict";return new class extends r.AbstractInteractableModule{initialize(e){this.currentModal=e,this.getData()}getData(){const e=this.getModalBody();a.ajax({url:n.getUrl("phpInfoGetData"),cache:!1,success:t=>{!0===t.success?e.empty().append(t.html):s.error("Something went wrong")},error:t=>{n.handleAjaxError(t,e)}})}}});