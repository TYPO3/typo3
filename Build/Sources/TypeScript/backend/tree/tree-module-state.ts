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

import { LitElement } from 'lit';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { ModuleStateStorage } from '@typo3/backend/storage/module-state-storage';
import type { ModuleStateUpdateEvent } from '@typo3/backend/storage/module-state-storage';
import type { TreeNodeInterface } from '@typo3/backend/tree/tree-node';
import type { Tree } from '@typo3/backend/tree/tree';
import type { Constructor } from '@lit/reactive-element/decorators/base';

export const TreeModuleState = <T extends Constructor<LitElement>>(superClass: T) => {
  abstract class TreeModuleStateClass extends superClass {
    protected abstract tree: Tree;
    protected abstract moduleStateType: string;

    public connectedCallback() {
      super.connectedCallback();
      document.addEventListener('typo3:module-state-storage:update:' + this.moduleStateType, this.moduleStateUpdated);
    }

    public disconnectedCallback() {
      super.disconnectedCallback();
      document.removeEventListener('typo3:module-state-storage:update:' + this.moduleStateType, this.moduleStateUpdated);
    }

    protected transformModuleStateIdentifierToNodeIdentifier(moduleStateIdentifier: string): string {
      return moduleStateIdentifier;
    }

    protected transformNodeIdentifierToModuleStateIdentifier(nodeIdentifier: string): string {
      return nodeIdentifier;
    }

    protected async selectActiveViaRootline(identifier: string) {
      const url = new URL(this.tree.settings.rootlineUrl, window.location.origin);
      url.searchParams.set('identifier', identifier);
      const response = await new AjaxRequest(url.toString()).get({ cache: 'no-cache' })
      const { rootline }: { rootline: string[] } = await response.resolve();
      rootline.pop();
      const propagate = false;
      await this.selectActiveNodeByParents(identifier, rootline.map(id => this.transformModuleStateIdentifierToNodeIdentifier(id)), propagate);
    }

    /**
     * Event listener called for each loaded node,
     * here used to mark node remembered from/in ModuleState as selected
     */
    protected readonly selectActiveNodeInLoadedNodes = (evt: CustomEvent<{nodes: TreeNodeInterface[]}>): void => {
      let moduleState = ModuleStateStorage.current(this.moduleStateType);
      if (!moduleState.identifier) {
        // No active node selected yet
        return;
      }

      const { nodes } = evt.detail;
      const treeIdentifier = this.transformModuleStateIdentifierToNodeIdentifier(moduleState.treeIdentifier);
      const identifier = this.transformModuleStateIdentifierToNodeIdentifier(moduleState.identifier);
      const nodeToActivate = nodes.find((node) => (
        (moduleState.treeIdentifier !== null && node.__treeIdentifier === treeIdentifier) ||
        (moduleState.treeIdentifier === null && node.identifier === identifier)
      ));


      if (!nodeToActivate) {
        // The active node was not found. That is fine since we only enhance a
        // subset of nodes here (like an ondemand loaded subtree) and we must not
        // initiate new rootline requests.
        // This will be checked by this.fetchActiveNodeIfMissing().
        return;
      }

      if (moduleState.treeIdentifier === null) {
        moduleState = ModuleStateStorage.updateWithTreeIdentifier(
          this.moduleStateType,
          this.transformNodeIdentifierToModuleStateIdentifier(nodeToActivate.identifier),
          this.transformNodeIdentifierToModuleStateIdentifier(nodeToActivate.__treeIdentifier)
        );
      }
      nodeToActivate.checked = true;
      // If the parent was loaded with this chunk, expand it (and its parents) immediately
      const parent = nodes.find((node) => node.__treeIdentifier === nodeToActivate.__parents.join('_'));
      if (parent && !parent.__expanded) {
        this.tree.updateComplete.then(() => this.tree.expandNodeParents(nodeToActivate));
      }
    };

    protected readonly fetchActiveNodeIfMissing = async (): Promise<void> => {
      const moduleState = ModuleStateStorage.current(this.moduleStateType);
      if (!moduleState.identifier) {
        // No active node selected yet
        return;
      }

      if (this.tree.nodes.find((node: TreeNodeInterface) => node.checked)) {
        return;
      }

      await this.selectActiveViaRootline(moduleState.identifier);
    };

    private readonly moduleStateUpdated = async (e: CustomEvent<ModuleStateUpdateEvent>): Promise<void> => {
      const identifier = e.detail.state.identifier;
      if (identifier && identifier === e.detail.oldState.identifier && this.tree.nodes.find((node: TreeNodeInterface) => node.checked)) {
        return;
      }

      if (!identifier) {
        console.error('invalid identifier', e.detail);
        return;
      }

      if (this.tree.loading) {
        await this.tree.loadComplete;
      }

      const nodeIdentifier = this.transformModuleStateIdentifierToNodeIdentifier(identifier);
      const node = this.tree.nodes.find((node) => node.identifier === nodeIdentifier);
      const selectedNode = this.tree.nodes.find((node: TreeNodeInterface) => node.checked);
      if (node && node === selectedNode) {
        // node is already selected, only ensure parents are expanded
        await this.tree.expandNodeParents(node);
        return;
      }

      const propagate = false;
      if (node) {
        await this.selectActiveNodeByParents(identifier, node.__parents, propagate);
        return;
      }

      await this.selectActiveViaRootline(identifier);
    };

    private async selectActiveNodeByParents(identifier: string, parents: string[], propagate: boolean = true) {
      await this.tree.expandParents(parents);
      const nodeIdentifier = this.transformModuleStateIdentifierToNodeIdentifier(identifier);
      const node = this.tree.nodes.find((node) => node.identifier === nodeIdentifier);
      if (node) {
        this.tree.selectNode(node, propagate);
      }
    }
  };
  return TreeModuleStateClass;
}
