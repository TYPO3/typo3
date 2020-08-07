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

import $ from 'jquery';
import 'jquery-ui/resizable';
import PersistentStorage = require('TYPO3/CMS/Backend/Storage/Persistent');

enum Selectors {
  resizableContainerIdentifier = '.t3js-viewpage-resizeable',
  sizeIdentifier = '.t3js-viewpage-size',
  moduleBodySelector = '.t3js-module-body',
  customSelector = '.t3js-preset-custom',
  customWidthSelector = '.t3js-preset-custom',
  customHeightSelector = '.t3js-preset-custom-height',
  changeOrientationSelector = '.t3js-change-orientation',
  changePresetSelector = '.t3js-change-preset',
  inputWidthSelector = '.t3js-viewpage-input-width',
  inputHeightSelector = '.t3js-viewpage-input-height',
  currentLabelSelector = '.t3js-viewpage-current-label',
  topbarContainerSelector = '.t3js-viewpage-topbar',
}

/**
 * Module: TYPO3/CMS/Viewpage/Main
 * Main logic for resizing the view of the frame
 */
class ViewPage {
  private defaultLabel: string = '';
  private readonly minimalHeight: number = 300;
  private readonly minimalWidth: number = 300;

  private readonly storagePrefix: string = 'moduleData.web_view.States.';
  private $iframe: JQuery;
  private $resizableContainer: JQuery;
  private $sizeSelector: JQuery;


  private readonly queue: Array<any> = [];
  private queueIsRunning: boolean = false;
  private queueDelayTimer: any;

  private static getCurrentWidth(): string {
    return $(Selectors.inputWidthSelector).val();
  }

  private static getCurrentHeight(): string {
    return $(Selectors.inputHeightSelector).val();
  }

  private static setLabel(label: string): void {
    $(Selectors.currentLabelSelector).html(label);
  }

  private static getCurrentLabel(): string {
    return $(Selectors.currentLabelSelector).html().trim();
  }

  constructor() {
    $((): void => {
      const $presetCustomLabel = $('.t3js-preset-custom-label');

      this.defaultLabel = $presetCustomLabel.length > 0 ? $presetCustomLabel.html().trim() : '';
      this.$iframe = $('#tx_this_iframe');
      this.$resizableContainer = $(Selectors.resizableContainerIdentifier);
      this.$sizeSelector = $(Selectors.sizeIdentifier);

      this.initialize();
    });
  }

  private persistQueue(): void {
    if (this.queueIsRunning === false && this.queue.length >= 1) {
      this.queueIsRunning = true;
      let item = this.queue.shift();
      PersistentStorage.set(item.storageIdentifier, item.data).done((): void => {
        this.queueIsRunning = false;
        this.persistQueue();
      });
    }
  }

  private addToQueue(storageIdentifier: string, data: any): void {
    const item = {
      storageIdentifier: storageIdentifier,
      data: data,
    };
    this.queue.push(item);
    if (this.queue.length >= 1) {
      this.persistQueue();
    }
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

    $(Selectors.inputWidthSelector).val(width);
    $(Selectors.inputHeightSelector).val(height);

    this.$resizableContainer.css({
      width: width,
      height: height,
      left: 0,
    });
  }

  private persistCurrentPreset(): void {
    let data = {
      width: ViewPage.getCurrentWidth(),
      height: ViewPage.getCurrentHeight(),
      label: ViewPage.getCurrentLabel(),
    };
    this.addToQueue(this.storagePrefix + 'current', data);
  }

  private persistCustomPreset(): void {
    let data = {
      width: ViewPage.getCurrentWidth(),
      height: ViewPage.getCurrentHeight(),
    };
    $(Selectors.customSelector).data('width', data.width);
    $(Selectors.customSelector).data('height', data.height);
    $(Selectors.customWidthSelector).html(data.width);
    $(Selectors.customHeightSelector).html(data.height);
    this.addToQueue(this.storagePrefix + 'custom', data);
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
    $(document).on('click', Selectors.changeOrientationSelector, (): void => {
      const width = $(Selectors.inputHeightSelector).val();
      const height = $(Selectors.inputWidthSelector).val();
      this.setSize(width, height);
      this.persistCurrentPreset();
    });

    // On change
    $(document).on('change', Selectors.inputWidthSelector, (): void => {
      const width = $(Selectors.inputWidthSelector).val();
      const height = $(Selectors.inputHeightSelector).val();
      this.setSize(width, height);
      ViewPage.setLabel(this.defaultLabel);
      this.persistCustomPresetAfterChange();
    });
    $(document).on('change', Selectors.inputHeightSelector, (): void => {
      const width = $(Selectors.inputWidthSelector).val();
      const height = $(Selectors.inputHeightSelector).val();
      this.setSize(width, height);
      ViewPage.setLabel(this.defaultLabel);
      this.persistCustomPresetAfterChange();
    });

    // Add event to width selector so the container is resized
    $(document).on('click', Selectors.changePresetSelector, (evt: JQueryEventObject): void => {
      const data = $(evt.currentTarget).data();
      this.setSize(parseInt(data.width, 10), parseInt(data.height, 10));
      ViewPage.setLabel(data.label);
      this.persistCurrentPreset();
    });

    // Initialize the jQuery UI Resizable plugin
    this.$resizableContainer.resizable({
      handles: 'w, sw, s, se, e',
    });

    this.$resizableContainer.on('resizestart', (evt: JQueryEventObject): void => {
      // Add iframe overlay to prevent losing the mouse focus to the iframe while resizing fast
      $(evt.currentTarget)
        .append('<div id="this-iframe-cover" style="z-index:99;position:absolute;width:100%;top:0;left:0;height:100%;"></div>');
    });

    this.$resizableContainer.on('resize', (evt: JQueryEventObject, ui: JQueryUI.ResizableUIParams): void => {
      ui.size.width = ui.originalSize.width + ((ui.size.width - ui.originalSize.width) * 2);
      if (ui.size.height < this.minimalHeight) {
        ui.size.height = this.minimalHeight;
      }
      if (ui.size.width < this.minimalWidth) {
        ui.size.width = this.minimalWidth;
      }
      $(Selectors.inputWidthSelector).val(ui.size.width);
      $(Selectors.inputHeightSelector).val(ui.size.height);
      this.$resizableContainer.css({
        left: 0,
      });
      ViewPage.setLabel(this.defaultLabel);
    });

    this.$resizableContainer.on('resizestop', (): void => {
      $('#viewpage-iframe-cover').remove();
      this.persistCurrentPreset();
      this.persistCustomPreset();
    });
  }

  private calculateContainerMaxHeight(): number {
    this.$resizableContainer.hide();
    let $moduleBody = $(Selectors.moduleBodySelector);
    let padding = $moduleBody.outerHeight() - $moduleBody.height(),
      documentHeight = $(document).height(),
      topbarHeight = $(Selectors.topbarContainerSelector).outerHeight();
    this.$resizableContainer.show();
    return documentHeight - padding - topbarHeight - 8;
  }

  private calculateContainerMaxWidth(): number {
    this.$resizableContainer.hide();
    let $moduleBody: JQuery = $(Selectors.moduleBodySelector);
    let padding: number = $moduleBody.outerWidth() - $moduleBody.width();
    let documentWidth: number = $(document).width();
    this.$resizableContainer.show();
    return parseInt((documentWidth - padding) + '', 10);
  }
}

export = new ViewPage();
