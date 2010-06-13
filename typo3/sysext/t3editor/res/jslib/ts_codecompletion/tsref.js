/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Stephan Petzl <spetzl@gmx.at> and Christian Kartnig <office@hahnepeter.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * @fileoverview contains the TsRef class
 * and the TsRefProperty and TsRefType helper classes
 */

/**
 * @class Represents a TsRefProperty in the tree
 *
 * @constructor
 */
var TsRefProperty = function(parentType,name,value) {
	this.parentType = parentType;
	this.name = name;
	this.value = value;
	var descriptionCache = null;
	this.getDescription = function(callBack) {
		if(descriptionCache == null){
			var urlParameters = '&ajaxID=tx_t3editor_TSrefLoader::getDescription' +
				'&typeId=' + this.parentType +
				'&parameterName=' + this.name;

			new Ajax.Request(
				T3editor.URL_typo3 + 'ajax.php',
				{
					method: 'get',
					parameters: urlParameters,
					onSuccess: function(transport) {
						descriptionCache = transport.responseText;
						callBack(transport.responseText);
					}
				}
			);
		} else {
			callBack(descriptionCache);
		}
	}
}

/**
 * @class Represents a TsRefType in the tree
 *
 * @constructor
 */
var TsRefType = function(typeId) {
	this.typeId = typeId;
	this.properties = new Array();

	// todo: types can have descriptions too!
	this.getDescription = function() {
	}
}

/**
 * Construct a new TsRef object.
 * @class This class receives the TsRef from the server and represents it as a tree
 * also supplies methods for access to treeNodes
 *
 * @constructor
 * @return A new TsRef instance
 */
var TsRef = function() {
	var typeTree = new Array();	

	var doc;

	this.loadTsrefAsync = function() {
		var urlParameters = '&ajaxID=tx_t3editor_TSrefLoader::getTypes';
		new Ajax.Request(
			T3editor.URL_typo3 + 'ajax.php',
			{
				method: 'get',
				parameters: urlParameters,
				onSuccess: function(transport) {
					doc = eval('('+ transport.responseText +')');
					buildTree();
				}
			}
		);
	}



	function buildTree() {

		typeTree = new Array();
		for (var typeId in doc) {

			var arr = doc[typeId];
			typeTree[typeId] = new TsRefType(typeId);

			if (arr['extends'] != null) {
				typeTree[typeId]['extends'] = arr['extends'];
			}
			for (propName in arr.properties) {
				var propType = arr.properties[propName].type;
				typeTree[typeId].properties[propName] = new TsRefProperty(typeId,propName,propType);
			}
		}
		for (var typeId in typeTree) {
			if (typeTree[typeId]['extends'] != null) {
				//console.log(typeId+" | "+typeTree[typeId].extends+" |");
				addPropertiesToType(typeTree[typeId], typeTree[typeId]['extends'], 100);
			}
		}
	}


	function addPropertiesToType(addToType,addFromTypeNames,maxRecDepth){
		if(maxRecDepth<0){
			throw "Maximum recursion depth exceeded while trying to resolve the extends in the TSREF!";
			return;
		}
		var exts = addFromTypeNames.split(',');
		var i;
		for(i=0;i<exts.length;i++){
			//"Type 'array' which is used to extend 'undefined', was not found in the TSREF!"
			if(typeTree[exts[i]]==null){
				//console.log("Error: Type '"+exts[i]+"' which is used to extend '"+addToType.typeId+"', was not found in the TSREF!");
			}else{
				if(typeTree[exts[i]]['extends'] != null){
					addPropertiesToType(typeTree[exts[i]],typeTree[exts[i]]['extends'],maxRecDepth-1);
				}
				var properties = typeTree[exts[i]].properties;
				for(propName in properties){
					// only add this property if it was not already added by a supertype (subtypes override supertypes)
					if(addToType.properties[propName] == null){
						addToType.properties[propName] = properties[propName];
					}
				}
			}
		}

	}

	this.getPropertiesFromTypeId = function(tId) {
		if (typeTree[tId] != null) {
			// clone is needed to assure that nothing of the tsref is overwritten by user setup
			typeTree[tId].properties.clone = function() {
				var result = new Array();
				for (key in this) {
					result[key] = new TsRefProperty(this[key].parentType,this[key].name,this[key].value);
				}
				return result;
			}	
			return typeTree[tId].properties;
		} else {
			return new Array();
		}
	}

	this.typeHasProperty = function(typeId,propertyName) {
		if (typeTree[typeId] != null && typeTree[typeId].properties[propertyName] != null) {
			return true;
		} else {
			return false;
		}
	}

	this.getType = function(typeId){
		return typeTree[typeId];
	}
	this.isType = function(typeId){
		return (typeTree[typeId] != null);
	}
}
