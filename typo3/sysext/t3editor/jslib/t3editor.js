/***************************************************************
*  Copyright notice
*
*  (c) 2007 Tobias Liebig <mail_typo3@etobi.de>
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
/* t3editor.js uses the Codemirror editor.
 */


// collection of all t3editor instances on the current page
var t3e_instances = {};

// path to the editor ext dir
  // can be overwritten in class.tx_t3editor.php
var PATH_t3e = "../../../sysext/t3editor/";




/* Demonstration of embedding CodeMirror in a bigger application. The
 * interface defined here is a mess of prompts and confirms, and
 * should probably not be used in a real project.
 */

function T3editor(textarea) {
	var self = this;

		// memorize the textarea
	this.textarea = $(textarea);
	
		// outer wrap around the whole t3editor
  	this.outerdiv = new Element("DIV", {
		"class": "t3e_wrap"
	});
	
		// place the div before the textarea
	this.textarea.parentNode.insertBefore(this.outerdiv, $(this.textarea));


	// an overlay that covers the whole editor
	this.modalOverlay = new Element("DIV", {
		"class": "t3e_modalOverlay",
		"id": "t3e_modalOverlay_wait"
	});

	this.modalOverlay.hide();
	this.modalOverlay.setStyle(this.outerdiv.getDimensions());
	this.modalOverlay.setStyle({
		opacity: 0.8
	});
	this.outerdiv.appendChild(this.modalOverlay);

/*
		// wrapping the Toolbar
	this.toolbar_wrap = new Element("DIV", {
		"class": "t3e_toolbar_wrap"
	});
	this.outerdiv.appendChild(this.toolbar_wrap);
*/
	
		// wrapping the linenumbers
	this.linenum_wrap = new Element("DIV", {
		"class": "t3e_linenum_wrap"
	});
		// the "linenumber" list itself
	this.linenum = new Element("DL", {
		"class": "t3e_linenum"
	});
	this.linenum_wrap.appendChild(this.linenum);
	this.outerdiv.appendChild(this.linenum_wrap);

		// wrapping the iframe
	this.mirror_wrap = new Element("DIV", {
		"class": "t3e_iframe_wrap"
	});
	this.outerdiv.appendChild(this.mirror_wrap);
	
		// wrapping the statusbar
	this.statusbar_wrap = new Element("DIV", {
		"class": "t3e_statusbar_wrap"
	});
	this.outerdiv.appendChild(this.statusbar_wrap);
	
	this.statusbar_title = new Element("SPAN", {
		"class": "t3e_statusbar_title"
	});
	this.statusbar_wrap.appendChild(this.statusbar_title);
	this.statusbar_title.update( this.textarea.readAttribute('alt') );
	
	this.t3e_statusbar_status = new Element("SPAN", {
		"class": "t3e_statusbar_status"
	});
	this.statusbar_wrap.appendChild(this.t3e_statusbar_status);
	this.t3e_statusbar_status.update( '' );

	var textareaDim = $(this.textarea).getDimensions();

	this.linenum_wrap.setStyle({
		height: (textareaDim.height) + 'px'
	});

		// setting options
	var options = {
		height: (
				textareaDim.height
				) + 'px',
		width: (
				textareaDim.width
				- 40	// line numbers
				) + 'px',
		content: $(this.textarea).value,
		parserfile: ["tokenizetyposcript.js", "parsetyposcript.js"],
		stylesheet: PATH_t3e + "css/t3editor_inner.css",
		path: PATH_t3e + "jslib/codemirror/",
		outerEditor: this,
		saveFunction: this.saveFunction.bind(this),
		initCallback: this.init.bind(this)
	};

		// get the editor
	this.mirror = new CodeMirror(this.mirror_wrap, options);

}

T3editor.prototype = {
		saveFunctionEvent: null,
		saveButtons: null,
	
		init: function() {
			var textareaDim = $(this.textarea).getDimensions();
			// hide the textarea
			this.textarea.hide();
			
			// get the form object (needed for Ajax saving)
			var form = $(this.textarea.form)
			this.saveButtons = form.getInputs('image', 'submit');

			// initialize ajax saving events
			this.saveFunctionEvent = this.saveFunction.bind(this);
			this.saveButtons.each(function(button) {
				Event.observe(button,'click',this.saveFunctionEvent);
			}.bind(this));

			this.resize(textareaDim.width, textareaDim.height );
		},
	
		// indicates is content is modified and not safed yet
		textModified: false,
		
		// check if code in editor has been modified since last saving
		checkTextModified: function() {
			if (!this.textModified) {
				this.textModified = true;
				this.updateLinenum();
			}
		},
		
		// scroll the line numbers
		scroll: function() {
			var scrOfX = 0,
			scrOfY = 0;
			if (typeof(this.mirror.editor.win.pageYOffset) == 'number') {
				// Netscape compliant
				scrOfY = this.mirror.editor.win.pageYOffset;
				scrOfX = this.mirror.editor.win.pageXOffset;
			} else if (this.mirror.editor.doc.body && (this.mirror.editor.doc.body.scrollLeft || this.mirror.editor.doc.body.scrollTop)) {
				// DOM compliant
				scrOfY = this.mirror.editor.doc.body.scrollTop;
				scrOfX = this.mirror.editor.doc.body.scrollLeft;
			} else if (this.mirror.editor.doc.documentElement
			  && (this.mirror.editor.doc.documentElement.scrollLeft
			  || this.mirror.editor.doc.documentElement.scrollTop)) {
				// IE6 standards compliant mode
				scrOfY = this.mirror.editor.doc.documentElement.scrollTop;
				scrOfX = this.mirror.editor.doc.documentElement.scrollLeft;
			}
			this.linenum_wrap.scrollTop = scrOfY;
		},
		

		// update the line numbers
		updateLinenum: function(code) {
			var theMatch;
			if (!code) {
				code = this.mirror.editor.container.innerHTML;
				theMatch = code.match(/<br/gi);
			} else {
				theMatch = code.match(/\n/gi);
			} 

			if (!theMatch) {
				theMatch = [1];
			} else if (Prototype.Browser.IE) {
				theMatch.push('1');
			}

			var bodyContentLineCount = theMatch.length;
			disLineCount = this.linenum.childNodes.length;
			while (disLineCount != bodyContentLineCount) {
				if (disLineCount > bodyContentLineCount) {
					this.linenum.removeChild(this.linenum.lastChild);
					disLineCount--;
				} else if (disLineCount < bodyContentLineCount) {
					ln = $(document.createElement('dt'));
					ln.update(disLineCount + 1 + '.');
					ln.addClassName(disLineCount % 2 == 1 ? 'even': 'odd');
					ln.setAttribute('id', 'ln' + (disLineCount + 1));
					this.linenum.appendChild(ln);
					disLineCount++;
				}
			}

			this.t3e_statusbar_status.update(
				(this.textModified ? ' <span alt="document has been modified">*</span> ': '') + bodyContentLineCount + ' lines');
		},
		
		saveFunction: function(event) {
			if (event) {
				Event.stop(event);
			}
			this.modalOverlay.show();
			this.textarea.value = this.mirror.editor.getCode();
			
			params = $(this.textarea.form).serialize(true);
			params = Object.extend( { ajaxID: 'tx_t3editor::saveCode' }, params);
			
			new Ajax.Request(
				(top && top.TS ? top.TS.PATH_typo3 : PATH_t3e + '../../' ) + 'ajax.php', { 
					parameters: params,
					onComplete: this.saveFunctionComplete.bind(this)
				}
			);
			
		},

		// callback if ajax saving was successful
		saveFunctionComplete: function(ajaxrequest) {
			if (ajaxrequest.status == 200
			  && ajaxrequest.headerJSON.result == true) {
				
				this.textModified = false;
				this.updateLinenum();
			} else {
				alert("An error occured while saving the data.");
			};
			this.modalOverlay.hide();
		},
		
		// find matching bracket
		checkBracketAtCursor: function() {
			var cursor = this.mirror.editor.win.select.markSelection(this.mirror.editor.win);
			
			if (!cursor || !cursor.start) return;
			
			this.cursorObj = cursor.start;
			
			// remove current highlights
			Selector.findChildElements(this.mirror.editor.doc,
				$A(['.highlight-bracket', '.error-bracket'])
			).each(function(item) {
				item.className = item.className.replace(' highlight-bracket', '');
				item.className = item.className.replace(' error-bracket', '');
			});


			if (!cursor.start || !cursor.start.node || !cursor.start.node.parentNode || !cursor.start.node.parentNode.className) {
				return;
			}

			// if cursor is behind an bracket, we search for the matching one

			// we have an opening bracket, search forward for a closing bracket
			if (cursor.start.node.parentNode.className.indexOf('curly-bracket-open') != -1) {
				var maybeMatch = cursor.start.node.parentNode.nextSibling;
				var skip = 0;
				while (maybeMatch) {
					if (maybeMatch.className.indexOf('curly-bracket-open') != -1) {
						skip++;
					}
					if (maybeMatch.className.indexOf('curly-bracket-close') != -1) {
						if (skip > 0) {
							skip--;
						} else {
							maybeMatch.className += ' highlight-bracket';
							cursor.start.node.parentNode.className += ' highlight-bracket';
							break;
						}
					}
					maybeMatch = maybeMatch.nextSibling;
				}
			}

			// we have a closing bracket, search backward for an opening bracket
			if (cursor.start.node.parentNode.className.indexOf('curly-bracket-close') != -1) {
				var maybeMatch = cursor.start.node.parentNode.previousSibling;
				var skip = 0;
				while (maybeMatch) {
					if (maybeMatch.className.indexOf('curly-bracket-close') != -1) {
						skip++;
					}
					if (maybeMatch.className.indexOf('curly-bracket-open') != -1) {
						if (skip > 0) {
							skip--;
						} else {
							maybeMatch.className += ' highlight-bracket';
							cursor.start.node.parentNode.className += ' highlight-bracket';
							break;
						}
					}
					maybeMatch = maybeMatch.previousSibling;
				}
			}

			if (cursor.start.node.parentNode.className.indexOf('curly-bracket-') != -1
			  && maybeMatch == null) {
				cursor.start.node.parentNode.className += ' error-bracket';
			}
		},

		// close an opend bracket
		autoCloseBracket: function(prevNode) {
			if (prevNode && prevNode.className.indexOf('curly-bracket-open') != -1) {
				this.mirror.editor.win.select.insertNewlineAtCursor(this.mirror.editor.win);
				this.mirror.editor.win.select.insertTextAtCursor(this.mirror.editor.win, "}");
			}
		},
		
		// click event. Refresh cursor object.
		click: function() {
			this.refreshCursorObj();
			this.checkBracketAtCursor();
		},
		
		
		refreshCursorObj: function() {
			var cursor = this.mirror.editor.win.select.markSelection(this.mirror.editor.win);
			this.cursorObj = cursor.start;
		},
		
		// toggle between the textarea and t3editor
		toggleView: function(checkboxEnabled) {
			if (checkboxEnabled) {
				this.textarea.value = this.mirror.editor.getCode();
				this.outerdiv.hide();
				this.textarea.show();
				this.saveButtons.each(function(button) {
					Event.stopObserving(button,'click',this.saveFunctionEvent);
				}.bind(this));
				
			} else {
				this.mirror.editor.importCode(this.textarea.value);
				this.textarea.hide();
				this.outerdiv.show();
				this.saveButtons.each(function(button) {
					this.saveFunctionEvent = this.saveFunction.bind(this);
					Event.observe(button,'click',this.saveFunctionEvent);
				}.bind(this));
			}
		},
		
		
		resize: function(width, height) {
			if (this.outerdiv) {
				newheight = (height - 1);
				newwidth = (width + 11);
				if (Prototype.Browser.IE) newwidth = newwidth + 8;
				
				$(this.outerdiv).setStyle({
					height: newheight + 'px',
					width: newwidth + 'px'
				});

				this.linenum_wrap.setStyle({
					height: (height - 22) + 'px'	// less footer height
				});

				numwwidth = this.linenum_wrap.getWidth();
				
				if (Prototype.Browser.IE) numwwidth = numwwidth - 17;
				if (!Prototype.Browser.IE) numwwidth = numwwidth - 11;

				$(this.mirror_wrap.firstChild).setStyle({
					'height': ((height - 22) + 'px'),
					'width': ((width - numwwidth) + 'px')
				});

				$(this.modalOverlay).setStyle(this.outerdiv.getDimensions());

			}

		},
		
		// toggle between normal view and fullscreen mode
		toggleFullscreen: function() {
			if (this.outerdiv.hasClassName('t3e_fullscreen')) {
				// turn fullscreen off

				// unhide the scrollbar of the body
				this.outerdiv.offsetParent.setStyle({
					overflow: ''
				});

				this.outerdiv.removeClassName('t3e_fullscreen');
				h = this.textarea.getDimensions().height;
				w = this.textarea.getDimensions().width;

			} else {
					// turn fullscreen on
				this.outerdiv.addClassName('t3e_fullscreen');
				h = this.outerdiv.offsetParent.getHeight();
				w = this.outerdiv.offsetParent.getWidth();

				// less scrollbar width
				w = w - 13;

				// hide the scrollbar of the body
				this.outerdiv.offsetParent.setStyle({
					overflow: 'hidden'
				});
				this.outerdiv.offsetParent.scrollTop = 0;
			}
			this.resize(w, h);
		}

} // T3editor.prototype


// ------------------------------------------------------------------------


/**
 * toggle between enhanced editor (t3editor) and simple textarea
 */
function t3editor_toggleEditor(checkbox, index) {
	if (!Prototype.Browser.MobileSafari
		&& !Prototype.Browser.WebKit) {
		
		if (index == undefined) {
			$$('textarea.t3editor').each(
				function(textarea, i) {
					t3editor_toggleEditor(checkbox, i);
				}
			);
		} else {
			if (t3e_instances[index] != undefined) {
				var t3e = t3e_instances[index];
				t3e.toggleView(checkbox.checked);
			} else if (!checkbox.checked) {
				var t3e = new T3editor($$('textarea.t3editor')[index], index);
				t3e_instances[index] = t3e;
			}
		}
	}
}

// ------------------------------------------------------------------------


if (!Prototype.Browser.MobileSafari
	&& !Prototype.Browser.WebKit) {
	
	// everything ready: turn textarea's into fancy editors	
	Event.observe(window, 'load',
		function() {
			$$('textarea.t3editor').each(
				function(textarea, i) {
					if ($('t3editor_disableEditor_' + (i + 1) + '_checkbox') 
					&& !$('t3editor_disableEditor_' + (i + 1) + '_checkbox').checked) {
						var t3e = new T3editor(textarea);
						t3e_instances[i] = t3e;
					}
				}
			);
		}
	);
}

