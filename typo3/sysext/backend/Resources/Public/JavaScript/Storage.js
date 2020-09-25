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
define(["require","exports","./Storage/Client","./Storage/Persistent"],(function(e,t,n,r){"use strict";var o,i=function(){var e=this;this.logDeprecated=function(e,t){console&&console.warn("top.TYPO3.Storage."+e+"."+t+"() is marked as deprecated since TYPO3 v9 and will be removed in TYPO3 v10.")},this.Client={clear:function(){e.logDeprecated("Client","clear"),n.clear()},get:function(t){return e.logDeprecated("Client","get"),n.get(t)},isset:function(t){return e.logDeprecated("Client","isset"),n.isset(t)},set:function(t,r){return e.logDeprecated("Client","set"),n.set(t,r)},unset:function(t){return e.logDeprecated("Client","unset"),n.unset(t)}},this.Persistent={addToList:function(t,n){return e.logDeprecated("Persistent","addToList"),r.addToList(t,n)},clear:function(){e.logDeprecated("Persistent","clear"),r.clear()},get:function(t){return e.logDeprecated("Persistent","get"),r.get(t)},isset:function(t){return e.logDeprecated("Persistent","isset"),r.isset(t)},load:function(t){return e.logDeprecated("Persistent","load"),r.load(t)},removeFromList:function(t,n){return e.logDeprecated("Persistent","removeFromList"),r.removeFromList(t,n)},set:function(t,n){return e.logDeprecated("Persistent","set"),r.set(t,n)},unset:function(t){return e.logDeprecated("Persistent","unset"),r.unset(t)}}};try{window.opener&&window.opener.TYPO3&&window.opener.TYPO3.Storage&&(o=window.opener.TYPO3.Storage),parent&&parent.window.TYPO3&&parent.window.TYPO3.Storage&&(o=parent.window.TYPO3.Storage),top&&top.TYPO3.Storage&&(o=top.TYPO3.Storage)}catch(e){}return o||(o=new i),TYPO3.Storage=o,o}));