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

import * as d3selection from 'd3-selection';
import {SvgTree, SvgTreeSettings, TreeNodeSelection} from '../../SvgTree';
import {TreeNode} from '../../Tree/TreeNode';
import FormEngineValidation = require('TYPO3/CMS/Backend/FormEngineValidation');

interface SelectTreeSettings extends SvgTreeSettings {
  exclusiveNodesIdentifiers: '';
  validation: {[keys: string]: any};
  unselectableElements: Array<any>,
  readOnlyMode: false
}

export class SelectTree extends SvgTree
{
  public settings: SelectTreeSettings = {
    unselectableElements: [],
    exclusiveNodesIdentifiers: '',
    validation: {},
    readOnlyMode: false,
    showIcons: false,
    marginTop: 15,
    nodeHeight: 20,
    indentWidth: 16,
    width: 300,
    duration: 400,
    dataUrl: '',
    defaultProperties: {},
    expandUpToLevel: null as any,
  };
  /**
   * Exclusive node which is currently selected
   */
  private exclusiveSelectedNode: TreeNode = null;

  public initialize(selector: HTMLElement, settings: any): boolean {
    if (!super.initialize(selector, settings)) {
      return false;
    }

    this.addIcons();
    this.dispatch.on('updateNodes.selectTree', (nodes: TreeNodeSelection) => this.updateNodes(nodes));
    this.dispatch.on('loadDataAfter.selectTree', () => this.loadDataAfter());
    this.dispatch.on('updateSvg.selectTree', (nodes: TreeNodeSelection) => this.renderCheckbox(nodes));
    this.dispatch.on('nodeSelectedAfter.selectTree', (node: TreeNode) => this.nodeSelectedAfter(node));
    this.dispatch.on('prepareLoadedNode.selectTree', (node: TreeNode) => this.prepareLoadedNode(node));
    return true;
  }

  /**
   * Node selection logic (triggered by different events)
   */
  public selectNode(node: TreeNode): void {
    if (!this.isNodeSelectable(node)) {
      return;
    }

    const checked = node.checked;
    this.handleExclusiveNodeSelection(node);

    if (this.settings.validation && this.settings.validation.maxItems) {
      if (!checked && this.getSelectedNodes().length >= this.settings.validation.maxItems) {
        return;
      }
    }

    node.checked = !checked;

    this.dispatch.call('nodeSelectedAfter', this, node);
    this.update();
  }

  /**
   * Function relays on node.indeterminate state being up to date
   *
   * @param {Selection} nodes
   */
  public updateNodes(nodes: TreeNodeSelection): void {
    nodes
      .selectAll('.tree-check use')
      .attr('visibility', function(this: SVGUseElement, node: TreeNode): string {
        const checked = Boolean(node.checked);
        const selection = d3selection.select(this);
        if (selection.classed('icon-checked') && checked) {
          return 'visible';
        } else if (selection.classed('icon-indeterminate') && node.indeterminate && !checked) {
          return 'visible';
        } else if (selection.classed('icon-check') && !node.indeterminate && !checked) {
          return 'visible';
        } else {
          return 'hidden';
        }
      });
  }

  /**
   * Check whether node can be selected.
   * In some cases (e.g. selecting a parent) it should not be possible to select
   * element (as it's own parent).
   */
  protected isNodeSelectable(node: TreeNode): boolean {
    return !this.settings.readOnlyMode && this.settings.unselectableElements.indexOf(node.identifier) === -1;
  }

  /**
   * Check if a node has all information to be used.
   */
  private prepareLoadedNode(node: TreeNode): void {
    // create stateIdentifier if doesn't exist (for category tree)
    if (!node.stateIdentifier) {
      const parentId = (node.parents.length) ? node.parents[node.parents.length - 1] : node.identifier;
      node.stateIdentifier = parentId + '_' + node.identifier;
    }
    if (node.selectable === false) {
      this.settings.unselectableElements.push(node.identifier);
    }
  }

  /**
   * Adds svg elements for checkbox rendering.
   *
   * @param {Selection} nodeSelection ENTER selection (only new DOM objects)
   */
  private renderCheckbox(nodeSelection: TreeNodeSelection): void {
    this.textPosition = 50;

    // this can be simplified to single "use" element with changing href on click
    // when we drop IE11 on WIN7 support
    const g = nodeSelection.filter((node: TreeNode) => {
      // do not render checkbox if node is not selectable
      return this.isNodeSelectable(node) || Boolean(node.checked);
    })
      .append('g')
      .attr('class', 'tree-check')
      .on('click', (evt: MouseEvent, node: TreeNode) => this.selectNode(node));

    g.append('use')
      .attr('x', 28)
      .attr('y', -8)
      .attr('visibility', 'hidden')
      .attr('class', 'icon-check')
      .attr('xlink:href', '#icon-check');
    g.append('use')
      .attr('x', 28)
      .attr('y', -8)
      .attr('visibility', 'hidden')
      .attr('class', 'icon-checked')
      .attr('xlink:href', '#icon-checked');
    g.append('use')
      .attr('x', 28)
      .attr('y', -8)
      .attr('visibility', 'hidden')
      .attr('class', 'icon-indeterminate')
      .attr('xlink:href', '#icon-indeterminate');
  }

  /**
   * Updates the indeterminate state for ancestors of the current node
   */
  private updateAncestorsIndeterminateState(node: TreeNode): void {
    // foreach ancestor except node itself
    let indeterminate = false;
    node.parents.forEach((index: number) => {
      const node = this.nodes[index];
      node.indeterminate = (node.checked || node.indeterminate || indeterminate);
      // check state for the next level
      indeterminate = (node.checked || node.indeterminate || node.checked || node.indeterminate);
    });
  };

  /**
   * Resets the node.indeterminate for the whole tree.
   * It's done once after loading data.
   * Later indeterminate state is updated just for the subset of nodes
   */
  private loadDataAfter(): void {
    this.nodes = this.nodes.map((node: TreeNode) => {
      node.indeterminate = false;
      return node;
    });
    this.calculateIndeterminate(this.nodes);

    // Initialise "value" attribute of input field after load and revalidate form engine fields
    this.saveCheckboxes();
    // @todo Unsure if this has ever worked before from `TYPO3.FormEngine.Validation`
    FormEngineValidation.validateField(this.settings.input);
  };

  /**
   * Sets indeterminate state for a subtree.
   * It relays on the tree to have indeterminate state reset beforehand.
   */
  private calculateIndeterminate(nodes: TreeNode[]): void {
    nodes.forEach((node: TreeNode) => {
      if ((node.checked || node.indeterminate) && node.parents && node.parents.length > 0) {
        node.parents.forEach((parentNodeIndex: number) => {
          nodes[parentNodeIndex].indeterminate = true;
        });
      }
    });
  };

  /**
   * Observer for the selectedNode event
   */
  private nodeSelectedAfter(node: TreeNode): void {
    this.updateAncestorsIndeterminateState(node);
    // check all nodes again, to ensure correct display of indeterminate state
    this.calculateIndeterminate(this.nodes);
    this.saveCheckboxes();
  };

  /**
   * Sets a comma-separated list of selected nodes identifiers to configured input
   */
  private saveCheckboxes(): void {
    if (typeof this.settings.input === 'undefined') {
      return;
    }
    this.settings.input.value = this.getSelectedNodes()
      .map((node: TreeNode): string => node.identifier);
  }

  /**
   * Handle exclusive nodes functionality
   * If a node is one of the exclusiveNodesIdentifiers list,
   * all other nodes has to be unselected before selecting this node.
   *
   * @param {Node} node
   */
  private handleExclusiveNodeSelection(node: TreeNode): void {
    const exclusiveKeys = this.settings.exclusiveNodesIdentifiers.split(',');
    if (this.settings.exclusiveNodesIdentifiers.length && node.checked === false) {
      if (exclusiveKeys.indexOf('' + node.identifier) > -1) {
        // this key is exclusive, so uncheck all others
        this.disableSelectedNodes();
        this.exclusiveSelectedNode = node;
      } else if (exclusiveKeys.indexOf('' + node.identifier) === -1 && this.exclusiveSelectedNode) {

        // current node is not exclusive, but other exclusive node is already selected
        this.exclusiveSelectedNode.checked = false;
        this.dispatch.call('nodeSelectedAfter', this, this.exclusiveSelectedNode);
        this.exclusiveSelectedNode = null;
      }
    }
  }

  /**
   * Add icons imitating checkboxes
   */
  private addIcons(): void {
    this.icons = {
      check: {
        identifier: 'check',
        icon: '<g width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">' +
          '<rect height="16" width="16" fill="transparent"></rect><path transform="scale(0.01)" d="M1312 256h-832q-66 0-113 47t-47 113v832q0 66 47 113t113 47h832q66 0 113-47t47-113v-832q0-66-47-113t-113-47zm288 160v832q0 119-84.5 203.5t-203.5 84.5h-832q-119 0-203.5-84.5t-84.5-203.5v-832q0-119 84.5-203.5t203.5-84.5h832q119 0 203.5 84.5t84.5 203.5z"></path></g>'
      },
      checked: {
        identifier: 'checked',
        icon: '<g width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><rect height="16" width="16" fill="transparent"></rect><path transform="scale(0.01)" d="M813 1299l614-614q19-19 19-45t-19-45l-102-102q-19-19-45-19t-45 19l-467 467-211-211q-19-19-45-19t-45 19l-102 102q-19 19-19 45t19 45l358 358q19 19 45 19t45-19zm851-883v960q0 119-84.5 203.5t-203.5 84.5h-960q-119 0-203.5-84.5t-84.5-203.5v-960q0-119 84.5-203.5t203.5-84.5h960q119 0 203.5 84.5t84.5 203.5z"></path></g>'
      },
      indeterminate: {
        identifier: 'indeterminate',
        icon: '<g width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><rect height="16" width="16" fill="transparent"></rect><path transform="scale(0.01)" d="M1344 800v64q0 14-9 23t-23 9h-832q-14 0-23-9t-9-23v-64q0-14 9-23t23-9h832q14 0 23 9t9 23zm128 448v-832q0-66-47-113t-113-47h-832q-66 0-113 47t-47 113v832q0 66 47 113t113 47h832q66 0 113-47t47-113zm128-832v832q0 119-84.5 203.5t-203.5 84.5h-832q-119 0-203.5-84.5t-84.5-203.5v-832q0-119 84.5-203.5t203.5-84.5h832q119 0 203.5 84.5t84.5 203.5z"></path></g>'
      }
    }
  }
}
