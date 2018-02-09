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
define(["require","exports","jquery"],function(a,b,c){"use strict";var d=function(){function a(){var a=this;this.data=!1,this.get=function(b){var c=a;if(a.data===!1){var d;return a.loadFromServer().done(function(){d=c.getRecursiveDataByDeepKey(c.data,b.split("."))}),d}return a.getRecursiveDataByDeepKey(a.data,b.split("."))},this.set=function(b,c){return a.data!==!1&&(a.data=a.setRecursiveDataByDeepKey(a.data,b.split("."),c)),a.storeOnServer(b,c)},this.addToList=function(b,d){var e=a;return c.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"addToList",key:b,value:d},method:"post"}).done(function(a){e.data=a})},this.removeFromList=function(b,d){var e=a;return c.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"removeFromList",key:b,value:d},method:"post"}).done(function(a){e.data=a})},this.unset=function(b){var d=a;return c.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"unset",key:b},method:"post"}).done(function(a){d.data=a})},this.clear=function(){c.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"clear"}}),a.data=!1},this.isset=function(b){var c=a.get(b);return"undefined"!=typeof c&&null!==c},this.load=function(b){a.data=b},this.loadFromServer=function(){var b=a;return c.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{async:!1,data:{action:"getAll"}}).done(function(a){b.data=a})},this.storeOnServer=function(b,d){var e=a;return c.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"set",key:b,value:d},method:"post"}).done(function(a){e.data=a})},this.getRecursiveDataByDeepKey=function(b,c){if(1===c.length)return(b||{})[c[0]];var d=c.shift();return a.getRecursiveDataByDeepKey(b[d]||{},c)},this.setRecursiveDataByDeepKey=function(b,c,d){if(1===c.length)b=b||{},b[c[0]]=d;else{var e=c.shift();b[e]=a.setRecursiveDataByDeepKey(b[e]||{},c,d)}return b}}return a}();return new d});