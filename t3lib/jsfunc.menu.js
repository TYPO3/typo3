/***************************************************************
*
*  JavaScript menu
*
*
*
*  Copyright notice
*
*  (c) 1998-2011 Kasper Skaarhoj
*  All rights reserved
*
*  This script is part of the TYPO3 t3lib/ library provided by
*  Kasper Skaarhoj <kasper@typo3.com> together with TYPO3
*
*  Released under GNU/GPL (see license file in tslib/)
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
*  This copyright notice MUST APPEAR in all copies of this script
***************************************************************/


function JSmenuItem (owner, id, nextItem, parent, openID, title, url, target) {
	this.owner = owner;
	this.id = id;
	this.nextItem = nextItem;
	this.child = 0;
	this.openID = openID;
	this.parent = parent;
	this.title = title;
	this.url = url;
	this.target = target;
}
function JSmenu (levels,formname) {
	this.name = name,
	this.levels = levels;
	this.formname = formname;

	this.openID = 0;

	this.entry = new JSmenuItem (this);
	this.count = 0;
	this.defTopTitle = new Array();
	this.add = JSmenuAddItem;			// Tilføjer Item
	this.writeOut = JSmenuWrite;
	this.act = JSactivate;
}
function JSmenuAddItem (parent,prevItem,openID,title,url,target) {
	this.count++;
	var entryID = this.count;
	this.entry[entryID] = new JSmenuItem (this, entryID, 0, parent, openID, title, url, target);
	if (prevItem) {
		this.entry[prevItem].nextItem = entryID;
	} else if(parent) {
		this.entry[parent].child = entryID;
	}
	return entryID;
}
function JSmenuWrite(theEntryID,openID,theLevel) {
	var level=theLevel;
	if (level<=this.levels)	{
		var entryID = theEntryID;
		var firstEntryID = theEntryID;
		var selectorBox = document[this.formname]["selector"+level];
		var index=0;
		selectorBox.length=0;
		selectorBox.length++;
		selectorBox.options[index].text = this.defTopTitle[theLevel] ? this.defTopTitle[theLevel] : "                   ";
		selectorBox.options[index].value = 0;
		index++;
		var indexSet=0;
		if (entryID && this.entry[entryID])	{
			var ids = "";
			while(entryID)	{
			ids+="-"+entryID;
				selectorBox.length++;
				selectorBox.options[index].text = this.entry[entryID].title;
				selectorBox.options[index].value = entryID;
				if (openID==entryID)	{
					var indexSet = 1;
					selectorBox.selectedIndex = index;
					if (level<this.levels)	{
						this.writeOut(this.entry[entryID].child, this.entry[entryID].openID,level+1);
					}
				}
				index++;
				entryID=this.entry[entryID].nextItem;
			}
			if (!indexSet) {
				selectorBox.selectedIndex=0;
				this.writeOut(this.entry[firstEntryID].child, this.entry[firstEntryID].openID,level+1);
			}
		} else if (level<this.levels)	{
			this.writeOut(0, 0,level+1);
		}
	}
}
function JSactivate(level) {
	var selectorBox = document[this.formname]["selector"+level];
	var entryID = selectorBox.options[selectorBox.selectedIndex].value;
	if (this.entry[entryID])	{
		this.writeOut(this.entry[entryID].child,this.entry[entryID].openID,level+1);
		if (this.entry[this.entry[entryID].parent])	{
			this.entry[this.entry[entryID].parent].openID = entryID;
		}
		if (this.entry[entryID].url)	{
			var baseURLs = document.getElementsByTagName('base');
			if (baseURLs.length && baseURLs[0].href.length > 0) {
				if (this.entry[entryID].url.search(/^http[s]?:\/\//))	{
					this.entry[entryID].url = baseURLs[0].href + this.entry[entryID].url;
				}
			}
			if (!this.entry[entryID].target || this.entry[entryID].target=="_self")	{
				window.location.href = this.entry[entryID].url;
			} else if (this.entry[entryID].target=="_top") {
				top.location.href = this.entry[entryID].url;
			} else {
				var test = eval ("parent."+this.entry[entryID].target);
				if (!test) {
					test = eval ("top."+this.entry[entryID].target);
				}
				if (test && test.document) {
					test.location.href = this.entry[entryID].url;
				} else {
					window.open(this.entry[entryID].url,this.entry[entryID].target,"status=yes,menubar=yes,resizable=yes,location=yes,directories=yes,scrollbars=yes,toolbar=yes");
				}
			}
		}
	}
}
