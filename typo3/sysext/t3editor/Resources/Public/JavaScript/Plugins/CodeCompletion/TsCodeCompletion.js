/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/T3editor/CodeCompletion/TsCodeCompletion
 * Contains the TsCodeCompletion class
 */
define([
	'jquery',
	'TYPO3/CMS/T3editor/Plugins/CodeCompletion/TsRef',
	'TYPO3/CMS/T3editor/Plugins/CodeCompletion/TsParser',
	'TYPO3/CMS/T3editor/Plugins/CodeCompletion/CompletionResult'
], function ($, TsRef, TsParser, CompletionResult) {
	/**
	 *
	 * @type {{tsRef: *, outerDiv: null, options: {ccWords: number}, index: number, currWord: number, cc_up: null, cc_down: null, mousePos: {x: number, y: number}, proposals: null, compResult: null, cc: number, linefeedsPrepared: boolean, currentCursorPosition: null, extTsObjTree: {}, latestCursorNode: null, codemirror: null, parser: null, plugins: string[], $codeCompleteBox: (*|jQuery)}}
	 * @exports TYPO3/CMS/T3editor/CodeCompletion/TsCodeCompletion
	 */
	var TsCodeCompletion = {
		tsRef: TsRef,
		outerDiv: null,
		options: {
			ccWords : 10
		},
		index: 0,
		currWord: 0,
		cc_up: null,
		cc_down: null,
		mousePos: {
			x:0,
			y:0
		},
		proposals: null,
		compResult: null,
		cc: 0,
		linefeedsPrepared: false,
		currentCursorPosition: null,
		extTsObjTree: {},
		latestCursorNode: null,
		codemirror: null,
		parser: null,
		plugins: [
			'TYPO3/CMS/T3editor/Plugins/CodeCompletion/DescriptionPlugin'
		],
		$codeCompleteBox: $('<div />', {class: 't3e_codeCompleteWrap'}).append(
			$('<div />', {class: 't3e_codeCompleteBox'})
		)
	};

	/**
	 * All external templates along the rootline have to be loaded,
	 * this function retrieves the JSON code by comitting a AJAX request
	 */
	TsCodeCompletion.loadExtTemplatesAsync = function() {
		var id = TsCodeCompletion.getGetVar('id');
		if (id === '') {
			return;
		}
		$.ajax({
			url: TYPO3.settings.ajaxUrls['t3editor_codecompletion_loadtemplates'],
			data: {
				pageId: id
			},
			success: function(response) {
				TsCodeCompletion.extTsObjTree.c = response;
				TsCodeCompletion.resolveExtReferencesRec(TsCodeCompletion.extTsObjTree.c);
			}
		});
	};

	/**
	 * Load registered plugins
	 */
	TsCodeCompletion.loadPluginArray = function() {
		$.ajax({
			url: TYPO3.settings.ajaxUrls['t3editor_get_plugins'],
			success: function(response) {
				TsCodeCompletion.plugins = $.merge(TsCodeCompletion.plugins, response);
				// register an internal plugin
				TsCodeCompletion.loadPlugins();
			}
		});
	};

	/**
	 * Load and initialize the plugins fetched by TsCodeCompletion.loadPluginArray()
	 */
	TsCodeCompletion.loadPlugins = function() {
		for (var i = 0; i < TsCodeCompletion.plugins.length; i++) {
			require([TsCodeCompletion.plugins[i]], function(plugin) {
				if (typeof plugin.init === 'function') {
					plugin.init({
						tsRef: TsCodeCompletion.tsRef,
						codeCompleteBox: TsCodeCompletion.$codeCompleteBox,
						codemirror: TsCodeCompletion.codemirror
					});
				} else {
					console.warn('Cannot initialize plugin ' + TsCodeCompletion.plugins[i] + ', missing "init" method');
				}
			});
		}
	};

	/**
	 * Get the value of a given GET parameter
	 *
	 * @param {String} name
	 * @return {String}
	 */
	TsCodeCompletion.getGetVar = function(name) {
		var get_string = document.location.search,
			return_value = '',
			value;

		do { //This loop is made to catch all instances of any get variable.
			var name_index = get_string.indexOf(name + '=');
			if (name_index !== -1) {
				get_string = get_string.substr(name_index + name.length + 1, get_string.length - name_index);
				var end_of_value = get_string.indexOf('&');
				if (end_of_value !== -1) {
					value = get_string.substr(0, end_of_value);
				} else {
					value = get_string;
				}

				if (return_value === '' || value === '') {
					return_value += value;
				} else {
					return_value += ', ' + value;
				}
			}
		} while (name_index !== -1);

		// Restores all the blank spaces.
		var space = return_value.indexOf('+');
		while (space !== -1) {
			return_value = return_value.substr(0, space) + ' ' + return_value.substr(space + 1, return_value.length);
			space = return_value.indexOf('+');
		}

		return return_value;
	};

	/**
	 * Since the references are not resolved server side we have to do it client-side
	 * Benefit: less loading time due to less data which has to be transmitted
	 *
	 * @param {Array} childNodes
	 */
	TsCodeCompletion.resolveExtReferencesRec = function(childNodes) {
		for (var key in childNodes) {
			var childNode;
			// if the childnode has a value and there is a part of a reference operator ('<')
			// and it does not look like a html tag ('>')
			if (childNodes[key].v && childNodes[key].v[0] === '<' && childNodes[key].v.indexOf('>') === -1 ) {
				var path = $.trim(childNodes[key].v.replace(/</, ''));
				// if there are still whitespaces it's no path
				if (path.indexOf(' ') === -1) {
					childNode = TsCodeCompletion.getExtChildNode(path);
					// if the node was found - reference it
					if (childNode !== null) {
						childNodes[key] = childNode;
					}
				}
			}
			// if there was no reference-resolving then we go deeper into the tree
			if (!childNode && childNodes[key].c) {
				TsCodeCompletion.resolveExtReferencesRec(childNodes[key].c);
			}
		}
	};

	/**
	 * Get the child node of given path
	 *
	 * @param {String} path
	 * @returns {Object}
	 */
	TsCodeCompletion.getExtChildNode = function(path) {
		var extTree = TsCodeCompletion.extTsObjTree,
			path = path.split('.'),
			pathSeg;

		for (var i = 0; i < path.length; i++) {
			pathSeg = path[i];
			if (typeof extTree.c === 'undefined' || typeof extTree.c[pathSeg] === 'undefined') {
				return null;
			}
			extTree = extTree.c[pathSeg];
		}
		return extTree;
	};

	/**
	 * Replaces editor functions insertNewlineAtCursor and indentAtCursor
	 * with modified ones that only execute when codecompletion box is not shown
	 * @todo check if this works correctly after updating the codemirror base
	 */
	TsCodeCompletion.prepareLinefeeds = function() {
		TsCodeCompletion.codemirror.win.select.insertNewlineAtCursor_original = TsCodeCompletion.codemirror.win.select.insertNewlineAtCursor;
		TsCodeCompletion.codemirror.win.select.insertNewlineAtCursor = function(window) {
			if (TsCodeCompletion.cc === 0) {
				TsCodeCompletion.codemirror.win.select.insertNewlineAtCursor_original(window);
			}
		};
		TsCodeCompletion.codemirror.editor.indentAtCursor_original = TsCodeCompletion.codemirror.editor.indentAtCursor;
		TsCodeCompletion.codemirror.editor.indentAtCursor = function() {
			if (TsCodeCompletion.cc === 0) {
				TsCodeCompletion.codemirror.editor.indentAtCursor_original();
			}
		};
		TsCodeCompletion.linefeedsPrepared = true;
	};

	/**
	 *
	 * @param cursorNode
	 * @returns {*}
	 */
	TsCodeCompletion.getFilter = function(cursorNode) {
		if (cursorNode.currentText) {
			var filter = cursorNode.currentText.replace('.', '');
			return filter.replace(/\s/g, '');
		}
		return '';
	};

	/**
	 * @returns {*}
	 */
	TsCodeCompletion.getCursorNode = function() {
		var cursorNode = TsCodeCompletion.codemirror.win.select.selectionTopNode(TsCodeCompletion.codemirror.win.document.body, false);
		// cursorNode is null if the cursor is positioned at the beginning of the first line
		if (cursorNode === null) {
			cursorNode = TsCodeCompletion.codemirror.editor.container.firstChild;
		} else if (cursorNode.tagName === 'BR') {
			// if cursor is at the end of the line -> jump to beginning of the next line
			cursorNode = cursorNode.nextSibling;
		}
		return cursorNode;
	};

	/**
	 *
	 * @param {Object} cursor
	 * @returns {String}
	 */
	TsCodeCompletion.getCurrentLine = function(cursor) {
		var line = '',
			currentNode = cursor.start.node.parentNode;

		while (currentNode.tagName !== 'BR') {
			if (currentNode.hasChildNodes()
				&& currentNode.firstChild.nodeType === 3
				&& currentNode.currentText.length > 0)
			{
				line = currentNode.currentText + line;
			}
			if (typeof currentNode.previousSibling === 'undefined') {
				break;
			} else {
				currentNode = currentNode.previousSibling;
			}
		}
		return line;
	};

	/**
	 * Eventhandler function executed after keystroke release
	 * triggers CC on pressed dot and typing on
	 *
	 * @param {Event} e fired prototype event object
	 * @type void
	 */
	TsCodeCompletion.keyUp = function(e) {
		// 190 = .
		if (e.which === 190) {
			TsCodeCompletion.initCodeCompletion();
			TsCodeCompletion.refreshCodeCompletion();
		} else if (TsCodeCompletion.cc === 1) {
			// 38 = KEYUP, 40 = KEYDOWN
			if (e.which !== 40 && e.which !== 38) {
				if (e.which === 13) {
					// return
					e.preventDefault();
					if (TsCodeCompletion.currWord !== -1) {
						TsCodeCompletion.insertCurrWordAtCursor();
					}
					TsCodeCompletion.endAutoCompletion();
				} else {
					TsCodeCompletion.refreshCodeCompletion();
				}
			}
		}
	};

	TsCodeCompletion.scroll = function(e) {
		TsCodeCompletion.refreshCodeCompletionPosition();
	};

	/**
	 * Highlights entry in codecomplete box by id
	 *
	 * @param {Number} id
	 */
	TsCodeCompletion.mouseOver = function(id) {
		TsCodeCompletion.highlightCurrWord(id);
		for (var i = 0; i < TsCodeCompletion.plugins.length; i++) {
			require([TsCodeCompletion.plugins[i]], function(plugin) {
				if (typeof plugin.afterMouseOver === 'function') {
					plugin.afterMouseOver(TsCodeCompletion.proposals[TsCodeCompletion.currWord], TsCodeCompletion.compResult);
				}
			});
		}
	};

	/**
	 * Event handler function executed after clicking in the editor.
	 */
	TsCodeCompletion.click = function() {
		if (TsCodeCompletion.latestCursorNode !== TsCodeCompletion.getCursorNode()) {
			TsCodeCompletion.endAutoCompletion();
		}
	};

	/**
	 * Eventhandler function executed after keystroke release
	 * triggers CC on pressed dot and typing on
	 *
	 * @param {Event} e fired prototype event object
	 */
	TsCodeCompletion.keyDown = function(e) {
		if (!TsCodeCompletion.linefeedsPrepared) {
			TsCodeCompletion.prepareLinefeeds();
		}
		if (TsCodeCompletion.cc === 1) {
			if (e.which === 38) {
				// Arrow up: move up cursor in codecomplete box
				e.preventDefault();
				TsCodeCompletion.codeCompleteBoxMoveUpCursor();
				for (var i = 0; i < TsCodeCompletion.plugins.length; i++) {
					require([TsCodeCompletion.plugins[i]], function(plugin) {
						if (typeof plugin.afterKeyUp === 'function') {
							plugin.afterKeyUp(TsCodeCompletion.proposals[TsCodeCompletion.currWord], TsCodeCompletion.compResult);
						}
					});
				}
			} else if (e.which === 40) {
				// Arrow down: move down cursor in codecomplete box
				e.preventDefault();
				TsCodeCompletion.codeCompleteBoxMoveDownCursor();
				for (var i = 0; i < TsCodeCompletion.plugins.length; i++) {
					require([TsCodeCompletion.plugins[i]], function(plugin) {
						if (typeof plugin.afterKeyDown === 'function') {
							plugin.afterKeyDown(TsCodeCompletion.proposals[TsCodeCompletion.currWord], TsCodeCompletion.compResult);
						}
					});
				}
			} else if (e.which === 27 || e.which === 37 || e.which === 39) {
				// Esc, Arrow Left, Arrow Right: if codecomplete box is showing, hide it
				TsCodeCompletion.endAutoCompletion();
			} else if (e.which === 32 && (!e.ctrlKey || !e.metaKey)) {
				// space
				TsCodeCompletion.endAutoCompletion();
			} else if (e.which === 32 && (e.ctrlKey || e.metaKey)) {
				// CTRL + space
				TsCodeCompletion.refreshCodeCompletion();
			} else if (e.which === 8) {
				// backspace
				var cursorNode = TsCodeCompletion.codemirror.win.select.selectionTopNode(TsCodeCompletion.codemirror.win.document.body, false);
				if (cursorNode.innerHTML === '.') {
					// force full refresh at keyUp
					TsCodeCompletion.compResult = null;
				}
			}
		} else { // if autocompletion is deactivated and ctrl+space is pressed
			if (e.which === 32 && (e.ctrlKey || e.metaKey)) {
				e.preventDefault();
				TsCodeCompletion.initCodeCompletion();
				TsCodeCompletion.refreshCodeCompletion();
			}
		}
	};

	/**
	 * Initializes the code completion
	 */
	TsCodeCompletion.initCodeCompletion = function() {
		if (TsCodeCompletion.outerDiv.has(TsCodeCompletion.$codeCompleteBox).length === 0) {
			TsCodeCompletion.outerDiv.append(TsCodeCompletion.$codeCompleteBox);
		}
	};

	/**
	 * Refreshes the code completion list based on the cursor's position
	 */
	TsCodeCompletion.refreshCodeCompletion = function() {
		// init vars for up/down moving in word list
		TsCodeCompletion.cc_up = 0;
		TsCodeCompletion.cc_down = TsCodeCompletion.options.ccWords-1;

		// clear the last completion wordposition
		TsCodeCompletion.currWord = -1;
		TsCodeCompletion.codemirror.editor.highlightAtCursor();

		// retrieves the node right to the cursor
		TsCodeCompletion.currentCursorPosition = TsCodeCompletion.codemirror.win.select.markSelection(TsCodeCompletion.codemirror);
		TsCodeCompletion.latestCursorNode = TsCodeCompletion.getCursorNode();

		// the cursornode has to be stored cause inserted breaks have to be deleted after pressing enter if the codecompletion is active
		var filter = TsCodeCompletion.getFilter(TsCodeCompletion.latestCursorNode);

		if (TsCodeCompletion.compResult === null || TsCodeCompletion.latestCursorNode.innerHTML === '.') {
			// TODO: implement cases: operatorCompletion reference/copy path completion (formerly found in getCompletionResults())
			var currentTsTreeNode = TsCodeCompletion.parser.buildTsObjTree(TsCodeCompletion.codemirror.editor.container.firstChild, TsCodeCompletion.latestCursorNode);
			TsCodeCompletion.compResult = CompletionResult.init({
				tsRef: TsRef,
				tsTreeNode: currentTsTreeNode
			});
		}

		TsCodeCompletion.proposals = TsCodeCompletion.compResult.getFilteredProposals(filter);

		// if proposals are found - show box
		if (TsCodeCompletion.proposals.length > 0) {
			// TODO: Drop instance dependency
			var index = 0;
			// make UL list of completation proposals
			var $ul = $('<ul />');
			for (var i = 0; i < TsCodeCompletion.proposals.length; i++) {
				var $li = $('<li />', {id: 'cc_word_' + i}).data('item', i).append(
						$('<span />', {class: 'word_' + TsCodeCompletion.proposals[i].cssClass}).text(TsCodeCompletion.proposals[i].word)
					).on('click', function() {
						TsCodeCompletion.insertCurrWordAtCursor($(this).data('item'));
						TsCodeCompletion.endAutoCompletion();
					}).on('mouseover', function() {
						TsCodeCompletion.mouseOver($(this).data('item'));
					});

				$ul.append($li);
			}

			// put HTML and show box
			var $codeCompleteBox = TsCodeCompletion.$codeCompleteBox.find('.t3e_codeCompleteBox');
			$codeCompleteBox.html($ul);
			$codeCompleteBox.scrollTop(0);

			// init styles
			$codeCompleteBox.css({
				overflow: 'scroll',
				height: ((TsCodeCompletion.options.ccWords + 1) * $('#cc_word_0').height()) + 'px'
			});

			var wrapOffset = TsCodeCompletion.codemirror.options.originalTextarea.parent().find('.t3e_iframe_wrap').offset(),
				$cursorNode = $(TsCodeCompletion.latestCursorNode),
				nodeOffset = $cursorNode.offset();

			var leftpos = Math.round(wrapOffset.left + nodeOffset.left + TsCodeCompletion.latestCursorNode.offsetWidth - $(TsCodeCompletion.codemirror.frame.contentDocument.body).scrollLeft()) + 'px',
				toppos = Math.round($cursorNode.position().top + TsCodeCompletion.latestCursorNode.offsetHeight - $cursorNode.scrollTop() - $(TsCodeCompletion.codemirror.frame.contentDocument.body).scrollTop()) + 'px';

			TsCodeCompletion.$codeCompleteBox.css({
				left: leftpos,
				top: toppos
			});

			// set flag to 1 - needed for continue typing word.
			TsCodeCompletion.cc = 1;

			// highlight first word in list
			TsCodeCompletion.highlightCurrWord(0);
			for (var i = 0; i < TsCodeCompletion.plugins.length; i++) {
				require([TsCodeCompletion.plugins[i]], function(plugin) {
					if (typeof plugin.afterCCRefresh === 'function') {
						plugin.afterCCRefresh(TsCodeCompletion.proposals[TsCodeCompletion.currWord], TsCodeCompletion.compResult);
					}
				});
			}
		} else {
			TsCodeCompletion.endAutoCompletion();
		}
	};

	/**
	 * refresh the position of the completion list (after scroll)
	 */
	TsCodeCompletion.refreshCodeCompletionPosition = function() {
		if (TsCodeCompletion.proposals && TsCodeCompletion.proposals.length > 0) {
			var wrapOffset = TsCodeCompletion.codemirror.options.originalTextarea.parent().find('.t3e_iframe_wrap').offset(),
				$cursorNode = $(TsCodeCompletion.latestCursorNode),
				nodeOffset = $cursorNode.offset();

			var leftpos = Math.round(wrapOffset.left + nodeOffset.left + TsCodeCompletion.latestCursorNode.offsetWidth - $(TsCodeCompletion.codemirror.frame.contentDocument.body).scrollLeft()) + 'px',
				toppos = Math.round($cursorNode.position().top + TsCodeCompletion.latestCursorNode.offsetHeight - $cursorNode.scrollTop() - $(TsCodeCompletion.codemirror.frame.contentDocument.body).scrollTop()) + 'px';

			TsCodeCompletion.$codeCompleteBox.css({
				left: leftpos,
				top: toppos
			});
		}
	};

	/**
	 * Stop code completion and call hooks
	 */
	TsCodeCompletion.endAutoCompletion = function() {
		TsCodeCompletion.cc = 0;
		TsCodeCompletion.$codeCompleteBox.remove();
		// force full refresh
		TsCodeCompletion.compResult = null;
		for (var i = 0; i < TsCodeCompletion.plugins.length; i++) {
			require([TsCodeCompletion.plugins[i]], function(plugin) {
				if (typeof plugin.endCodeCompletion === 'function') {
					plugin.endCodeCompletion();
				}
			});
		}
	};

	/**
	 * Move cursor in autocomplete box up
	 */
	TsCodeCompletion.codeCompleteBoxMoveUpCursor = function() {
		var id;
		// if previous position was first or position not initialized - then move cursor to last word, else decrease position
		if (TsCodeCompletion.currWord === 0 || TsCodeCompletion.currWord === -1) {
			id = TsCodeCompletion.proposals.length - 1;
		} else {
			id = TsCodeCompletion.currWord - 1;
		}
		// hightlight new cursor position
		TsCodeCompletion.highlightCurrWord(id);
		// update id of first and last showing proposals and scroll box
		if (TsCodeCompletion.currWord < TsCodeCompletion.cc_up || TsCodeCompletion.currWord === (TsCodeCompletion.proposals.length - 1)) {
			TsCodeCompletion.cc_up = TsCodeCompletion.currWord;
			TsCodeCompletion.cc_down = TsCodeCompletion.currWord + (TsCodeCompletion.options.ccWords - 1);
			if (TsCodeCompletion.cc_up === (TsCodeCompletion.proposals.length - 1)) {
				TsCodeCompletion.cc_down = TsCodeCompletion.proposals.length - 1;
				TsCodeCompletion.cc_up = TsCodeCompletion.cc_down - (TsCodeCompletion.options.ccWords - 1);
			}
			TsCodeCompletion.$codeCompleteBox.find('.t3e_codeCompleteBox').scrollTop(TsCodeCompletion.cc_up * 18);
		}
	};

	/**
	 * Move cursor in codecomplete box down
	 */
	TsCodeCompletion.codeCompleteBoxMoveDownCursor = function() {
		var id;
		// if previous position was last word in list - then move cursor to first word if not than	position ++
		if (TsCodeCompletion.currWord === TsCodeCompletion.proposals.length - 1) {
			id = 0;
		} else {
			id = TsCodeCompletion.currWord + 1;
		}
		// highlight new cursor position
		TsCodeCompletion.highlightCurrWord(id);

		// update id of first and last showing proposals and scroll box
		if (TsCodeCompletion.currWord > TsCodeCompletion.cc_down || TsCodeCompletion.currWord === 0) {
			TsCodeCompletion.cc_down = TsCodeCompletion.currWord;
			TsCodeCompletion.cc_up = TsCodeCompletion.currWord - (TsCodeCompletion.options.ccWords - 1);
			if (TsCodeCompletion.cc_down == 0) {
				TsCodeCompletion.cc_up = 0;
				TsCodeCompletion.cc_down = TsCodeCompletion.options.ccWords - 1;
			}
			TsCodeCompletion.$codeCompleteBox.find('.t3e_codeCompleteBox').scrollTop(TsCodeCompletion.cc_up * 18);
		}
	};

	/**
	 * Highlight the active word in the code completion list
	 *
	 * @param {Number} id
	 */
	TsCodeCompletion.highlightCurrWord = function(id) {
		if (TsCodeCompletion.currWord !== -1) {
			$('#cc_word_' + TsCodeCompletion.currWord).removeClass('active');
		}
		$('#cc_word_' + id).addClass('active');
		TsCodeCompletion.currWord = id;
	};

	/**
	 * Insert selected word into text from codecompletebox
	 */
	TsCodeCompletion.insertCurrWordAtCursor = function() {
		var word = TsCodeCompletion.proposals[TsCodeCompletion.currWord].word;
		// tokenize current line
		TsCodeCompletion.codemirror.editor.highlightAtCursor();
		var select = TsCodeCompletion.codemirror.win.select;
		var cursorNode = TsCodeCompletion.getCursorNode();

		if (cursorNode.currentText
			&& cursorNode.currentText !== '.'
			&& $.trim(cursorNode.currentText) !== '' ) {
			// if there is some typed text already, left to the "." -> simply replace node content with the word
			cursorNode.innerHTML = word;
			cursorNode.currentText = word;
			select.setCursorPos(TsCodeCompletion.codemirror.editor.container, {node: cursorNode, offset: 0});
		} else { // if there is no text there, insert the word at the cursor position
			TsCodeCompletion.codemirror.replaceSelection(word);
		}
	};

	/**
	 * Save the mouse position
	 *
	 * @param {Event} e
	 */
	TsCodeCompletion.saveMousePos = function(e) {
		TsCodeCompletion.mousePos.x = e.clientX;
		TsCodeCompletion.mousePos.y = e.clientY;
	};

	$(document).on('t3editor:init', function(e, codemirror, $outerDiv) {
		TsCodeCompletion.codemirror = codemirror;
		TsCodeCompletion.outerDiv = $outerDiv;

		TsCodeCompletion.parser = TsParser.init(TsCodeCompletion.tsRef, TsCodeCompletion.extTsObjTree);
		TsCodeCompletion.tsRef.loadTsrefAsync();

		$(codemirror.win)
			.on('click', TsCodeCompletion.click)
			.on('scroll', TsCodeCompletion.scroll)
			.on('mousemove', TsCodeCompletion.saveMousePos)
			.on('keydown', function(e) {
				TsCodeCompletion.codemirror = codemirror;
				TsCodeCompletion.outerDiv = $outerDiv;
				TsCodeCompletion.keyDown(e);
			})
			.on('keyup', function(e) {
				TsCodeCompletion.codemirror = codemirror;
				TsCodeCompletion.outerDiv = $outerDiv;
				TsCodeCompletion.keyUp(e);
			});

		TsCodeCompletion.loadExtTemplatesAsync();
		TsCodeCompletion.loadPluginArray();
	});

	return TsCodeCompletion;
});
