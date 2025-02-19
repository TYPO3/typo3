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
class e{static{this.prefix="t3-module-state-"}static update(s,t){if(typeof t=="number")t=t.toString(10);else if(typeof t!="string")throw new SyntaxError("identifier must be of type string");const r=e.current(s),n=t===r.identifier?r.treeIdentifier:null,i={identifier:t,treeIdentifier:n};return e.commit(s,"update",i),i}static updateWithTreeIdentifier(s,t,r){if(typeof t=="number")t=t.toString(10);else if(typeof t!="string")throw new SyntaxError("identifier must be of type string");if(typeof r=="number")r=r.toString(10);else if(typeof r!="string")throw new SyntaxError("treeIdentifier must be of type string");const n={identifier:t,treeIdentifier:r};return e.commit(s,"update-with-tree-identifier",n),n}static updateWithCurrentMount(s,t){e.update(s,t)}static current(s){return{...e.getInitialState(),...e.fetch(s)??{}}}static purge(){Object.keys(sessionStorage).filter(s=>s.startsWith(e.prefix)).forEach(s=>sessionStorage.removeItem(s))}static fetch(s){const t=sessionStorage.getItem(e.prefix+s);return t===null?null:JSON.parse(t)}static async commit(s,t,r){const n=e.current(s);sessionStorage.setItem(e.prefix+s,JSON.stringify(r)),top.document.dispatchEvent(new CustomEvent("typo3:module-state-storage:"+t+":"+s,{detail:{state:r,oldState:n}}))}static getInitialState(){return{identifier:"",treeIdentifier:null}}}window.ModuleStateStorage=e;export{e as ModuleStateStorage};
