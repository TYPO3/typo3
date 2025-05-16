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

import { ScaffoldIdentifierEnum } from '../enum/viewport/scaffold-identifier';
import { AbstractContainer } from './abstract-container';
import ClientRequest from '../event/client-request';
import InteractionRequest from '../event/interaction-request';
import Loader from './loader';
import TriggerRequest from '../event/trigger-request';

class ContentContainer extends AbstractContainer {
  public get(): Window {
    return (document.querySelector(ScaffoldIdentifierEnum.contentModuleIframe) as HTMLIFrameElement).contentWindow;
  }

  public beforeSetUrl(interactionRequest: InteractionRequest): Promise<void> {
    return this.consumerScope.invoke(
      new TriggerRequest('typo3.beforeSetUrl', interactionRequest),
    );
  }

  public setUrl(urlToLoad: string, interactionRequest?: InteractionRequest, module?: string): Promise<void> {
    const router = this.resolveRouterElement();
    if (router === null && self !== top) {
      // abort, if router can not be found and this module is not used in a top frame (popup)
      return Promise.reject(new Error('Content container used in unsupported frame context'));
    }

    if (!(interactionRequest instanceof InteractionRequest)) {
      interactionRequest = new ClientRequest('typo3.setUrl', null);
    }
    const promise: Promise<void> = this.consumerScope.invoke(
      new TriggerRequest('typo3.setUrl', interactionRequest),
    );
    promise.then((): void => {
      if (router !== null) {
        Loader.start();
        router.setAttribute('endpoint', urlToLoad);
        router.setAttribute('module', module ? module : null);
        router.parentElement.addEventListener('typo3-module-loaded', (): void => Loader.finish(), { once: true });
      } else {
        // popup mode assume that we're in a standalone frame if the router cannot be found.
        document.location.assign(urlToLoad);
      }
    });
    return promise;
  }

  /**
   * @returns {string}
   */
  public getUrl(): string {
    return this.resolveRouterElement().getAttribute('endpoint');
  }

  public refresh(interactionRequest?: InteractionRequest): Promise<void> {
    const iFrame = <HTMLIFrameElement>this.resolveIFrameElement();
    // abort, if no IFRAME can be found
    if (iFrame === null) {
      return Promise.reject();
    }
    const promise: Promise<void> = this.consumerScope.invoke(
      new TriggerRequest('typo3.refresh', interactionRequest),
    );
    promise.then((): void => {
      iFrame.contentWindow.location.reload();
    });
    return promise;
  }

  public getIdFromUrl(): number {
    if (this.getUrl()) {
      const id = new URL(this.getUrl(), window.location.origin).searchParams.get('id') ?? '';
      return parseInt(id, 10);
    }
    return 0;
  }

  private resolveIFrameElement(): HTMLElement|null {
    return document.querySelector(ScaffoldIdentifierEnum.contentModuleIframe);
  }

  private resolveRouterElement(): HTMLElement|null {
    return document.querySelector(ScaffoldIdentifierEnum.contentModuleRouter);
  }
}

export default ContentContainer;
