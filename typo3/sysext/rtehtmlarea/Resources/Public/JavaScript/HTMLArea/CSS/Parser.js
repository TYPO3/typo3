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
 * Module: TYPO3/CMS/Rtehtmlarea/HTMLArea/CSS/Parser
 * HTMLArea.CSS.Parser: CSS Parser
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event'],
	function (UserAgent, Util, Event) {

	/**
	 * Parser constructor
	 *
	 * @param config
	 * @constructor
	 * @exports TYPO3/CMS/Rtehtmlarea/HTMLArea/CSS/Parser
	 */
	var Parser = function (config) {
		var configDefaults = {
			parseAttemptsMaximumNumber: 20,
			prefixLabelWithClassName: false,
			postfixLabelWithClassName: false,
			showTagFreeClasses: false,
			tags: null,
			editor: null
		};
		// config.editor MUST be set!
		Util.apply(this, config, configDefaults);
		if (this.editor.config.styleSheetsMaximumAttempts) {
			this.parseAttemptsMaximumNumber = this.editor.config.styleSheetsMaximumAttempts;
		}

		/**
		 * The parsed classes
		 */
		this.parsedClasses = {};

		/**
		 * Boolean indicating whether are not parsing is complete
		 */
		this.ready = false;

		/**
		 * Boolean indicating whether or not the stylesheets were accessible
		 */
		this.cssLoaded = false;

		/**
		 * Counter of the number of attempts at parsing the stylesheets
		 */
		this.parseAttemptsCounter = 0;

		/**
		 * Parsing attempt timeout id
		 */
		this.attemptTimeout = null;

		/**
		 * The error that occurred on the last attempt at parsing the stylesheets
		 */
		this.error = null;
	};

	/**
	 * This function gets the parsed css classes
	 *
	 * @return object this.parsedClasses
	 */
	Parser.prototype.getClasses = function() {
		return this.parsedClasses;
	};

	/**
	 * This function gets the ready state
	 *
	 * @return bool this.ready
	 */
	Parser.prototype.isReady = function() {
		return this.ready;
	};

	/**
	 * This function parses the stylesheets of the iframe set in config
	 *
	 * @return void	parsed css classes are accumulated in this.parsedClasses
	 */
	Parser.prototype.parse = function() {
		if (this.editor.document) {
			var self = this;
			if (!this.editor.classesConfigurationIsLoaded()) {
				this.attemptTimeout = window.setTimeout(function () {
					self.parse();
				}, 100);
			} else {
				this.parseStyleSheets();
				if (!this.cssLoaded) {
					if (/Security/i.test(this.error)) {
						this.editor.appendToLog('HTMLArea.CSS.Parser', 'parse', 'A security error occurred. Make sure all stylesheets are accessed from the same domain/subdomain and using the same protocol as the current script.', 'error');
						/**
						 * @event HTMLAreaEventCssParsingComplete
						 * Fires when parsing of the stylesheets of the iframe is complete
						 */
						Event.trigger(this, 'HTMLAreaEventCssParsingComplete');
					} else if (this.parseAttemptsCounter < this.parseAttemptsMaximumNumber) {
						this.parseAttemptsCounter++;
						this.attemptTimeout = window.setTimeout(function () {
							self.parse();
						}, 200);
					} else {
						this.editor.appendToLog('HTMLArea.CSS.Parser', 'parse', 'The stylesheets could not be parsed. Reported error: ' + this.error, 'error');
						/**
						 * @event HTMLAreaEventCssParsingComplete
						 * Fires when parsing of the stylesheets of the iframe is complete
						 */
						Event.trigger(this, 'HTMLAreaEventCssParsingComplete');
					}
				} else {
					if (this.attemptTimeout) {
						window.clearTimeout(this.attemptTimeout);
					}
					this.ready = true;
					this.filterAllowedClasses();
					this.sort();
					/**
					 * @event HTMLAreaEventCssParsingComplete
					 * Fires when parsing of the stylesheets of the iframe is complete
					 */
					Event.trigger(this, 'HTMLAreaEventCssParsingComplete');
				}
			}
		}
	};

	/**
	 * This function parses the stylesheets of an iframe
	 *
	 * @return void	parsed css classes are accumulated in this.parsedClasses
	 */
	Parser.prototype.parseStyleSheets = function () {
		this.cssLoaded = true;
		this.error = null;
		// Test if the styleSheets array is at all accessible
		if (UserAgent.isOpera) {
			if (this.editor.document.readyState !== 'complete') {
				this.cssLoaded = false;
				this.error = 'Document.readyState not complete';
			}
		} else {
			try {
				this.editor.document.styleSheets && this.editor.document.styleSheets[0] && this.editor.document.styleSheets[0].rules;
			} catch(e) {
				this.cssLoaded = false;
				this.error = e;
			}
		}
		if (this.cssLoaded) {
			// Expecting at least 2 stylesheets...
			if (this.editor.document.styleSheets.length > 1) {
				var styleSheets = this.editor.document.styleSheets;
				for (var index = 0, n = styleSheets.length; index < n; index++) {
					try {
						var styleSheet = styleSheets[index];
						if (!UserAgent.isIE || styleSheet.cssRules.length) {
							this.parseRules(styleSheet.cssRules);
						} else {
							this.cssLoaded = false;
							this.parsedClasses = {};
							break;
						}
					} catch (e) {
						this.error = e;
						this.cssLoaded = false;
						this.parsedClasses = {};
						break;
					}
				}
			} else {
				this.cssLoaded = false;
				this.error = 'Empty stylesheets array or missing linked stylesheets';
			}
		}
	};

	/**
	 * This function parses the set of rules from a standard stylesheet
	 *
	 * @param array cssRules: the array of rules of a stylesheet
	 * @return void
	 */
	Parser.prototype.parseRules = function (cssRules) {
		for (var rule = 0, n = cssRules.length; rule < n; rule++) {
			// Style rule
			if (cssRules[rule].selectorText) {
				this.parseSelectorText(cssRules[rule].selectorText);
			} else {
				// Import rule
				try {
					if (cssRules[rule].styleSheet && cssRules[rule].styleSheet.cssRules) {
						this.parseRules(cssRules[rule].styleSheet.cssRules);
					}
				} catch (e) {
					if (/Security/i.test(e)) {
						// If this is a security error, silently log the error and continue parsing
						this.editor.appendToLog('HTMLArea.CSS.Parser', 'parseRules', 'A security error occurred. Make sure all stylesheets are accessed from the same domain/subdomain and using the same protocol as the current script.', 'error');
					} else {
						throw e;
					}
				}
				// Media rule
				if (cssRules[rule].cssRules) {
					this.parseRules(cssRules[rule].cssRules);
				}
			}
		}
	};

	/**
	 * This function parses the set of rules from an IE stylesheet
	 *
	 * @param array cssRules: the array of rules of a stylesheet
	 * @return void
	 */
	Parser.prototype.parseIeRules = function (cssRules) {
		for (var rule = 0, n = cssRules.length; rule < n; rule++) {
				// Import rule
			if (cssRules[rule].imports) {
				this.parseIeRules(cssRules[rule].imports);
			}
				// Style rule
			if (cssRules[rule].rules) {
				this.parseRules(cssRules[rule].rules);
			}
		}
	};

	/**
	 * This function parses a selector rule
	 *
	 * @param string selectorText: the text of the rule to parsed
	 * @return void
	 */
	Parser.prototype.parseSelectorText = function (selectorText) {
		var cssElements = [],
			cssElement = [],
			nodeName, className,
			pattern = /(\S*)\.(\S+)/;
		if (selectorText.search(/:+/) == -1) {
				// Split equal styles
			cssElements = selectorText.split(',');
			for (var k = 0, n = cssElements.length; k < n; k++) {
					// Match all classes (<element name (optional)>.<class name>) in selector rule
				var s = cssElements[k], index;
				while ((index = s.search(pattern)) > -1) {
					var match = pattern.exec(s.substring(index));
					s = s.substring(index+match[0].length);
					nodeName = (match[1] && (match[1] != '*')) ? match[1].toLowerCase().trim() : 'all';
					className = match[2];
					if (className && !HTMLArea.reservedClassNames.test(className)) {
						if (((nodeName != 'all') && (!this.tags || !this.tags[nodeName]))
							|| ((nodeName == 'all') && (!this.tags || !this.tags[nodeName]) && this.showTagFreeClasses)
							|| (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses && this.tags[nodeName].allowedClasses.test(className))) {
							if (!this.parsedClasses[nodeName]) {
								this.parsedClasses[nodeName] = {};
							}
							cssName = className;
							if (HTMLArea.classesLabels && HTMLArea.classesLabels[className]) {
								cssName = this.prefixLabelWithClassName ? (className + ' - ' + HTMLArea.classesLabels[className]) : HTMLArea.classesLabels[className];
								cssName = this.postfixLabelWithClassName ? (cssName + ' - ' + className) : cssName;
							}
							this.parsedClasses[nodeName][className] = cssName;
						}
					}
				}
			}
		}
	};

	/**
	 * This function filters the class selectors allowed for each nodeName
	 *
	 * @return void
	 */
	Parser.prototype.filterAllowedClasses = function() {
		var nodeName, cssClass;
		for (nodeName in this.tags) {
			var allowedClasses = {};
			// Get classes allowed for all tags
			if (nodeName !== 'all' && typeof this.parsedClasses['all'] !== 'undefined') {
				if (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses) {
					var allowed = this.tags[nodeName].allowedClasses;
					for (cssClass in this.parsedClasses['all']) {
						if (allowed.test(cssClass)) {
							allowedClasses[cssClass] = this.parsedClasses['all'][cssClass];
						}
					}
				} else {
					allowedClasses = this.parsedClasses['all'];
				}
			}
			// Merge classes allowed for nodeName
			if (typeof this.parsedClasses[nodeName] !== 'undefined') {
				if (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses) {
					var allowed = this.tags[nodeName].allowedClasses;
					for (cssClass in this.parsedClasses[nodeName]) {
						if (allowed.test(cssClass)) {
							allowedClasses[cssClass] = this.parsedClasses[nodeName][cssClass];
						}
					}
				} else {
					for (cssClass in this.parsedClasses[nodeName]) {
						allowedClasses[cssClass] = this.parsedClasses[nodeName][cssClass];
					}
				}
			}
			this.parsedClasses[nodeName] = allowedClasses;
		}
		// If showTagFreeClasses is set and there is no allowedClasses clause on a tag, merge classes allowed for all tags
		if (this.showTagFreeClasses && typeof this.parsedClasses['all'] !== 'undefined') {
			for (nodeName in this.parsedClasses) {
				if (nodeName !== 'all' && !this.tags[nodeName]) {
					for (cssClass in this.parsedClasses['all']) {
						this.parsedClasses[nodeName][cssClass] = this.parsedClasses['all'][cssClass];
					}
				}
			}
		}
	};

	/**
	 * This function sorts the class selectors for each nodeName
	 *
	 * @return void
	 */
	Parser.prototype.sort = function() {
		var nodeName, cssClass, i, n, x, y;
		for (nodeName in this.parsedClasses) {
			var value = this.parsedClasses[nodeName];
			var classes = [];
			var sortedClasses = {};
			// Collect keys
			for (cssClass in value) {
				classes.push(cssClass);
			}
			function compare(a, b) {
				x = value[a];
				y = value[b];
				return ((x < y) ? -1 : ((x > y) ? 1 : 0));
			}
			// Sort keys by comparing texts
			classes = classes.sort(compare);
			for (i = 0, n = classes.length; i < n; ++i) {
				sortedClasses[classes[i]] = value[classes[i]];
			}
			this.parsedClasses[nodeName] = sortedClasses;
		}
	};

	return Parser;

});
