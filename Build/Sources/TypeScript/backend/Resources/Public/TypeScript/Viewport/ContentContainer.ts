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

import {ScaffoldIdentifierEnum} from '../Enum/Viewport/ScaffoldIdentifier';
import {AbstractContainer} from './AbstractContainer';
import $ from 'jquery';
import ClientRequest = require('../Event/ClientRequest');
import InteractionRequest = require('../Event/InteractionRequest');
import Loader = require('./Loader');
import Utility = require('../Utility');
import TriggerRequest = require('../Event/TriggerRequest');

class ContentContainer extends AbstractContainer {
  public get(): Window {
    return (<HTMLIFrameElement>$(ScaffoldIdentifierEnum.contentModuleIframe)[0]).contentWindow;
  }

  /**
   * @param {InteractionRequest} interactionRequest
   * @returns {JQueryDeferred<TriggerRequest>}
   */
  public beforeSetUrl(interactionRequest: InteractionRequest): JQueryDeferred<TriggerRequest> {
    return this.consumerScope.invoke(
      new TriggerRequest('typo3.beforeSetUrl', interactionRequest),
    );
  }

  /**
   * @param {String} urlToLoad
   * @param {InteractionRequest} [interactionRequest]
   * @returns {JQueryDeferred<TriggerRequest>}
   */
  public setUrl(urlToLoad: string, interactionRequest?: InteractionRequest): JQueryDeferred<TriggerRequest> {
    let deferred: JQueryDeferred<TriggerRequest>;
    const iFrame = this.resolveIFrameElement();
    // abort, if no IFRAME can be found
    if (iFrame === null) {
      deferred = $.Deferred();
      deferred.reject();
      return deferred;
    }
    if (!(interactionRequest instanceof InteractionRequest)) {
      interactionRequest = new ClientRequest('typo3.setUrl', null);
    }
    deferred = this.consumerScope.invoke(
      new TriggerRequest('typo3.setUrl', interactionRequest),
    );
    deferred.then((): void => {
      Loader.start();
      $(ScaffoldIdentifierEnum.contentModuleIframe)
        .attr('src', urlToLoad)
        .one('load', (): void => {
          Loader.finish();
        });
    });
    return deferred;
  }

  /**
   * @returns {string}
   */
  public getUrl(): string {
    return $(ScaffoldIdentifierEnum.contentModuleIframe).attr('src');
  }

  /**
   * @param {InteractionRequest} interactionRequest
   * @returns {JQueryDeferred<{}>}
   */
  public refresh(interactionRequest?: InteractionRequest): JQueryDeferred<{}> {
    let deferred;
    const iFrame = <HTMLIFrameElement>this.resolveIFrameElement();
    // abort, if no IFRAME can be found
    if (iFrame === null) {
      deferred = $.Deferred();
      deferred.reject();
      return deferred;
    }
    deferred = this.consumerScope.invoke(
      new TriggerRequest('typo3.refresh', interactionRequest),
    );
    deferred.then((): void => {
      iFrame.contentWindow.location.reload();
    });
    return deferred;
  }

  public getIdFromUrl(): number {
    if (this.getUrl) {
      return parseInt(Utility.getParameterFromUrl(this.getUrl(), 'id'), 10);
    }
    return 0;
  }

  private resolveIFrameElement(): HTMLElement {
    const $iFrame = $(ScaffoldIdentifierEnum.contentModuleIframe + ':first');
    if ($iFrame.length === 0) {
      return null;
    }
    return $iFrame.get(0);
  }
}

export = ContentContainer;
