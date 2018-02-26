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
define(["require","exports","jquery","./Viewport/ContentContainer","./Event/ConsumerScope","./Viewport/Loader","./Viewport/NavigationContainer","./Viewport/Topbar"],function(t,n,o,i,e,a,r,s){"use strict";var c,u=function(){function t(){var t=this;this.Loader=a,this.Topbar=s,this.NavigationContainer=null,this.ContentContainer=null,this.consumerScope=e,o(function(){t.initialize()}),this.NavigationContainer=new r(this.consumerScope),this.ContentContainer=new i(this.consumerScope)}return t.prototype.initialize=function(){var t=this;this.doLayout(),o(window).on("resize",function(){t.doLayout()})},t.prototype.doLayout=function(){this.NavigationContainer.cleanup(),this.NavigationContainer.calculateScrollbar(),o(".t3js-topbar-header").css("padding-right",o(".t3js-scaffold-toolbar").outerWidth())},t}();return top.TYPO3.Backend?c=top.TYPO3.Backend:(c=new u,top.TYPO3.Backend=c),c});