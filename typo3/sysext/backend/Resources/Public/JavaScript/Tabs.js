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
define(["require","exports","jquery","./Storage/Client","bootstrap"],function(t,e,i,r){"use strict";return new(function(){function t(){this.cacheTimeInSeconds=1800,this.storeLastActiveTab=!0,this.storage=r;var t=this;i(function(){i(".t3js-tabs").each(function(){var e=i(this);t.storeLastActiveTab=1===e.data("storeLastTab");var r=t.receiveActiveTab(e.attr("id"));r&&e.find('a[href="'+r+'"]').tab("show"),e.on("show.bs.tab",function(e){if(t.storeLastActiveTab){var i=e.currentTarget.id,r=e.target.hash;t.storeActiveTab(i,r)}})})})}return t.getTimestamp=function(){return Math.round((new Date).getTime()/1e3)},t.prototype.receiveActiveTab=function(e){var i=this.storage.get(e)||"";return(this.storage.get(e+".expire")||0)>t.getTimestamp()?i:""},t.prototype.storeActiveTab=function(e,i){this.storage.set(e,i),this.storage.set(e+".expire",t.getTimestamp()+this.cacheTimeInSeconds)},t}())});