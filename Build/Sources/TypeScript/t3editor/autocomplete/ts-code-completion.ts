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

/**
 * Module: @typo3/t3editor/autocomplete/ts-code-completion
 * Contains the TsCodeCompletion class
 */
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { TsRef } from '@typo3/t3editor/autocomplete/ts-ref';
import { TsParser } from '@typo3/t3editor/autocomplete/ts-parser';
import { CompletionResult, Proposal } from '@typo3/t3editor/autocomplete/completion-result';

import type { CodeMirror5CompatibleCompletionState } from '@typo3/t3editor/language/typoscript';

export type ContentObjectIdentifier = string;

export type TsObjTree = {
  c?: Record<string, TsObjTree>;
  v?: ContentObjectIdentifier;
};

export class TsCodeCompletion {
  public extTsObjTree: TsObjTree = {};
  private readonly tsRef: TsRef;
  private readonly parser: TsParser = null;
  private proposals: Array<Proposal> = null;
  private compResult: CompletionResult = null;

  constructor(id: number) {
    this.tsRef = new TsRef();
    this.parser = new TsParser(this.tsRef, this.extTsObjTree);
    this.tsRef.loadTsrefAsync();
    this.loadExtTemplatesAsync(id);
  }

  /**
   * Refreshes the code completion list based on the cursor's position
   */
  public refreshCodeCompletion(
    completionState: CodeMirror5CompatibleCompletionState
  ): Array<Proposal['word']> {
    // the cursornode has to be stored cause inserted breaks have to be deleted after pressing enter if the codecompletion is active
    const filter = this.getFilter(completionState);

    // TODO: implement cases: operatorCompletion reference/copy path completion (formerly found in getCompletionResults())
    const currentTsTreeNode = this.parser.buildTsObjTree(completionState);
    this.compResult = new CompletionResult(
      this.tsRef,
      currentTsTreeNode
    );

    this.proposals = this.compResult.getFilteredProposals(filter);

    const proposals: string[] = [];
    for (let i = 0; i < this.proposals.length; i++) {
      proposals[i] = this.proposals[i].word;
    }

    return proposals;
  }

  /**
   * All external templates along the rootline have to be loaded,
   * this function retrieves the JSON code by committing a AJAX request
   */
  private loadExtTemplatesAsync(id: number): void {
    if (Number.isNaN(id) || id === 0) {
      return null;
    }
    new AjaxRequest(TYPO3.settings.ajaxUrls.t3editor_codecompletion_loadtemplates)
      .withQueryArguments({ pageId: id })
      .get()
      .then(async (response) => {
        this.extTsObjTree.c = await response.resolve();
        this.resolveExtReferencesRec(this.extTsObjTree.c);
      });
  }

  /**
   * Since the references are not resolved server side we have to do it client-side
   * Benefit: less loading time due to less data which has to be transmitted
   *
   * @param {Array} childNodes
   */
  private resolveExtReferencesRec(childNodes: Record<string, TsObjTree>): void {
    for (const key of Object.keys(childNodes)) {
      let childNode;
      // if the childnode has a value and there is a part of a reference operator ('<')
      // and it does not look like a html tag ('>')
      if (childNodes[key].v && childNodes[key].v.startsWith('<') && !childNodes[key].v.includes('>')) {
        const path = childNodes[key].v.replace(/</, '').trim();
        // if there are still whitespaces it's no path
        if (path.indexOf(' ') === -1) {
          childNode = this.getExtChildNode(path);
          // if the node was found - reference it
          if (childNode !== null) {
            childNodes[key] = childNode;
          }
        }
      }
      // if there was no reference-resolving then we go deeper into the tree
      if (!childNode && childNodes[key].c) {
        this.resolveExtReferencesRec(childNodes[key].c);
      }
    }
  }

  /**
   * Get the child node of given path
   */
  private getExtChildNode(path: string): object {
    let extTree = this.extTsObjTree;

    const pathParts = path.split('.');
    for (let i = 0; i < pathParts.length; i++) {
      const pathSeg = pathParts[i];
      if (typeof extTree.c === 'undefined' || typeof extTree.c[pathSeg] === 'undefined') {
        return null;
      }
      extTree = extTree.c[pathSeg];
    }
    return extTree;
  }

  private getFilter(completionState: CodeMirror5CompatibleCompletionState): string {
    if (completionState.completingAfterDot) {
      return '';
    }

    return completionState.token.string.replace('.', '').replace(/\s/g, '');
  }
}
