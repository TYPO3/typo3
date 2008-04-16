/* String streams are the things fed to parsers (which can feed them
 * to a tokenizer if they want). They provide peek and next methods
 * for looking at the current character (next 'consumes' this
 * character, peek does not), and a get method for retrieving all the
 * text that was consumed since the last time get was called.
 *
 * An easy mistake to make is to let a StopIteration exception finish
 * the token stream while there are still characters pending in the
 * string stream (hitting the end of the buffer while parsing a
 * token). To make it easier to detect such errors, the strings throw
 * an exception when this happens.
 */

(function(){
  // Generic operations that apply to stringstreams.
  var base = {
    more: function() {
      return this.peek() !== null;
    },
    applies: function(test) {
      var next = this.peek();
      return (next !== null && test(next));
    },
    nextWhile: function(test) {
      while (this.applies(test))
        this.next();
    },
    equals: function(ch) {
      return ch === this.peek();
    },
    notEquals: function(ch) {
      var next = this.peek();
      return (next !== null && next != ch);
    }
  };

  // Make a stream out of a single string. Not used by the editor, but
  // very useful for testing your parser.
  window.singleStringStream = function(string) {
    var pos = 0, start = 0;
    return update({
      peek: function() {
        if (pos < string.length)
          return string.charAt(pos);
        else
          return null;
      },
      next: function() {
        if (pos >= string.length) {
          if (pos < start)
            throw "End of stringstream reached without emptying buffer.";
          else 
            throw StopIteration;
        }
        return string.charAt(pos++);
      },
      get: function() {
        var result = string.slice(start, pos);
        start = pos;
        return result;
      }
    }, base);
  }

  // Make a string stream out of an iterator that returns strings. This
  // is applied to the result of traverseDOM (see codemirror.js), and
  // the resulting stream is fed to the parser.
  window.multiStringStream = function(source){
    source = iter(source);
    var current = "", pos = 0;
    var peeked = null, accum = "";

    return update({
      peek: function(){
        if (!peeked) {
          try {peeked = this.step();}
          catch (e) {
            if (e != StopIteration) throw e;
            else peeked = null;
          }
        }
        return peeked;
      },
      step: function(){
        if (peeked){
          var temp = peeked;
          peeked = null;
          return temp;
        }
        while (pos == current.length){
          accum += current;
          current = ""; // In case source.next() throws
          pos = 0;
          current = source.next();
        }
        return current.charAt(pos++);

      },
      next: function(){
        try {return this.step();}
        catch (e) {
          if (e == StopIteration && accum.length > 0)
            throw "End of stringstream reached without emptying buffer ('" + accum + "').";
          else
            throw e;
        }
      },
      get: function(){
        var temp = accum;
        var realPos = peeked ? pos - 1 : pos;
        accum = "";
        if (realPos > 0){
          temp += current.slice(0, realPos);
          current = current.slice(realPos);
          pos = peeked ? 1 : 0;
        }
        return temp;
      }
    }, base);
  }
})();
