// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE

(function(mod) {
  if (typeof exports === 'object' && typeof module === 'object') // CommonJS
    mod(require('codemirror', 'TYPO3/CMS/T3editor/Addon/Hint/TsCodeCompletion'));
  else if (typeof define === 'function' && define.amd) // AMD
    define(['codemirror', 'TYPO3/CMS/T3editor/Addon/Hint/TsCodeCompletion'], mod);
  else // Plain browser env
    mod(CodeMirror);
})(function(CodeMirror, TsCodeCompletion) {
  var Pos = CodeMirror.Pos;

  CodeMirror.registerHelper('hint', 'typoscript', function(editor) {
    return typoScriptHint(editor, function(e, cur) {
      return e.getTokenAt(cur);
    });
  });

  function arrayContains(arr, item) {
    if (!Array.prototype.indexOf) {
      var i = arr.length;
      while (i--) {
        if (arr[i] === item) {
          return true;
        }
      }
      return false;
    }
    return arr.indexOf(item) !== -1;
  }

  function typoScriptHint(editor, getToken) {
    var keywords = TsCodeCompletion.refreshCodeCompletion(editor);

    // Find the token at the cursor
    var cur = editor.getCursor(),
      token = getToken(editor, cur);

    if (/\b(?:string|comment)\b/.test(token.type) && tokenIsSubStringOfKeywords(token, keywords)) {
      return;
    }
    token.state = CodeMirror.innerMode(editor.getMode(), token.state).state;

    // If it's not a 'word-style' token, ignore the token.
    if (!/^[\w$_]*$/.test(token.string)) {
      token = {
        start: cur.ch, end: cur.ch, string: '', state: token.state,
        type: token.string === '.' ? 'property' : null
      };
    } else if (token.end > cur.ch) {
      token.end = cur.ch;
      token.string = token.string.slice(0, cur.ch - token.start);
    }

    var completions = {
      list: getCompletions(token, keywords),
      from: Pos(cur.line, token.start),
      to: Pos(cur.line, token.end)
    };

    CodeMirror.on(completions, 'pick', function() {
      TsCodeCompletion.resetCompList();
    });

    return completions;
  }

  function tokenIsSubStringOfKeywords(token, keywords) {
    var tokenLength = token.string.length;
    for (var i = 0; i < keywords.length; ++i) {
      if (token.string === keywords[i].substr(tokenLength)) {
        return true;
      }
    }

    return false;
  }

  function getCompletions(token, keywords) {
    var found = [],
      start = token.string;

    function maybeAdd(str) {
      if (str.lastIndexOf(start, 0) === 0 && !arrayContains(found, str)) {
        found.push(str);
      }
    }

    // If not, just look in the global object and any local scope
    // (reading into JS mode internals to get at the local and global variables)
    for (var v = token.state.localVars; v; v = v.next) {
      maybeAdd(v.name);
    }
    for (var v = token.state.globalVars; v; v = v.next) {
      maybeAdd(v.name);
    }
    for (var i = 0, e = keywords.length; i < e; ++i) {
      maybeAdd(keywords[i]);
    }
    found.sort();

    return found;
  }
});
