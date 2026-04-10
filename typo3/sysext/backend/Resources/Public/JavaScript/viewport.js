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
import e from"@typo3/backend/viewport/content-container.js";import n from"@typo3/backend/event/consumer-scope.js";import i from"@typo3/backend/viewport/loader.js";import r from"@typo3/backend/viewport/navigation-container.js";import a from"@typo3/backend/viewport/topbar.js";import{topWindow as o}from"@typo3/core/utility/top-window.js";class p{constructor(){this.Loader=i,this.NavigationContainer=null,this.ContentContainer=null,this.consumerScope=n,this.Topbar=new a,this.NavigationContainer=new r(this.consumerScope),this.ContentContainer=new e(this.consumerScope)}}let t;!o.TYPO3||!o.TYPO3.Backend?(t=new p,typeof o.TYPO3<"u"&&(o.TYPO3.Backend=t)):t=o.TYPO3.Backend;var s=t;export{s as default};
