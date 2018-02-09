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
define(["require","exports","./Storage/Client","./Storage/Persistent"],function(a,b,c,d){"use strict";var e,f=function(){function a(){var a=this;this.logDeprecated=function(a,b){console&&console.warn("top.TYPO3.Storage."+a+"."+b+"() is marked as deprecated since TYPO3 v9 and will be removed in TYPO3 v10.")},this.Client={clear:function(){a.logDeprecated("Client","clear"),c.clear()},get:function(b){return a.logDeprecated("Client","get"),c.get(b)},isset:function(b){return a.logDeprecated("Client","isset"),c.isset(b)},set:function(b,d){return a.logDeprecated("Client","set"),c.set(b,d)},unset:function(b){return a.logDeprecated("Client","unset"),c.unset(b)}},this.Persistent={addToList:function(b,c){return a.logDeprecated("Persistent","addToList"),d.addToList(b,c)},clear:function(){a.logDeprecated("Persistent","clear"),d.clear()},get:function(b){return a.logDeprecated("Persistent","get"),d.get(b)},isset:function(b){return a.logDeprecated("Persistent","isset"),d.isset(b)},load:function(b){return a.logDeprecated("Persistent","load"),d.load(b)},removeFromList:function(b,c){return a.logDeprecated("Persistent","removeFromList"),d.removeFromList(b,c)},set:function(b,c){return a.logDeprecated("Persistent","set"),d.set(b,c)},unset:function(b){return a.logDeprecated("Persistent","unset"),d.unset(b)}}}return a}();try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.Storage&&(e=window.opener.TYPO3.Storage),parent&&parent.window.TYPO3&&parent.window.TYPO3.Storage&&(e=parent.window.TYPO3.Storage),top&&top.TYPO3.Storage&&(e=top.TYPO3.Storage)}catch(a){}return e||(e=new f),TYPO3.Storage=e,e});