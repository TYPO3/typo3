/**
 * AutoLinking plugin for CKEditor 4.7
 *
 * Automatically creates an anchor tag when typing an URL or email.
 * Inspired by https://github.com/Gnodiah/ckeditor-autolink
 */
CKEDITOR.plugins.add('autolinking', {
  init: function(editor) {
    var spaceChar = 32, enterChar = 13, tabChar = 9, fillChar = ' ';
    var isFillChar = function(node, isInStart) {
      return node.nodeType == 3 && !node.nodeValue.replace(new RegExp((isInStart ? '^' : '') + ' '), '').length;
    };
    var isBodyTag = function(node) {
      return node && node.nodeType == 1 && node.tagName.toLowerCase() == 'body';
    };
    var isAnchorTag = function(node) {
      return node && node.nodeType == 1 && node.tagName.toLowerCase() === 'a';
    };
    var html = function(str) {
      return str ? str.replace(/&((g|l|quo)t|amp|#39);/g, function(m) {
        return {
          '&lt;': '<',
          '&amp;': '&',
          '&quot;': '"',
          '&gt;': '>',
          '&#39;': "'"
        }[m]
      }) : '';
    };

    var hasParentAnchorTag = function(node) {
      if (node && !isBodyTag(node)) {
        while (node) {
          if (isBodyTag(node)) {
            return false;
          } else if (isAnchorTag(node)) {
            return true;
          }
          node = node.parentNode;
        }
      }
      return false;
    };

    editor.on('instanceReady', function() {
      editor.autolinking = function(evt) {
        var sel = editor.getSelection().getNative(),
          range = sel.getRangeAt(0).cloneRange(),
          offset,
          charCode;

        var start = range.startContainer;
        while (start.nodeType == 1 && range.startOffset > 0) {
          start = range.startContainer.childNodes[range.startOffset - 1];
          if (!start) break;

          range.setStart(start, start.nodeType == 1 ? start.childNodes.length : start.nodeValue.length);
          range.collapse(true);
          start = range.startContainer;
        }

        do {
          if (range.startOffset == 0) {
            start = range.startContainer.previousSibling;

            while (start && start.nodeType == 1) {
              if (CKEDITOR.env.gecko && start.firstChild)
                start = start.firstChild;
              else
                start = start.lastChild;
            }
            if (!start || isFillChar(start)) break;
            offset = start.nodeValue.length;
          } else {
            start = range.startContainer;
            offset = range.startOffset;
          }
          range.setStart(start, offset - 1);
          charCode = range.toString().charCodeAt(0);
        } while (charCode != 160 && charCode != spaceChar);

        if (range.toString().replace(new RegExp(fillChar, 'g'), '').match(/(?:https?:\/\/|ssh:\/\/|ftp:\/\/|file:\/|www\.)/i)) {
          while (range.toString().length) {
            if (/^(?:https?:\/\/|ssh:\/\/|ftp:\/\/|file:\/|www\.)/i.test(range.toString())) break;

            try {
              range.setStart(range.startContainer, range.startOffset + 1);
            } catch (e) {
              var startChar = range.startContainer;
              while (!(next = startChar.nextSibling)) {
                if (isBodyTag(startChar)) {
                  return;
                }
                startChar = startChar.parentNode;
              }
              range.setStart(next, 0);
            }
          }

          if (hasParentAnchorTag(range.startContainer)) {
            return;
          }

          var a = document.createElement('a'),
            href;

          editor.undoManger && editor.undoManger.save();
          a.appendChild(range.extractContents());
          a.href = a.innerHTML = a.innerHTML.replace(/<[^>]+>/g, '');
          href = a.getAttribute('href').replace(new RegExp(fillChar, 'g'), '');
          href = /^(?:https?:\/\/)/ig.test(href) ? href : 'http://' + href;
          a.href = html(href);

          range.insertNode(a);
          range.setStart(a.nextSibling, 0);
          range.collapse(true);
          sel.removeAllRanges();
          sel.addRange(range);
          editor.undoManger && editor.undoManger.save();
        }
      };

      editor.on('key', function(evt) {
        if (this.mode !== 'source') {
          if (evt.data.keyCode === spaceChar || evt.data.keyCode === tabChar || evt.data.keyCode === enterChar) {
            editor.autolinking(evt);
          }
        }
      });
    });
  }
});
