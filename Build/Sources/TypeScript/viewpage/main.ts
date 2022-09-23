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

import interact from 'interactjs';
import DocumentService from '@typo3/core/document-service';
import PersistentStorage from '@typo3/backend/storage/persistent';
import RegularEvent from '@typo3/core/event/regular-event';
import DebounceEvent from '@typo3/core/event/debounce-event';
import {ResizeEvent} from '@interactjs/actions/resize/plugin';

enum Selectors {
  resizableContainerIdentifier = '.t3js-viewpage-resizeable',
  moduleBodySelector = '.t3js-module-body',
  customSelector = '.t3js-preset-custom',
  customWidthSelector = '.t3js-preset-custom-width',
  customHeightSelector = '.t3js-preset-custom-height',
  changeOrientationSelector = '.t3js-change-orientation',
  changePresetSelector = '.t3js-change-preset',
  inputWidthSelector = '.t3js-viewpage-input-width',
  inputHeightSelector = '.t3js-viewpage-input-height',
  currentLabelSelector = '.t3js-viewpage-current-label',
  topbarContainerSelector = '.t3js-viewpage-topbar',
  refreshSelector = '.t3js-viewpage-refresh',
}

/**
 * Module: @typo3/viewpage/main
 * Main logic for resizing the view of the frame
 */
class ViewPage {
  private defaultLabel: string = '';
  private readonly minimalHeight: number = 300;
  private readonly minimalWidth: number = 300;

  private readonly storagePrefix: string = 'moduleData.web_ViewpageView.States.';
  private iframe: HTMLIFrameElement;
  private inputCustomWidth: HTMLInputElement;
  private inputCustomHeight: HTMLInputElement;
  private customPresetItem: HTMLElement;
  private customPresetItemWidth: HTMLElement;
  private customPresetItemHeight: HTMLElement;
  private currentLabelElement: HTMLElement;
  private resizableContainer: HTMLElement;

  private queueDelayTimer: any;

  constructor() {
    DocumentService.ready().then((): void => {
      const presetCustomLabel = document.querySelector('.t3js-preset-custom-label');

      this.defaultLabel = presetCustomLabel?.textContent.trim() ?? '';
      this.iframe = document.getElementById('tx_this_iframe') as HTMLIFrameElement;
      this.inputCustomWidth = document.querySelector(Selectors.inputWidthSelector) as HTMLInputElement;
      this.inputCustomHeight = document.querySelector(Selectors.inputHeightSelector) as HTMLInputElement;
      this.customPresetItem = document.querySelector(Selectors.customSelector) as HTMLElement;
      this.customPresetItemWidth = document.querySelector(Selectors.customWidthSelector) as HTMLElement;
      this.customPresetItemHeight = document.querySelector(Selectors.customHeightSelector) as HTMLElement;
      this.currentLabelElement = document.querySelector(Selectors.currentLabelSelector) as HTMLElement;
      this.resizableContainer = document.querySelector(Selectors.resizableContainerIdentifier);

      this.initialize();
    });
  }

  private getCurrentWidth(): number {
    return this.inputCustomWidth.valueAsNumber;
  }

  private getCurrentHeight(): number {
    return this.inputCustomHeight.valueAsNumber;
  }

  private setLabel(label: string): void {
    this.currentLabelElement.textContent = label;
  }

  private getCurrentLabel(): string {
    return this.currentLabelElement.textContent;
  }

  private persistChanges(storageIdentifier: string, data: any): void {
    PersistentStorage.set(storageIdentifier, data);
  }

  private setSize(width: number, height: number): void {
    if (isNaN(height)) {
      height = this.calculateContainerMaxHeight();
    }
    if (height < this.minimalHeight) {
      height = this.minimalHeight;
    }
    if (isNaN(width)) {
      width = this.calculateContainerMaxWidth();
    }
    if (width < this.minimalWidth) {
      width = this.minimalWidth;
    }

    this.inputCustomWidth.valueAsNumber = width;
    this.inputCustomHeight.valueAsNumber = height;

    this.resizableContainer.style.width = `${width}px`;
    this.resizableContainer.style.height = `${height}px`;
    this.resizableContainer.style.left = '0';
  }

  private persistCurrentPreset(): void {
    let data = {
      width: this.getCurrentWidth(),
      height: this.getCurrentHeight(),
      label: this.getCurrentLabel(),
    };
    this.persistChanges(this.storagePrefix + 'current', data);
  }

  private persistCustomPreset(): void {
    let data = {
      width: this.getCurrentWidth(),
      height: this.getCurrentHeight(),
    };

    this.customPresetItem.dataset.width = data.width.toString(10);
    this.customPresetItem.dataset.height = data.height.toString(10);
    this.customPresetItemWidth.textContent = data.width.toString(10);
    this.customPresetItemHeight.textContent = data.height.toString(10);

    this.persistChanges(this.storagePrefix + 'current', data);
    this.persistChanges(this.storagePrefix + 'custom', data);
  }

  private persistCustomPresetAfterChange(): void {
    clearTimeout(this.queueDelayTimer);
    this.queueDelayTimer = window.setTimeout(() => { this.persistCustomPreset(); }, 1000);
  }

  /**
   * Initialize
   */
  private initialize (): void {
    // Change orientation
    new RegularEvent('click', (): void => {
      this.setSize(this.getCurrentWidth(), this.getCurrentHeight());
      this.persistCurrentPreset();
    }).bindTo(document.querySelector(Selectors.changeOrientationSelector));

    // On change
    [this.inputCustomWidth, this.inputCustomHeight].forEach((customDimensionControl: HTMLInputElement): void => {
      new DebounceEvent('change', (): void => {
        this.setSize(this.getCurrentWidth(), this.getCurrentHeight());
        this.setLabel(this.defaultLabel);
        this.persistCustomPresetAfterChange();
      }, 50).bindTo(customDimensionControl);
    });

    // Add event to width selector so the container is resized
    new RegularEvent('click', (e: Event, selectedElement: HTMLElement): void => {
      this.setSize(parseInt(selectedElement.dataset.width, 10), parseInt(selectedElement.dataset.height, 10));
      this.setLabel(selectedElement.dataset.label);
      this.persistCurrentPreset();
    }).delegateTo(document, Selectors.changePresetSelector);

    // Add event for refresh button click
    new RegularEvent('click', (): void => {
      this.iframe.contentWindow.location.reload();
    }).bindTo(document.querySelector(Selectors.refreshSelector));

    interact(this.resizableContainer).on('resizestart', (e: ResizeEvent): void => {
      // Add iframe overlay to prevent losing the mouse focus to the iframe while resizing fast
      const iframeCover = document.createElement('div');
      iframeCover.id = 'viewpage-iframe-cover';
      iframeCover.setAttribute('style', 'z-index:99;position:absolute;width:100%;top:0;left:0;height:100%;');
      e.target.appendChild(iframeCover);
    }).on('resizeend', (): void => {
      document.getElementById('viewpage-iframe-cover').remove();
      this.persistCustomPreset();
    }).resizable({
      origin: 'self',
      edges: {
        top: false,
        left: true,
        bottom: true,
        right: true,
      },
      listeners: {
        move: (event: ResizeEvent): void => {
          Object.assign(event.target.style, {
            width: `${event.rect.width}px`,
            height: `${event.rect.height}px`,
          })

          this.inputCustomWidth.valueAsNumber = event.rect.width;
          this.inputCustomHeight.valueAsNumber = event.rect.height;

          this.setLabel(this.defaultLabel);
        }
      },
      modifiers: [
        interact.modifiers.restrictSize({
          min: {
            width: this.minimalWidth,
            height: this.minimalHeight
          }
        })
      ]
    });
  }

  private calculateContainerMaxHeight(): number {
    this.resizableContainer.hidden = true;

    const computedStyleOfModuleBody = getComputedStyle(document.querySelector(Selectors.moduleBodySelector));
    const padding = parseFloat(computedStyleOfModuleBody.getPropertyValue('padding-top')) + parseFloat(computedStyleOfModuleBody.getPropertyValue('padding-bottom'));
    const documentHeight: number = document.body.getBoundingClientRect().height;
    const topbarHeight = (document.querySelector(Selectors.topbarContainerSelector) as HTMLElement).getBoundingClientRect().height;

    this.resizableContainer.hidden = false;
    return documentHeight - padding - topbarHeight - 8;
  }

  private calculateContainerMaxWidth(): number {
    this.resizableContainer.hidden = true;

    const computedStyleOfModuleBody = getComputedStyle(document.querySelector(Selectors.moduleBodySelector));
    const padding = parseFloat(computedStyleOfModuleBody.getPropertyValue('padding-left')) + parseFloat(computedStyleOfModuleBody.getPropertyValue('padding-right'));
    const documentWidth: number = document.body.getBoundingClientRect().width;

    this.resizableContainer.hidden = false;
    return documentWidth - padding;
  }
}

export default new ViewPage();
