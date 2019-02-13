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
define(["require","exports","jquery"],function(t,e,r){"use strict";return new(function(){function t(){var t=this;this.thisScriptUrl="",this.urlParameters={},this.parameters={},this.addOnParams="",this.additionalLinkAttributes={},this.loadTarget=function(t){var e=r(t.currentTarget);r(".t3js-linkTarget").val(e.val()),e.get(0).selectedIndex=0},r(function(){var e=r("body").data();t.thisScriptUrl=e.thisScriptUrl,t.urlParameters=e.urlParameters,t.parameters=e.parameters,t.addOnParams=e.addOnParams,t.linkAttributeFields=e.linkAttributeFields,r(".t3js-targetPreselect").on("change",t.loadTarget),r("form.t3js-dummyform").on("submit",function(t){t.preventDefault()})}),window.jumpToUrl=function(e,r){"?"===e.charAt(0)&&(e=t.thisScriptUrl+e.substring(1));var i=t.encodeGetParameters(t.urlParameters,"",e),n=t.encodeGetParameters(t.getLinkAttributeValues(),"linkAttributes","");return window.location.href=e+i+n+t.addOnParams+("string"==typeof r?r:""),!1}}return t.prototype.getLinkAttributeValues=function(){var t={};return r.each(this.linkAttributeFields,function(e,i){var n=r('[name="l'+i+'"]').val();n&&(t[i]=n)}),r.extend(t,this.additionalLinkAttributes),t},t.prototype.encodeGetParameters=function(t,e,r){var i=[];for(var n in t)if(t.hasOwnProperty(n)){var a=e?e+"["+n+"]":n,o=t[n];-1===r.indexOf(a+"=")&&i.push("object"==typeof o?this.encodeGetParameters(o,a,r):encodeURIComponent(a)+"="+encodeURIComponent(o))}return"&"+i.join("&")},t.prototype.setAdditionalLinkAttribute=function(t,e){this.additionalLinkAttributes[t]=e},t.prototype.finalizeFunction=function(t){throw"The link browser requires the finalizeFunction to be set. Seems like you discovered a major bug."},t}())});