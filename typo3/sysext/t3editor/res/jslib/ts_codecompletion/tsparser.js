/***************************************************************
* Copyright notice
*
* (c) 2008-2010 Stephan Petzl <spetzl@gmx.at> and Christian Kartnig <office@hahnepeter.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
* A copy is found in the textfile GPL.txt and important notices to the license
* from the author is found in LICENSE.txt distributed with these scripts.
*
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * @fileoverview contains the TsParser class and the TreeNode helper class
 */

/**
 * Construct a new TsParser object.
 * @class This class takes care of the parsing and builds the codeTree
 *
 * @constructor
 * @param tsRef typoscript reference tree
 * @param extTsObjTree codeTree for all typoscript templates
 *			 excluding the current one.
 * @return A new TsParser instance
 */
var TsParser = function(tsRef,extTsObjTree){

	/**
	 * @class data structure for the nodes of the code tree
	 * mainly used for retrieving the externals templates childnodes
	 * @constructor
	 * @param {String} name
	 */
	function TreeNode(nodeName){
		this.name = nodeName;
		//this.tsObjTree = tsObjTree;
		this.childNodes = new Array();
		//has to be set, so the node can retrieve the childnodes of the external templates
		this.extPath = "";
		// the TS-objecttype ID (TSREF)
		this.value = "";
		//this.extTsObjTree = null;
		// current template or external template
		this.isExternal = false;

		/**
		 * returns local properties and the properties of the external templates
		 * @returns {Array} ChildNodes
		 */
		this.getChildNodes = function(){
			var node = this.getExtNode();
			if(node){
				for(key in node.c){
					var tn = new TreeNode(key,this.tsObjTree);
					tn.global = true;
					tn.value = (node.c[key].v)? node.c[key].v : "";
					tn.isExternal = true;
					this.childNodes[key] = tn;
				}
			}
			return this.childNodes;
		}

		this.getValue = function(){
			if(this.value) {
				return this.value;
			} else {
				var node = this.getExtNode();
				if(node && node.v) {
					return node.v;
				} else {
					var type = this.getNodeTypeFromTsref();
					if(type) {
						return type;
					} else {
						return '';
				}
			}
		}
		}

		/**
		 * This method will try to resolve the properties recursively from right
		 * to left. If the node's value property is not set, it will look for the
		 * value of its parent node, and if there is a matching childProperty
		 * (according to the TSREF) it will return the childProperties value.
		 * If there is no value in the parent node it will go one step further
		 * and look into the parent node of the parent node,...
		 */
		this.getNodeTypeFromTsref = function(){
			var path = this.extPath.split('.');
			var lastSeg = path.pop();
			// attention: there will be recursive calls if necessary
			var parentValue = this.parent.getValue();
			if(parentValue){
				if(tsRef.typeHasProperty(parentValue,lastSeg)){
					var type = tsRef.getType(parentValue);
					var propertyTypeId = type.properties[lastSeg].value;
					return propertyTypeId;
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
		this.getExtNode = function(){
			var extTree = extTsObjTree;
			var path = this.extPath.split('.');
			var pathSeg;
			if (path == "") {
			return extTree;
			}
			var i;
			for(i=0;i<path.length;i++){
				pathSeg = path[i];
				if(extTree.c == null || extTree.c[pathSeg] == null) {
					return null;
				}
				extTree = extTree.c[pathSeg];
			}
			return extTree;
		}

	}

	// the top level treenode
	var tsTree = new TreeNode("_L_");
	var currentLine = "";

	/**
	 * build Tree of TsObjects from beginning of editor to actual cursorPos
	 * and store it in tsTree.
	 * also store string from cursor position to the beginning of the line in currentLine
	 * and return the reference to the last path before the cursor position in currentTsTreeNode
	 * @param startNode DOM Node containing the first word in the editor
	 * @param cursorNode DOM Node containing the word at cursor position
	 * @return currentTsTreeNode
	 */
	this.buildTsObjTree = function(startNode, cursorNode){
		return buildTsObjTree(startNode, cursorNode);
	}
	function buildTsObjTree(startNode, cursorNode) {
		var currentNode = startNode;
		var line = "";
		tsTree = new TreeNode("");
		tsTree.value = "TLO";
		function Stack() {
		}

		Stack.prototype = new Array();

		Stack.prototype.lastElementEquals = function(str) {
			if (this.length > 0 && this[this.length-1]==str) {
				return true;
			}else {
				return false;
			}
		}

		Stack.prototype.popIfLastElementEquals = function(str) {
			if(this.length > 0 && this[this.length-1]==str) {
				this.pop();
				return true;
			}else {
				return false;
			}
		}

		var stack = new Stack();
		var prefixes = new Array();
		var ignoreLine = false;
		//var cursorReached = false;
		var insideCondition = false;

		while(true) {
			if(currentNode.hasChildNodes() && currentNode.firstChild.nodeType==3 && currentNode.currentText.length>0) {
				node = currentNode.currentText;
				if (node[0] == '#') {
					stack.push('#');
				}
				if (node == '(') {
					stack.push('(');
				}
				if (node[0] == '/' && node[1]=='*') {
					stack.push('/*');
				}
				if (node == '{') {
					// TODO: ignore whole block if wrong whitespaces in this line
					if (getOperator(line)==-1) {
						stack.push('{');
						prefixes.push(line.strip());
						ignoreLine = true;
					}
				}
				// TODO: conditions
				// if condition starts -> ignore everything until end of condition
				if (node.search(/^\s*\[.*\]/) != -1
						&& line.search(/\S/) == -1
						&& node.search(/^\s*\[(global|end|GLOBAL|END)\]/) == -1
						&& !stack.lastElementEquals('#')
						&& !stack.lastElementEquals('/*')
						&& !stack.lastElementEquals('{')
						&& !stack.lastElementEquals('(')
				) {
					insideCondition = true;
					ignoreLine = true;
				}

				// if end of condition reached
				if (line.search(/\S/) == -1
						&& !stack.lastElementEquals('#')
						&& !stack.lastElementEquals('/*')
						&& !stack.lastElementEquals('(')
						&& (
								(node.search(/^\s*\[(global|end|GLOBAL|END)\]/) != -1
										&& !stack.lastElementEquals('{'))
										|| (node.search(/^\s*\[(global|GLOBAL)\]/) != -1)
						)
				) {
					insideCondition = false;
					ignoreLine = true;
				}

				if (node == ')') {
					stack.popIfLastElementEquals('(');
				}
				if (node[0] == '*' && node[1]=='/') {
					stack.popIfLastElementEquals('/*');
					ignoreLine = true;
				}
				if (node == '}') {
					//no characters except whitespace allowed before closing bracket
					trimmedLine = line.replace(/\s/g,"");
					if (trimmedLine=="") {
						stack.popIfLastElementEquals('{');
						if (prefixes.length>0) prefixes.pop();
						ignoreLine = true;
					}
				}
				if (!stack.lastElementEquals('#')) {
					line += node;
				}

			} else {
				//end of line? divide line into path and text and try to build a node
				if (currentNode.tagName == "BR") {
					// ignore comments, ...
					if(!stack.lastElementEquals('/*') && !stack.lastElementEquals('(') && !ignoreLine && !insideCondition) {
						line = line.strip();
						// check if there is any operator in this line
						var op = getOperator(line);
						if (op != -1) {
							// figure out the position of the operator
							var pos = line.indexOf(op);
							// the target objectpath should be left to the operator
							var path = line.substring(0,pos);
							// if we are in between curly brackets: add prefixes to object path
							if (prefixes.length>0) {
								path = prefixes.join('.') + '.' + path;
							}
							// the type or value should be right to the operator
							var str = line.substring(pos+op.length, line.length);
							path = path.strip();
							str = str.strip();
							switch(op) { // set a value or create a new object
							case '=':
								//ignore if path is empty or contains whitespace
								if (path.search(/\s/g) == -1 && path.length > 0) {
								setTreeNodeValue(path, str);
								}
								break;
							case '=<': // reference to another object in the tree
								 // resolve relative path
								if(prefixes.length > 0 && str.substr(0, 1) == '.') {
									str = prefixes.join('.') + str;
								}
								//ignore if either path or str is empty or contains whitespace
								if (path.search(/\s/g) == -1
								 && path.length > 0
								 && str.search(/\s/g) == -1
								 && str.length > 0) {
								setReference(path, str);
								}
								break;
							case '<': // copy from another object in the tree
								// resolve relative path
								if(prefixes.length > 0 && str.substr(0, 1) == '.') {
									str = prefixes.join('.') + str;
								}
								//ignore if either path or str is empty or contains whitespace
								if (path.search(/\s/g) == -1
								 && path.length > 0
								 && str.search(/\s/g) == -1
								 && str.length > 0) {
								setCopy(path, str);
								}
								break;
							case '>': // delete object value and properties
								deleteTreeNodeValue(path);
								break;
							case ':=': // function operator
								// TODO: function-operator
								break;
							}
						}
					}
					stack.popIfLastElementEquals('#');
					ignoreLine = false;
					line = "";
				}
			}
			// todo: fix problem: occurs if you type something, delete it with backspace and press ctrl+space
			// hack: cursor.start does not always return the node on the same level- so we have to check both
			// if (currentNode == cursor.start.node.parentNode || currentNode == cursor.start.node.previousSibling){
			// another problem: also the filter is calculated wrong, due to the buggy cursor, so this hack is useless
			if (currentNode == cursorNode) {
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

		if(!stack.lastElementEquals('/*') && !stack.lastElementEquals('(') && !ignoreLine) {
			currentLine = line;
			var i = line.indexOf('<');
			if (i != -1) {
				var path = line.substring(i+1, line.length);
				path = path.strip();
				if ( prefixes.length > 0 && path.substr(0,1) == '.') {
					path = prefixes.join('.') + path;
				}
			} else {
				var path = line;
				if (prefixes.length>0) {
					path = prefixes.join('.') + '.' + path;
					path = path.replace(/\s/g,"");
				}
			}
			var lastDot = path.lastIndexOf(".");
			path = path.substring(0, lastDot);
		}
		return getTreeNode(path);
	}

	/**
	 * check if there is an operator in the line and return it
	 * if there is none, return -1
	 */
	function getOperator(line) {
		var operators = new Array(":=", "=<", "<", ">", "=");
		for (var i=0; i<operators.length; i++) {
			var op = operators[i];
			if (line.indexOf(op) != -1) {
				// check if there is some HTML in this line (simple check, however it's the only difference between a reference operator and HTML)
				// we do this check only in case of the two operators "=<" and "<" since the delete operator would trigger our "HTML-finder"
				if((op == "=<" || op == "<") && line.indexOf(">") != -1){
					// if there is a ">" in the line suppose there's some HTML
					return "=";
				}
				return op;
			}
		}
		return -1;
	}

	/**
	 * iterates through the object tree, and creates treenodes
	 * along the path, if necessary
	 */
	function getTreeNode(path){
		var aPath = path.strip().split(".");
		if (aPath == "") {
			return tsTree;
		}
		var subTree = tsTree.childNodes;
		var pathSeg;
		var parent = tsTree;
		var currentNodePath = '';
		// step through the path from left to right
		for(i=0;i<aPath.length;i++){
			pathSeg = aPath[i];

			// if there isn't already a treenode
			if(subTree[pathSeg] == null || subTree[pathSeg].childNodes == null){ // if this subpath is not defined in the code
				// create a new treenode
				subTree[pathSeg] = new TreeNode(pathSeg);
				subTree[pathSeg].parent = parent;
				// the extPath has to be set, so the TreeNode can retrieve the respecting node in the external templates
				var extPath = parent.extPath;
				if(extPath) {
					extPath += '.';
				}
				extPath += pathSeg;
				subTree[pathSeg].extPath = extPath;
			}
			if(i==aPath.length-1){
				return subTree[pathSeg];
			}
			parent = subTree[pathSeg];
			subTree = subTree[pathSeg].childNodes;
		}
	}

	/**
	 * navigates to the respecting treenode,
	 * create nodes in the path, if necessary, and sets the value
	 */
	function setTreeNodeValue(path, value) {
		var treeNode = getTreeNode(path);
		// if we are inside a GIFBUILDER Object
		if(treeNode.parent != null && (treeNode.parent.value == "GIFBUILDER" || treeNode.parent.getValue() == "GMENU_itemState") && value == "TEXT") {
			value = "GB_TEXT";
		}
		if(treeNode.parent != null && (treeNode.parent.value == "GIFBUILDER" || treeNode.parent.getValue() == "GMENU_itemState") && value == "IMAGE") {
			value = "GB_IMAGE";
		}
		// just override if it is a real objecttype
		if (tsRef.isType(value)) {
			treeNode.value = value;
		}
	}

	/**
	 * navigates to the respecting treenode,
	 * creates nodes if necessary, empties the value and childNodes-Array
	 */
	function deleteTreeNodeValue(path) {
		var treeNode = getTreeNode(path);
		// currently the node is not deleted really, its just not displayed cause value == null
		// deleting it would be a cleaner solution
		treeNode.value = null;
		treeNode.childNodes = null;
		treeNode = null;
	}

	/**
	 * copies a reference of the treeNode specified by path2
	 * to the location specified by path1
	 */
	function setReference(path1, path2) {
		path1arr = path1.split('.');
		lastNodeName = path1arr[path1arr.length-1];
		var treeNode1 = getTreeNode(path1);
		var treeNode2 = getTreeNode(path2);
		if(treeNode1.parent != null) {
			treeNode1.parent.childNodes[lastNodeName] = treeNode2;
		} else {
			tsTree.childNodes[lastNodeName] = treeNode2;
		}
	}

	/**
	 * copies a treeNode specified by path2
	 * to the location specified by path1
	 */
	function setCopy(path1,path2){
		this.clone = function(myObj) {
			if (myObj == null || typeof(myObj) != 'object') {
				return myObj;
			}

			var myNewObj = new Object();

			for(var i in myObj){
				// disable recursive cloning for parent object -> copy by reference
				if(i != "parent"){
					if (typeof myObj[i] == 'object') {
						myNewObj[i] = clone(myObj[i]);
					} else {
						myNewObj[i] = myObj[i];
					}
				} else {
					myNewObj.parent = myObj.parent;
				}
			}
			return myNewObj;
		}
		var path1arr = path1.split('.');
		var lastNodeName = path1arr[path1arr.length-1];
		var treeNode1 = getTreeNode(path1);
		var treeNode2 = getTreeNode(path2);

		if(treeNode1.parent != null) {
			treeNode1.parent.childNodes[lastNodeName] = this.clone(treeNode2);
			//treeNode1.parent.childNodes[lastNodeName].extTsObjTree = extTsObjTree;
		} else {
			tsTree.childNodes[lastNodeName] = this.clone(treeNode2);
			//tsTree[lastNodeName].extTsObjTree = extTsObjTree;
		}
	}
}