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
/* t3editor.js is based on codemirror.js from the Codemirror editor. 
 * See LICENSE file for further informations
 */



/**
 * Browser checks
 *  inspired by tinyMCE
 */
var ua = navigator.userAgent;
var isMSIE = (navigator.appName == "Microsoft Internet Explorer");
var isMSIE5 = this.isMSIE && (ua.indexOf('MSIE 5') != -1);
var isMSIE5_0 = this.isMSIE && (ua.indexOf('MSIE 5.0') != -1);
var isMSIE7 = this.isMSIE && (ua.indexOf('MSIE 7') != -1);
var isGecko = ua.indexOf('Gecko') != -1; // Will also be true on Safari
var isSafari = ua.indexOf('Safari') != -1;
var isOpera = window['opera'] && opera.buildNumber ? true : false;
var isMac = ua.indexOf('Mac') != -1;
var isNS7 = ua.indexOf('Netscape/7') != -1;
var isNS71 = ua.indexOf('Netscape/7.1') != -1;



// collection of all t3editor instances on the current page
var t3e_instances = {};



/* CodeMirror main module
 *
 * Implements the CodeMirror constructor and prototype, which take care
 * of initializing the editor and managing the highlighting and
 * indentation, and some functions for transforming arbitrary DOM
 * structures into plain sequences of <span> and <br> elements.
 */

// The MirrorOptions object is used to specify a default
// configuration. If you specify such an object before loading this
// file, the values you put into it will override the defaults given
// below.
var t3eOptions = window.t3eOptions || {};

// safeKeys specifies the set of keys that will probably not modify
//   the content of the editor, and thus do not have to be responded to.
//   You usually won't have to change this.
// reindentKeys gives the keys that should cause the editor to
//   re-indent the current line
// reindentAfterKeys works like reindentKeys, but in this case the
//   key's normal effect is first allowed to take place. Use this for
//   keys that might change the indentation level of the current line.
// stylesheet is the filename of the stylesheet that should be used to
//   colour the code in the editor.
// parser should refer to a function that, when given a string stream
//   (see stringstream.js), produces an object that acts as a stream of
//   tokens plus some other functionality. See parsejavascript.js for an
//   example and more information.
// linesPerPass is the maximum amount of lines that the highlighter
//   tries to colour in one shot. Setting this too high will cause the
//   code to 'freeze' the browser for noticeable intervals.
// passDelay gives the amount of milliseconds between colouring passes
setdefault(t3eOptions,
           {safeKeys: setObject("KEY_ARROW_UP", "KEY_ARROW_DOWN", "KEY_ARROW_LEFT", "KEY_ARROW_RIGHT", "KEY_END", "KEY_HOME",
                                "KEY_PAGE_UP", "KEY_PAGE_DOWN", "KEY_SHIFT", "KEY_CTRL", "KEY_ALT", "KEY_SELECT"),
	    reindentKeys: setObject("KEY_TAB"),
	    reindentAfterKeys: setObject("KEY_RIGHT_SQUARE_BRACKET"),
        stylesheet: PATH_t3e+"css/t3editor.css",
        parser: parseTypoScript,
	    linesPerPass: 10,
	    passDelay: 500,
        autoComplete: true,
        acWords:5});
// These default options can be overridden by passing a set of options
// to a specific CodeMirror constructor.

var t3editor = function(){
  // The HTML elements whose content should be suffixed by a newline
  // when converting them to flat text.
  var newlineElements = setObject("P", "DIV", "LI");

  // Helper function for traverseDOM. Flattens an arbitrary DOM node
  // into an array of textnodes and <br> tags.
  function simplifyDOM(root) {
    var doc = root.ownerDocument;
    var result = [];
    var leaving = false;

    function simplifyNode(node) {
      leaving = false;

      if (node.nodeType == 3) {
        node.nodeValue = node.nodeValue.replace(/[\n\r]/g, "").replace(/[\t ]/g, nbsp);
        result.push(node);
      }
      else if (node.nodeName == "BR" && node.childNodes.length == 0) {
        result.push(node);
      }
      else {
        forEach(node.childNodes, simplifyNode);
        if (!leaving && newlineElements.hasOwnProperty(node.nodeName)) {
          leaving = true;
          el = withDocument(doc, SPAN);
		  result.push(withDocument(doc, BR));
        }
      }
    }

    simplifyNode(root);
    return result;
  }

  // Creates a MochiKit-style iterator that goes over a series of DOM
  // nodes. The values it yields are strings, the textual content of
  // the nodes. It makes sure that all nodes up to and including the
  // one whose text is being yielded have been 'normalized' to be just
  // <span> and <br> elements.
  // See the story.html file for some short remarks about the use of
  // continuation-passing style in this iterator.
  function traverseDOM(start){
    function yield(value, c){cc = c; return value;}
    function push(fun, arg, c){return function(){return fun(arg, c);};}
    function stop(){cc = stop; throw StopIteration;};
    var cc = push(scanNode, start, stop);
    var owner = start.ownerDocument;

    // Create a function that can be used to insert nodes after the
    // one given as argument.
    function pointAt(node){
      var parent = node.parentNode;
      var next = node.nextSibling;
      if (next)
        return function(newnode){parent.insertBefore(newnode, next);};
      else
        return function(newnode){parent.appendChild(newnode);};
    }
    var point = null;

    // Insert a normalized node at the current point. If it is a text
    // node, wrap it in a <span>, and give that span a currentText
    // property -- this is used to cache the nodeValue, because
    // directly accessing nodeValue is horribly slow on some browsers.
    // The dirty property is used by the highlighter to determine
    // which parts of the document have to be re-highlighted.
    function insertPart(part){
      var text = "\n";
      if (part.nodeType == 3) {
        text = part.nodeValue;
        part = withDocument(owner, partial(SPAN, {"class": "part"}, part));
        part.currentText = text;
      }
      part.dirty = true;
      point(part);
      return text;
    }

    // Extract the text and newlines from a DOM node, insert them into
    // the document, and yield the textual content. Used to replace
    // non-normalized nodes.
    function writeNode(node, c){
      var toYield = [];
      forEach(simplifyDOM(node), function(part) {
        toYield.push(insertPart(part));
      });
      return yield(toYield.join(""), c);
    }

    // Check whether a node is a normalized <span> element.
    function partNode(node){
      if (node.nodeName == "SPAN" && node.childNodes.length == 1 && node.firstChild.nodeType == 3){
        node.currentText = node.firstChild.nodeValue;
        return true;
      }
      return false;
    }

    // Handle a node. Add its successor to the continuation if there
    // is one, find out whether the node is normalized. If it is,
    // yield its content, otherwise, normalize it (writeNode will take
    // care of yielding).
    function scanNode(node, c){
      if (node.nextSibling)
        c = push(scanNode, node.nextSibling, c);

      if (partNode(node)){
        return yield(node.currentText, c);
      }
      else if (node.nodeName == "BR") {
        return yield("\n", c);
      }
      else {
        point = pointAt(node);
        removeElement(node);
        return writeNode(node, c);
      }
    }

    // MochiKit iterators are objects with a next function that
    // returns the next value or throws StopIteration when there are
    // no more values.
    return {next: function(){return cc();}};
  } // traverseDOM

  var nbspRegexp = new RegExp(nbsp, "g");



  function t3editor(theTextarea, index, options) {
   
   // Use passed options, if any, to override defaults.
    this.options = options || {}
    setdefault(this.options, t3eOptions);
	
    //History Array
    this.history = [];
    //Max History Size
    this.historySize = 100;
    //Init history position
    this.currHistoryPosition = -1;

	// memorize the textarea
	this.textarea = $(theTextarea);
    
	this.documentname = this.textarea.readAttribute('alt');
	
	// count index (helpful if more than one editor is on the page)
	this.index = index;

    // create the wrapping div
    this.outerdiv = $(createDOM("DIV", {
    	"class": 	"t3e_outerdiv",
    	"id":		"t3e_"+this.textarea.getAttribute('id')
    	}));

	// place the div before the textarea
    this.textarea.parentNode.insertBefore(this.outerdiv,$(this.textarea));

	// an overlay that covers the whole editor
	this.modalOverlay = $(createDOM("DIV", {
    	"class": 	"t3e_modalOverlay",
		"id":		"t3e_modalOverlay_wait"
    	}));
    this.modalOverlay.hide();
    this.modalOverlay.setStyle(this.outerdiv.getDimensions());
    this.modalOverlay.setStyle({opacity: 0.5});
    this.outerdiv.appendChild(this.modalOverlay);
	
	this.helpOverlay = $(createDOM("DIV", {
    	"class": 	"t3e_modalOverlay",
    	"id":		"t3e_modalOverlay_help"
    	}));
    this.helpOverlay.innerHTML = "<h2>t3editor</h2>"+
									"<p>put some helpful text here</p><br/><br/>"+
									"<p>Hotkeys:</p>"+
									"<p>"+
									"<strong>CTRL-S</strong> send code to server<br/>"+
									"<strong>CTRL-F11</strong> toggle fullscreen mode<br/>"+
									"<strong>CTRL-SPACE</strong> auto-complete (based on letters at current cursor-position)<br/>"+
									"<strong>CTRL-Z</strong> undo<br/>"+
									"<strong>CTRL-Y</strong> redo<br/>"+
									"</p><br/>"+
									"<p><a href='javascript:void(0)' onclick='t3e_instances["+this.index+"].toggleHelp();'>click here to close this help window</a></p>"+
									"";
    this.helpOverlay.hide();
	this.outerdiv.appendChild(this.helpOverlay);
    
	// wrapping the ilnenumbers
    this.linenum_wrap = $(createDOM("DIV", {
    	"class": 	"t3e_linenum_wrap"
    	}));
	// the "linenumber" list itself
    this.linenum = $(createDOM("DL", {
    	"class": 	"t3e_linenum"
    	}));
    this.linenum_wrap.appendChild(this.linenum);
    this.outerdiv.appendChild(this.linenum_wrap);
    
    //autocomplete box
    this.autoCompleteBox = $(createDOM("DIV",{"class":"t3e_autoCompleteBox"}));
    this.autoCompleteBox.hide();
    this.outerdiv.appendChild(this.autoCompleteBox);
    
	// wrapping the iframe
    this.iframe_wrap = $(createDOM("DIV", {
    	"class": 	"t3e_iframe_wrap"
    	}));

	// the iframe (the actual "editor")
    // display: block occasionally suppresses some Firefox bugs, so we
    // always add it, redundant as it sounds.
    this.iframe = $(createDOM("IFRAME", {
    	"style": "border: 0; display: block;",
    	"class": "t3e_iframe" 
    	}));

    this.iframe_wrap.appendChild(this.iframe);
    this.outerdiv.appendChild(this.iframe_wrap);
    
	// wrapping the footer/statusline
    this.footer_wrap = $(createDOM("DIV", {
    	"class": 	"t3e_footer_wrap"
    	}));
    this.outerdiv.appendChild(this.footer_wrap);
    
	// this.fitem_resize = this.createFooterItem('#', false);
    // this.footer_wrap.appendChild(this.fitem_resize);
	
	// footer item: show help Window
	  // TODO make this more flexible! And get rid of inline css and unsed options!	
    this.fitem_help = this.createFooterItem('Help', true, 'toggleHelp');
    this.footer_wrap.appendChild(this.fitem_help);
	

	// footer item: options menu
	this.fitem_options_overlay = $(createDOM("DIV", {
    	"class": 	"t3e_footer_overlay",
    	"id":		"t3e_footer_overlay_options"
    	}));
	
	  // TODO make this more flexible! And get rid of inline css and unsed options!
    this.fitem_options_overlay.innerHTML = '<ul>'+
				// '<li style="color:grey"><input type="checkbox" disabled="disabled" /> Syntax highlighting</li>'+ 
				'<li><input type="checkbox" onclick="t3e_instances['+this.index+'].fitem_options_overlay.hide();t3e_instances['+this.index+'].toggleAutoComplete();" id="t3e_autocomplete" checked="checked" /><label for="t3e_autocomplete">AutoCompletion</label></li>'+
				'<li><span onclick="t3e_instances['+this.index+'].fitem_options_overlay.hide();t3e_instances['+this.index+'].footeritem_demo_click();">Test snippets</span></li>'+
				'<li><input type="checkbox" onclick="t3e_instances['+this.index+'].fitem_options_overlay.hide();t3e_instances['+this.index+'].toggleFullscreen();" id="t3e_fullscreen" /> <label for="t3e_fullscreen">Fullscreen</label></li>'+
				// '<li style="color:grey"><input type="checkbox" disabled="disabled" /> other fancy stuff</li>'+
				'</ul>';
    this.fitem_options_overlay.hide();
    this.fitem_options = this.createFooterItem('Options', true, this.fitem_options_overlay);
    this.footer_wrap.appendChild(this.fitem_options);
    this.footer_wrap.appendChild(this.fitem_options_overlay);
    
	
	// footer item: status field (total line numbers)
    this.fitem_status = this.createFooterItem('', false);
    this.footer_wrap.appendChild(this.fitem_status);
    
    // footer item: "name" of the document (taken from textarea alt-attribut), and save indicator
    this.fitem_name = this.createFooterItem(this.documentname, false);
    this.footer_wrap.appendChild(this.fitem_name);
	
	
    
	// window and document objects from the iframe
	this.win = this.iframe.contentWindow;
    this.doc = this.win.document;
    
	// make the iframe "editable"
	this.doc.designMode = "on";
    
    this.doc.open();
    this.doc.write("<html><head><link rel=\"stylesheet\" type=\"text/css\" href=\"" + t3eOptions.stylesheet + "\"/></head>" +
                   "<body class=\"editbox\" spellcheck=\"false\"></body></html>");
    this.doc.close();


 	// new Resizable(this.outerdiv,{handle:$(this.fitem_resize)});

    // An array of known dirty nodes, nodes that have been modified
    // since they were last parsed.
    this.dirty = [];

	// dimensions
    this.width   = $(this.textarea).getDimensions().width;
    this.height  = $(this.textarea).getDimensions().height;

	var content = this.textarea.value;

	// hide the textarea
    this.textarea.hide();

    // Some browsers immediately produce a <body> in a new <iframe>,
    // others only do so later and fire an onload event when they do.
    if (this.doc.body) {
      this.init(content);
    } else {
      connect(this.iframe, "onload", bind(function(){disconnectAll(this.iframe, "onload"); this.init(content);}, this));
    }
  }



  t3editor.prototype = {

	textModified: false,	// editor-content has been modified
	saveAjaxEvent: null,	// Event for save code with ajax	
	
    // Called after we are sure that our frame has a body
    init: function (code) {
      this.container = this.doc.body;
      
	  // fetch key press events
	  connect(this.doc, "onkeydown", method(this, "keyDown"));
      connect(this.doc, "onkeyup", method(this, "keyUp"));
	  
	  // fetch scroll events for updateing line numbers
	  connect(this.doc, "onscroll", method(this, "scroll"));
      connect(this.win, "onscroll", method(this, "scroll"));
      
      //fetch mouse click event
      connect(this.doc, "onclick", method(this, "click"));
    
      // get the form object (needed for Ajax saving)
      var form = $(this.textarea.form)
	  this.saveButtons = form.getInputs('submit', 'submit');

      // initialize ajax saving events
      this.saveAjaxEvent = this.saveAjax.bind(this);
      this.saveButtons.each(function(button) {
      	Event.observe(button,'click',this.saveAjaxEvent);
      }.bind(this));
      
	  // resize the editor
      this.resize(this.width, this.height);
	  
      //Import code to editor. If code is empty the method importCode put a BR or SPAN into the codewindow - dependence on browser
      this.importCode(code);
	  
	  // set focus
	  this.win.focus();
        var cursor = new select.Cursor(this.container);
	cursor.focus();
    
    },
	
	// for demonstation only!
	footeritem_demo_click: function() {
		// insertNewlineAtCursor(this.win);
        
        // focus editor and cursor
        this.win.focus();
        var cursor = new select.Cursor(this.container);
        cursor.start = this.cursorObj;
        cursor.focus();

		select.insertTextAtCursor(this.win, "page = PAGE");select.insertNewlineAtCursor(this.win);
		select.insertTextAtCursor(this.win, "page {");	   select.insertNewlineAtCursor(this.win);
		select.insertTextAtCursor(this.win, "  10 = TEXT");select.insertNewlineAtCursor(this.win);
		select.insertTextAtCursor(this.win, "  10.value = Hello World!");		select.insertNewlineAtCursor(this.win);
		select.insertTextAtCursor(this.win, "}");			select.insertNewlineAtCursor(this.win);
		
		this.markCursorDirty();
		this.scheduleHighlight();

		// this.doc.execCommand("undo", false, null);
	},
	
    // toggle between the textarea and t3editor
	toggleView: function(checkboxEnabled)	{
		if (checkboxEnabled) {
			this.textarea.value = this.getCode();
			this.outerdiv.hide();
			this.textarea.show();
			this.saveButtons.each(function(button) {
      			Event.stopObserving(button,'click',this.saveAjaxEvent);
      		}.bind(this));
		} else {
			this.importCode(this.textarea.value);
			this.textarea.hide();
			this.outerdiv.show();
			this.saveButtons.each(function(button) {
      			Event.observe(button,'click',this.saveAjaxEvent);
      		}.bind(this));
		}
	},

	// create an item for the footer line and connect an event
    createFooterItem: function(title, mouseover, clickAction)	{
    	var item = $(createDOM("DIV", {
    		"class": 	"t3e_footer_item"
    	}));
    	item.innerHTML = title;
    	
    	if (mouseover) {
			item.addClassName('t3e_clickable');
    		Event.observe(item, "mouseover", function(e){Event.element(e).addClassName('t3e_footeritem_active');} );
    		Event.observe(item, "mouseout",  function(e){Event.element(e).removeClassName('t3e_footeritem_active');} );
		}
		
		if (typeof clickAction == 'object') { // display an overlay
			Event.observe(item, "click",  function(e){ clickAction.toggle(); } );
		
		} else if (typeof clickAction == 'string' && clickAction != '') {	// execute a method
			connect(item, "onclick", method(this, clickAction+''));
		}
		
    	return item;
    },
    
	// resize the editor
    resize: function(width, height)	{
		if (this.outerdiv) {
			
			// TODO: make it more flexible, get rid of "hardcoded" numbers!
			
			newheight = (height - 1);
			newwidth  = (width + 11);
			if (isMSIE) newwidth = newwidth + 8;

			this.outerdiv.setStyle({
					height: newheight,
            		width: newwidth
                });

			this.linenum_wrap.setStyle({
				height: (height - 22) 	// less footer height (TODO)
			});

			numwwidth = this.linenum_wrap.getWidth();
			if (isMSIE)  numwwidth = numwwidth - 17;
			if (!isMSIE) numwwidth = numwwidth - 11;	

			this.iframe.setStyle({
	            height: (height - 22),	// less footer height (TODO)
				width:  (width - numwwidth)
			});
			
			this.modalOverlay.setStyle(this.outerdiv.getDimensions());
		}
	},

	// toggle between normal view and fullscreen mode
	toggleFullscreen : function()	{
		if (this.outerdiv.hasClassName('t3e_fullscreen')) {
			// turn fullscreen off
			this.outerdiv.removeClassName('t3e_fullscreen');
			h = this.textarea.getDimensions().height;
			w = this.textarea.getDimensions().width;
			
			// hide the scrollbar of the body
			$$('body')[0].setStyle({overflow : ''});
			
		} else {
			// turn fullscreen on
			this.outerdiv.addClassName('t3e_fullscreen');
			h = window.innerHeight ? window.innerHeight : $$('body')[0].getHeight();
			w = window.innerWidth ? window.innerWidth : $$('body')[0].getWidth();
			
			// TODO: proof if this is needed anymore
			w = w - 13;
			
			// hide the scrollbar of the body
			$$('body')[0].setStyle({overflow : 'hidden'});
		}

		this.resize(w,h);
	},
	
	toggleHelp: function()	{
		this.modalOverlay.toggle();
		this.helpOverlay.toggle();
	},

    
    //toggle AutoCompletation beetwen on and off
    toggleAutoComplete : function() {
        this.options.autoComplete = (this.options.autoComplete)?false:true;
    },
    
    //autocomplete box
    autoComplete : function() {
        this.clicked = false;
        //get lastword into this.lastWord
        this.getLastWord();
        // init vars for up/down moving in word list
        this.ac_up = 0;
        this.ac_down = this.options.acWords-1;
        
        //refresh cursorObj
        var cursor = new select.Cursor(this.container);
        this.cursorObj = cursor.start;
        //init currWord, used in word list. Contain selected word
        this.currWord = -1;
        
        // If lastword is not empty and not space - continue
        if (this.lastWord!='&nbsp;' && this.lastWord){
            // get list of words
            this.words = this.getCompleteWordsByTrigger(this.lastWord.toLowerCase());
            // if words are found - show box
            if (this.words.length > 0){
                // make UL list of completation words
                var html = '<ul>';
                for (i=0;i<this.words.length;i++){
                    html+= '<li style="height:16px;vertical-align:middle;" id="ac_word_'+i+'" onclick="t3e_instances['+this.index+'].clicked=true;t3e_instances['+this.index+'].insertCurrWordAtCursor();" onmouseover="t3e_instances['+this.index+'].highlightCurrWord('+i+');"><span class="word_'+this.words[i].type+'">'+this.words[i].word+'</span></li>';
                }
                html+='</ul>';
                //put HTML and show box
                this.autoCompleteBox.innerHTML = html;
                this.autoCompleteBox.show();
                this.autoCompleteBox.scrollTop = 0;
                //  init styles
                if (this.words.length > this.options.acWords){
                    this.autoCompleteBox.style.overflowY = 'scroll';
                    if (isGecko){
                        this.autoCompleteBox.style.height = (this.options.acWords*($("ac_word_0").offsetHeight))+'px';
                    }else{
                        this.autoCompleteBox.style.height = (this.options.acWords*($("ac_word_0").offsetHeight))+4+'px';
                        this.autoCompleteBox.style.width = this.autoCompleteBox.offsetWidth+20+'px';
                    }
                    
                }else{
                    this.autoCompleteBox.style.overflowY = 'auto';
                    this.autoCompleteBox.style.height = 'auto';
                    this.autoCompleteBox.style.width = 'auto'; // '0px';
                }
                
                // positioned box to word
                this.autoCompleteBox.style.left = Position.cumulativeOffset(this.iframe)[0]-Position.cumulativeOffset(this.outerdiv)[0]+Position.cumulativeOffset(cursor.start)[0]+cursor.start.offsetWidth;
                this.autoCompleteBox.style.top = Position.cumulativeOffset(this.iframe)[1]-Position.cumulativeOffset(this.outerdiv)[1]+Position.cumulativeOffset(cursor.start)[1]+cursor.start.offsetHeight-this.container.scrollTop;
                // set flag to 1 - needed for continue typing word. 
                this.ac = 1;    
                //highlight first word in list
                this.highlightCurrWord(0);
            } 
        }
    },
    // Get word where cursor focused
    getLastWord : function (){
        var cursor = new select.Cursor(this.container);
        if (cursor.start){
            this.lastTrigger = this.lastWord;
            this.lastWord = (cursor.start.innerHTML)?cursor.start.innerHTML:'';
        }
    },
    
    // highlighitng word in autocomplete box by id
    highlightCurrWord : function (id) {
        if (this.currWord!=-1){
            $('ac_word_'+this.currWord).className = '';
        }
        $('ac_word_'+id).className = 'active';
        this.currWord = id;
    },
    
    //insert selected word into text from autocompletebox
    insertCurrWordAtCursor: function (){
        var trigger = this.lastWord;
        var insertText = this.words[this.currWord].word;
        //if MSIE and select word my mouse click
        var cursor = new select.Cursor(this.container);
        if (isMSIE && this.clicked){
            if (trigger.length > 0){
                this.cursorObj.innerHTML = insertText;
                this.win.focus();
                cursor.start = this.cursorObj;
                cursor.focus();
                this.highlightAtCursor(cursor);
            }
        }
        // if Safari browser
        else if (isSafari){
             if (trigger.length > 0){
                this.cursorObj.innerHTML = insertText;
                if (this.clicked){
                   this.win.focus();
                }
                cursor.start = this.cursorObj;
                cursor.focus();
                this.highlightAtCursor(cursor);            
            }
        }
        //for all others times
        else{
            if (trigger.length > 0){
                cursor.start.innerHTML = '';
            }
            select.insertTextAtCursor (this.win,insertText);
            if (this.clicked){
                this.win.focus();    
            }
            cursor.focus();
            this.highlightAtCursor(cursor);
        }
        // set ac flag to 0 - autocomplete is finish
        this.ac = 0;
        //hide box
        this.autoCompleteBox.hide();
    },
    //return words for autocomplete by trigger (part of word)
    getCompleteWordsByTrigger : function (trigger){
        result = [];
        
        for(word in typoscriptWords){
            lword = word.toLowerCase();
            if (lword.indexOf(trigger) === 0){
                var wordObj = new Object();
                wordObj.word = word;
                wordObj.type = typoscriptWords[word];
                result.push(wordObj);
            }
        }        
        return result;
    },
    
    //move cursor in autcomplete box up
    autoCompleteBoxMoveUpCursor : function () {
        // if previous position was first - then move cursor to last word if not than position --
        if (this.currWord == 0){
            var id = this.words.length-1;
        }else{
            var id = this.currWord-1;
        }
        // hightlight new cursor position
        this.highlightCurrWord (id);
        //update id of first and last showing words and scroll box
        if (this.currWord < this.ac_up || this.currWord == (this.words.length-1)){
            this.ac_up = this.currWord;
            this.ac_down = this.currWord+(this.options.acWords-1);
            if (this.ac_up === this.words.length-1){
                this.ac_down = this.words.length-1;
                this.ac_up = this.ac_down-(this.options.acWords-1);
            }
            this.autoCompleteBox.scrollTop = this.ac_up*16;
        }
    },
    //move cursor in autocomplete box down
    autoCompleteBoxMoveDownCursor : function () {
        // if previous position was last word in list - then move cursor to first word if not than  position ++
        if (this.currWord == this.words.length-1){
            var id = 0;
        }else{
            var id = this.currWord+1;
        }
        // hightlight new cursor position
        this.highlightCurrWord (id);
         //update id of first and last showing words and scroll box
        if (this.currWord > this.ac_down || this.currWord==0){
            this.ac_down = this.currWord;
            this.ac_up = this.currWord-(this.options.acWords-1);
            if (this.ac_down == 0){
                this.ac_up = 0;
                this.ac_down = this.options.acWords-1;
            }
            this.autoCompleteBox.scrollTop = this.ac_up*16;
        }
    },
    // put code to history
    pushToHistory:function () {
        var obj = {};
        //create SPAN mark of cursor
        var cursorEl  = this.win.document.createElement("SPAN");
        cursorEl.id = "cursor";       
        this.refreshCursorObj();
        // added mark to code
        if (this.initable){
            if (!this.cursorObj){
                if (this.container.firstChild){
                   this.win.document.body.insertBefore(cursorEl,this.container.firstChild);
                }
            }else{
                this.win.document.body.insertBefore(cursorEl,this.cursorObj);
            }
        }else{
            this.win.document.body.appendChild(cursorEl);
        }
        //save code and text to history object
        obj.code = this.container.innerHTML;
        obj.text = this.getCode();
        // check if was undo/redo than refresh history array
        if (this.currHistoryPosition+1 < this.history.length){
            this.history = this.history.slice (0,this.currHistoryPosition+1);
            this.currHistoryPosition = this.history.length-1;
        }
        //push history oject to history array
        this.history.push(obj);
        this.currHistoryPosition++;
        //check limit of history size
        if (this.currHistoryPosition > this.historySize){
            this.history = this.history.slice ((this.history.length-this.historySize-1));
            this.currHistoryPosition = this.history.length-1;
        }
    },
    
    //undo function
    undo: function () {
        //check if position in history not first
        if (this.currHistoryPosition > 0){
            this.currHistoryPosition--;
            var obj = this.history[this.currHistoryPosition];
            if (!obj){return ;}
            //insert code from history
            this.container.innerHTML = obj.code;
            //focus cursor to next el of marked span
            var cursor = new select.Cursor(this.container);
            var cursorEl = this.win.document.getElementById('cursor');
            if (cursorEl){
                cursor.start = cursorEl.nextSibling;
                cursor.focus();
            }
        }
        
    },
    
    //redo function
    redo: function () {
        //check if position in history not last
        if (this.currHistoryPosition < this.history.length){
            this.currHistoryPosition++;
            var obj = this.history[this.currHistoryPosition];
            if (!obj){return ;}
            //insert code from history
            this.container.innerHTML = obj.code;
            //focus cursor to next el of marked span
            var cursor = new select.Cursor(this.container);
            var cursorEl = this.win.document.getElementById('cursor');
            if (cursorEl){
                cursor.start = cursorEl.nextSibling;
                cursor.focus();
            }
        }
        
    },
    // check changes in history
    checkHistoryChanges:function () {
        
        var code = this.container.innerHTML;
        if (this.undoable == 1){
            this.undoable = 0;
            return ;
        }
        if (this.redoable == 1){
            this.redoable = 0;
            return ;
        }
        if (!this.history[this.currHistoryPosition]){
            this.pushToHistory();
            return ;
        }
        if (this.getCode(code) != this.history[this.currHistoryPosition].text){
            this.pushToHistory();
        }
        
    },

	// update the line numbers
    updateLinenum: function()      {
		var theMatch = this.container.innerHTML.match(/<br/gi);
        if (!theMatch) {
            theMatch = '1';
        } else if (isMSIE) { 
			theMatch.push('1');
		}

        var bodyContentLineCount = theMatch.length;
        disLineCount = this.linenum.childNodes.length;
        while (disLineCount != bodyContentLineCount)    {
	        if (disLineCount > bodyContentLineCount)        {
	            this.linenum.removeChild(this.linenum.lastChild);
	            disLineCount--;
	        } else if (disLineCount < bodyContentLineCount) {
	            ln = $(document.createElement('dt'));
	            ln.update(disLineCount+1+'.');
	            ln.addClassName(disLineCount%2==1?'even':'odd');
	            ln.setAttribute('id','ln'+(disLineCount+1));
	            this.linenum.appendChild(ln);
	            disLineCount++;
	        }
        }

		this.fitem_status.update(bodyContentLineCount + ' lines');
		this.fitem_name.update(this.documentname + (this.textModified?' <span alt="document has been modified">*</span>':''));
    },

	// scroll the line numbers
    scroll: function()  {
		var scrOfX = 0, scrOfY = 0;
  		if( typeof( this.win.pageYOffset ) == 'number' ) {
		    // Netscape compliant
		    scrOfY = this.win.pageYOffset;
		    scrOfX = this.win.pageXOffset;
		} else if( this.doc.body && ( this.doc.body.scrollLeft || this.doc.body.scrollTop ) ) {
		    // DOM compliant
		    scrOfY = this.doc.body.scrollTop;
		    scrOfX = this.doc.body.scrollLeft;
		} else if( this.doc.documentElement && ( this.doc.documentElement.scrollLeft || this.doc.documentElement.scrollTop ) ) {
		    // IE6 standards compliant mode
		    scrOfY = this.doc.documentElement.scrollTop;
		    scrOfX = this.doc.documentElement.scrollLeft;
		}
        this.linenum_wrap.scrollTop = scrOfY;
    },
    
    // click event. Refresh cursor object. if autocomplete is not finish - finish it and hide box
    click: function()  {
        if (this.ac === 1){this.ac = 0;this.autoCompleteBox.hide();}
        this.refreshCursorObj();
    },

    // Split a chunk of code into lines, put them in the frame, and
    // schedule them to be coloured.
    importCode: function(code) {
      replaceChildNodes(this.container);

      if (code == "\n" || code == "\r\n" || code == "\r"){code = '';}
      var lines = code.replace(/[ \t]/g, nbsp).replace(/\r\n?/g, "\n").split("\n");

      for (var i = 0; i != lines.length; i++) {
        if (i > 0)
          this.container.appendChild(withDocument(this.doc, BR));
        var line = lines[i];
        if (line.length > 0)
          this.container.appendChild(this.doc.createTextNode(line));
      }
      if (code == "") {
        var empty = this.win.document.createElement('BR');//(isGecko && !isSafari)?this.win.document.createElement('BR'):this.win.document.createElement('SPAN');
      	this.container.appendChild(empty);
      }

      if (this.container.firstChild){
		this.addDirtyNode(this.container.firstChild);
        this.scheduleHighlight(); // this.highlightDirty();
      }
	  this.updateLinenum();
    },

    // Extract the code from the editor.
    getCode: function() {
      if (!this.container.firstChild)
        return "";

      var accum = [];
      forEach(traverseDOM(this.container.firstChild), method(accum, "push"));
      return accum.join("").replace(nbspRegexp, " ");
    },

    // Intercept enter and any keys that are specified to re-indent
    // the current line.
    keyDown: function(event) {
      var name = event.key().string;

      if (name == "KEY_ENTER") {
            event.stop();
			if (this.ac === 1)	{
               this.insertCurrWordAtCursor();
            } else if (!isMac) {
                select.insertNewlineAtCursor(this.win);
        	    this.indentAtCursor();
            }
            this.updateLinenum();
			
   	  } else if (name == "KEY_S" && event.modifier().ctrl) { 	// save via ajax request
      		this.saveAjax();
      		event.stop();
      		return;
			
      } else if (name == "KEY_F11" && event.modifier().ctrl) {	// toogle fullscreen mode
      		this.toggleFullscreen();
      		event.stop();
      		return;
			
      } else if (name == "KEY_SPACEBAR" && event.modifier().ctrl && this.options.autoComplete){ // call autocomplete if autocomplete turn on
        this.autoComplete();
        event.stop();
      } else if (name=="KEY_ARROW_UP" && this.ac == 1){ // move up cursor in autocomplete box
        event.stop();
        window.setTimeout('t3e_instances['+this.index+'].autoCompleteBoxMoveUpCursor()',100);
      } else if (name=="KEY_ARROW_DOWN" && this.ac == 1){ // move down cursor in autocomplete box
        event.stop();
        window.setTimeout('t3e_instances['+this.index+'].autoCompleteBoxMoveDownCursor();',100);
      } else if (name=="KEY_ESCAPE" && this.ac === 1){ // if autocomplete box is showing. by ESC press it's hide and autocomplete is finish
            this.ac = 0;
            this.autoCompleteBox.hide();
      } else if (name=='KEY_Z' && event.modifier().ctrl){
         this.undoable = 1;
         this.undo();
         event.stop();
      } else if (name=='KEY_Y' && event.modifier().ctrl){
         this.redoable = 1;
         this.redo();
         event.stop();
      }
    },

    // Re-indent when a key in options.reindentAfterKeys is released,
    // mark the node at the cursor dirty when a non-safe key is
    // released.
    keyUp: function(event) {
      var name = event.key().string;
      if (this.options.reindentAfterKeys.hasOwnProperty(name))
        this.indentAtCursor();
      else if (!this.options.safeKeys.hasOwnProperty(name)) {
        this.markCursorDirty();
        this.checkTextModified();
	  }
	  
       if (this.ac===1){ // if autocomplete now is not finish, but started and continue typing - refresh autocomplete box
            this.getLastWord();
            if (this.lastTrigger!=this.lastWord){
                this.autoCompleteBox.hide();
                this.ac = 0;
                this.autoComplete();     
            }
       } 
      
      if (name == "KEY_ENTER" || name == "KEY_BACKSPACE" || name == "KEY_DELETE" ){
          this.updateLinenum();
      }
       
      this.refreshCursorObj();
    },
	
    refreshCursorObj: function () {
        var cursor = new select.Cursor(this.container);
        this.cursorObj = cursor.start;
    },


	// check if code in editor has been modified since last saving
    checkTextModified: function() {
	  if (!this.textModified) {
      	this.textModified = true;
		this.updateLinenum();
      }
    },


	// send ajax request to save the code
	saveAjax: function(event) {
		if (event) {
			// event = new Event(event);
			Event.stop(event);
		}
		
		this.modalOverlay.show();
		this.textarea.value = this.getCode();
		
		/* erst ab prototype 1.5.1
		Form.request($(this.textarea.form),{
  			onComplete: function(){ alert('Form data saved!'); }
		});
		*/
		
		formdata = "submitAjax=1&" + Form.serialize($(this.textarea.form));

		var myAjax = new Ajax.Request(
			$(this.textarea.form).action, 
			{ method: "post",
			  parameters: formdata, 
			  onComplete: this.saveAjaxOnSuccess.bind(this)
			});
	},

	// callback if ajax saving was successful
	saveAjaxOnSuccess: function(ajaxrequest) {
		if (ajaxrequest.status == 200
			&& ajaxrequest.responseText == "OK") {
			this.textModified = false;
			this.updateLinenum();
		} else {
			// TODO: handle if session is timed out
			alert("An error occured while saving the data.");
		};
		this.modalOverlay.hide();
		
	},


    // Ensure that the start of the line the cursor is on is parsed
    // and coloured properly, so that the correct indentation can be
    // computed.
    highlightAtCursor: function(cursor) {
      if (cursor.valid) {
        var node = cursor.start || this.container.firstChild;
        if (node) {
	  // If the node is a text node, it will be recognized as
	  // dirty anyway, and some browsers do not allow us to add
	  // properties to text nodes.
          if (node.nodeType != 3)
            node.dirty = true;
	  // Store selection, highlight, restore selection.
          var sel = select.markSelection(this.win);
          this.highlight(node);
          select.selectMarked(sel);
	  // Cursor information is probably no longer valid after
	  // highlighting.
          cursor = new select.Cursor(this.container);
        }
      }
      return cursor;
    },

    // Adjust the amount of whitespace at the start of the line that
    // the cursor is on so that it is indented properly.
    indentAtCursor: function() {
      var cursor = new select.Cursor(this.container);
      // The line has to have up-to-date lexical information, so we
      // highlight it first.
      cursor = this.highlightAtCursor(cursor);
      // If we couldn't determine the place of the cursor, there's
      // nothing to indent.
      if (!cursor.valid)
        return;

      // start is the <br> before the current line, or null if this is
      // the first line.
      var start = cursor.startOfLine();
      // whiteSpace is the whitespace span at the start of the line,
      // or null if there is no such node.
      var whiteSpace = start ? start.nextSibling : this.container.lastChild;
      if (whiteSpace && !hasClass(whiteSpace, "whitespace"))
        whiteSpace = null;

      // Sometimes the first character on a line can influence the
      // correct indentation, so we retrieve it.
      var firstText = whiteSpace ? whiteSpace.nextSibling : start ? start.nextSibling : this.container.firstChild;
      var firstChar = (start && firstText && firstText.currentText) ? firstText.currentText.charAt(0) : "";

      // Ask the lexical context for the correct indentation, and
      // compute how much this differs from the current indentation.
     
	  var indent = start ? start.lexicalContext.indentation(firstChar) : 0;
      var indentDiff = indent - (whiteSpace ? whiteSpace.currentText.length : 0);

      // If there is too much, this is just a matter of shrinking a span.
      if (indentDiff < 0) {
        whiteSpace.currentText = repeatString(nbsp, indent);
        whiteSpace.firstChild.nodeValue = whiteSpace.currentText;
      }
      // Not enough...
      else if (indentDiff > 0) {
	// If there is whitespace, we grow it.
        if (whiteSpace) {
          whiteSpace.currentText += repeatString(nbsp, indentDiff);
          whiteSpace.firstChild.nodeValue = whiteSpace.currentText;
        }
	// Otherwise, we have to add a new whitespace node.
        else {
          whiteSpace = withDocument(this.doc, function(){return SPAN({"class": "part whitespace"}, repeatString(nbsp, indentDiff))});
          if (start)
            insertAfter(whiteSpace, start);
          else
            insertAtStart(whiteSpace, this.containter);
        }
	// If the cursor is at the start of the line, move it to after
	// the whitespace.
        if (cursor.start == start)
          cursor.start = whiteSpace;
      }
	  
	  if (cursor.start == whiteSpace)
        cursor.focus();
    },

    // highlight is a huge function defined below.
    highlight: highlight,

    // Find the node that the cursor is in, mark it as dirty, and make
    // sure a highlight pass is scheduled.
    markCursorDirty: function() {
      var cursor = new select.Cursor(this.container);
      if (cursor.valid) {
        var node = cursor.start || this.container.firstChild;
        if (node) {
          this.addDirtyNode(node);
		  this.scheduleHighlight();
        }
      }
    },

    // Add a node to the set of dirty nodes, if it isn't already in
    // there.
    addDirtyNode: function(node) {
      if (this.dirty.indexOf(node) == -1) {
        if (node.nodeType != 3)
          node.dirty = true;
        this.dirty.push(node);
      }
    },

    // Cause a highlight pass to happen in options.passDelay
    // milliseconds. Clear the existing timeout, if one exists. This
    // way, the passes do not happen while the user is typing, and
    // should as unobtrusive as possible.
    scheduleHighlight: function() {
	  if (this.highlightTimeout) clearTimeout(this.highlightTimeout);
  	  this.highlightTimeout = setTimeout(bind(this.highlightDirty, this), this.options.passDelay);
    },

    // Fetch one dirty node, and remove it from the dirty set.
    getDirtyNode: function() {
      while (this.dirty.length > 0) {
        var found = this.dirty.pop();
	// If the node has been coloured in the meantime, or is no
	// longer in the document, it should not be returned.
        if ((found.dirty || found.nodeType == 3) && found.parentNode)
          return found;
      }
      return null;
    },

    // Pick dirty nodes, and highlight them, until
    // options.linesPerPass lines have been highlighted. The highlight
    // method will continue to next lines as long as it finds dirty
    // nodes. It returns an object indicating the amount of lines
    // left, and information about the place where it stopped. If
    // there are dirty nodes left after this function has spent all
    // its lines, it shedules another highlight to finish the job.
    highlightDirty: function() {
	  var lines = this.options.linesPerPass;
      var sel = select.markSelection(this.win);
      var start;
      while (lines > 0 && (start = this.getDirtyNode())){
        var result = this.highlight(start, lines);
        if (result) {
          lines = result.left;
          if (result.node && result.dirty)
            this.addDirtyNode(result.node);
        }
      }
      select.selectMarked(sel);
	  if (start)
        this.scheduleHighlight();
    }
  }

  // The function that does the actual highlighting/colouring (with
  // help from the parser and the DOM normalizer). Its interface is
  // rather overcomplicated, because it is used in different
  // situations: ensuring that a certain line is highlighted, or
  // highlighting up to X lines starting from a certain point. The
  // 'from' argument gives the node at which it should start. If this
  // is null, it will start at the beginning of the frame. When a
  // number of lines is given with the 'lines' argument, it will colour
  // no more than that amount. If at any time it comes across a
  // 'clean' line (no dirty nodes), it will stop.
  function highlight(from, lines){
    var container = this.container;
    var document = this.doc;
//	this.updateLinenum();

    if (!container.firstChild)
      return;
    // Backtrack to the first node before from that has a partial
    // parse stored.
    while (from && !from.parserFromHere)
      from = from.previousSibling;
    // If we are at the end of the document, do nothing.
    if (from && !from.nextSibling)
      return;

    // Check whether a part (<span> node) and the corresponding token
    // match.
    function correctPart(token, part){
      return !part.reduced && part.currentText == token.value && hasClass(part, token.style);
    }
    // Shorten the text associated with a part by chopping off
    // characters from the front. Note that only the currentText
    // property gets changed. For efficiency reasons, we leave the
    // nodeValue alone -- we set the reduced flag to indicate that
    // this part must be replaced.
    function shortenPart(part, minus){
      part.currentText = part.currentText.substring(minus);
      part.reduced = true;
    }
    // Create a part corresponding to a given token.
    function tokenPart(token){
      var part = withDocument(document, partial(SPAN, {"class": "part " + token.style}, token.value));
      part.currentText = token.value;
      return part;
    }

    // Get the token stream. If from is null, we start with a new
    // parser from the start of the frame, otherwise a partial parse
    // is resumed.
    var parsed = from ? from.parserFromHere(multiStringStream(traverseDOM(from.nextSibling)))
      : this.options.parser(multiStringStream(traverseDOM(container.firstChild)));

    // parts is a wrapper that makes it possible to 'delay' going to
    // the next DOM node until we are completely done with the one
    // before it. This is necessary because we are constantly poking
    // around in the DOM tree, and if the next node is fetched to
    // early it might get replaced before it is used.
    var parts = {
      current: null,
      forward: false,
      // Get the current part.
      get: function(){
        if (!this.current)
          this.current = from ? from.nextSibling : container.firstChild;
        else if (this.forward)
          this.current = this.current.nextSibling;
        this.forward = false;
        return this.current;
      },
      // Advance to the next part (do not fetch it yet).
      next: function(){
        if (this.forward)
          this.get();
        this.forward = true;
      },
      // Remove the current part from the DOM tree, and move to the
      // next.
      remove: function(){
        this.current = this.get().previousSibling;
        container.removeChild(this.current ? this.current.nextSibling : container.firstChild);
        this.forward = true;
      },
      // Advance to the next part that is not empty, discarding empty
      // parts.
      nextNonEmpty: function(){
        var part = this.get();
        while (part.nodeName == "SPAN" && part.currentText == ""){
          var old = part;
          this.remove();
          part = this.get();
	  // Adjust selection information, if any. See select.js for
	  // details.
          select.replaceSelection(old.firstChild, part.firstChild || part, 0, 0);
        }
        return part;
      }
    };

    var lineDirty = false;

    // This forEach loops over the tokens from the parsed stream, and
    // at the same time uses the parts object to proceed through the
    // corresponding DOM nodes.
    forEach(parsed, function(token){
      var part = parts.nextNonEmpty();
      if (token.value == "\n"){
	// The idea of the two streams actually staying synchronized
	// is such a long shot that we explicitly check.
        if (part.nodeName != "BR")
          throw "Parser out of sync. Expected BR.";
        if (part.dirty || !part.lexicalContext)
          lineDirty = true;
	// Every <br> gets a copy of the parser state and a lexical
	// context assigned to it. The first is used to be able to
	// later resume parsing from this point, the second is used
	// for indentation.
        part.parserFromHere = parsed.copy();
        part.lexicalContext = token.lexicalContext;
        part.dirty = false;
	// A clean line means we are done. Throwing a StopIteration is
	// the way to break out of a MochiKit forEach loop.
        if ((lines !== undefined && --lines <= 0) || !lineDirty)
          throw StopIteration;
        lineDirty = false;
        parts.next();
      }
      else {
        if (part.nodeName != "SPAN")
          throw "Parser out of sync. Expected SPAN.";
        if (part.dirty)
          lineDirty = true;

	// If the part matches the token, we can leave it alone.
        if (correctPart(token, part)){
          part.dirty = false;
          parts.next();
        }
	// Otherwise, we have to fix it.
        else {
          lineDirty = true;
	  // Insert the correct part.
          var newPart = tokenPart(token);
          container.insertBefore(newPart, part);
          var tokensize = token.value.length;
          var offset = 0;
	  // Eat up parts until the text for this token has been
	  // removed, adjusting the stored selection info (see
	  // select.js) in the process.
          while (tokensize > 0) {
            part = parts.get();
            var partsize = part.currentText.length;
            select.replaceSelection(part.firstChild, newPart.firstChild, tokensize, offset);
            if (partsize > tokensize){
              shortenPart(part, tokensize);
              tokensize = 0;
            }
            else {
              tokensize -= partsize;
              offset += partsize;
              parts.remove();
            }
          }
        }
      }
    });
    this.refreshCursorObj();
    this.initable = 1;
    window.setTimeout ('t3e_instances['+this.index+'].checkHistoryChanges();',100);
    // The function returns some status information that is used by
    // hightlightDirty to determine whether and where it has to
    // continue.
    return {left: lines,
            node: parts.get(),
            dirty: lineDirty};
  }

  return t3editor;
}();


// ------------------------------------------------------------------------



function t3editor_toggleEditor(checkbox,index) {
	if (index == undefined) {
		$$('textarea.t3editor').each(
			function(textarea,i) {
				t3editor_toggleEditor(checkbox,i);
			}
		);	
	} else {
		if (t3e_instances[index] != undefined) {
			var t3e = t3e_instances[index];
			t3e.toggleView(checkbox.checked);
		} else if (!checkbox.checked) {
			var t3e = new t3editor($$('textarea.t3editor')[index], index);
			t3e_instances[index] = t3e;
		}
	}	
}

// ------------------------------------------------------------------------


/**
 * everything ready: turn textareas into fancy editors
 */
Event.observe(window,'load',function() {
	$$('textarea.t3editor').each(
		function(textarea,i) {
			if ($('t3editor_disableEditor_'+(i+1)+'_checkbox') && !$('t3editor_disableEditor_'+(i+1)+'_checkbox').checked) {
				var t3e = new t3editor(textarea,i);
				t3e_instances[i] = t3e;
			}
		}
	);
});
