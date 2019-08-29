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
define(["require","exports","jquery","TYPO3/CMS/Backend/Storage/Persistent","TYPO3/CMS/Backend/Icons"],function(t,e,i,n,a){"use strict";return new(function(){return function(){var t=this;this.identifier={entity:".t3js-entity",toggle:".t3js-toggle-recordlist",localize:".t3js-action-localize",icons:{collapse:"actions-view-list-collapse",expand:"actions-view-list-expand",editMultiple:".t3js-record-edit-multiple"}},this.toggleClick=function(e){e.preventDefault();var l=i(e.currentTarget),s=l.data("table"),d=i(l.data("target")),o="expanded"===d.data("state"),c=l.find(".collapseIcon"),r=o?t.identifier.icons.expand:t.identifier.icons.collapse;a.getIcon(r,a.sizes.small).done(function(t){c.html(t)});var u={};n.isset("moduleData.list")&&(u=n.get("moduleData.list"));var f={};f[s]=o?1:0,i.extend(!0,u,f),n.set("moduleData.list",u).done(function(){d.data("state",o?"collapsed":"expanded")})},this.onEditMultiple=function(e){var n,a,l,s,d;e.preventDefault(),0!==(n=i(e.currentTarget).closest("[data-table]")).length&&(s=i(e.currentTarget).data("uri"),a=n.data("table"),l=n.find(t.identifier.entity+'[data-uid][data-table="'+a+'"]').map(function(t,e){return i(e).data("uid")}).toArray().join(","),d=s.match(/{[^}]+}/g),i.each(d,function(t,e){var n,d=e.substr(1,e.length-2).split(":");switch(d.shift()){case"entityIdentifiers":n=l;break;case"T3_THIS_LOCATION":n=T3_THIS_LOCATION;break;default:return}i.each(d,function(t,e){"editList"===e&&(n=editList(a,n))}),s=s.replace(e,n)}),window.location.href=s)},this.disableButton=function(t){i(t.currentTarget).prop("disable",!0).addClass("disabled")},i(document).on("click",this.identifier.toggle,this.toggleClick),i(document).on("click",this.identifier.icons.editMultiple,this.onEditMultiple),i(document).on("click",this.identifier.localize,this.disableButton)}}())});