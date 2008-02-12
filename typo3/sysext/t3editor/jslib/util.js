/* A few useful utility functions. */

// Retrieve the next value from an iterator, or return an alternative
// value if the iterator is at its end.
function nextOr(iter, alternative) {
	try {
		return iter.next();
	} catch(e) {
		if (e != StopIteration) {
			throw e;
		} else {
			return alternative;
		}
	}
}

// Create an object to represent a set. Takes any number of strings as
// arguments, and returns an object in which the properties named by
// these strings are set to true.
function setObject() {
	var obj = {};
	forEach(arguments,
		function(value) {
			obj[value] = true;

		});
	return obj;
}

// Create a predicate function that tests a string againsts a given
// regular expression.
function matcher(regexp) {
	return function(value) {
		return regexp.test(value);
	};
}

// Test whether a DOM node has a certain CSS class. Much faster than
// the MochiKit equivalent, for some reason.
function hasClass(element, className) {
	var classes = element.className;
	return classes && new RegExp("(^| )" + className + "($| )").test(classes);
}

function repeatString(str, times) {
	var result = [];
	while (times--) result.push(str);
	return result.join("");
}

// Insert a DOM node after another node.
function insertAfter(newNode, oldNode) {
	var parent = oldNode.parentNode;
	var next = oldNode.nextSibling;
	if (next) {
		parent.insertBefore(newNode, next);
	} else {
		parent.appendChild(newNode);
	}
	return newNode;
}

// Insert a dom node at the start of a container.
function insertAtStart(node, container) {
	if (container.firstChild) {
		container.insertBefore(node, container.firstChild);
	} else {
		container.appendChild(node);
	}
	return node;
}

// Check whether a node is contained in another one.
function isAncestor(node, child) {
	while (child = child.parentNode) {
		if (node == child) {
			return true;
		}
	}
	return false;
}

// The non-breaking space character.
var nbsp = String.fromCharCode(160);


// fix prototype issue: ajax request do not respect charset of the page and screw up code
if (document.characterSet != "UTF-8") {
	encodeURIComponent = escape;
}
