/* Functionality for finding, storing, and re-storing selections
 *
 * This does not provide a generic API, just the minimal functionality
 * required by the CodeMirror system.
 */

// Namespace object.
var select = {};

(function() {
  var ie_selection = document.selection && document.selection.createRangeCollection;

  // Find the 'top-level' (defined as 'a direct child of the node
  // passed as the top argument') node that the given node is
  // contained in. Return null if the given node is not inside the top
  // node.
  function topLevelNodeAt(node, top) {
    while (node && node.parentNode != top)
      node = node.parentNode;
    return node;
  }

  // Find the top-level node that contains the node before this one.
  function topLevelNodeBefore(node, top) {
    if (!node){
        return null;
    }
    while (!node.previousSibling && node.parentNode != top){
        node = node.parentNode;
    }
      
    return topLevelNodeAt(node.previousSibling, top);
  }

  // Most functions are defined in two ways, one for the IE selection
  // model, one for the W3C one.
  if (ie_selection) {
    // Store the current selection in such a way that it can be
    // restored after we manipulated the DOM tree. For IE, we store
    // pixel coordinates.
    select.markSelection = function (win) {
      var selection = win.document.selection;
      var start = selection.createRange(), end = start.duplicate();
      start.collapse(true);
      end.collapse(false);

      var body = win.document.body;
      // And we better hope no fool gave this window a padding or a
      // margin, or all these computations will be in vain.
      return {start: {x: start.boundingLeft + body.scrollLeft - 1,
                      y: start.boundingTop + body.scrollTop},
              end: {x: end.boundingLeft + body.scrollLeft - 1,
                    y: end.boundingTop + body.scrollTop},
              window: win};
    };

    // Restore a stored selection.
    select.selectMarked = function(sel) {
      if (!sel)
	return;
      var range1 = sel.window.document.body.createTextRange(), range2 = range1.duplicate();
      range1.moveToPoint(sel.start.x, sel.start.y);
      range2.moveToPoint(sel.end.x, sel.end.y);
      range1.setEndPoint("EndToStart", range2);
      range1.select();
    };

    // Not needed in IE model -- see W3C model.
    select.replaceSelection = function(){};

    // A Cursor object represents a top-level node that the cursor is
    // currently in or after. It is not possible to reliably get more
    // detailed information, but just this node is enough for most
    // purposes.
    select.Cursor = function(container) {
        this.container = container;
        this.doc = container.ownerDocument;
        var selection = this.doc.selection;
        this.valid = !!selection;
        if (this.valid) {
            var range = selection.createRange();
            range.collapse(false);
            var around = range.parentElement();
            if (around && isAncestor(container, around)) {
                  this.start = topLevelNodeAt(around, container);
            }
            else {
                range.pasteHTML("<span id='// temp //'></span>");
                var temp = this.doc.getElementById("// temp //");
                this.start = topLevelNodeBefore(temp, container);
                if (temp)
                    removeElement(temp);
            }
        }
    };

    // Place the cursor after this.start. This is only useful when
    // manually moving the cursor instead of restoring it to its old
    // position.
    select.Cursor.prototype.focus = function () {
      var range = this.doc.body.createTextRange();
      range.moveToElementText(this.start || this.container);
      range.collapse(!this.start);
      range.select();
    };


    // Used to normalize the effect of the enter key, since browsers
    // do widely different things when pressing enter in designMode.
    select.insertNewlineAtCursor = function(window) {
      var selection = window.document.selection;
      if (selection) {
	var range = selection.createRange();
	range.pasteHTML("<br/>");
	range.collapse(false);
	range.select();
      }
    };
	
	// Insert a custom string at current cursor position (added for t3editor)
    select.insertTextAtCursor = function(window,text) {
      var selection = window.document.selection;
      if (selection) {
        var range = selection.createRange();
        range.pasteHTML(text);
        range.collapse(false);
      	range.select();
      }
  	};
	  
  }
  // W3C model
  else {
    // Well, Opera isn't even supported at the moment, but it almost
    // is, and this is used to fix an issue with getting the scroll
    // position.
    var opera_scroll = !window.scrollX && !window.scrollY;

    // Store start and end nodes, and offsets within these, and refer
    // back to the selection object from those nodes, so that this
    // object can be updated when the nodes are replaced before the
    // selection is restored.
    select.markSelection = function (win) {
      var selection = win.getSelection();
      if (!selection || selection.rangeCount == 0)
	return null;
      var range = selection.getRangeAt(0);

      var result = {start: {node: range.startContainer, offset: range.startOffset},
                    end: {node: range.endContainer, offset: range.endOffset},
                    window: win,
                    scrollX: opera_scroll && win.document.body.scrollLeft,
                    scrollY: opera_scroll && win.document.body.scrollTop};

      // We want the nodes right at the cursor, not one of their
      // ancestors with a suitable offset. This goes down the DOM tree
      // until a 'leaf' is reached (or is it *up* the DOM tree?).
      function normalize(point){
	while (point.node.nodeType != 3 && point.node.nodeName != "BR") {
          var newNode = point.node.childNodes[point.offset] || point.node.nextSibling;
          point.offset = 0;
          while (!newNode && point.node.parentNode) {
            point.node = point.node.parentNode;
            newNode = point.node.nextSibling;
          }
          point.node = newNode;
          if (!newNode)
            break;
	}
      }

      normalize(result.start);
      normalize(result.end);
      // Make the links back to the selection object (see
      // replaceSelection).
      if (result.start.node)
	result.start.node.selectStart = result.start;
      if (result.end.node)
	result.end.node.selectEnd = result.end;

      return result;
    };

    // Helper for selecting a range object.
    function selectRange(range, window) {
      var selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
    };

    select.selectMarked = function (sel) {
      if (!sel)
	return;
      var win = sel.window;
      var range = win.document.createRange();

      function setPoint(point, which) {
	if (point.node) {
	  // Remove the link back to the selection.
          delete point.node["select" + which];
	  // Some magic to generalize the setting of the start and end
	  // of a range.
          if (point.offset == 0)
            range["set" + which + "Before"](point.node);
          else
            range["set" + which](point.node, point.offset);
	}
	else {
          range.setStartAfter(win.document.body.lastChild || win.document.body);
	}
      }

      // Have to restore the scroll position of the frame in Opera.
      if (opera_scroll){
	sel.window.document.body.scrollLeft = sel.scrollX;
	sel.window.document.body.scrollTop = sel.scrollY;
      }
	  try {
        setPoint(sel.start, "Start");
        setPoint(sel.end, "End");
        selectRange(range, win);
	  } catch(e) {}
    };

    // This is called by the code in codemirror.js whenever it is
    // replacing a part of the DOM tree. The function sees whether the
    // given oldNode is part of the current selection, and updates
    // this selection if it is. Because nodes are often only partially
    // replaced, the length of the part that gets replaced has to be
    // taken into account -- the selection might stay in the oldNode
    // if the newNode is smaller than the selection's offset. The
    // offset argument is needed in case the selection does move to
    // the new object, and the given length is not the whole length of
    // the new node (part of it might have been used to replace
    // another node).
    select.replaceSelection = function(oldNode, newNode, length, offset) {
      function replace(which) {
	var selObj = oldNode["select" + which];
	if (selObj) {
          if (selObj.offset > length) {
            selObj.offset -= length;
          }
          else {
            newNode["select" + which] = selObj;
            delete oldNode["select" + which];
            selObj.node = newNode;
            selObj.offset += (offset || 0);
          }
	}
      }
      replace("Start");
      replace("End");
    };

    // Finding the top-level node at the cursor in the W3C is, as you
    // can see, quite an involved process. [Some of this can probably
    // be simplified, but I'm afraid to touch it now that it finally
    // works.]
    select.Cursor = function(container) {
      this.container = container;
      this.win = container.ownerDocument.defaultView;
      var selection = this.win.getSelection();
      this.valid = selection && selection.rangeCount > 0;
      if (this.valid) {
	var range = selection.getRangeAt(0);
	var end = range.endContainer;
	// For text nodes, we look at the node itself if the cursor is
	// inside, or at the node before it if the cursor is at the
	// start.
    
	if (end.nodeType == 3){
          if (range.endOffset > 0)
            this.start = topLevelNodeAt(end, this.container);
          else
            this.start = topLevelNodeBefore(end, this.container);
	}
	// Occasionally, browsers will return the HTML node as
	// selection (Opera does this all the time, which is the
	// reason this editor does not work on that browser). If the
	// offset is 0, we take the start of the frame ('after null'),
	// otherwise, we take the last node.
	else if (end.nodeName == "HTML") {
          this.start = (range.endOffset == 1 ? null : container.lastChild);
	}
	// If the given node is our 'container', we just look up the
	// correct node by using the offset.
	else if (end == container) {
          if (range.endOffset == 0)
            this.start = null;
          else
            this.start = end.childNodes[range.endOffset - 1];
	}
	// In any other case, we have a regular node. If the cursor is
	// at the end of the node, we use the node itself, if it is at
	// the start, we use the node before it, and in any other
	// case, we look up the child before the cursor and use that.
	else {
          if (range.endOffset == end.childNodes.length)
            this.start = topLevelNodeAt(end, this.container);
          else if (range.endOffset == 0)
            this.start = topLevelNodeBefore(end, this.container);
          else
            this.start = topLevelNodeAt(end.childNodes[range.endOffset - 1], this.container);
	}
      }
    };

    select.Cursor.prototype.focus = function() {
        var sel = this.win.getSelection();
        var range = this.win.document.createRange();
        range.setStartBefore(this.container.firstChild || this.container);
        if (this.start)
            range.setEndAfter(this.start);
        else
            range.setEndBefore(this.container.firstChild || this.container);
                 
        range.collapse(false);
        selectRange(range, this.win);
    };
	
	

    select.insertNewlineAtCursor = function(window) {
      var selection = window.getSelection();
      if (selection && selection.rangeCount > 0) {
	var range = selection.getRangeAt(0);
	var br = window.document.createElement('br');
	range.insertNode(br);
	range.setEndAfter(br);
	range.collapse(false);
	selectRange(range, window);
      }
    };
	
	// added for t3editor
    select.insertTextAtCursor = function(window,text) {
      var selection = window.getSelection();
      if (selection && selection.rangeCount > 0) {
        var range = selection.getRangeAt(0);
        // var br = withDocument(window.document, BR);
        textnode = window.document.createTextNode(text);
        range.insertNode(textnode);
        range.setEndAfter(textnode);
        range.collapse(false);
        selectRange(range, window);
      }
    }; 
  }

  // Search backwards through the top-level nodes until the next BR or
  // the start of the frame.
  select.Cursor.prototype.startOfLine = function() {
    var start = this.start || this.container.firstChild;
    while (start && start.nodeName != "BR")
      start = start.previousSibling;
    return start;
  };
}());
