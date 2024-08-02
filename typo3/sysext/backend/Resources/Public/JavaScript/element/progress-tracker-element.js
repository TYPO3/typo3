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
var __decorate=function(r,e,t,a){var o,s=arguments.length,c=s<3?e:null===a?a=Object.getOwnPropertyDescriptor(e,t):a;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)c=Reflect.decorate(r,e,t,a);else for(var i=r.length-1;i>=0;i--)(o=r[i])&&(c=(s<3?o(c):s>3?o(e,t,c):o(e,t))||c);return s>3&&c&&Object.defineProperty(e,t,c),c};import{css,html,LitElement,nothing}from"lit";import{customElement,property}from"lit/decorators.js";import{styleMap}from"lit/directives/style-map.js";import{classMap}from"lit/directives/class-map.js";let ProgressTrackerElement=class extends LitElement{constructor(){super(...arguments),this.stages=[],this.activeStage=0}render(){if(this.stages.length<2)return nothing;this.activeStage=Math.min(this.activeStage,this.stages.length-1);const r=styleMap({"--progress-stage-active":`${this.activeStage}`,"--progress-stages-columns":`${this.stages.length}`});return html`
      <div class="track-wrapper" role="group" style=${r}>
        <div class="track-bar-wrapper">
          <div class="track-bar track-bar-rail"></div>
          <div class="track-bar track-bar-active"></div>
        </div>
        <ul class="stages">
          ${this.stages.map(((r,e)=>this.renderStage(r,e)))}
        </ul>
      </div>
    `}renderStage(r,e){const t=classMap({"stage-indicator":!0,"stage-indicator-complete":e<this.activeStage,"stage-indicator-current":e===this.activeStage,"stage-indicator-open":e>this.activeStage});return html`
      <li class="stage" aria-current="${e===this.activeStage?"step":"false"}">
        <span class=${t}></span>
        <span class="stage-label">${r}</span>
      </li>
    `}};ProgressTrackerElement.styles=css`
    :host {
      --progress-stage-active: 0;
      --progress-stages-columns: 1;

      /* Progress bar skeleton */
      --progress-tracker-bar-height: 4px;
      --progress-tracker-bar-border-radius: var(--progress-tracker-bar-height);
      --progress-tracker-bar-progress-bg-color: var(--typo3-component-primary-color);
      --progress-tracker-bar-rail-bg-color: light-dark(var(--token-color-neutral-20), var(--token-color-neutral-70));

      /* Indicator skeleton */
      --progress-tracker-indicator-width: 12px;
      --progress-tracker-indicator-height: 12px;
      --progress-tracker-indicator-border-radius: 100%;
      --progress-tracker-indicator-border-height: 1px;

      /* Indicator background colors */
      --progress-tracker-indicator-bg-color-default: light-dark(var(--token-color-neutral-20), var(--token-color-neutral-70));
      --progress-tracker-indicator-bg-color-complete: var(--typo3-component-primary-color);
      --progress-tracker-indicator-bg-color-current: light-dark(var(--token-color-neutral-0), var(--token-color-neutral-90));

      /* Indicator border colors */
      --progress-tracker-indicator-border-color-complete: var(--typo3-component-primary-color);
      --progress-tracker-indicator-border-color-current: var(--typo3-component-primary-color);

      /* Indicator inlet */
      --progress-tracker-indicator-inlet-scale-complete: 1;
      --progress-tracker-indicator-inlet-scale-current: 0.6;
      --progress-tracker-indicator-inlet-bg-color: var(--progress-tracker-indicator-border-color-current);
      --progress-tracker-indicator-inlet-border-radius: 100%;

      display: block;
      width: 100%;
    }

    .track-wrapper {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .track-bar-wrapper {
      position: relative;
      top: calc((var(--progress-tracker-indicator-height) - var(--progress-tracker-bar-height)) / 2 + var(--progress-tracker-indicator-border-height));
      width: 100%;
      height: var(--progress-tracker-bar-height);
    }

    .track-bar {
      --progress-tracker-bar-inset: calc(calc(100% / var(--progress-stages-columns) / 2) - var(--progress-tracker-indicator-width) / 2);

      position: absolute;
      height: 100%;
      inset-inline-start: var(--progress-tracker-bar-inset);
      border-radius: var(--progress-tracker-bar-border-radius);
    }

    .track-bar-rail {
      --progress-tracker-rail-width: calc(calc(100% / var(--progress-stages-columns) * calc(var(--progress-stages-columns) - 1)) + calc(var(--progress-tracker-indicator-width)));

      width: var(--progress-tracker-rail-width);
      background-color: var(--progress-tracker-bar-rail-bg-color);
    }

    .track-bar-active {
      --progress-tracker-bar-width: calc(calc(100% / var(--progress-stages-columns) * var(--progress-stage-active)) + calc(var(--progress-tracker-indicator-width)));

      width: var(--progress-tracker-bar-width, 0);
      background-color: var(--progress-tracker-bar-progress-bg-color);
    }

    .stages {
      display: grid;
      grid-template-columns: repeat(var(--progress-stages-columns), 1fr);
      list-style: none;
      padding: 0;
      margin: 0;
      width: 100%;
      position: relative;
      top: calc(var(--progress-tracker-bar-height) * -1);
    }

    .stage {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .stage-indicator {
      display: block;
      width: var(--progress-tracker-indicator-width);
      height: var(--progress-tracker-indicator-height);
      background-color: var(--progress-tracker-indicator-bg-color-default);
      border-radius: var(--progress-tracker-indicator-border-radius);
      border: var(--progress-tracker-indicator-border-height) solid transparent;
    }

    .stage-indicator:after {
      display: block;
      content: "";
      width: var(--progress-tracker-indicator-width);
      height: var(--progress-tracker-indicator-height);
      background-color: var(--progress-tracker-indicator-inlet-bg-color);
      transform: scale(0);
      border-radius: var(--progress-tracker-indicator-inlet-border-radius);
    }

    .stage-indicator-complete {
      border-color: var(--progress-tracker-indicator-border-color-complete);
      background-color: var(--progress-tracker-indicator-bg-color-complete);
    }

    .stage-indicator-complete:after {
      transform: scale(var(--progress-tracker-indicator-inlet-scale-complete));
    }

    .stage-indicator-current {
      border-color: var(--progress-tracker-indicator-border-color-current);
      background-color: var(--progress-tracker-indicator-bg-color-current);
    }

    .stage-indicator-current:after {
      transform: scale(var(--progress-tracker-indicator-inlet-scale-current));
    }

    .stage-indicator-open:after {
      background-color: transparent;
    }

    .stage-label {
      margin-top: 2px;
    }

    @media (prefers-reduced-motion: no-preference) {
      .track-bar-active {
        transition: width 0.5s ease-in-out;
      }

      .stage-indicator {
        transition: background-color .5s ease-in-out, border-color .5s ease-in-out;
      }

      .stage-indicator:after {
        transition: transform .5s ease-in-out, background-color .5s ease-in-out;
      }
    }
  `,__decorate([property({attribute:"stages",type:Array})],ProgressTrackerElement.prototype,"stages",void 0),__decorate([property({attribute:"active",type:Number,reflect:!0})],ProgressTrackerElement.prototype,"activeStage",void 0),ProgressTrackerElement=__decorate([customElement("typo3-backend-progress-tracker")],ProgressTrackerElement);export{ProgressTrackerElement};