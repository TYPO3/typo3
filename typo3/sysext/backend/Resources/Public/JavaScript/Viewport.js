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
define(["require","exports","jquery","./Viewport/ContentContainer","./Event/ConsumerScope","./Viewport/Loader","./Viewport/NavigationContainer","./Viewport/Topbar"],function(t,n,i,o,e,a,r,s){"use strict";var c,u=function(){function t(){var t=this;this.Loader=a,this.Topbar=s,this.NavigationContainer=null,this.ContentContainer=null,this.consumerScope=e,i(function(){t.initialize()}),this.NavigationContainer=new r(this.consumerScope),this.ContentContainer=new o(this.consumerScope)}return t.prototype.initialize=function(){var t=this;this.doLayout(),i(window).on("resize",function(){t.doLayout()})},t.prototype.doLayout=function(){this.NavigationContainer.cleanup(),this.NavigationContainer.calculateScrollbar(),i(".t3js-topbar-header").css("padding-right",i(".t3js-scaffold-toolbar").outerWidth())},t}();return TYPO3.Backend?c=TYPO3.Backend:(c=new u,TYPO3.Backend=c),c});