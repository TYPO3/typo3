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
    // abort, if router can not be found
    if (router === null) {
      return Promise.reject();
    }
    if (!(interactionRequest instanceof InteractionRequest)) {
      interactionRequest = new ClientRequest('typo3.setUrl', null);
    }
    const promise: Promise<void> = this.consumerScope.invoke(
      new TriggerRequest('typo3.setUrl', interactionRequest),
    );
    promise.then((): void => {
      Loader.start();
      router.setAttribute('endpoint', urlToLoad);
      router.setAttribute('module', module ? module : null);
      router.parentElement.addEventListener('typo3-module-loaded', (): void => Loader.finish(), { once: true });
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

  private resolveRouterElement(): HTMLElement {
    return document.querySelector(ScaffoldIdentifierEnum.contentModuleRouter);
  }
}

export default ContentContainer;
