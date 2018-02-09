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
define(["require","exports","jquery","./Storage/Client","bootstrap"],function(a,b,c,d){"use strict";var e=function(){function a(){this.cacheTimeInSeconds=1800,this.storeLastActiveTab=!0,this.storage=d;var a=this;c(".t3js-tabs").each(function(){var b=c(this);a.storeLastActiveTab=1===b.data("storeLastTab");var d=a.receiveActiveTab(b.attr("id"));d&&b.find('a[href="'+d+'"]').tab("show"),b.on("show.bs.tab",function(b){if(a.storeLastActiveTab){var c=b.currentTarget.id,d=b.target.hash;a.storeActiveTab(c,d)}})})}return a.getTimestamp=function(){return Math.round((new Date).getTime()/1e3)},a.prototype.receiveActiveTab=function(b){var c=this.storage.get(b)||"",d=this.storage.get(b+".expire")||0;return d>a.getTimestamp()?c:""},a.prototype.storeActiveTab=function(b,c){this.storage.set(b,c),this.storage.set(b+".expire",a.getTimestamp()+this.cacheTimeInSeconds)},a}(),f=new e;return f});