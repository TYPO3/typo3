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

type RendererDeclaration = { module: string, callback: Function };
type RendererDeclarationCollection = { [key: string]: RendererDeclaration };
type FunctionObjects = { [key: string]: Function };

class LiveSearchConfigurator {
  private renderers: RendererDeclarationCollection = {};
  private invokeHandlers: FunctionObjects = {};

  public getRenderers(): RendererDeclarationCollection {
    return this.renderers;
  }

  public addRenderer(type: string, module: string, callback: Function): void {
    this.renderers[type] = { module, callback };
  }

  public getInvokeHandlers(): FunctionObjects {
    return this.invokeHandlers;
  }

  public addInvokeHandler(type: string, action: string, callback: Function): void {
    this.invokeHandlers[type + '_' + action] = callback;
  }
}

let configuratorObject: LiveSearchConfigurator;
if (!top.TYPO3.LiveSearchConfigurator) {
  configuratorObject = new LiveSearchConfigurator();
  top.TYPO3.LiveSearchConfigurator = configuratorObject;
} else {
  configuratorObject = top.TYPO3.LiveSearchConfigurator;
}

export default configuratorObject;

