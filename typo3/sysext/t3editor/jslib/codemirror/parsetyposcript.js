/* TypoScript parser
 *
 * based on parsejavascript.js by Marijn Haverbeke
 *
 * A parser that can be plugged into the CodeMirror system has to
 * implement the following interface: It is a function that, when
 * called with a string stream (stringstream.js) as an argument,
 * returns a MochiKit-style iterator (object with a 'next' method).
 * This iterator, when called, consumes some input from the string
 * stream, and returns a token object. Token objects must have a
 * 'value' property (the text they represent), a 'style' property (the
 * CSS style that should be used to colour them). Tokens for newline
 * characters must also have a 'lexicalContext' property, which has an
 * 'indentation' method that can be used to determine the proper
 * indentation level for the next line. This method optionally takes
 * the first character of the next line as an argument, which it can
 * use to adjust the indentation level.
 *
 * So far this should be easy. The hard part is that the iterator
 * produced by the parse function must also have a 'copy' method. This
 * method, called without arguments, returns a function representing
 * the current state of the parser. When this function is later called
 * with a string stream as its argument, it returns a parser iterator
 * object that resumes parsing using the old state and the new input
 * stream. It may assume that only one parser is active at a time, and
 * clobber the state of the old parser (the implementation below
 * certianly does).
 */

// Parse function for TypoScript. Makes use of the tokenizer from
// tokenizetyposcript.js. Note that your parsers do not have to be
// this complicated -- if you don't want to recognize local variables,
// in many languages it is enough to just look for braces, semicolons,
// parentheses, etc, and know when you are inside a string or comment.
Editor.Parser = (function() {
	// Token types that can be considered to be atoms.
	var atomicTypes = {
		"atom": true,
		"number": true,
		"variable": true,
		"string": true,
		"regexp": true
	};

	// Constructor for the lexical context objects.
	function TSLexical(indented, column, type, align, prev) {
		// indentation at start of this line
		this.indented = indented;
		// column at which this scope was opened
		this.column = column;
		// type of scope ('vardef', 'stat' (statement), '[', '{', or '(')
		this.type = type;
		// '[', '{', or '(' blocks that have any text after their opening
		// character are said to be 'aligned' -- any lines below are
		// indented all the way to the opening character.
		if (align != null) {
			this.align = align;
		}
		// Parent scope, if any.
		this.prev = prev;

	}
	
	
	// My favourite TypoScript indentation rules.
	function indentTS(lexical) {
		return function(firstChars) {
		 	var firstChar = firstChars && firstChars.charAt(0);
			var closing = firstChar == lexical.type;

			if (lexical.type == "{" && firstChar != "}") {
				return lexical.indented + 2;
			}

			if (firstChar == "}" && lexical.prev) {
				lexical = lexical.prev;
			}
			
			if (lexical.align) {
				return lexical.column - (closing ? 1: 0);
			} else {
				return lexical.indented + (closing ? 0: 2);
			}

		};
	}

	// The parser-iterator-producing function itself.
	function parseTS(input) {
		// Wrap the input in a token stream
		var tokens = tokenizeTypoScript(input);
		// The parser state. cc is a stack of actions that have to be
		// performed to finish the current statement. For example we might
		// know that we still need to find a closing parenthesis and a
		// semicolon. Actions at the end of the stack go first. It is
		// initialized with an infinitely looping action that consumes
		// whole statements.
		var cc = [statements];
		// Context contains information about the current local scope, the
		// variables defined in that, and the scopes above it.
		var context = null;
		// The lexical scope, used mostly for indentation.
		var lexical = new TSLexical( -2, 0, "block", false);
		// Current column, and the indentation at the start of the current
		// line. Used to create lexical scope objects.
		var column = 0;
		var indented = 0;
		// Variables which are used by the mark, cont, and pass functions
		// below to communicate with the driver loop in the 'next'
		// function.
		var consume,
		marked;

		// The iterator object.
		var parser = {
			next: next,
			copy: copy
		};

		function next() {
			// Start by performing any 'lexical' actions (adjusting the
			// lexical variable), or the operations below will be working
			// with the wrong lexical state.
			while (cc[cc.length - 1].lex) {
				cc.pop()();
			}

			// Fetch a token.
			var token = tokens.next();
			// Adjust column and indented.
			if (token.type == "whitespace" && column == 0) {
				indented = token.value.length;
			}
			column += token.value.length;
			if (token.type == "newline") {
				indented = column = 0;
				// If the lexical scope's align property is still undefined at
				// the end of the line, it is an un-aligned scope.
				if (! ("align" in lexical)) {
					lexical.align = false;
				}
		        // Newline tokens get a lexical context associated with them,
				// which is used for indentation.
				token.indentation = indentTS(lexical);
			}
			// No more processing for meaningless tokens.
			if (token.type == "whitespace" || token.type == "newline" || token.type == "comment") {
				return token;
			}
			// When a meaningful token is found and the lexical scope's
			// align is undefined, it is an aligned scope.
			if (! ("align" in lexical)) {
				lexical.align = true;
			}
			// Execute actions until one 'consumes' the token and we can
			// return it. Marked is used to
			while (true) {
				consume = marked = false;
				// Take and execute the topmost action.
				cc.pop()(token.type, token.name);
				if (consume) {
					// Marked is used to change the style of the current token.
					if (marked) {
						token.style = marked;
					}
					return token;
				}
			}
		}

		// This makes a copy of the parser state. It stores all the
		// stateful variables in a closure, and returns a function that
		// will restore them when called with a new input stream. Note
		// that the cc array has to be copied, because it is contantly
		// being modified. Lexical objects are not mutated, and context
		// objects are not mutated in a harmful way, so they can be shared
		// between runs of the parser.
		function copy() {
			var _context = context,
			_lexical = lexical,
			_cc = cc.concat([]),
			_regexp = tokens.regexp,
			_comment = tokens.inComment;

			return function(input) {
				context = _context;
				lexical = _lexical;
				cc = _cc.concat([]);
				// copies the array
				column = indented = 0;
				tokens = tokenizeTypoScript(input);
				tokens.regexp = _regexp;
				tokens.inComment = _comment;
				return parser;
			};
		}

		// Helper function for pushing a number of actions onto the cc
		// stack in reverse order.
		function push(fs) {
			for (var i = fs.length - 1; i >= 0; i--) {
				cc.push(fs[i]);
			}
		}

		// cont and pass are used by the action functions to add other
		// actions to the stack. cont will cause the current token to be
		// consumed, pass will leave it for the next action.
		function cont() {
			push(arguments);
			consume = true;
		}

		function pass() {
			push(arguments);
			consume = false;
		}

		// Used to change the style of the current token.
		function mark(style) {
			marked = style;
		}

		// Push a new scope. Will automatically link the the current
		// scope.
		function pushcontext() {
			context = {
				prev: context,
				vars: {
					"this": true,
					"arguments": true
				}
			};
		}

		// Pop off the current scope.
		function popcontext() {
			context = context.prev;
		}

		// Register a variable in the current scope.
		function register(varname) {
			if (context) {
				mark("variabledef");
				context.vars[varname] = true;
			}
		}

		// Push a new lexical context of the given type.
		function pushlex(type) {
			var result = function() {
				lexical = new TSLexical(indented, column, type, null, lexical)
			};
			result.lex = true;
			return result;
		}

		// Pop off the current lexical context.
		function poplex() {
			lexical = lexical.prev;
		}

		poplex.lex = true;
		// The 'lex' flag on these actions is used by the 'next' function
		// to know they can (and have to) be ran before moving on to the
		// next token.

		// Creates an action that discards tokens until it finds one of
		// the given type.
		function expect(wanted) {
			return function(type) {
				if (type == wanted) {
					cont();
				} else {
					cont(arguments.callee);
				}
			};
		}

		// Looks for a statement, and then calls itself.
		function statements(type) {
			return pass(statement, statements);
		}
		// Dispatches various types of statements based on the type of the
		// current token.
		function statement(type) {
			if (type == "{") {
				cont(pushlex("{"), block, poplex);
			} else {
				cont();
			}
		}

		// Dispatch expression types.
		function expression(type) {
			if (atomicTypes.hasOwnProperty(type)) {
				cont(maybeoperator);

			} else if (type == "function") {
				cont(functiondef);

			} else if (type == "keyword c") {
				cont(expression);

			} else if (type == "(") {
				cont(pushlex(")"), expression, expect(")"), poplex);

			} else if (type == "operator") {
				cont(expression);

			} else if (type == "[") {
				cont(pushlex("]"), commasep(expression), expect("]"), poplex);

			} else if (type == "{") {
				cont(pushlex("}"), commasep(objprop), expect("}"), poplex);
			}
		}

		// Called for places where operators, function calls, or
		// subscripts are valid. Will skip on to the next action if none
		// is found.
		function maybeoperator(type) {
			if (type == "operator") {
				cont(expression);

			} else if (type == "(") {
				cont(pushlex(")"), expression, commasep(expression), expect(")"), poplex);

			} else if (type == ".") {
				cont(property, maybeoperator);

			} else if (type == "[") {
				cont(pushlex("]"), expression, expect("]"), poplex);
			}
		}

		// When a statement starts with a variable name, it might be a
		// label. If no colon follows, it's a regular statement.
		function maybelabel(type) {
			if (type == ":") {
				cont(poplex, statement);
			} else {
				pass(maybeoperator, expect(";"), poplex);
			}
		}

		// Property names need to have their style adjusted -- the
		// tokenizer think they are variables.
		function property(type) {
			if (type == "variable") {
				mark("property");
				cont();
			}
		}

		// This parses a property and its value in an object literal.
		function objprop(type) {
			if (type == "variable") {
				mark("property");
			}
			if (atomicTypes.hasOwnProperty(type)) {
				cont(expect(":"), expression);
			}
		}

		// Parses a comma-separated list of the things that are recognized
		// by the 'what' argument.
		function commasep(what) {
			function proceed(type) {
				if (type == ",") {
					cont(what, proceed);
				}
			};
			return function() {
				pass(what, proceed);
			};
		}

		// Look for statements until a closing brace is found.
		function block(type) {
			if (type == "}") {
				cont();
			} else {
				pass(statement, block);
			}
		}

		// Look for statements until a closing brace is found.
		function condition(type) {
			if (type == "]") {
				cont();
			} else {
				pass(statement, block);
			}
		}

		// Variable definitions are split into two actions -- 1 looks for
		// a name or the end of the definition, 2 looks for an '=' sign or
		// a comma.
		function vardef1(type, value) {
			if (type == "variable") {
				register(value);
				cont(vardef2);
			} else {
				cont();
			}
		}

		function vardef2(type) {
			if (type == "operator") {
				cont(expression, vardef2);
			} else if (type == ",") {
				cont(vardef1);
			}
		}

		// For loops.
		function forspec1(type, value) {
			if (type == "var") {
				cont(vardef1, forspec2);
			} else {
				cont(expression, forspec2);
			}
		}

		function forspec2(type) {
			if (type == ",") {
				cont(forspec1);
			}
			if (type == ";") {
				cont(expression, expect(";"), expression);
			}
		}

		// A function definition creates a new context, and the variables
		// in its argument list have to be added to this context.
		function functiondef(type, value) {
			if (type == "variable") {
				register(value);
				cont(functiondef);
			} else if (type == "(") {
				cont(pushcontext, commasep(funarg), expect(")"), statement, popcontext);
			}
		}

		function funarg(type, value) {
			if (type == "variable") {
				register(value);
				cont();
			}
		}

		return parser;
	}
	
	return {make: parseTS, electricChars: "{}"};
})();