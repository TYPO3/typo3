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

import type { TsRef } from './ts-ref';
import type { TsObjTree } from './ts-code-completion';
import type { CodeMirror5CompatibleCompletionState } from '../language/typoscript';

export class TreeNode {
  public value: string;
  public childNodes: Record<string, TreeNode> = {};
  public extPath = '';
  public name: string;
  public isExternal: boolean;

  public global: boolean;
  public parent: TreeNode = null;

  private readonly tsParser: TsParser;

  constructor(nodeName: string, tsParser: TsParser) {
    this.name = nodeName;
    this.childNodes = {};
    this.extPath = '';
    this.value = '';
    this.isExternal = false;

    this.tsParser = tsParser;
  }

  /**
   * Returns local properties and the properties of the external templates
   */
  public getChildNodes(): Record<string, TreeNode> {
    const node = this.getExtNode();
    if (node !== null && typeof node.c === 'object') {
      for (const key of Object.keys(node.c)) {
        const tn = new TreeNode(key, this.tsParser);
        tn.global = true;
        tn.value = (node.c[key].v) ? node.c[key].v : '';
        tn.isExternal = true;
        this.childNodes[key] = tn;
      }
    }
    return this.childNodes;
  }

  /**
   * Returns the value of a node
   */
  public getValue(): string {
    if (this.value) {
      return this.value;
    }
    const node = this.getExtNode();
    if (node && node.v) {
      return node.v;
    }

    const type = this.getNodeTypeFromTsref();
    if (type) {
      return type;
    }
    return '';
  }

  /**
   * This method will try to resolve the properties recursively from right
   * to left. If the node's value property is not set, it will look for the
   * value of its parent node, and if there is a matching childProperty
   * (according to the TSREF) it will return the childProperties value.
   * If there is no value in the parent node it will go one step further
   * and look into the parent node of the parent node,...
   */
  private getNodeTypeFromTsref(): string {
    const path = this.extPath.split('.'),
      lastSeg = path.pop();

    // attention: there will be recursive calls if necessary
    const parentValue = this.parent.getValue();
    if (parentValue) {
      if (this.tsParser.tsRef.typeHasProperty(parentValue, lastSeg)) {
        const type = this.tsParser.tsRef.getType(parentValue);
        return type.properties[lastSeg].value;
      }
    }
    return '';
  }

  /**
   * Will look in the external ts-tree (static templates, templates on other pages)
   * if there is a value or childproperties assigned to the current node.
   * The method uses the extPath of the current node to navigate to the corresponding
   * node in the external tree
   */
  private getExtNode(): TsObjTree {
    let extTree = this.tsParser.extTsObjTree;

    if (this.extPath === '') {
      return extTree;
    }
    const path = this.extPath.split('.');

    for (let i = 0; i < path.length; i++) {
      const pathSeg = path[i];
      if (typeof extTree.c === 'undefined' || typeof extTree.c[pathSeg] === 'undefined') {
        return null;
      }
      extTree = extTree.c[pathSeg];
    }
    return extTree;
  }
}

class Stack<T> extends Array<T> {
  public lastElementEquals(el: T): boolean {
    return this.length > 0 && this[this.length - 1] === el;
  }

  public popIfLastElementEquals(el: T): boolean {
    if (this.lastElementEquals(el)) {
      this.pop();
      return true;
    }
    return false;
  }
}

export class TsParser {
  public readonly tsRef: TsRef;
  public readonly extTsObjTree: TsObjTree;
  private tsTree: TreeNode;

  private clone: <T extends object | unknown>(myObj: T) => T;

  constructor(tsRef: TsRef, extTsObjTree: TsObjTree) {
    this.tsRef = tsRef;
    this.extTsObjTree = extTsObjTree;
    this.tsTree = new TreeNode('_L_', this);
  }

  /**
   * Check if there is an operator in the line and return it
   * if there is none, return -1
   */
  public getOperator(line: string): string | -1 {
    const operators = [':=', '=<', '<', '>', '='];
    for (let i = 0; i < operators.length; i++) {
      const op = operators[i];
      if (line.indexOf(op) !== -1) {
        // check if there is some HTML in this line (simple check, however it's the only difference between a reference operator and HTML)
        // we do this check only in case of the two operators "=<" and "<" since the delete operator would trigger our "HTML-finder"
        if ((op === '=<' || op === '<') && line.indexOf('>') > -1) {
          // if there is a ">" in the line suppose there's some HTML
          return '=';
        }
        return op;
      }
    }
    return -1;
  }

  /**
   * Build the TypoScript object tree
   */
  public buildTsObjTree(completionState: CodeMirror5CompatibleCompletionState): TreeNode {
    this.tsTree = new TreeNode('', this);
    this.tsTree.value = 'TLO';

    let currentLine = 1,
      line = '',
      ignoreLine = false,
      insideCondition = false;
    const stack = new Stack<string>();
    const prefixes = [];
    let path;

    while (currentLine <= completionState.currentLineNumber) {
      line = '';
      const tokens = completionState.lineTokens[currentLine - 1];
      for (let i = 0; i <= tokens.length; ++i) {
        if (i < tokens.length && tokens[i].string.length > 0) {
          const tokenValue = tokens[i].string;

          if (tokenValue.startsWith('#')) {
            stack.push('#');
          } else if (tokenValue === '(') {
            stack.push('(');
          } else if (tokenValue.startsWith('/*')) {
            stack.push('/*');
          } else if (tokenValue === '{') {
            // TODO: ignore whole block if wrong whitespaces in this line
            if (this.getOperator(line) === -1) {
              stack.push('{');
              prefixes.push(line.trim());
              ignoreLine = true;
            }
          }
          // TODO: conditions
          // if condition starts -> ignore everything until end of condition
          if (tokenValue.search(/^\s*\[.*\]/) !== -1
            && line.search(/\S/) === -1
            && tokenValue.search(/^\s*\[(global|end|GLOBAL|END)\]/) === -1
            && !stack.lastElementEquals('#')
            && !stack.lastElementEquals('/*')
            && !stack.lastElementEquals('{')
            && !stack.lastElementEquals('(')
          ) {
            insideCondition = true;
            ignoreLine = true;
          }

          // if end of condition reached
          if (line.search(/\S/) === -1
            && !stack.lastElementEquals('#')
            && !stack.lastElementEquals('/*')
            && !stack.lastElementEquals('(')
            && (
              (tokenValue.search(/^\s*\[(global|end|GLOBAL|END)\]/) !== -1
                && !stack.lastElementEquals('{'))
              || (tokenValue.search(/^\s*\[(global|GLOBAL)\]/) !== -1)
            )
          ) {
            insideCondition = false;
            ignoreLine = true;
          }

          if (tokenValue === ')') {
            stack.popIfLastElementEquals('(');
          }
          if (tokenValue.startsWith('*/')) {
            stack.popIfLastElementEquals('/*');
            ignoreLine = true;
          }
          if (tokenValue === '}') {
            //no characters except whitespace allowed before closing bracket
            const trimmedLine = line.replace(/\s/g, '');
            if (trimmedLine === '') {
              stack.popIfLastElementEquals('{');
              if (prefixes.length > 0) {
                prefixes.pop();
              }
              ignoreLine = true;
            }
          }
          if (!stack.lastElementEquals('#')) {
            line += tokenValue;
          }
        } else {
          // ignore comments, ...
          if (!stack.lastElementEquals('/*') && !stack.lastElementEquals('(') && !ignoreLine && !insideCondition) {
            line = line.trim();
            // check if there is any operator in this line
            const op = this.getOperator(line);
            if (op !== -1) {
              // figure out the position of the operator
              const pos = line.indexOf(op);
              // the target objectpath should be left to the operator
              path = line.substring(0, pos);
              // if we are in between curly brackets: add prefixes to object path
              if (prefixes.length > 0) {
                path = prefixes.join('.') + '.' + path;
              }
              // the type or value should be right to the operator
              let str = line.substring(pos + op.length, line.length).trim();
              path = path.trim();
              switch (op) { // set a value or create a new object
                case '=':
                  //ignore if path is empty or contains whitespace
                  if (path.search(/\s/g) === -1 && path.length > 0) {
                    this.setTreeNodeValue(path, str);
                  }
                  break;
                case '=<': // reference to another object in the tree
                  // resolve relative path
                  if (prefixes.length > 0 && str.substr(0, 1) === '.') {
                    str = prefixes.join('.') + str;
                  }
                  //ignore if either path or str is empty or contains whitespace
                  if (path.search(/\s/g) === -1
                    && path.length > 0
                    && str.search(/\s/g) === -1
                    && str.length > 0
                  ) {
                    this.setReference(path, str);
                  }
                  break;
                case '<': // copy from another object in the tree
                  // resolve relative path
                  if (prefixes.length > 0 && str.substr(0, 1) === '.') {
                    str = prefixes.join('.') + str;
                  }
                  //ignore if either path or str is empty or contains whitespace
                  if (path.search(/\s/g) === -1
                    && path.length > 0
                    && str.search(/\s/g) === -1
                    && str.length > 0
                  ) {
                    this.setCopy(path, str);
                  }
                  break;
                case '>': // delete object value and properties
                  this.deleteTreeNodeValue(path);
                  break;
                case ':=': // function operator
                  // TODO: function-operator
                  break;
                default:
                  break;
              }
            }
          }
          stack.popIfLastElementEquals('#');
          ignoreLine = false;
        }
      }
      currentLine++;
    }
    // when node at cursorPos is reached:
    // save currentLine, currentTsTreeNode and filter if necessary
    // if there is a reference or copy operator ('<' or '=<')
    // return the treeNode of the path right to the operator,
    // else try to build a path from the whole line
    if (!stack.lastElementEquals('/*') && !stack.lastElementEquals('(') && !ignoreLine) {
      const i = line.indexOf('<');
      if (i !== -1) {
        path = line.substring(i + 1, line.length).trim();
        if (prefixes.length > 0 && path.substr(0, 1) === '.') {
          path = prefixes.join('.') + path;
        }
      } else {
        path = line;
        if (prefixes.length > 0) {
          path = prefixes.join('.') + '.' + path;
          path = path.replace(/\s/g, '');
        }
      }
      const lastDot = path.lastIndexOf('.');
      path = path.substring(0, lastDot);
    }
    return this.getTreeNode(path);
  }

  /**
   * Iterates through the object tree, and creates treenodes
   * along the path, if necessary
   */
  public getTreeNode(path: string): TreeNode | undefined {
    path = path.trim();
    if (path.length === 0) {
      return this.tsTree;
    }
    const aPath = path.split('.');

    let subTree = this.tsTree.childNodes,
      pathSeg,
      parent = this.tsTree;

    // step through the path from left to right
    for (let i = 0; i < aPath.length; i++) {
      pathSeg = aPath[i];

      // if there isn't already a treenode
      if (typeof subTree[pathSeg] === 'undefined' || typeof subTree[pathSeg].childNodes === 'undefined') { // if this subpath is not defined in the code
        // create a new treenode
        subTree[pathSeg] = new TreeNode(pathSeg, this);
        subTree[pathSeg].parent = parent;
        // the extPath has to be set, so the TreeNode can retrieve the respecting node in the external templates
        let extPath = parent.extPath;
        if (extPath) {
          extPath += '.';
        }
        extPath += pathSeg;
        subTree[pathSeg].extPath = extPath;
      }
      if (i === aPath.length - 1) {
        return subTree[pathSeg];
      }
      parent = subTree[pathSeg];
      subTree = subTree[pathSeg].childNodes;
    }
    return undefined;
  }

  /**
   * Navigates to the respecting treenode,
   * create nodes in the path, if necessary, and sets the value
   */
  public setTreeNodeValue(path: string, value: string): void {
    const treeNode = this.getTreeNode(path);
    // if we are inside a GIFBUILDER Object
    if (treeNode.parent !== null && treeNode.parent.value === 'GIFBUILDER' && value === 'TEXT') {
      value = 'GB_TEXT';
    }
    if (treeNode.parent !== null && treeNode.parent.value === 'GIFBUILDER' && value === 'IMAGE') {
      value = 'GB_IMAGE';
    }

    // just override if it is a real objecttype
    if (this.tsRef.isType(value)) {
      treeNode.value = value;
    }
  }

  /**
   * Navigates to the respecting treenode,
   * creates nodes if necessary, empties the value and childNodes-Array
   */
  public deleteTreeNodeValue(path: string) {
    const treeNode = this.getTreeNode(path);
    // currently the node is not deleted really, it's just not displayed cause value == null
    // deleting it would be a cleaner solution
    treeNode.value = null;
    treeNode.childNodes = {};
  }

  /**
   * Copies a reference of the treeNode specified by path2
   * to the location specified by path1
   */
  public setReference(path1: string, path2: string): void {
    const path1arr = path1.split('.'),
      lastNodeName = path1arr[path1arr.length - 1],
      treeNode1 = this.getTreeNode(path1),
      treeNode2 = this.getTreeNode(path2);

    if (treeNode1.parent !== null) {
      treeNode1.parent.childNodes[lastNodeName] = treeNode2;
    } else {
      this.tsTree.childNodes[lastNodeName] = treeNode2;
    }
  }

  /**
   * copies a treeNode specified by path2
   * to the location specified by path1
   */
  public setCopy(path1: string, path2: string): void {
    this.clone = <T extends object | unknown>(myObj: T): T => {
      if (typeof myObj !== 'object') {
        return myObj;
      }

      const myNewObj: Record<string, unknown> = {};
      for (const i in myObj) {
        if (i === 'tsParser') {
          continue;
        }
        // disable recursive cloning for parent object -> copy by reference
        if (i !== 'parent') {
          if (typeof myObj[i] === 'object') {
            myNewObj[i] = this.clone(myObj[i]);
          } else {
            myNewObj[i] = myObj[i];
          }
        } else {
          if ('parent' in myObj) {
            myNewObj.parent = myObj.parent;
          }
        }
      }
      return myNewObj as T;
    };

    const path1arr = path1.split('.'),
      lastNodeName = path1arr[path1arr.length - 1],
      treeNode1 = this.getTreeNode(path1),
      treeNode2 = this.getTreeNode(path2);

    if (treeNode1.parent !== null) {
      treeNode1.parent.childNodes[lastNodeName] = this.clone(treeNode2);
    } else {
      this.tsTree.childNodes[lastNodeName] = this.clone(treeNode2);
    }
  }
}
