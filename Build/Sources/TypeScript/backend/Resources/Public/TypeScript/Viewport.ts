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

import * as $  from 'jquery';
import ContentContainer = require('./Viewport/ContentContainer');
import ConsumerScope = require('./Event/ConsumerScope');
import Loader = require('./Viewport/Loader');
import NavigationContainer = require('./Viewport/NavigationContainer');
import Topbar = require('./Viewport/Topbar');

class Viewport {
  // The attributes are uppercase for compatibility reasons
  public readonly Loader: Loader = Loader;
  public readonly Topbar: Topbar;
  public readonly NavigationContainer: NavigationContainer = null;
  public readonly ContentContainer: ContentContainer = null;
  public readonly consumerScope: any = ConsumerScope;

  constructor() {
    $((): void => this.initialize());
    this.Topbar = new Topbar();
    this.NavigationContainer = new NavigationContainer(this.consumerScope);
    this.ContentContainer = new ContentContainer(this.consumerScope);
  }

  public doLayout(): void {
    this.NavigationContainer.cleanup();
    this.NavigationContainer.calculateScrollbar();
    $('.t3js-topbar-header').css('padding-right', $('.t3js-scaffold-toolbar').outerWidth());
  }

  private initialize(): void {
    this.doLayout();
    $(window).on('resize', () => {
      this.doLayout();
    });
  }
}

let viewportObject: Viewport;

if (!top.TYPO3.Backend) {
  viewportObject = new Viewport();
  top.TYPO3.Backend = viewportObject;
} else {
  viewportObject = top.TYPO3.Backend;
}

export = viewportObject;
