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

import {ScaffoldIdentifierEnum} from '../enum/viewport/scaffold-identifier';
import {AbstractContainer} from './abstract-container';
import $ from 'jquery';
import ClientRequest from '../event/client-request';
import InteractionRequest from '../event/interaction-request';
import Loader from './loader';
import Utility from '../utility';
import TriggerRequest from '../event/trigger-request';

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
   * @param {string} urlToLoad
   * @param {InteractionRequest} [interactionRequest]
   * @param {string|null} module
   * @returns {JQueryDeferred<TriggerRequest>}
   */
  public setUrl(urlToLoad: string, interactionRequest?: InteractionRequest, module?: string): JQueryDeferred<TriggerRequest> {
    let deferred: JQueryDeferred<TriggerRequest>;
    const router = this.resolveRouterElement();
    // abort, if router can not be found
    if (router === null) {
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
      router.setAttribute('endpoint', urlToLoad);
      router.setAttribute('module', module ? module : null);
      router.parentElement.addEventListener('typo3-module-loaded', (): void => Loader.finish(), { once: true });
    });
    return deferred;
  }

  /**
   * @returns {string}
   */
  public getUrl(): string {
    return this.resolveRouterElement().getAttribute('endpoint');
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

  private resolveRouterElement(): HTMLElement {
    return document.querySelector(ScaffoldIdentifierEnum.contentModuleRouter);
  }
}

export default ContentContainer;
