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
var __decorate=function(r,e,t,a){var o,s=arguments.length,i=s<3?e:null===a?a=Object.getOwnPropertyDescriptor(e,t):a;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(r,e,t,a);else for(var n=r.length-1;n>=0;n--)(o=r[n])&&(i=(s<3?o(i):s>3?o(e,t,i):o(e,t))||i);return s>3&&i&&Object.defineProperty(e,t,i),i};import{css,html,LitElement,nothing}from"lit";import{customElement,property}from"lit/decorators.js";import{classMap}from"lit/directives/class-map.js";import{styleMap}from"lit/directives/style-map.js";import Severity from"@typo3/backend/severity.js";import{SeverityEnum}from"@typo3/backend/enum/severity.js";let ProgressBarElement=class extends LitElement{constructor(){super(...arguments),this.value=void 0,this.max=100,this.severity=SeverityEnum.info}render(){const r="progress-label-"+(Math.random()+1).toString(36).substring(2),e=void 0!==this.label&&this.label,t=isNaN(this.value),a="bar-"+Severity.getCssClass(this.severity),o=classMap({bar:!0,[a]:!t,indeterminate:t}),s=t?nothing:styleMap({width:(this.clamp(this.value,0,this.max)/this.max*100).toString()+"%"});return html`
      <div class="progress-wrapper">
        <div
          role="progressbar"
          class="progress"
          aria-valuenow=${t?nothing:this.value}
          aria-valuemin="0"
          aria-valuemax=${this.max}
          aria-describedby=${e?r:nothing}
        >
          <div class="track"></div>
          <div class=${o} style=${s}></div>
        </div>
        ${e?html`<div class="label" id=${r}>${this.label}</div>`:nothing}
      </div>
    `}clamp(r,e,t){return Math.min(t,Math.max(e,r))}};ProgressBarElement.styles=css`
    @keyframes progress-indeterminate {
      0% {
        inset-inline-start: -33%;
      }

      100% {
        inset-inline-start: 100%;
      }
    }

    :host {
      --progress-bar-height: 3px;
      --progress-track-bg-color: light-dark(var(--bs-gray-300), var(--bs-gray-800));
      display: block;
      width: 100%;
      border-radius: var(--typo3-component-border-radius);
    }

    .progress {
      position: relative;
      overflow: hidden;
      height: var(--progress-bar-height);
      border-radius: var(--typo3-component-border-radius);
    }

    .track {
      background: var(--progress-track-bg-color);
      inset: 0;
    }

    .bar {
      --progress-bar-bg-color: var(--typo3-component-primary-color);
      background: var(--progress-bar-bg-color);
      transition: width 0.5s ease-in-out;

      &.bar-success {
        --progress-bar-bg-color: var(--bs-success);
      }

      &.bar-warning {
        --progress-bar-bg-color: var(--bs-warning);
      }

      &.bar-danger {
        --progress-bar-bg-color: var(--bs-danger);
      }

      &.indeterminate {
        animation-name: progress-indeterminate;
        animation-duration: 3s;
        animation-iteration-count: infinite;
        animation-timing-function: linear;
        width: 33%;
        background-image: linear-gradient(to right, var(--progress-track-bg-color) 0%, transparent 50%, var(--progress-track-bg-color) 100%)
      }
    }

    .track,
    .bar {
      position: absolute;
      height: var(--progress-bar-height);
      border-radius: var(--typo3-component-border-radius);
    }

    .label {
      margin-top: 2px;
    }
  `,__decorate([property({type:Number,reflect:!0})],ProgressBarElement.prototype,"value",void 0),__decorate([property({type:Number,reflect:!0})],ProgressBarElement.prototype,"max",void 0),__decorate([property({type:Number,reflect:!0})],ProgressBarElement.prototype,"severity",void 0),__decorate([property({type:String,reflect:!0})],ProgressBarElement.prototype,"label",void 0),ProgressBarElement=__decorate([customElement("typo3-backend-progress-bar")],ProgressBarElement);export{ProgressBarElement};