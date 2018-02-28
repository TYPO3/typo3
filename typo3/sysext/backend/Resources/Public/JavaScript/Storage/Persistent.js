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
define(["require","exports","jquery"],function(t,e,a){"use strict";return new function(){var t=this;this.data=!1,this.get=function(e){var a,s=t;return!1===t.data?(t.loadFromServer().done(function(){a=s.getRecursiveDataByDeepKey(s.data,e.split("."))}),a):t.getRecursiveDataByDeepKey(t.data,e.split("."))},this.set=function(e,a){return!1!==t.data&&(t.data=t.setRecursiveDataByDeepKey(t.data,e.split("."),a)),t.storeOnServer(e,a)},this.addToList=function(e,s){var n=t;return a.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"addToList",key:e,value:s},method:"post"}).done(function(t){n.data=t})},this.removeFromList=function(e,s){var n=t;return a.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"removeFromList",key:e,value:s},method:"post"}).done(function(t){n.data=t})},this.unset=function(e){var s=t;return a.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"unset",key:e},method:"post"}).done(function(t){s.data=t})},this.clear=function(){a.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"clear"}}),t.data=!1},this.isset=function(e){var a=t.get(e);return null!=a},this.load=function(e){t.data=e},this.loadFromServer=function(){var e=t;return a.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{async:!1,data:{action:"getAll"}}).done(function(t){e.data=t})},this.storeOnServer=function(e,s){var n=t;return a.ajax(TYPO3.settings.ajaxUrls.usersettings_process,{data:{action:"set",key:e,value:s},method:"post"}).done(function(t){n.data=t})},this.getRecursiveDataByDeepKey=function(e,a){if(1===a.length)return(e||{})[a[0]];var s=a.shift();return t.getRecursiveDataByDeepKey(e[s]||{},a)},this.setRecursiveDataByDeepKey=function(e,a,s){if(1===a.length)(e=e||{})[a[0]]=s;else{var n=a.shift();e[n]=t.setRecursiveDataByDeepKey(e[n]||{},a,s)}return e}}});