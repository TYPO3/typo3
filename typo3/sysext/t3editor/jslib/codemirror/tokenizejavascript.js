/* Tokenizer for JavaScript code */

var tokenizeJavaScript = function(){
  // A map of JavaScript's keywords. The a/b/c keyword distinction is
  // very rough, but it gives the parser enough information to parse
  // correct code correctly (we don't care much how we parse incorrect
  // code). The style information included in these objects is used by
  // the highlighter to pick the correct CSS style for a token.
  var keywords = function(){
    function result(type, style){
      return {type: type, style: style};
    }
    // keywords that take a parenthised expression, and then a
    // statement (if)
    var keywordA = result("keyword a", "keyword");
    // keywords that take just a statement (else)
    var keywordB = result("keyword b", "keyword");
    // keywords that optionally take an expression, and form a
    // statement (return)
    var keywordC = result("keyword c", "keyword");
    var operator = result("operator", "keyword");
    var atom = result("atom", "atom");
    return {
      "if": keywordA, "switch": keywordA, "while": keywordA, "with": keywordA,
      "else": keywordB, "do": keywordB, "try": keywordB, "finally": keywordB,
      "return": keywordC, "break": keywordC, "continue": keywordC, "new": keywordC, "delete": keywordC, "throw": keywordC,
      "in": operator, "typeof": operator, "instanceof": operator,
      "var": result("var", "keyword"), "function": result("function", "keyword"), "catch": result("catch", "keyword"),
      "for": result("for", "keyword"), "case": result("case", "keyword"),
      "true": atom, "false": atom, "null": atom, "undefined": atom, "NaN": atom, "Infinity": atom
    };
  }();

  // Some helper regexp matchers.
  var isOperatorChar = matcher(/[\+\-\*\&\%\/=<>!\?]/);
  var isDigit = matcher(/[0-9]/);
  var isHexDigit = matcher(/[0-9A-Fa-f]/);
  var isWordChar = matcher(/[\w\$_]/);
  function isWhiteSpace(ch){
    // In our scheme newlines are no whitespace (they are another
    // special case).
    return ch != "\n" && realWhiteSpace.test(ch);
  }

  // This function produces a MochiKit-style iterator that tokenizes
  // the output of the given stringstream (see stringstream.js).
  // Tokens are objects with a type, style, and value property. The
  // value contains the textual content of the token. Because this may
  // include trailing whitespace (for efficiency reasons), some
  // tokens, such a variable names, also have a name property
  // containing their actual textual value.
  return function(source){
    // Produce a value to return. Automatically skips and includes any
    // whitespace. The base argument is prepended to the value
    // property and assigned to the name property -- this is used when
    // the caller has already extracted the text from the stream
    // himself.
    function result(type, style, base){
      source.nextWhile(isWhiteSpace);
      var value = {type: type, style: style, value: (base ? base + source.get() : source.get())};
      if (base) value.name = base;
      return value;
    }

    // Advance the stream until the given character (not preceded by a
    // backslash) is encountered (or a newline is found).
    function nextUntilUnescaped(end){
      var escaped = false;
      var next;
      while(source.notEquals("\n")){
        var next = source.next();
        if (next == end && !escaped)
          break;
        escaped = next == "\\";
      }
    }
  
    function readHexNumber(){
      source.next(); // skip the 'x'
      source.nextWhile(isHexDigit);
      return result("number", "atom");
    }
    function readNumber(){
      source.nextWhile(isDigit);
      if (source.equals(".")){
        source.next();
        source.nextWhile(isDigit);
      }
      if (source.equals("e") || source.equals("E")){
        source.next();
        if (source.equals("-"))
          source.next();
        source.nextWhile(isDigit);
      }
      return result("number", "atom");
    }
    // Read a word, look it up in keywords. If not found, it is a
    // variable, otherwise it is a keyword of the type found.
    function readWord(){
      source.nextWhile(isWordChar);
      var word = source.get();
      var known = keywords.hasOwnProperty(word) && keywords.propertyIsEnumerable(word) && keywords[word];
      return known ? result(known.type, known.style, word) : result("variable", "variable", word);
    }
    function readRegexp(){
      nextUntilUnescaped("/");
      source.nextWhile(matcher(/[gi]/));
      return result("regexp", "string");
    }
    // Mutli-line comments are tricky. We want to return the newlines
    // embedded in them as regular newline tokens, and then continue
    // returning a comment token for every line of the comment. So
    // some state has to be saved (inComment) to indicate whether we
    // are inside a /* */ sequence.
    function readMultilineComment(start){
      this.inComment = true;
      var maybeEnd = (start == "*");
      while(true){
        if (source.equals("\n"))
          break;
        var next = source.next();
        if (next == "/" && maybeEnd){
          this.inComment = false;
          break;
        }
        maybeEnd = (next == "*");
      }
      return result("comment", "comment");
    }

    // Fetch the next token. Dispatches on first character in the
    // stream, or first two characters when the first is a slash. The
    // || things are a silly trick to keep simple cases on a single
    // line.
    function next(){
      var token = null;
      var ch = source.next();
      if (ch == "\n")
        token = {type: "newline", style: "whitespace", value: source.get()};
      else if (this.inComment)
        token = readMultilineComment.call(this, ch);
      else if (isWhiteSpace(ch))
        token = source.nextWhile(isWhiteSpace) || result("whitespace", "whitespace");
      else if (ch == "\"" || ch == "'")
        token = nextUntilUnescaped(ch) || result("string", "string");
      // with punctuation, the type of the token is the symbol itself
      else if (/[\[\]{}\(\),;\:\.]/.test(ch))
        token = result(ch, "punctuation");
      else if (ch == "0" && (source.equals("x") || source.equals("X")))
        token = readHexNumber();
      else if (isDigit(ch))
        token = readNumber();
      else if (ch == "/"){
        if (source.equals("*"))
          token = readMultilineComment.call(this, ch);
        else if (source.equals("/"))
          token = nextUntilUnescaped(null) || result("comment", "comment");
        else if (this.regexp)
          token = readRegexp();
        else
          token = source.nextWhile(isOperatorChar) || result("operator", "operator");
      }
      else if (isOperatorChar(ch))
        token = source.nextWhile(isOperatorChar) || result("operator", "operator");
      else
        token = readWord();

      // JavaScript's syntax rules for when a slash might be the start
      // of a regexp and when it is just a division operator are kind
      // of non-obvious. This decides, based on the current token,
      // whether the next token could be a regular expression.
      if (token.style != "whitespace" && token != "comment")
        this.regexp = token.type == "operator" || token.type == "keyword c" || token.type.match(/[\[{}\(,;:]/);
      return token;
    }

    // Wrap it in an iterator. The state (regexp and inComment) is
    // exposed because a parser will need to save it when making a
    // copy of its state.
    return {next: next, regexp: true, inComment: false};
  }
}();
