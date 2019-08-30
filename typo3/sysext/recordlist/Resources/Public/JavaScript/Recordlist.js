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
define(["require","exports","jquery","TYPO3/CMS/Backend/Storage/Persistent","TYPO3/CMS/Backend/Icons"],function(t,e,i,a,s){"use strict";return new class{constructor(){this.identifier={entity:".t3js-entity",toggle:".t3js-toggle-recordlist",localize:".t3js-action-localize",icons:{collapse:"actions-view-list-collapse",expand:"actions-view-list-expand",editMultiple:".t3js-record-edit-multiple"}},this.toggleClick=(t=>{t.preventDefault();const e=i(t.currentTarget),l=e.data("table"),n=i(e.data("target")),d="expanded"===n.data("state"),o=e.find(".collapseIcon"),c=d?this.identifier.icons.expand:this.identifier.icons.collapse;s.getIcon(c,s.sizes.small).done(t=>{o.html(t)});let r={};a.isset("moduleData.list")&&(r=a.get("moduleData.list"));const u={};u[l]=d?1:0,i.extend(!0,r,u),a.set("moduleData.list",r).done(()=>{n.data("state",d?"collapsed":"expanded")})}),this.onEditMultiple=(t=>{let e,a,s,l,n;t.preventDefault(),0!==(e=i(t.currentTarget).closest("[data-table]")).length&&(l=i(t.currentTarget).data("uri"),a=e.data("table"),s=e.find(this.identifier.entity+'[data-uid][data-table="'+a+'"]').map((t,e)=>i(e).data("uid")).toArray().join(","),n=l.match(/{[^}]+}/g),i.each(n,(t,e)=>{const n=e.substr(1,e.length-2).split(":");let d;switch(n.shift()){case"entityIdentifiers":d=s;break;case"T3_THIS_LOCATION":d=T3_THIS_LOCATION;break;default:return}i.each(n,(t,e)=>{"editList"===e&&(d=editList(a,d))}),l=l.replace(e,d)}),window.location.href=l)}),this.disableButton=(t=>{i(t.currentTarget).prop("disable",!0).addClass("disabled")}),i(document).on("click",this.identifier.toggle,this.toggleClick),i(document).on("click",this.identifier.icons.editMultiple,this.onEditMultiple),i(document).on("click",this.identifier.localize,this.disableButton)}}});