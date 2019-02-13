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
define(["require","exports","jquery","TYPO3/CMS/Backend/Storage/Persistent","TYPO3/CMS/Backend/Icons"],function(t,e,i,a,n){"use strict";return new function(){var t=this;this.identifier={entity:".t3js-entity",toggle:".t3js-toggle-recordlist",icons:{collapse:"actions-view-list-collapse",expand:"actions-view-list-expand",editMultiple:".t3js-record-edit-multiple"}},this.toggleClick=function(e){e.preventDefault();var l=i(e.currentTarget),s=l.data("table"),d=i(l.data("target")),r="expanded"===d.data("state"),o=l.find(".collapseIcon"),c=r?t.identifier.icons.expand:t.identifier.icons.collapse;n.getIcon(c,n.sizes.small).done(function(t){o.html(t)});var u={};a.isset("moduleData.list")&&(u=a.get("moduleData.list"));var f={};f[s]=r?1:0,i.extend(!0,u,f),a.set("moduleData.list",u).done(function(){d.data("state",r?"collapsed":"expanded")})},this.onEditMultiple=function(e){var a,n,l,s,d;e.preventDefault(),0!==(a=i(e.currentTarget).closest("[data-table]")).length&&(s=i(e.currentTarget).data("uri"),n=a.data("table"),l=a.find(t.identifier.entity+'[data-uid][data-table="'+n+'"]').map(function(t,e){return i(e).data("uid")}).toArray().join(","),d=s.match(/{[^}]+}/g),i.each(d,function(t,e){var a,d=e.substr(1,e.length-2).split(":");switch(d.shift()){case"entityIdentifiers":a=l;break;case"T3_THIS_LOCATION":a=T3_THIS_LOCATION;break;default:return}i.each(d,function(t,e){"editList"===e&&(a=editList(n,a))}),s=s.replace(e,a)}),window.location.href=s)},i(document).on("click",this.identifier.toggle,this.toggleClick),i(document).on("click",this.identifier.icons.editMultiple,this.onEditMultiple)}});