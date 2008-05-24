/* This file defines an XML parser, with a few kludges to make it
 * useable for HTML. autoSelfClosers defines a set of tag names that
 * are expected to not have a closing tag, and doNotIndent specifies
 * the tags inside of which no indentation should happen (see Config
 * object). These can be disabled by passing the editor an object like
 * {useHTMLKludges: false} as parserConfig option.
 */

Editor.Parser = (function() {
  var Kludges = {
    autoSelfClosers: {"br": true, "img": true, "hr": true, "link": true, "input": true,
                      "meta": true, "col": true, "frame": true, "base": true, "area": true},
    doNotIndent: {"pre": true}
  };
  var NoKludges = {autoSelfClosers: {}, doNotIndent: {}};
  var UseKludges = Kludges;

  // Simple stateful tokenizer for XML documents. Returns a
  // MochiKit-style iterator, with a state property that contains a
  // function encapsulating the current state.
  function tokenizeXML(source, startState) {
    function isWhiteSpace(ch) {
      return ch != "\n" && realWhiteSpace.test(ch);
    }
    // The following functions are all state functions -- they 'consume'
    // and label the next token based on the current parser state.
    function inText() {
      var ch = this.source.next();
      if (ch == "<") {
        if (this.source.equals("!")) {
          this.source.next();
          if (this.source.equals("[")) {
            this.source.next();
            this.state = inBlock("cdata", "]]>");
            return this.state();
          }
          else if (this.source.equals("-")) {
            this.source.next();
            this.state = inBlock("comment", "-->");
            return this.state();
          }
          else {
            return "text";
          }
        }
        else {
          if (this.source.applies(matcher(/[?\/]/))) this.source.next();
          this.state = inTag;
          return "punctuation";
        }
      }
      else if (ch == "&") {
        while (this.source.notEquals("\n")) {
          if (this.source.next() == ";")
            break;
        }
        return "entity";
      }
      else if (isWhiteSpace(ch)) {
        this.source.nextWhile(isWhiteSpace);
        return "whitespace";
      }
      else {
        this.source.nextWhile(matcher(/[^&<\n]/));
        return "text";
      }
    }
    function inTag() {
      var ch = this.source.next();
      if (ch == ">") {
        this.state = inText;
        return "punctuation";
      }
      else if (/[?\/]/.test(ch) && this.source.equals(">")) {
        this.source.next();
        this.state = inText;
        return "punctuation";
      }
      else if (ch == "=") {
        return "punctuation";
      }
      else if (/[\'\"]/.test(ch)) {
        this.state = inAttribute(ch);
        return this.state();
      }
      else if (isWhiteSpace(ch)) {
        this.source.nextWhile(isWhiteSpace);
        return "whitespace";
      }
      else {
        this.source.nextWhile(matcher(/[^\s\u00a0=<>\"\'\/?]/));
        return "name";
      }
    }
    function inAttribute(quote) {
      return function() {
        while (this.source.notEquals("\n")) {
          if (this.source.next() == quote) {
            this.state = inTag;
            break;
          }
        }
        return "attribute";
      };
    }
    function inBlock(style, terminator) {
      return function() {
        var rest = terminator;
        while (this.source.more() && this.source.notEquals("\n")) {
          var ch = this.source.next();
          if (ch == rest.charAt(0)) {
            rest = rest.slice(1);
            if (rest.length == 0) {
              this.state = inText;
              break;
            }
          }
          else {
            rest = terminator;
          }
        }
        return style;
      };
    }

    return {
      state: startState || inText,
      source: source,
      
      newLine: function() {
        this.source.next();
        return "whitespace";
      },

      next: function(){
        if (!this.source.more()) throw StopIteration;
        
        var token = {
          style: (this.source.equals("\n") ? this.newLine() : this.state()),
          content: this.source.get()
        };
        if (token.content != "\n") // newlines must stand alone
          this.source.nextWhile(isWhiteSpace);
        token.value = token.content + this.source.get();
        return token;
      }
    };
  }

  // The parser. The structure of this function largely follows that of
  // parseJavaScript in parsejavascript.js (there is actually a bit more
  // shared code than I'd like), but it is quite a bit simpler.
  var parseXML = function(source) {
    var tokens = tokenizeXML(source);
    var cc = [base];
    var tokenNr = 0, indented = 0;
    var currentTag = null, context = null;
    var consume, marked;
    
    function push(fs) {
      for (var i = fs.length - 1; i >= 0; i--)
        cc.push(fs[i]);
    }
    function cont() {
      push(arguments);
      consume = true;
    }
    function pass() {
      push(arguments);
      consume = false;
    }

    function mark(style) {
      marked = style;
    }
    function expect(text) {
      return function(style, content) {
        if (content == text) cont();
        else mark("error") || cont(arguments.callee);
      };
    }

    function pushContext(tagname, startOfLine) {
      var noIndent = UseKludges.doNotIndent.hasOwnProperty(tagname) || (context && context.noIndent);
      context = {prev: context, name: tagname, indent: indented, startOfLine: startOfLine, noIndent: noIndent};
    }
    function popContext() {
      context = context.prev;
    }
    function computeIndentation(baseContext) {
      return function(nextChars) {
        var context = baseContext;
        if (context && context.noIndent)
          return 0;
        if (context && /^<\//.test(nextChars))
          context = context.prev;
        while (context && !context.startOfLine)
          context = context.prev;
        if (context)
          return context.indent + 2;
        else
          return 0;
      };
    }

    function base() {
      return pass(element, base);
    }
    var harmlessTokens = {"text": true, "entity": true, "comment": true, "cdata": true};
    function element(style, content) {
      if (content == "<") cont(tagname, attributes, endtag(tokenNr == 1));
      else if (content == "</") cont(closetagname, expect(">"));
      else if (content == "<?") cont(tagname, attributes, expect("?>"));
      else if (harmlessTokens.hasOwnProperty(style)) cont();
      else mark("error") || cont();
    }
    function tagname(style, content) {
      if (style == "name") {
        currentTag = content.toLowerCase();
        mark("tagname");
        cont();
      }
      else {
        currentTag = null;
        pass();
      }
    }
    function closetagname(style, content) {
      if (style == "name" && context && content.toLowerCase() == context.name) {
        popContext();
        mark("tagname");
      }
      else {
        mark("error");
      }
      cont();
    }
    function endtag(startOfLine) {
      return function(style, content) {
        if (content == "/>" || (content == ">" && UseKludges.autoSelfClosers.hasOwnProperty(currentTag))) cont();
        else if (content == ">") pushContext(currentTag, startOfLine) || cont();
        else mark("error") || cont(arguments.callee);
      };
    }
    function attributes(style) {
      if (style == "name") mark("attname") || cont(attribute, attributes);
      else pass();
    }
    function attribute(style, content) {
      if (content == "=") cont(value);
      else if (content == ">" || content == "/>") pass(endtag);
      else pass();
    }
    function value(style) {
      if (style == "attribute") cont(value);
      else pass();
    }

    return {
      next: function(){
        var token = tokens.next();
        if (token.style == "whitespace" && tokenNr == 0)
          indented = token.value.length;
        else
          tokenNr++;
        if (token.content == "\n") {
          indented = tokenNr = 0;
          token.indentation = computeIndentation(context);
        }

        if (token.style == "whitespace" || token.type == "comment")
          return token;

        while(true){
          consume = marked = false;
          cc.pop()(token.style, token.content);
          if (consume){
            if (marked)
              token.style = marked;
            return token;
          }
        }
      },

      copy: function(){
        var _cc = cc.concat([]), _tokenState = tokens.state, _context = context;
        var parser = this;
        
        return function(input){
          cc = _cc.concat([]);
          tokenNr = indented = 0;
          context = _context;
          tokens = tokenizeXML(input, _tokenState);
          return parser;
        };
      }
    };
  }

  return {
    make: parseXML,
    electricChars: "/",
    configure: function(config) {
      if (config.useHTMLKludges)
        UseKludges = Kludges;
      else
        UseKludges = NoKludges;
    }
  };
})();
