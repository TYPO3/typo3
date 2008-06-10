/* A few useful utility functions. */

// Capture a method on an object.
function method(obj, name) {
  return function() {obj[name].apply(obj, arguments);};
}

// Write properties from an object into another object.
function update(obj, from) {
  for (var name in from)
    obj[name] = from[name];
  return obj;
}

// The value used to signal the end of a sequence in iterators.
var StopIteration = {toString: function() {return "StopIteration"}};

// Checks whether the argument is an iterator or a regular sequence,
// turns it into an iterator.
function iter(seq) {
  var i = 0;
  if (seq.next) return seq;
  else return {
    next: function() {
      if (i >= seq.length) throw StopIteration;
      else return seq[++i];
    }
  };
}

// Apply a function to each element in a sequence.
function forEach(iter, f) {
  if (iter.next) {
    try {while (true) f(iter.next());}
    catch (e) {if (e != StopIteration) throw e;}
  }
  else {
    for (var i = 0; i < iter.length; i++)
      f(iter[i]);
  }
}

// Map a function over a sequence, producing an array of results.
function map(iter, f) {
  var accum = [];
  forEach(iter, function(val) {accum.push(f(val));});
  return accum;
}

// Create a predicate function that tests a string againsts a given
// regular expression.
function matcher(regexp){
  return function(value){return regexp.test(value);};
}

// Test whether a DOM node has a certain CSS class. Much faster than
// the MochiKit equivalent, for some reason.
function hasClass(element, className){
  var classes = element.className;
  return classes && new RegExp("(^| )" + className + "($| )").test(classes);
}

// Insert a DOM node after another node.
function insertAfter(newNode, oldNode) {
  var parent = oldNode.parentNode;
  var next = oldNode.nextSibling;
  if (next)
    parent.insertBefore(newNode, next);
  else
    parent.appendChild(newNode);
  return newNode;
}

// Insert a dom node at the start of a container.
function insertAtStart(node, container) {
  if (container.firstChild)
    container.insertBefore(node, container.firstChild);
  else
    container.appendChild(node);
  return node;
}

function removeElement(node) {
  if (node.parentNode)
    node.parentNode.removeChild(node);
}

function clearElement(node) {
  while (node.firstChild)
    node.removeChild(node.firstChild);
}

// Check whether a node is contained in another one.
function isAncestor(node, child) {
  while (child = child.parentNode) {
    if (node == child)
      return true;
  }
  return false;
}

// The non-breaking space character.
var nbsp = "\u00a0";
// Unfortunately, IE's regexp matcher thinks non-breaking spaces
// aren't whitespace.
var realWhiteSpace = /^[\s\u00a0]*$/;

// Standardize a few unportable event properties.
function normalizeEvent(event) {
  if (!event.stopPropagation) {
    event.stopPropagation = function() {this.cancelBubble = true;};
    event.preventDefault = function() {this.returnValue = false;};
  }
  if (!event.stop) {
    event.stop = function() {
      this.stopPropagation();
      this.preventDefault();
    };
  }

  if (event.type == "keypress") {
    if (event.charCode === 0 || event.charCode == undefined)
      event.code = event.keyCode;
    else
      event.code = event.charCode;
    event.character = String.fromCharCode(event.code);
  }
  return event;
}

// Portably register event handlers.
function addEventHandler(node, type, handler) {
  function wrapHandler(event) {
    handler(normalizeEvent(event || window.event));
  }
  if (typeof node.addEventListener == "function")
    node.addEventListener(type, wrapHandler, false);
  else
    node.attachEvent("on" + type, wrapHandler);
}


