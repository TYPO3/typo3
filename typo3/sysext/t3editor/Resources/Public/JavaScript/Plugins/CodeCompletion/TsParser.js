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
 * Module: TYPO3/CMS/T3editor/CodeCompletion/TsParser
 * Contains the TsCodeCompletion class
 */
define([
	'jquery', 'TYPO3/CMS/T3editor/Plugins/CodeCompletion/TsRef'
], function ($) {
	/**
	 *
	 * @type {{typeId: null, properties: null, typeTree: Array, doc: null, tsRef: null, extTsObjTree: Array, tsTree: null}}
	 * @exports TYPO3/CMS/T3editor/CodeCompletion/TsParser
	 */
	var TsParser = {
		typeId: null,
		properties: null,
		typeTree: [],
		doc: null,
		tsRef: null,
		extTsObjTree: [],
		tsTree: null
	};

	/**
	 *
	 * @param {Object} tsRef
	 * @param {Object} extTsObjTree
	 * @returns {{typeId: null, properties: null, typeTree: Array, doc: null, tsRef: null, extTsObjTree: Array, tsTree: null}}
	 */
	TsParser.init = function(tsRef, extTsObjTree) {
		TsParser.tsRef = tsRef;
		TsParser.extTsObjTree = extTsObjTree;
		TsParser.tsTree = new TsParser.treeNode('_L_');

		return TsParser;
	};

	/**
	 *
	 * @param {String} nodeName
	 */
	TsParser.treeNode = function(nodeName) {
		this.name = nodeName;
		this.childNodes = [];
		this.extPath = '';
		this.value = '';
		this.isExternal = false;

		/**
		 * Returns local properties and the properties of the external templates
		 *
		 * @return {Array}
		 */
		this.getChildNodes = function() {
			var node = this.getExtNode();
			if (node !== null && typeof node.c === 'object') {
				for (key in node.c) {
					var tn = new TsParser.treeNode(key, this.tsObjTree);
					tn.global = true;
					tn.value = (node.c[key].v)? node.c[key].v : "";
					tn.isExternal = true;
					this.childNodes[key] = tn;
				}
			}
			return this.childNodes;
		};

		/**
		 * Returns the value of a node
		 *
		 * @returns {String}
		 */
		this.getValue = function() {
			if (this.value) {
				return this.value;
			}
			var node = this.getExtNode();
			if (node && node.v) {
				return node.v;
			}

			var type = this.getNodeTypeFromTsref();
			if (type) {
				return type;
			}
			return '';
		};

		/**
		 * This method will try to resolve the properties recursively from right
		 * to left. If the node's value property is not set, it will look for the
		 * value of its parent node, and if there is a matching childProperty
		 * (according to the TSREF) it will return the childProperties value.
		 * If there is no value in the parent node it will go one step further
		 * and look into the parent node of the parent node,...
		 *
		 * @return {String}
		 */
		this.getNodeTypeFromTsref = function() {
			var path = this.extPath.split('.'),
				lastSeg = path.pop();

			// attention: there will be recursive calls if necessary
			var parentValue = this.parent.getValue();
			if (parentValue) {
				if (TsParser.tsRef.typeHasProperty(parentValue, lastSeg)) {
					var type = TsParser.tsRef.getType(parentValue);
					return type.properties[lastSeg].value;
				}
			}
			return '';
		};

		/**
		 * Will look in the external ts-tree (static templates, templates on other pages)
		 * if there is a value or childproperties assigned to the current node.
		 * The method uses the extPath of the current node to navigate to the corresponding
		 * node in the external tree
		 *
		 * @return {Object}
		 */
		this.getExtNode = function() {
			var extTree = TsParser.extTsObjTree,
				path,
				pathSeg;

			if (this.extPath === '') {
				return extTree;
			}
			path = this.extPath.split('.');

			for (var i = 0; i < path.length; i++) {
				pathSeg = path[i];
				if (typeof extTree.c === 'undefined' || typeof extTree.c[pathSeg] === 'undefined') {
					return null;
				}
				extTree = extTree.c[pathSeg];
			}
			return extTree;
		};
	};

	/**
	 * Check if there is an operator in the line and return it
	 * if there is none, return -1
	 *
	 * @return {(String|Number)}
	 */
	TsParser.getOperator = function(line) {
		var operators = [':=', '=<', '<', '>', '='];
		for (var i = 0; i < operators.length; i++) {
			var op = operators[i];
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
	};

	/**
	 * Build the TypoScript object tree
	 *
	 * @param {Object} startNode
	 * @param {Object} cursorNode
	 */
	TsParser.buildTsObjTree = function(startNode, cursorNode) {
		TsParser.tsTree = new TsParser.treeNode('');
		TsParser.tsTree.value = 'TLO';

		function Stack() {
		}
		Stack.prototype = [];
		Stack.prototype.lastElementEquals = function(str) {
			return this.length > 0 && this[this.length-1] === str;
		};

		Stack.prototype.popIfLastElementEquals = function(str) {
			if (this.lastElementEquals(str)) {
				this.pop();
				return true;
			}
			return false;
		};

		var currentNode = startNode,
			line = '',
			stack = new Stack(),
			prefixes = [],
			ignoreLine = false,
			insideCondition = false;

		while (true) {
			if (currentNode.hasChildNodes() && currentNode.firstChild.nodeType === 3 && currentNode.currentText.length > 0) {
				var node = currentNode.currentText;
				if (node[0] === '#') {
					stack.push('#');
				}
				if (node === '(') {
					stack.push('(');
				}
				if (node[0] === '/' && node[1] === '*') {
					stack.push('/*');
				}
				if (node === '{') {
					// TODO: ignore whole block if wrong whitespaces in this line
					if (TsParser.getOperator(line) === -1) {
						stack.push('{');
						prefixes.push($.trim(line));
						ignoreLine = true;
					}
				}
				// TODO: conditions
				// if condition starts -> ignore everything until end of condition
				if (node.search(/^\s*\[.*\]/) !== -1
					&& line.search(/\S/) === -1
					&& node.search(/^\s*\[(global|end|GLOBAL|END)\]/) === -1
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
						(node.search(/^\s*\[(global|end|GLOBAL|END)\]/) !== -1
						&& !stack.lastElementEquals('{'))
						|| (node.search(/^\s*\[(global|GLOBAL)\]/) !== -1)
					)
				) {
					insideCondition = false;
					ignoreLine = true;
				}

				if (node === ')') {
					stack.popIfLastElementEquals('(');
				}
				if (node[0] === '*' && node[1] === '/') {
					stack.popIfLastElementEquals('/*');
					ignoreLine = true;
				}
				if (node === '}') {
					//no characters except whitespace allowed before closing bracket
					var trimmedLine = line.replace(/\s/g, '');
					if (trimmedLine === '') {
						stack.popIfLastElementEquals('{');
						if (prefixes.length > 0) {
							prefixes.pop();
						}
						ignoreLine = true;
					}
				}
				if (!stack.lastElementEquals('#')) {
					line += node;
				}
			} else {
				//end of line? divide line into path and text and try to build a node
				if (currentNode.tagName === 'BR') {
					// ignore comments, ...
					if (!stack.lastElementEquals('/*') && !stack.lastElementEquals('(') && !ignoreLine && !insideCondition) {
						line = $.trim(line);
						// check if there is any operator in this line
						var op = TsParser.getOperator(line);
						if (op !== -1) {
							// figure out the position of the operator
							var pos = line.indexOf(op);
							// the target objectpath should be left to the operator
							var path = line.substring(0, pos);
							// if we are in between curly brackets: add prefixes to object path
							if (prefixes.length > 0) {
								path = prefixes.join('.') + '.' + path;
							}
							// the type or value should be right to the operator
							var str = line.substring(pos + op.length, line.length);
							path = $.trim(path);
							str = $.trim(str);
							switch (op) { // set a value or create a new object
								case '=':
									//ignore if path is empty or contains whitespace
									if (path.search(/\s/g) === -1 && path.length > 0) {
										TsParser.setTreeNodeValue(path, str);
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
										TsParser.setReference(path, str);
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
										TsParser.setCopy(path, str);
									}
									break;
								case '>': // delete object value and properties
									TsParser.deleteTreeNodeValue(path);
									break;
								case ':=': // function operator
									// TODO: function-operator
									break;
							}
						}
					}
					stack.popIfLastElementEquals('#');
					ignoreLine = false;
					line = '';
				}
			}
			// todo: fix problem: occurs if you type something, delete it with backspace and press ctrl+space
			// hack: cursor.start does not always return the node on the same level- so we have to check both
			// if (currentNode == cursor.start.node.parentNode || currentNode == cursor.start.node.previousSibling){
			// another problem: also the filter is calculated wrong, due to the buggy cursor, so this hack is useless
			if (currentNode === cursorNode) {
				break;
			} else {
				currentNode = currentNode.nextSibling;
			}
		}
		// when node at cursorPos is reached:
		// save currentLine, currentTsTreeNode and filter if necessary
		// if there is a reference or copy operator ('<' or '=<')
		// return the treeNode of the path right to the operator,
		// else try to build a path from the whole line
		if (!stack.lastElementEquals('/*') && !stack.lastElementEquals('(') && !ignoreLine) {
			var currentLine = line,
				i = line.indexOf('<');

			if (i !== -1) {
				var path = line.substring(i+1, line.length);
				path = $.trim(path);
				if (prefixes.length > 0 && path.substr(0, 1) === '.') {
					path = prefixes.join('.') + path;
				}
			} else {
				var path = line;
				if (prefixes.length > 0) {
					path = prefixes.join('.') + '.' + path;
					path = path.replace(/\s/g, '');
				}
			}
			var lastDot = path.lastIndexOf('.');
			path = path.substring(0, lastDot);
		}
		return TsParser.getTreeNode(path);
	};

	/**
	 * Iterates through the object tree, and creates treenodes
	 * along the path, if necessary
	 *
	 * @param {String} path
	 * @returns {Object}
	 */
	TsParser.getTreeNode = function(path) {
		path = $.trim(path);
		if (path.length === 0) {
			return TsParser.tsTree;
		}
		var aPath = path.split('.');

		var subTree = TsParser.tsTree.childNodes,
			pathSeg,
			parent = TsParser.tsTree;

		// step through the path from left to right
		for (var i = 0; i < aPath.length; i++) {
			pathSeg = aPath[i];

			// if there isn't already a treenode
			if (typeof subTree[pathSeg] === 'undefined' || typeof subTree[pathSeg].childNodes === 'undefined') { // if this subpath is not defined in the code
				// create a new treenode
				subTree[pathSeg] = new TsParser.treeNode(pathSeg);
				subTree[pathSeg].parent = parent;
				// the extPath has to be set, so the TreeNode can retrieve the respecting node in the external templates
				var extPath = parent.extPath;
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
	};

	/**
	 * Navigates to the respecting treenode,
	 * create nodes in the path, if necessary, and sets the value
	 *
	 * @param {String} path
	 * @param {String} value
	 */
	TsParser.setTreeNodeValue = function(path, value) {
		var treeNode = TsParser.getTreeNode(path);
		// if we are inside a GIFBUILDER Object
		if (treeNode.parent !== null && (treeNode.parent.value === "GIFBUILDER" || treeNode.parent.getValue() === "GMENU_itemState") && value === "TEXT") {
			value = 'GB_TEXT';
		}
		if (treeNode.parent !== null && (treeNode.parent.value === "GIFBUILDER" || treeNode.parent.getValue() === "GMENU_itemState") && value === "IMAGE") {
			value = 'GB_IMAGE';
		}

		// just override if it is a real objecttype
		if (TsParser.tsRef.isType(value)) {
			treeNode.value = value;
		}
	};

	/**
	 * Navigates to the respecting treenode,
	 * creates nodes if necessary, empties the value and childNodes-Array
	 *
	 * @param {String} path
	 */
	TsParser.deleteTreeNodeValue = function(path) {
		var treeNode = TsParser.getTreeNode(path);
		// currently the node is not deleted really, it's just not displayed cause value == null
		// deleting it would be a cleaner solution
		treeNode.value = null;
		treeNode.childNodes = {};
	};

	/**
	 * Copies a reference of the treeNode specified by path2
	 * to the location specified by path1
	 *
	 * @param {String} path1
	 * @param {String} path2
	 */
	TsParser.setReference = function(path1, path2) {
		var path1arr = path1.split('.'),
			lastNodeName = path1arr[path1arr.length - 1],
			treeNode1 = TsParser.getTreeNode(path1),
			treeNode2 = TsParser.getTreeNode(path2);

		if (treeNode1.parent !== null) {
			treeNode1.parent.childNodes[lastNodeName] = treeNode2;
		} else {
			TsParser.tsTree.childNodes[lastNodeName] = treeNode2;
		}
	};

	/**
	 * copies a treeNode specified by path2
	 * to the location specified by path1
	 *
	 * @param {String} path1
	 * @param {String} path2
	 */
	TsParser.setCopy = function(path1, path2) {
		this.clone = function(myObj) {
			if (typeof myObj !== 'object') {
				return myObj;
			}

			var myNewObj = {};
			for (var i in myObj) {
				// disable recursive cloning for parent object -> copy by reference
				if (i !== 'parent') {
					if (typeof myObj[i] === 'object') {
						myNewObj[i] = this.clone(myObj[i]);
					} else {
						myNewObj[i] = myObj[i];
					}
				} else {
					myNewObj.parent = myObj.parent;
				}
			}
			return myNewObj;
		};

		var path1arr = path1.split('.'),
			lastNodeName = path1arr[path1arr.length - 1],
			treeNode1 = TsParser.getTreeNode(path1),
			treeNode2 = TsParser.getTreeNode(path2);

		if (treeNode1.parent !== null) {
			treeNode1.parent.childNodes[lastNodeName] = this.clone(treeNode2);
		} else {
			TsParser.tsTree.childNodes[lastNodeName] = this.clone(treeNode2);
		}
	};

	return TsParser;
});
