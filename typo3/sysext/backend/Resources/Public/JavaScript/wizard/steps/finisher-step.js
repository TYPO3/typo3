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
import{html as n,nothing as d}from"lit";import{until as a}from"lit/directives/until.js";import{Task as u,TaskStatus as o}from"@lit/task";import s from"~labels/backend.wizards.general";class h{constructor(r,i){this.wizard=r,this.finisher=i,this.key="finisher",this.title=s.get("step.finisher.title"),this.autoAdvance=!1,this.finisherInstance=null,this.hasError=!1,this.task=new u(this.wizard,{task:async([e])=>e.execute(),args:()=>[this.finisher],autoRun:!1})}isComplete(){return this.task.status===o.COMPLETE}async beforeAdvance(){if(this.hasError){this.wizard.dismissWizard();return}if(!this.finisherInstance)throw new Error("Finisher instance not loaded");this.wizard.dismissWizard(),await this.finisherInstance.execute()}render(){return this.task.status===o.INITIAL&&this.task.run(),this.task.render({pending:()=>this.wizard.renderLoader(s.get("wizard.status.pending.message")),error:r=>(this.hasError=!0,this.wizard.renderError(s.get("wizard.status.error.message"),r)),complete:r=>r.success===!1?(this.hasError=!0,this.wizard.renderError(s.get("wizard.status.error.message"),r.errors)):this.renderFinisher(r.finisher)})}renderFinisher(r){if(!this.finisherInstance){const i=this.loadFinisher(r).then(e=>(this.finisherInstance=e,e.render())).catch(e=>(console.error("Failed to load finisher:",e),this.hasError=!0,this.wizard.renderError(s.get("wizard.finisher.load_error.message"),e)));return n`${a(i,this.wizard.renderLoader(s.get("wizard.loading_finisher")))}`}return n`${a(this.finisherInstance.render(),d)}`}async loadFinisher(r){if(!r.module)throw new Error("Finisher data does not contain a module path");const e=(await import(r.module)).default;if(!e)throw new Error(`Finisher module ${r.module} does not export a default class`);const t=new e;return t.setConfig(r),t}}export{h as FinisherStep,h as default};
