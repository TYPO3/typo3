/**
 * plupload.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

// JSLint defined globals
/*global window:false, escape:false */

(function() {
	var count = 0, runtimes = [], i18n = {}, mimes = {},
		xmlEncodeChars = {'<' : 'lt', '>' : 'gt', '&' : 'amp', '"' : 'quot', '\'' : '#39'},
		xmlEncodeRegExp = /[<>&\"\']/g, undef, delay = window.setTimeout;

	// IE W3C like event funcs
	function preventDefault() {
		this.returnValue = false;
	}

	function stopPropagation() {
		this.cancelBubble = true;
	}

	// Parses the default mime types string into a mimes lookup map
	(function(mime_data) {
		var items = mime_data.split(/,/), i, y, ext;

		for (i = 0; i < items.length; i += 2) {
			ext = items[i + 1].split(/ /);

			for (y = 0; y < ext.length; y++) {
				mimes[ext[y]] = items[i];
			}
		}
	})(
		"application/msword,doc dot," +
		"application/pdf,pdf," +
		"application/pgp-signature,pgp," +
		"application/postscript,ps ai eps," +
		"application/rtf,rtf," +
		"application/vnd.ms-excel,xls xlb," +
		"application/vnd.ms-powerpoint,ppt pps pot," +
		"application/zip,zip," +
		"application/x-shockwave-flash,swf swfl," +
		"application/vnd.openxmlformats,docx pptx xlsx," +
		"audio/mpeg,mpga mpega mp2 mp3," +
		"audio/x-wav,wav," +
		"image/bmp,bmp," +
		"image/gif,gif," +
		"image/jpeg,jpeg jpg jpe," +
		"image/png,png," +
		"image/svg+xml,svg svgz," +
		"image/tiff,tiff tif," +
		"text/html,htm html xhtml," +
		"text/rtf,rtf," +
		"video/mpeg,mpeg mpg mpe," +
		"video/quicktime,qt mov," +
		"video/x-flv,flv," +
		"video/vnd.rn-realvideo,rv," +
		"text/plain,asc txt text diff log," +
		"application/octet-stream,exe"
	);

	/**
	 * Plupload class with some global constants and functions.
	 *
	 * @example
	 * // Encode entities
	 * console.log(plupload.xmlEncode("My string &lt;&gt;"));
	 *
	 * // Generate unique id
	 * console.log(plupload.guid());
	 *
	 * @static
	 * @class plupload
	 */
	var plupload = {
		/**
		 * Inital state of the queue and also the state ones it's finished all it's uploads.
		 *
		 * @property STOPPED
		 * @final
		 */
		STOPPED : 1,

		/**
		 * Upload process is running
		 *
		 * @property STARTED
		 * @final
		 */
		STARTED : 2,

		/**
		 * File is queued for upload
		 *
		 * @property QUEUED
		 * @final
		 */
		QUEUED : 1,

		/**
		 * File is being uploaded
		 *
		 * @property UPLOADING
		 * @final
		 */
		UPLOADING : 2,

		/**
		 * File has failed to be uploaded
		 *
		 * @property FAILED
		 * @final
		 */
		FAILED : 4,

		/**
		 * File has been uploaded successfully
		 *
		 * @property DONE
		 * @final
		 */
		DONE : 5,

		// Error constants used by the Error event

		/**
		 * Generic error for example if an exception is thrown inside Silverlight.
		 *
		 * @property GENERIC_ERROR
		 * @final
		 */
		GENERIC_ERROR : -100,

		/**
		 * HTTP transport error. For example if the server produces a HTTP status other than 200.
		 *
		 * @property HTTP_ERROR
		 * @final
		 */
		HTTP_ERROR : -200,

		/**
		 * Generic I/O error. For exampe if it wasn't possible to open the file stream on local machine.
		 *
		 * @property IO_ERROR
		 * @final
		 */
		IO_ERROR : -300,

		/**
		 * Generic I/O error. For exampe if it wasn't possible to open the file stream on local machine.
		 *
		 * @property SECURITY_ERROR
		 * @final
		 */
		SECURITY_ERROR : -400,

		/**
		 * Initialization error. Will be triggered if no runtime was initialized.
		 *
		 * @property INIT_ERROR
		 * @final
		 */
		INIT_ERROR : -500,

		/**
		 * File size error. If the user selects a file that is to large it will be blocked and an error of this type will be triggered.
		 *
		 * @property FILE_SIZE_ERROR
		 * @final
		 */
		FILE_SIZE_ERROR : -600,

		/**
		 * File extension error. If the user selects a file that isn't valid according to the filters setting.
		 *
		 * @property FILE_EXTENSION_ERROR
		 * @final
		 */
		FILE_EXTENSION_ERROR : -700,

		/**
		 * Mime type lookup table.
		 *
		 * @property mimeTypes
		 * @type Object
		 * @final
		 */
		mimeTypes : mimes,

		/**
		 * Extends the specified object with another object.
		 *
		 * @method extend
		 * @param {Object} target Object to extend.
		 * @param {Object..} obj Multiple objects to extend with.
		 * @return {Object} Same as target, the extended object.
		 */
		extend : function(target) {
			plupload.each(arguments, function(arg, i) {
				if (i > 0) {
					plupload.each(arg, function(value, key) {
						target[key] = value;
					});
				}
			});

			return target;
		},

		/**
		 * Cleans the specified name from national characters (diacritics). The result will be a name with only a-z, 0-9 and _.
		 *
		 * @method cleanName
		 * @param {String} s String to clean up.
		 * @return {String} Cleaned string.
		 */
		cleanName : function(name) {
			var i, lookup;

			// Replace diacritics
			lookup = [
				/[\300-\306]/g, 'A', /[\340-\346]/g, 'a',
				/\307/g, 'C', /\347/g, 'c',
				/[\310-\313]/g, 'E', /[\350-\353]/g, 'e',
				/[\314-\317]/g, 'I', /[\354-\357]/g, 'i',
				/\321/g, 'N', /\361/g, 'n',
				/[\322-\330]/g, 'O', /[\362-\370]/g, 'o',
				/[\331-\334]/g, 'U', /[\371-\374]/g, 'u'
			];

			for (i = 0; i < lookup.length; i += 2) {
				name = name.replace(lookup[i], lookup[i + 1]);
			}

			// Replace whitespace
			name = name.replace(/\s+/g, '_');

			// Remove anything else
			name = name.replace(/[^a-z0-9_\-\.]+/gi, '');

			return name;
		},

		/**
		 * Adds a specific upload runtime like for example flash or gears.
		 *
		 * @method addRuntime
		 * @param {String} name Runtime name for example flash.
		 * @param {Object} obj Object containing init/destroy method.
		 */
		addRuntime : function(name, runtime) {
			runtime.name = name;
			runtimes[name] = runtime;
			runtimes.push(runtime);

			return runtime;
		},

		/**
		 * Generates an unique ID. This is 99.99% unique since it takes the current time and 5 random numbers.
		 * The only way a user would be able to get the same ID is if the two persons at the same exact milisecond manages
		 * to get 5 the same random numbers between 0-65535 it also uses a counter so each call will be guaranteed to be page unique.
		 * It's more probable for the earth to be hit with an ansteriod. You can also if you want to be 100% sure set the plupload.guidPrefix property
		 * to an user unique key.
		 *
		 * @method guid
		 * @return {String} Virtually unique id.
		 */
		guid : function() {
			var guid = new Date().getTime().toString(32), i;

			for (i = 0; i < 5; i++) {
				guid += Math.floor(Math.random() * 65535).toString(32);
			}

			return (plupload.guidPrefix || 'p') + guid + (count++).toString(32);
		},

		/**
		 * Builds a full url out of a base URL and an object with items to append as query string items.
		 *
		 * @param {String} url Base URL to append query string items to.
		 * @param {Object} items Name/value object to serialize as a querystring.
		 * @return {String} String with url + serialized query string items.
		 */
		buildUrl : function(url, items) {
			var query = '';

			plupload.each(items, function(value, name) {
				query += (query ? '&' : '') + encodeURIComponent(name) + '=' + encodeURIComponent(value);
			});

			if (query) {
				url += (url.indexOf('?') > 0 ? '&' : '?') + query;
			}

			return url;
		},

		/**
		 * Executes the callback function for each item in array/object. If you return false in the
		 * callback it will break the loop.
		 *
		 * @param {Object} obj Object to iterate.
		 * @param {function} callback Callback function to execute for each item.
		 */
		each : function(obj, callback) {
			var length, key, i;

			if (obj) {
				length = obj.length;

				if (length === undef) {
					// Loop object items
					for (key in obj) {
						if (obj.hasOwnProperty(key)) {
							if (callback(obj[key], key) === false) {
								return;
							}
						}
					}
				} else {
					// Loop array items
					for (i = 0; i < length; i++) {
						if (callback(obj[i], i) === false) {
							return;
						}
					}
				}
			}
		},

		/**
		 * Formats the specified number as a size string for example 1024 becomes 1 KB.
		 *
		 * @method formatSize
		 * @param {Number} size Size to format as string.
		 * @return {String} Formatted size string.
		 */
		formatSize : function(size) {
			if (size === undef) {
				return plupload.translate('N/A');
			}

			// MB
			if (size > 1048576) {
				return Math.round(size / 1048576, 1) + " MB";
			}

			// KB
			if (size > 1024) {
				return Math.round(size / 1024, 1) + " KB";
			}

			return size + " b";
		},

		/**
		 * Returns the absolute x, y position of an Element. The position will be returned in a object with x, y fields.
		 *
		 * @method getPos
		 * @param {Element} node HTML element or element id to get x, y position from.
		 * @param {Element} root Optional root element to stop calculations at.
		 * @return {object} Absolute position of the specified element object with x, y fields.
		 */
		 getPos : function(node, root) {
			var x = 0, y = 0, parent, doc = document, nodeRect, rootRect;

			node = node;
			root = root || doc.body;

			// Returns the x, y cordinate for an element on IE 6 and IE 7
			function getIEPos(node) {
				var bodyElm, rect, x = 0, y = 0;

				if (node) {
					rect = node.getBoundingClientRect();
					bodyElm = doc.compatMode === "CSS1Compat" ? doc.documentElement : doc.body;
					x = rect.left + bodyElm.scrollLeft;
					y = rect.top + bodyElm.scrollTop;
				}

				return {
					x : x,
					y : y
				};
			}

			// Use getBoundingClientRect on IE 6 and IE 7 but not on IE 8 in standards mode
			if (node.getBoundingClientRect && (navigator.userAgent.indexOf('MSIE') > 0 && doc.documentMode !== 8)) {
				nodeRect = getIEPos(node);
				rootRect = getIEPos(root);

				return {
					x : nodeRect.x - rootRect.x,
					y : nodeRect.y - rootRect.y
				};
			}

			parent = node;
			while (parent && parent != root && parent.nodeType) {
				x += parent.offsetLeft || 0;
				y += parent.offsetTop || 0;
				parent = parent.offsetParent;
			}

			parent = node.parentNode;
			while (parent && parent != root && parent.nodeType) {
				x -= parent.scrollLeft || 0;
				y -= parent.scrollTop || 0;
				parent = parent.parentNode;
			}

			return {
				x : x,
				y : y
			};
		},

		/**
		 * Returns the size of the specified node in pixels.
		 *
		 * @param {Node} node Node to get the size of.
		 * @return {Object} Object with a w and h property.
		 */
		getSize : function(node) {
			return {
				w : node.clientWidth || node.offsetWidth,
				h : node.clientHeight || node.offsetHeight
			};
		},

		/**
		 * Parses the specified size string into a byte value. For example 10kb becomes 10240.
		 *
		 * @method parseSize
		 * @param {String/Number} size String to parse or number to just pass through.
		 * @return {Number} Size in bytes.
		 */
		parseSize : function(size) {
			var mul;

			if (typeof(size) == 'string') {
				size = /^([0-9]+)([mgk]+)$/.exec(size.toLowerCase().replace(/[^0-9mkg]/g, ''));
				mul = size[2];
				size = +size[1];

				if (mul == 'g') {
					size *= 1073741824;
				}

				if (mul == 'm') {
					size *= 1048576;
				}

				if (mul == 'k') {
					size *= 1024;
				}
			}

			return size;
		},

		/**
		 * Encodes the specified string.
		 *
		 * @method xmlEncode
		 * @param {String} s String to encode.
		 * @return {String} Encoded string.
		 */
		xmlEncode : function(str) {
			return str ? ('' + str).replace(xmlEncodeRegExp, function(chr) {
				return xmlEncodeChars[chr] ? '&' + xmlEncodeChars[chr] + ';' : chr;
			}) : str;
		},

		/**
		 * Forces anything into an array.
		 *
		 * @method toArray
		 * @param {Object} obj Object with length field.
		 * @return {Array} Array object containing all items.
		 */
		toArray : function(obj) {
			var i, arr = [];

			for (i = 0; i < obj.length; i++) {
				arr[i] = obj[i];
			}

			return arr;
		},

		/**
		 * Extends the language pack object with new items.
		 *
		 * @param {Object} pack Language pack items to add.
		 * @return {Object} Extended language pack object.
		 */
		addI18n : function(pack) {
			return plupload.extend(i18n, pack);
		},

		/**
		 * Translates the specified string by checking for the english string in the language pack lookup.
		 *
		 * @param {String} str String to look for.
		 * @reutrn {String} Translated string or the input string if it wasn't found.
		 */
		translate : function(str) {
			return i18n[str] || str;
		},

		/**
		 * Adds an event handler to the specified object.
		 *
		 * @param {Object} obj DOM element like object to add handler to.
		 * @param {String} name Name to add event listener to.
		 * @param {function} callback Function to call when event occurs.
		 */
		addEvent : function(obj, name, callback) {
			if (obj.attachEvent) {
				obj.attachEvent('on' + name, function() {
					var evt = window.event;

					if (!evt.target) {
						evt.target = evt.srcElement;
					}

					evt.preventDefault = preventDefault;
					evt.stopPropagation = stopPropagation;

					callback(evt);
				});
			} else if (obj.addEventListener) {
				obj.addEventListener(name, callback, false);
			}
		}
	};

	/**
	 * Uploader class, an instance of this class will be created for each upload field.
	 *
	 * @example
	 * var uploader = new plupload.Uploader({
	 *     runtimes : 'gears,html5,flash',
	 *     browse_button : 'button_id'
	 * });
	 *
	 * uploader.bind('Init', function(up) {
	 *     alert('Supports drag/drop: ' + (!!up.features.dragdrop));
	 * });
	 *
	 * uploader.bind('FilesAdded', function(up, files) {
	 *     alert('Selected files: ' + files.length);
	 * });
	 *
	 * uploader.bind('QueueChanged', function(up) {
	 *     alert('Queued files: ' + uploader.files.length);
	 * });
	 *
	 * uploader.init();
	 *
	 * @class plupload.Uploader
	 */

	/**
	 * Constructs a new uploader instance.
	 *
	 * @constructor
	 * @method Uploader
	 * @param {Object} settings Initialization settings, to be used by the uploader instance and runtimes.
	 */
	plupload.Uploader = function(settings) {
		var events = {}, total, files = [], fileIndex, startTime;

		// Inital total state
		total = new plupload.QueueProgress();

		// Default settings
		settings = plupload.extend({
			chunk_size : 0,
			multipart : true,
			multi_selection : true,
			file_data_name : 'file',
			filters : []
		}, settings);

		// Private methods
		function uploadNext() {
			var file;

			if (this.state == plupload.STARTED && fileIndex < files.length) {
				file = files[fileIndex++];

				if (file.status == plupload.QUEUED) {
					this.trigger('BeforeUpload', file);
					this.trigger("UploadFile", file);
				} else {
					uploadNext.call(this);
				}
			} else {
				this.stop();
			}
		}

		function calc() {
			var i, file;

			// Reset stats
			total.reset();

			// Check status, size, loaded etc on all files
			for (i = 0; i < files.length; i++) {
				file = files[i];

				if (file.size !== undef) {
					total.size += file.size;
					total.loaded += file.loaded;
				} else {
					total.size = undef;
				}

				if (file.status == plupload.DONE) {
					total.uploaded++;
				} else if (file.status == plupload.FAILED) {
					total.failed++;
				} else {
					total.queued++;
				}
			}

			// If we couldn't calculate a total file size then use the number of files to calc percent
			if (total.size === undef) {
				total.percent = files.length > 0 ? Math.ceil(total.uploaded / files.length * 100) : 0;
			} else {
				total.bytesPerSec = Math.ceil(total.loaded / ((+new Date() - startTime || 1) / 1000.0));
				total.percent = total.size > 0 ? Math.ceil(total.loaded / total.size * 100) : 0;
			}
		}

		// Add public methods
		plupload.extend(this, {
			/**
			 * Current state of the total uploading progress. This one can either be plupload.STARTED or plupload.STOPPED.
			 * These states are controlled by the stop/start methods. The default value is STOPPED.
			 *
			 * @property state
			 * @type Number
			 */
			state : plupload.STOPPED,

			/**
			 * Map of features that are available for the uploader runtime. Features will be filled
			 * before the init event is called, these features can then be used to alter the UI for the end user.
			 * Some of the current features that might be in this map is: dragdrop, chunks, jpgresize, pngresize.
			 *
			 * @property features
			 * @type Object
			 */
			features : {},

			/**
			 * Current upload queue, an array of File instances.
			 *
			 * @property files
			 * @type Array
			 * @see plupload.File
			 */
			files : files,

			/**
			 * Object with name/value settings.
			 *
			 * @property settings
			 * @type Object
			 */
			settings : settings,

			/**
			 * Total progess information. How many files has been uploaded, total percent etc.
			 *
			 * @property total
			 * @type plupload.QueueProgress
			 */
			total : total,

			/**
			 * Unique id for the Uploader instance.
			 *
			 * @property id
			 * @type String
			 */
			id : plupload.guid(),

			/**
			 * Initializes the Uploader instance and adds internal event listeners.
			 *
			 * @method init
			 */
			init : function() {
				var self = this, i, runtimeList, a, runTimeIndex = 0, items;

				if (typeof(settings.preinit) == "function") {
					settings.preinit(self);
				} else {
					plupload.each(settings.preinit, function(func, name) {
						self.bind(name, func);
					});
				}

				settings.page_url = settings.page_url || document.location.pathname.replace(/\/[^\/]+$/g, '/');

				// If url is relative force it absolute to the current page
				if (!/^(\w+:\/\/|\/)/.test(settings.url)) {
					settings.url = settings.page_url + settings.url;
				}

				// Convert settings
				settings.chunk_size = plupload.parseSize(settings.chunk_size);
				settings.max_file_size = plupload.parseSize(settings.max_file_size);

				// Add files to queue
				self.bind('FilesAdded', function(up, selected_files) {
					var i, file, count = 0, extensionsRegExp, filters = settings.filters;

					// Convert extensions to regexp
					if (filters && filters.length) {
						extensionsRegExp = [];
						
						plupload.each(filters, function(filter) {
							plupload.each(filter.extensions.split(/,/), function(ext) {
								extensionsRegExp.push('\\.' + ext.replace(new RegExp('[' + ('/^$.*+?|()[]{}\\'.replace(/./g, '\\$&')) + ']', 'g'), '\\$&'));
							});
						});

						extensionsRegExp = new RegExp(extensionsRegExp.join('|') + '$', 'i');
					}

					for (i = 0; i < selected_files.length; i++) {
						file = selected_files[i];
						file.loaded = 0;
						file.percent = 0;
						file.status = plupload.QUEUED;

						// Invalid file extension
						if (extensionsRegExp && !extensionsRegExp.test(file.name)) {
							up.trigger('Error', {
								code : plupload.FILE_EXTENSION_ERROR,
								message : 'File extension error.',
								file : file
							});

							continue;
						}

						// Invalid file size
						if (file.size !== undef && file.size > settings.max_file_size) {
							up.trigger('Error', {
								code : plupload.FILE_SIZE_ERROR,
								message : 'File size error.',
								file : file
							});

							continue;
						}

						// Add valid file to list
						files.push(file);
						count++;
					}

					// Only trigger QueueChanged event if any files where added
					if (count) {
						delay(function() {
							self.trigger("QueueChanged");
							self.refresh();
						});
					} else {
						return false; // Stop the FilesAdded event from immediate propagation
					}
				});

				// Generate unique target filenames
				if (settings.unique_names) {
					self.bind("UploadFile", function(up, file) {
						var matches = file.name.match(/\.([^.]+)$/), ext = "tmp";

						if (matches) {
							ext = matches[1];
						}

						file.target_name = file.id + '.' + ext;
					});
				}

				self.bind('UploadProgress', function(up, file) {
					if (file.status == plupload.QUEUED) {
						file.status = plupload.UPLOADING;
					}

					file.percent = file.size > 0 ? Math.ceil(file.loaded / file.size * 100) : 100;
					calc();
				});

				self.bind('StateChanged', function(up) {
					if (up.state == plupload.STARTED) {
						// Get start time to calculate bps
						startTime = (+new Date());
					}
				});

				self.bind('QueueChanged', calc);

				self.bind("Error", function(up, err) {
					// Set failed status if an error occured on a file
					if (err.file) {
						err.file.status = plupload.FAILED;
						calc();

						// Upload next file but detach it from the error event
						// since other custom listeners might want to stop the queue
						delay(function() {
							uploadNext.call(self);
						});
					}
				});

				self.bind("FileUploaded", function(up, file) {
					file.status = plupload.DONE;
					file.loaded = file.size;
					up.trigger('UploadProgress', file);

					// Upload next file but detach it from the error event
					// since other custom listeners might want to stop the queue
					delay(function() {
						uploadNext.call(self);
					});
				});

				// Setup runtimeList
				if (settings.runtimes) {
					runtimeList = [];
					items = settings.runtimes.split(/\s?,\s?/);

					for (i = 0; i < items.length; i++) {
						if (runtimes[items[i]]) {
							runtimeList.push(runtimes[items[i]]);
						}
					}
				} else {
					runtimeList = runtimes;
				}

				// Call init on each runtime in sequence
				function callNextInit() {
					var runtime = runtimeList[runTimeIndex++], features, requiredFeatures, i;

					if (runtime) {
						features = runtime.getFeatures();

						// Check if runtime supports required features
						requiredFeatures = self.settings.required_features;
						if (requiredFeatures) {
							requiredFeatures = requiredFeatures.split(',');

							for (i = 0; i < requiredFeatures.length; i++) {
								// Specified feature doesn't exist
								if (!features[requiredFeatures[i]]) {
									callNextInit();
									return;
								}
							}
						}

						// Try initializing the runtime
						runtime.init(self, function(res) {
							if (res && res.success) {
								// Successful initialization
								self.features = features;
								self.trigger('Init', {runtime : runtime.name});
								self.trigger('PostInit');
								self.refresh();
							} else {
								callNextInit();
							}
						});
					} else {
						// Trigger an init error if we run out of runtimes
						self.trigger('Error', {
							code : plupload.INIT_ERROR,
							message : 'Init error.'
						});
					}
				}

				callNextInit();

				if (typeof(settings.init) == "function") {
					settings.init(self);
				} else {
					plupload.each(settings.init, function(func, name) {
						self.bind(name, func);
					});
				}
			},

			/**
			 * Refreshes the upload instance by dispatching out a refresh event to all runtimes.
			 * This would for example reposition flash/silverlight shims on the page.
			 *
			 * @method refresh
			 */
			refresh : function() {
				this.trigger("Refresh");
			},

			/**
			 * Starts uploading the queued files.
			 *
			 * @method start
			 */
			start : function() {
				if (this.state != plupload.STARTED) {
					fileIndex = 0;

					this.state = plupload.STARTED;
					this.trigger("StateChanged");

					uploadNext.call(this);
				}
			},

			/**
			 * Stops the upload of the queued files.
			 *
			 * @method stop
			 */
			stop : function() {
				if (this.state != plupload.STOPPED) {
					this.state = plupload.STOPPED;
					this.trigger("StateChanged");
				}
			},

			/**
			 * Returns the specified file object by id.
			 *
			 * @method getFile
			 * @param {String} id File id to look for.
			 * @return {plupload.File} File object or undefined if it wasn't found;
			 */
			getFile : function(id) {
				var i;

				for (i = files.length - 1; i >= 0; i--) {
					if (files[i].id === id) {
						return files[i];
					}
				}
			},

			/**
			 * Removes a specific file.
			 *
			 * @method removeFile
			 * @param {plupload.File} file File to remove from queue.
			 */
			removeFile : function(file) {
				var i;

				for (i = files.length - 1; i >= 0; i--) {
					if (files[i].id === file.id) {
						return this.splice(i, 1)[0];
					}
				}
			},

			/**
			 * Removes part of the queue and returns the files removed. This will also trigger the FilesRemoved and QueueChanged events.
			 *
			 * @method splice
			 * @param {Number} start (Optional) Start index to remove from.
			 * @param {Number} length (Optional) Lengh of items to remove.
			 * @return {Array} Array of files that was removed.
			 */
			splice : function(start, length) {
				var removed;

				// Splice and trigger events
				removed = files.splice(start === undef ? 0 : start, length === undef ? files.length : length);

				this.trigger("FilesRemoved", removed);
				this.trigger("QueueChanged");

				return removed;
			},

			/**
			 * Dispatches the specified event name and it's arguments to all listeners.
			 *
			 *
			 * @method trigger
			 * @param {String} name Event name to fire.
			 * @param {Object..} Multiple arguments to pass along to the listener functions.
			 */
			trigger : function(name) {
				var list = events[name.toLowerCase()], i, args;

				// console.log(name, arguments);

				if (list) {
					// Replace name with sender in args
					args = Array.prototype.slice.call(arguments);
					args[0] = this;

					// Dispatch event to all listeners
					for (i = 0; i < list.length; i++) {
						// Fire event, break chain if false is returned
						if (list[i].func.apply(list[i].scope, args) === false) {
							return false;
						}
					}
				}

				return true;
			},

			/**
			 * Adds an event listener by name.
			 *
			 * @method bind
			 * @param {String} name Event name to listen for.
			 * @param {function} func Function to call ones the event gets fired.
			 * @param {Object} scope Optional scope to execute the specified function in.
			 */
			bind : function(name, func, scope) {
				var list;

				name = name.toLowerCase();
				list = events[name] || [];
				list.push({func : func, scope : scope || this});
				events[name] = list;
			},

			/**
			 * Removes the specified event listener.
			 *
			 * @method unbind
			 * @param {String} name Name of event to remove.
			 * @param {function} func Function to remove from listener.
			 */
			unbind : function(name, func) {
				var list = events[name.toLowerCase()], i;

				if (list) {
					for (i = list.length - 1; i >= 0; i--) {
						if (list[i].func === func) {
							list.splice(i, 1);
						}
					}
				}
			}

			/**
			 * Fires when the current RunTime has been initialized.
			 *
			 * @event Init
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 */

			/**
			 * Fires after the init event incase you need to perform actions there.
			 *
			 * @event PostInit
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 */

			/**
			 * Fires when the silverlight/flash or other shim needs to move.
			 *
			 * @event Refresh
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 */
	
			/**
			 * Fires when the overall state is being changed for the upload queue.
			 *
			 * @event StateChanged
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 */

			/**
			 * Fires when a file is to be uploaded by the runtime.
			 *
			 * @event UploadFile
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 * @param {plupload.File} file File to be uploaded.
			 */

			/**
			 * Fires when just before a file is uploaded. This event enables you to override settings
			 * on the uploader instance before the file is uploaded.
			 *
			 * @event BeforeUpload
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 * @param {plupload.File} file File to be uploaded.
			 */

			/**
			 * Fires when the file queue is changed. In other words when files are added/removed to the files array of the uploader instance.
			 *
			 * @event QueueChanged
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 */
	
			/**
			 * Fires while a file is being uploaded. Use this event to update the current file upload progress.
			 *
			 * @event UploadProgress
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 * @param {plupload.File} file File that is currently being uploaded.
			 */

			/**
			 * Fires while a file was removed from queue.
			 *
			 * @event FilesRemoved
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 * @param {Array} files Array of files that got removed.
			 */

			/**
			 * Fires while when the user selects files to upload.
			 *
			 * @event FilesAdded
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 * @param {Array} files Array of file objects that was added to queue/selected by the user.
			 */

			/**
			 * Fires when a file is successfully uploaded.
			 *
			 * @event FileUploaded
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 * @param {plupload.File} file File that was uploaded.
			 * @param {Object} response Object with response properties.
			 */

			/**
			 * Fires when file chunk is uploaded.
			 *
			 * @event ChunkUploaded
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 * @param {plupload.File} file File that the chunk was uploaded for.
			 * @param {Object} response Object with response properties.
			 */

			/**
			 * Fires when a error occurs.
			 *
			 * @event Error
			 * @param {plupload.Uploader} uploader Uploader instance sending the event.
			 * @param {Object} error Contains code, message and sometimes file and other details.
			 */
		});
	};

	/**
	 * File instance.
	 *
	 * @class plupload.File
	 * @param {String} name Name of the file.
	 * @param {Number} size File size.
	 */

	/**
	 * Constructs a new file instance.
	 *
	 * @constructor
	 * @method File
	 * @param {String} id Unique file id.
	 * @param {String} name File name.
	 * @param {Number} size File size in bytes.
	 */
	plupload.File = function(id, name, size) {
		var self = this; // Setup alias for self to reduce code size when it's compressed

		/**
		 * File id this is a globally unique id for the specific file.
		 *
		 * @property id
		 * @type String
		 */
		self.id = id;

		/**
		 * File name for example "myfile.gif".
		 *
		 * @property name
		 * @type String
		 */
		self.name = name;

		/**
		 * File size in bytes.
		 *
		 * @property size
		 * @type Number
		 */
		self.size = size;

		/**
		 * Number of bytes uploaded of the files total size.
		 *
		 * @property loaded
		 * @type Number
		 */
		self.loaded = 0;

		/**
		 * Number of percentage uploaded of the file.
		 *
		 * @property percent
		 * @type Number
		 */
		self.percent = 0;

		/**
		 * Status constant matching the plupload states QUEUED, UPLOADING, FAILED, DONE.
		 *
		 * @property status
		 * @type Number
		 * @see plupload
		 */
		self.status = 0;
	};

	/**
	 * Runtime class gets implemented by each upload runtime.
	 *
	 * @class plupload.Runtime
	 * @static
	 */
	plupload.Runtime = function() {
		/**
		 * Returns a list of supported features for the runtime.
		 *
		 * @return {Object} Name/value object with supported features.
		 */
		this.getFeatures = function() {
		};

		/**
		 * Initializes the upload runtime. This method should add necessary items to the DOM and register events needed for operation. 
		 *
		 * @method init
		 * @param {plupload.Uploader} uploader Uploader instance that needs to be initialized.
		 * @param {function} callback Callback function to execute when the runtime initializes or fails to initialize. If it succeeds an object with a parameter name success will be set to true.
		 */
		this.init = function(uploader, callback) {
		};
	};

	/**
	 * Runtime class gets implemented by each upload runtime.
	 *
	 * @class plupload.QueueProgress
	 */

	/**
	 * Constructs a queue progress.
	 *
	 * @constructor
	 * @method QueueProgress
	 */
	 plupload.QueueProgress = function() {
		var self = this; // Setup alias for self to reduce code size when it's compressed

		/**
		 * Total queue file size.
		 *
		 * @property size
		 * @type Number
		 */
		self.size = 0;

		/**
		 * Total bytes uploaded.
		 *
		 * @property loaded
		 * @type Number
		 */
		self.loaded = 0;

		/**
		 * Number of files uploaded.
		 *
		 * @property uploaded
		 * @type Number
		 */
		self.uploaded = 0;

		/**
		 * Number of files failed to upload.
		 *
		 * @property failed
		 * @type Number
		 */
		self.failed = 0;

		/**
		 * Number of files yet to be uploaded.
		 *
		 * @property queued
		 * @type Number
		 */
		self.queued = 0;

		/**
		 * Total percent of the uploaded bytes.
		 *
		 * @property percent
		 * @type Number
		 */
		self.percent = 0;

		/**
		 * Bytes uploaded per second.
		 *
		 * @property bytesPerSec
		 * @type Number
		 */
		self.bytesPerSec = 0;

		/**
		 * Resets the progress to it's initial values.
		 *
		 * @method reset
		 */
		self.reset = function() {
			self.size = self.loaded = self.uploaded = self.failed = self.queued = self.percent = self.bytesPerSec = 0;
		};
	};

	// Create runtimes namespace
	plupload.runtimes = {};

	// Expose plupload namespace
	window.plupload = plupload;
})();
/**
 * plupload.gears.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

// JSLint defined globals
/*global plupload:false, google:false, window:false */

(function(plupload) {
	var blobs = {};

	function scaleImage(image_blob, width, height, quality, mime) {
		var percentage, canvas, context, scale;

		// Setup canvas and scale
		canvas = google.gears.factory.create('beta.canvas');
		canvas.decode(image_blob);
		scale = Math.min(width / canvas.width, height / canvas.height);

		if (scale < 1) {
			width = Math.round(canvas.width * scale);
			height = Math.round(canvas.height * scale);
		} else {
			width = canvas.width;
			height = canvas.height;
		}

		canvas.resize(width, height);

		return canvas.encode(mime, {quality : quality / 100});
	}

	/**
	 * Gears implementation. This runtime supports these features: dragdrop, jpgresize, pngresize, chunks.
	 *
	 * @static
	 * @class plupload.runtimes.Gears
	 * @extends plupload.Runtime
	 */
	plupload.runtimes.Gears = plupload.addRuntime("gears", {
		/**
		 * Returns a list of supported features for the runtime.
		 *
		 * @return {Object} Name/value object with supported features.
		 */
		getFeatures : function() {
			return {
				dragdrop: true,
				jpgresize: true,
				pngresize: true,
				chunks: true,
				progress: true,
				multipart: true
			};
		},

		/**
		 * Initializes the upload runtime.
		 *
		 * @method init
		 * @param {plupload.Uploader} uploader Uploader instance that needs to be initialized.
		 * @param {function} callback Callback to execute when the runtime initializes or fails to initialize. If it succeeds an object with a parameter name success will be set to true.
		 */
		init : function(uploader, callback) {
			var desktop;

			// Check for gears support
			if (!window.google || !google.gears) {
				return callback({success : false});
			}

			try {
				desktop = google.gears.factory.create('beta.desktop');
			} catch (e) {
				// Might fail on the latest Gecko build for some odd reason
				return callback({success : false});
			}

			function addSelectedFiles(selected_files) {
				var file, i, files = [], id;

				// Add the selected files to the file queue
				for (i = 0; i < selected_files.length; i++) {
					file = selected_files[i];

					// Store away gears blob internally
					id = plupload.guid();
					blobs[id] = file.blob;

					files.push(new plupload.File(id, file.name, file.blob.length));
				}

				// Fire FilesAdded event
				uploader.trigger("FilesAdded", files);
			}

			// Add drop handler
			uploader.bind("PostInit", function() {
				var settings = uploader.settings, dropElm = document.getElementById(settings.drop_element);

				if (dropElm) {
					// Block browser default drag over
					plupload.addEvent(dropElm, 'dragover', function(e) {
						desktop.setDropEffect(e, 'copy');
						e.preventDefault();
					});

					// Attach drop handler and grab files from Gears
					plupload.addEvent(dropElm, 'drop', function(e) {
						var dragData = desktop.getDragData(e, 'application/x-gears-files');

						if (dragData) {
							addSelectedFiles(dragData.files);
						}

						e.preventDefault();
					});

					// Prevent IE leak
					dropElm = 0;
				}

				// Add browse button
				plupload.addEvent(document.getElementById(settings.browse_button), 'click', function(e) {
					var filters = [], i, a, ext;

					e.preventDefault();

					for (i = 0; i < settings.filters.length; i++) {
						ext = settings.filters[i].extensions.split(',');

						for (a = 0; a < ext.length; a++) {
							filters.push('.' + ext[a]);
						}
					}

					desktop.openFiles(addSelectedFiles, {singleFile : !settings.multi_selection, filter : filters});
				});
			});

			uploader.bind("UploadFile", function(up, file) {
				var chunk = 0, chunks, chunkSize, loaded = 0, resize = up.settings.resize, chunking;

				chunkSize = up.settings.chunk_size;
				chunking = chunkSize > 0;
				chunks = Math.ceil(file.size / chunkSize);

				// If chunking is disabled then upload the whole file in one huge chunk
				if (!chunking) {
					chunkSize = file.size;
					chunks = 1;
				}

				// If file is png or jpeg and resize is configured then resize it
				if (resize && /\.(png|jpg|jpeg)$/i.test(file.name)) {
					blobs[file.id] = scaleImage(blobs[file.id], resize.width, resize.height, resize.quality || 90, /\.png$/i.test(file.name) ? 'image/png' : 'image/jpeg');
				}

				file.size = blobs[file.id].length;

				function uploadNextChunk() {
					var req, curChunkSize, multipart = up.settings.multipart, multipartLength = 0, reqArgs = {name : file.target_name || file.name}, url = up.settings.url;

					// Sends the binary blob multipart encoded or raw depending on config
					function sendBinaryBlob(blob) {
						var builder, boundary = '----pluploadboundary' + plupload.guid(), dashdash = '--', crlf = '\r\n', multipartBlob, mimeType;

						// Build multipart request
						if (multipart) {
							req.setRequestHeader('Content-Type', 'multipart/form-data; boundary=' + boundary);
							builder = google.gears.factory.create('beta.blobbuilder');

							// Append mutlipart parameters
							plupload.each(plupload.extend(reqArgs, up.settings.multipart_params), function(value, name) {
								builder.append(
									dashdash + boundary + crlf +
									'Content-Disposition: form-data; name="' + name + '"' + crlf + crlf
								);

								builder.append(value + crlf);
							});

							mimeType = plupload.mimeTypes[file.name.replace(/^.+\.([^.]+)/, '$1')] || 'application/octet-stream';

							// Add file header
							builder.append(
								dashdash + boundary + crlf +
								'Content-Disposition: form-data; name="' + up.settings.file_data_name + '"; filename="' + file.name + '"' + crlf +
								'Content-Type: ' + mimeType + crlf + crlf
							);

							// Add file data
							builder.append(blob);

							// Add footer
							builder.append(crlf + dashdash + boundary + dashdash + crlf);
							multipartBlob = builder.getAsBlob();
							multipartLength = multipartBlob.length - blob.length;
							blob = multipartBlob;
						}

						// Send blob or multipart blob depending on config
						req.send(blob);
					}

					// File upload finished
					if (file.status == plupload.DONE || file.status == plupload.FAILED || up.state == plupload.STOPPED) {
						return;
					}

					// Only add chunking args if needed
					if (chunking) {
						reqArgs.chunk = chunk;
						reqArgs.chunks = chunks;
					}

					// Setup current chunk size
					curChunkSize = Math.min(chunkSize, file.size - (chunk  * chunkSize));

					if (!multipart) {
						url = plupload.buildUrl(up.settings.url, reqArgs);
					}

					req = google.gears.factory.create('beta.httprequest');
					req.open('POST', url);

					// Add disposition and type if multipart is disabled
					if (!multipart) {
						req.setRequestHeader('Content-Disposition', 'attachment; filename="' + file.name + '"');
						req.setRequestHeader('Content-Type', 'application/octet-stream');
					}

					// Set custom headers
					plupload.each(up.settings.headers, function(value, name) {
						req.setRequestHeader(name, value);
					});

					req.upload.onprogress = function(progress) {
						file.loaded = loaded + progress.loaded - multipartLength;
						up.trigger('UploadProgress', file);
					};

					req.onreadystatechange = function() {
						var chunkArgs;

						if (req.readyState == 4) {
							if (req.status == 200) {
								chunkArgs = {
									chunk : chunk,
									chunks : chunks,
									response : req.responseText,
									status : req.status
								};

								up.trigger('ChunkUploaded', file, chunkArgs);

								// Stop upload
								if (chunkArgs.cancelled) {
									file.status = plupload.FAILED;
									return;
								}

								loaded += curChunkSize;

								if (++chunk >= chunks) {
									file.status = plupload.DONE;
									up.trigger('FileUploaded', file, {
										response : req.responseText,
										status : req.status
									});
								} else {
									uploadNextChunk();
								}
							} else {
								up.trigger('Error', {
									code : plupload.HTTP_ERROR,
									message : 'HTTP Error.',
									file : file,
									chunk : chunk,
									chunks : chunks,
									status : req.status
								});
							}
						}
					};

					if (chunk < chunks) {
						sendBinaryBlob(blobs[file.id].slice(chunk * chunkSize, curChunkSize));
					}
				}

				// Start uploading chunks
				uploadNextChunk();
			});

			callback({success : true});
		}
	});
})(plupload);
/**
 * plupload.silverlight.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

// JSLint defined globals
/*global plupload:false, ActiveXObject:false, window:false */

(function(plupload) {
	var uploadInstances = {};

	function jsonSerialize(obj) {
		var value, type = typeof obj, undef, isArray, i, key;

		// Encode strings
		if (type === 'string') {
			value = '\bb\tt\nn\ff\rr\""\'\'\\\\';

			return '"' + obj.replace(/([\u0080-\uFFFF\x00-\x1f\"])/g, function(a, b) {
				var idx = value.indexOf(b);

				if (idx + 1) {
					return '\\' + value.charAt(idx + 1);
				}

				a = b.charCodeAt().toString(16);

				return '\\u' + '0000'.substring(a.length) + a;
			}) + '"';
		}

		// Loop objects/arrays
		if (type == 'object') {
			isArray = obj.length !== undef;
			value = '';

			if (isArray) {
				for (i = 0; i < obj.length; i++) {
					if (value) {
						value += ',';
					}

					value += jsonSerialize(obj[i]);
				}

				value = '[' + value + ']';
			} else {
				for (key in obj) {
					if (obj.hasOwnProperty(key)) {
						if (value) {
							value += ',';
						}

						value += jsonSerialize(key) + ':' + jsonSerialize(obj[key]);
					}
				}

				value = '{' + value + '}';
			}

			return value;
		}

		// Treat undefined as null
		if (obj === undef) {
			return 'null';
		}

		// Convert all other types to string
		return '' + obj;
	}

	function isInstalled(version) {
		var isVersionSupported = false, container = null, control = null, actualVer,
			actualVerArray, reqVerArray, requiredVersionPart, actualVersionPart, index = 0;

		try {
			try {
				control = new ActiveXObject('AgControl.AgControl');

				if (control.IsVersionSupported(version)) {
					isVersionSupported = true;
				}

				control = null;
			} catch (e) {
				var plugin = navigator.plugins["Silverlight Plug-In"];

				if (plugin) {
					actualVer = plugin.description;

					if (actualVer === "1.0.30226.2") {
						actualVer = "2.0.30226.2";
					}

					actualVerArray = actualVer.split(".");

					while (actualVerArray.length > 3) {
						actualVerArray.pop();
					}

					while ( actualVerArray.length < 4) {
						actualVerArray.push(0);
					}

					reqVerArray = version.split(".");

					while (reqVerArray.length > 4) {
						reqVerArray.pop();
					}

					do {
						requiredVersionPart = parseInt(reqVerArray[index], 10);
						actualVersionPart = parseInt(actualVerArray[index], 10);
						index++;
					} while (index < reqVerArray.length && requiredVersionPart === actualVersionPart);

					if (requiredVersionPart <= actualVersionPart && !isNaN(requiredVersionPart)) {
						isVersionSupported = true;
					}
				}
			}
		} catch (e2) {
			isVersionSupported = false;
		}

		return isVersionSupported;
	}

	plupload.silverlight = {
		trigger : function(id, name) {
			var uploader = uploadInstances[id], i, args;

			if (uploader) {
				args = plupload.toArray(arguments).slice(1);
				args[0] = 'Silverlight:' + name;

				// Detach the call so that error handling in the browser is presented correctly
				setTimeout(function() {
					uploader.trigger.apply(uploader, args);
				}, 0);
			}
		}
	};

	/**
	 * Silverlight implementation. This runtime supports these features: jpgresize, pngresize, chunks.
	 *
	 * @static
	 * @class plupload.runtimes.Silverlight
	 * @extends plupload.Runtime
	 */
	plupload.runtimes.Silverlight = plupload.addRuntime("silverlight", {
		/**
		 * Returns a list of supported features for the runtime.
		 *
		 * @return {Object} Name/value object with supported features.
		 */
		getFeatures : function() {
			return {
				jpgresize: true,
				pngresize: true,
				chunks: true,
				progress: true,
				multipart: true
			};
		},

		/**
		 * Initializes the upload runtime. This runtime supports these features: jpgresize, pngresize, chunks.
		 *
		 * @method init
		 * @param {plupload.Uploader} uploader Uploader instance that needs to be initialized.
		 * @param {function} callback Callback to execute when the runtime initializes or fails to initialize. If it succeeds an object with a parameter name success will be set to true.
		 */
		init : function(uploader, callback) {
			var silverlightContainer, filter = '', filters = uploader.settings.filters, i, container = document.body;

			// Check if Silverlight is installed, Silverlight windowless parameter doesn't work correctly on Opera so we disable it for now
			if (!isInstalled('2.0.31005.0') || (window.opera && window.opera.buildNumber)) {
				callback({success : false});
				return;
			}

			uploadInstances[uploader.id] = uploader;

			// Create silverlight container and insert it at an absolute position within the browse button
			silverlightContainer = document.createElement('div');
			silverlightContainer.id = uploader.id + '_silverlight_container';

			plupload.extend(silverlightContainer.style, {
				position : 'absolute',
				top : '0px',
				background : uploader.settings.shim_bgcolor || 'transparent',
				zIndex : 99999,
				width : '100px',
				height : '100px',
				overflow : 'hidden',
				opacity : uploader.settings.shim_bgcolor || document.documentMode > 8 ? '' : 0.01 // Force transparent if bgcolor is undefined
			});

			silverlightContainer.className = 'plupload silverlight';

			if (uploader.settings.container) {
				container = document.getElementById(uploader.settings.container);
				container.style.position = 'relative';
			}

			container.appendChild(silverlightContainer);

			for (i = 0; i < filters.length; i++) {
				filter += (filter != '' ? '|' : '') + filters[i].title + " | *." + filters[i].extensions.replace(/,/g, ';*.');
			}

			// Insert the Silverlight object inide the Silverlight container
			silverlightContainer.innerHTML = '<object id="' + uploader.id + '_silverlight" data="data:application/x-silverlight," type="application/x-silverlight-2" style="outline:none;" width="1024" height="1024">' +
				'<param name="source" value="' + uploader.settings.silverlight_xap_url + '"/>' +
				'<param name="background" value="Transparent"/>' +
				'<param name="windowless" value="true"/>' +
				'<param name="enablehtmlaccess" value="true"/>' +
				'<param name="initParams" value="id=' + uploader.id + ',filter=' + filter + '"/>' +
				'</object>';

			function getSilverlightObj() {
				return document.getElementById(uploader.id + '_silverlight').content.Upload;
			}

			uploader.bind("Silverlight:Init", function() {
				var selectedFiles, lookup = {};

				uploader.bind("Silverlight:StartSelectFiles", function(up) {
					selectedFiles = [];
				});

				uploader.bind("Silverlight:SelectFile", function(up, sl_id, name, size) {
					var id;

					// Store away silverlight ids
					id = plupload.guid();
					lookup[id] = sl_id;
					lookup[sl_id] = id;

					// Expose id, name and size
					selectedFiles.push(new plupload.File(id, name, size));
				});

				uploader.bind("Silverlight:SelectSuccessful", function() {
					// Trigger FilesAdded event if we added any
					if (selectedFiles.length) {
						uploader.trigger("FilesAdded", selectedFiles);
					}
				});

				uploader.bind("Silverlight:UploadChunkError", function(up, file_id, chunk, chunks, message) {
					uploader.trigger("Error", {
						code : plupload.IO_ERROR,
						message : 'IO Error.',
						details : message,
						file : up.getFile(lookup[file_id])
					});
				});

				uploader.bind("Silverlight:UploadFileProgress", function(up, sl_id, loaded, total) {
					var file = up.getFile(lookup[sl_id]);

					if (file.status != plupload.FAILED) {
						file.size = total;
						file.loaded = loaded;

						up.trigger('UploadProgress', file);
					}
				});

				uploader.bind("Refresh", function(up) {
					var browseButton, browsePos, browseSize;

					browseButton = document.getElementById(up.settings.browse_button);
					browsePos = plupload.getPos(browseButton, document.getElementById(up.settings.container));
					browseSize = plupload.getSize(browseButton);

					plupload.extend(document.getElementById(up.id + '_silverlight_container').style, {
						top : browsePos.y + 'px',
						left : browsePos.x + 'px',
						width : browseSize.w + 'px',
						height : browseSize.h + 'px'
					});
				});

				uploader.bind("Silverlight:UploadChunkSuccessful", function(up, sl_id, chunk, chunks, text) {
					var chunkArgs, file = up.getFile(lookup[sl_id]);

					chunkArgs = {
						chunk : chunk,
						chunks : chunks,
						response : text
					};

					up.trigger('ChunkUploaded', file, chunkArgs);

					// Stop upload if file is maked as failed
					if (file.status != plupload.FAILED) {
						getSilverlightObj().UploadNextChunk();
					}

					// Last chunk then dispatch FileUploaded event
					if (chunk == chunks - 1) {
						file.status = plupload.DONE;

						up.trigger('FileUploaded', file, {
							response : text
						});
					}
				});

				uploader.bind("Silverlight:UploadSuccessful", function(up, sl_id, response) {
					var file = up.getFile(lookup[sl_id]);

					file.status = plupload.DONE;

					up.trigger('FileUploaded', file, {
						response : response
					});
				});

				uploader.bind("FilesRemoved", function(up, files) {
					var i;

					for (i = 0; i < files.length; i++) {
						getSilverlightObj().RemoveFile(lookup[files[i].id]);
					}
				});

				uploader.bind("UploadFile", function(up, file) {
					var settings = up.settings, resize = settings.resize || {};

					getSilverlightObj().UploadFile(
						lookup[file.id],
						up.settings.url,
						jsonSerialize({
							name : file.target_name || file.name,
							mime : plupload.mimeTypes[file.name.replace(/^.+\.([^.]+)/, '$1')] || 'application/octet-stream',
							chunk_size : settings.chunk_size,
							image_width : resize.width,
							image_height : resize.height,
							image_quality : resize.quality || 90,
							multipart : !!settings.multipart,
							multipart_params : settings.multipart_params || {},
							headers : settings.headers
						})
					);
				});

				callback({success : true});
			});
		}
	});
})(plupload);
/**
 * plupload.flash.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

// JSLint defined globals
/*global plupload:false, ActiveXObject:false, escape:false */

(function(plupload) {
	var uploadInstances = {};

	function getFlashVersion() {
		var version;

		try {
			version = navigator.plugins['Shockwave Flash'];
			version = version.description;
		} catch (e1) {
			try {
				version = new ActiveXObject('ShockwaveFlash.ShockwaveFlash').GetVariable('$version');
			} catch (e2) {
				version = '0.0';
			}
		}

		version = version.match(/\d+/g);

		return parseFloat(version[0] + '.' + version[1]);
	}

	plupload.flash = {
		/**
		 * Will be executed by the Flash runtime when it sends out events.
		 *
		 * @param {String} id If for the upload instance.
		 * @param {String} name Event name to trigger.
		 * @param {Object} obj Parameters to be passed with event.
		 */
		trigger : function(id, name, obj) {
			// Detach the call so that error handling in the browser is presented correctly
			setTimeout(function() {
				var uploader = uploadInstances[id], i, args;

				if (uploader) {
					uploader.trigger('Flash:' + name, obj);
				}
			}, 0);
		}
	};

	/**
	 * FlashRuntime implementation. This runtime supports these features: jpgresize, pngresize, chunks.
	 *
	 * @static
	 * @class plupload.runtimes.Flash
	 * @extends plupload.Runtime
	 */
	plupload.runtimes.Flash = plupload.addRuntime("flash", {
		/**
		 * Returns a list of supported features for the runtime.
		 *
		 * @return {Object} Name/value object with supported features.
		 */
		getFeatures : function() {
			return {
				jpgresize: true,
				pngresize: true,
				chunks: true,
				progress: true,
				multipart: true
			};
		},

		/**
		 * Initializes the upload runtime. This method should add necessary items to the DOM and register events needed for operation. 
		 *
		 * @method init
		 * @param {plupload.Uploader} uploader Uploader instance that needs to be initialized.
		 * @param {function} callback Callback to execute when the runtime initializes or fails to initialize. If it succeeds an object with a parameter name success will be set to true.
		 */
		init : function(uploader, callback) {
			var browseButton, flashContainer, flashVars, initialized, waitCount = 0, container = document.body;

			if (getFlashVersion() < 10) {
				callback({success : false});
				return;
			}

			uploadInstances[uploader.id] = uploader;

			// Find browse button and set to to be relative
			browseButton = document.getElementById(uploader.settings.browse_button);

			// Create flash container and insert it at an absolute position within the browse button
			flashContainer = document.createElement('div');
			flashContainer.id = uploader.id + '_flash_container';

			plupload.extend(flashContainer.style, {
				position : 'absolute',
				top : '0px',
				background : uploader.settings.shim_bgcolor || 'transparent',
				zIndex : 99999,
				width : '100%',
				height : '100%'
			});

			flashContainer.className = 'plupload flash';

			if (uploader.settings.container) {
				container = document.getElementById(uploader.settings.container);
				container.style.position = 'relative';
			}

			container.appendChild(flashContainer);

			flashVars = 'id=' + escape(uploader.id);

			// Insert the Flash inide the flash container
			flashContainer.innerHTML = '<object id="' + uploader.id + '_flash" width="100%" height="100%" style="outline:0" type="application/x-shockwave-flash" data="' + uploader.settings.flash_swf_url + '">' +
				'<param name="movie" value="' + uploader.settings.flash_swf_url + '" />' +
				'<param name="flashvars" value="' + flashVars + '" />' +
				'<param name="wmode" value="transparent" />' +
				'<param name="allowscriptaccess" value="always" /></object>';

			function getFlashObj() {
				return document.getElementById(uploader.id + '_flash');
			}

			function waitLoad() {
				// Wait for 5 sec
				if (waitCount++ > 5000) {
					callback({success : false});
					return;
				}

				if (!initialized) {
					setTimeout(waitLoad, 1);
				}
			}

			waitLoad();

			// Fix IE memory leaks
			browseButton = flashContainer = null;

			// Wait for Flash to send init event
			uploader.bind("Flash:Init", function() {
				var lookup = {}, i, resize = uploader.settings.resize || {};

				initialized = true;
				getFlashObj().setFileFilters(uploader.settings.filters, uploader.settings.multi_selection);

				uploader.bind("UploadFile", function(up, file) {
					var settings = up.settings;

					getFlashObj().uploadFile(lookup[file.id], settings.url, {
						name : file.target_name || file.name,
						mime : plupload.mimeTypes[file.name.replace(/^.+\.([^.]+)/, '$1')] || 'application/octet-stream',
						chunk_size : settings.chunk_size,
						width : resize.width,
						height : resize.height,
						quality : resize.quality || 90,
						multipart : settings.multipart,
						multipart_params : settings.multipart_params || {},
						file_data_name : settings.file_data_name,
						format : /\.(jpg|jpeg)$/i.test(file.name) ? 'jpg' : 'png',
						headers : settings.headers,
						urlstream_upload : settings.urlstream_upload
					});
				});

				uploader.bind("Flash:UploadProcess", function(up, flash_file) {
					var file = up.getFile(lookup[flash_file.id]);

					if (file.status != plupload.FAILED) {
						file.loaded = flash_file.loaded;
						file.size = flash_file.size;

						up.trigger('UploadProgress', file);
					}
				});

				uploader.bind("Flash:UploadChunkComplete", function(up, info) {
					var chunkArgs, file = up.getFile(lookup[info.id]);

					chunkArgs = {
						chunk : info.chunk,
						chunks : info.chunks,
						response : info.text
					};

					up.trigger('ChunkUploaded', file, chunkArgs);

					// Stop upload if file is maked as failed
					if (file.status != plupload.FAILED) {
						getFlashObj().uploadNextChunk();
					}

					// Last chunk then dispatch FileUploaded event
					if (info.chunk == info.chunks - 1) {
						file.status = plupload.DONE;

						up.trigger('FileUploaded', file, {
							response : info.text
						});
					}
				});

				uploader.bind("Flash:SelectFiles", function(up, selected_files) {
					var file, i, files = [], id;

					// Add the selected files to the file queue
					for (i = 0; i < selected_files.length; i++) {
						file = selected_files[i];

						// Store away flash ref internally
						id = plupload.guid();
						lookup[id] = file.id;
						lookup[file.id] = id;

						files.push(new plupload.File(id, file.name, file.size));
					}

					// Trigger FilesAdded event if we added any
					if (files.length) {
						uploader.trigger("FilesAdded", files);
					}
				});

				uploader.bind("Flash:SecurityError", function(up, err) {
					uploader.trigger('Error', {
						code : plupload.SECURITY_ERROR,
						message : 'Security error.',
						details : err.message,
						file : uploader.getFile(lookup[err.id])
					});
				});

				uploader.bind("Flash:GenericError", function(up, err) {
					uploader.trigger('Error', {
						code : plupload.GENERIC_ERROR,
						message : 'Generic error.',
						details : err.message,
						file : uploader.getFile(lookup[err.id])
					});
				});

				uploader.bind("Flash:IOError", function(up, err) {
					uploader.trigger('Error', {
						code : plupload.IO_ERROR,
						message : 'IO error.',
						details : err.message,
						file : uploader.getFile(lookup[err.id])
					});
				});

				uploader.bind("QueueChanged", function(up) {
					uploader.refresh();
				});

				uploader.bind("FilesRemoved", function(up, files) {
					var i;

					for (i = 0; i < files.length; i++) {
						getFlashObj().removeFile(lookup[files[i].id]);
					}
				});

				uploader.bind("StateChanged", function(up) {
					uploader.refresh();
				});

				uploader.bind("Refresh", function(up) {
					var browseButton, browsePos, browseSize;

					// Set file filters incase it has been changed dynamically
					getFlashObj().setFileFilters(uploader.settings.filters, uploader.settings.multi_selection);

					browseButton = document.getElementById(up.settings.browse_button);
					browsePos = plupload.getPos(browseButton, document.getElementById(up.settings.container));
					browseSize = plupload.getSize(browseButton);

					plupload.extend(document.getElementById(up.id + '_flash_container').style, {
						top : browsePos.y + 'px',
						left : browsePos.x + 'px',
						width : browseSize.w + 'px',
						height : browseSize.h + 'px'
					});
				});

				callback({success : true});
			});
		}
	});
})(plupload);
/**
 * plupload.browserplus.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

// JSLint defined globals
/*global plupload:false, BrowserPlus:false, window:false */

(function(plupload) {
	/**
	 * Yahoo BrowserPlus implementation. This runtime supports these features: dragdrop, jpgresize, pngresize.
	 *
	 * @static
	 * @class plupload.runtimes.BrowserPlus
	 * @extends plupload.Runtime
	 */
	plupload.runtimes.BrowserPlus = plupload.addRuntime("browserplus", {
		/**
		 * Returns a list of supported features for the runtime.
		 *
		 * @return {Object} Name/value object with supported features.
		 */
		getFeatures : function() {
			return {
				dragdrop : true,
				jpgresize : true,
				pngresize : true,
				chunks : true,
				progress: true,
				multipart: true
			};
		},

		/**
		 * Initializes the browserplus runtime.
		 *
		 * @method init
		 * @param {plupload.Uploader} uploader Uploader instance that needs to be initialized.
		 * @param {function} callback Callback to execute when the runtime initializes or fails to initialize. If it succeeds an object with a parameter name success will be set to true.
		 */
		init : function(uploader, callback) {
			var browserPlus = window.BrowserPlus, browserPlusFiles = {}, settings = uploader.settings, resize = settings.resize;

			function addSelectedFiles(native_files) {
				var files, i, selectedFiles = [], file, id;

				// Add the native files and setup plupload files
				for (i = 0; i < native_files.length; i++) {
					file = native_files[i];
					id = plupload.guid();
					browserPlusFiles[id] = file;

					selectedFiles.push(new plupload.File(id, file.name, file.size));
				}

				// Any files selected fire event
				if (i) {
					uploader.trigger("FilesAdded", selectedFiles);
				}
			}

			// Setup event listeners if browserplus was initialized
			function setup() {
				// Add drop handler
				uploader.bind("PostInit", function() {
					var dropTargetElm, dropElmId = settings.drop_element,
						dropTargetId = uploader.id + '_droptarget',
						dropElm = document.getElementById(dropElmId),
						lastState;

					// Enable/disable drop support for the drop target
					// this is needed to resolve IE bubbeling issues and make it possible to drag/drop
					// files into gears runtimes on the same page
					function addDropHandler(id, end_callback) {
						// Add drop target and listener
						browserPlus.DragAndDrop.AddDropTarget({id : id}, function(res) {
							browserPlus.DragAndDrop.AttachCallbacks({
								id : id,
								hover : function(res) {
									if (!res && end_callback) {
										end_callback();
									}
								},
								drop : function(res) {
									if (end_callback) {
										end_callback();
									}

									addSelectedFiles(res);
								}
							}, function() {
							});
						});
					}

					function hide() {
						document.getElementById(dropTargetId).style.top = '-1000px';
					}

					if (dropElm) {
						// Since IE has issues with bubbeling when it comes to the drop of files
						// we need to do this hack where we show a drop target div element while dropping
						if (document.attachEvent && (/MSIE/gi).test(navigator.userAgent)) {
							// Create drop target
							dropTargetElm = document.createElement('div');
							dropTargetElm.setAttribute('id', dropTargetId);
							plupload.extend(dropTargetElm.style, {
								position : 'absolute',
								top : '-1000px',
								background : 'red',
								filter : 'alpha(opacity=0)',
								opacity : 0
							});

							document.body.appendChild(dropTargetElm);

							plupload.addEvent(dropElm, 'dragenter', function(e) {
								var dropElm, dropElmPos;

								dropElm = document.getElementById(dropElmId);
								dropElmPos = plupload.getPos(dropElm);

								plupload.extend(document.getElementById(dropTargetId).style, {
									top : dropElmPos.y + 'px',
									left : dropElmPos.x + 'px',
									width : dropElm.offsetWidth + 'px',
									height : dropElm.offsetHeight + 'px'
								});
							});

							addDropHandler(dropTargetId, hide);
						} else {
							addDropHandler(dropElmId);
						}
					}

					plupload.addEvent(document.getElementById(settings.browse_button), 'click', function(e) {
						var mimeTypes = [], i, a, filters = settings.filters, ext;

						e.preventDefault();

						// Convert extensions to mimetypes
						for (i = 0; i < filters.length; i++) {
							ext = filters[i].extensions.split(',');

							for (a = 0; a < ext.length; a++) {
								mimeTypes.push(plupload.mimeTypes[ext[a]]);
							}
						}

						browserPlus.FileBrowse.OpenBrowseDialog({
							mimeTypes : mimeTypes
						}, function(res) {
							if (res.success) {
								addSelectedFiles(res.value);
							}
						});
					});

					// Prevent IE leaks
					dropElm = dropTargetElm = null;
				});

				uploader.bind("UploadFile", function(up, file) {
					var nativeFile = browserPlusFiles[file.id], reqParams = {},
					    chunkSize = up.settings.chunk_size, loadProgress, chunkStack = [];

					function uploadFile(chunk, chunks) {
						var chunkFile;

						// Stop upload if file is maked as failed
						if (file.status == plupload.FAILED) {
							return;
						}

						reqParams.name = file.target_name || file.name;

						// Only send chunk parameters if chunk size is defined
						if (chunkSize) {
							reqParams.chunk = "" + chunk;
							reqParams.chunks = "" + chunks;
						}

					    chunkFile = chunkStack.shift();

						browserPlus.Uploader.upload({
							url : up.settings.url,
							files : {file : chunkFile},
							cookies : document.cookies,
							postvars : plupload.extend(reqParams, up.settings.multipart_params),
							progressCallback : function(res) {
								var i, loaded = 0;

								// since more than 1 chunk can be sent at a time, keep track of how many bytes
								// of each chunk was sent
								loadProgress[chunk] = parseInt(res.filePercent * chunkFile.size / 100, 10);
								for (i = 0; i < loadProgress.length; i++) {
									loaded += loadProgress[i];
								}

								file.loaded = loaded;
								up.trigger('UploadProgress', file);
							}
						}, function(res) {
							var httpStatus, chunkArgs;

							if (res.success) {
								httpStatus = res.value.statusCode;

								if (chunkSize) {
									up.trigger('ChunkUploaded', file, {
										chunk : chunk,
										chunks : chunks,
										response : res.value.body,
										status : httpStatus
									});
								}

							    if (chunkStack.length > 0) {
									// More chunks to be uploaded
									uploadFile(++chunk, chunks);
							    } else {
									file.status = plupload.DONE;

									up.trigger('FileUploaded', file, {
										response : res.value.body,
										status : httpStatus
									});

									// Is error status
									if (httpStatus >= 400) {
										up.trigger('Error', {
											code : plupload.HTTP_ERROR,
											message : 'HTTP Error.',
											file : file,
											status : httpStatus
										});
									}
							    }
							} else {
								up.trigger('Error', {
									code : plupload.GENERIC_ERROR,
									message : 'Generic Error.',
									file : file,
									details : res.error
								});
							}
						});
					}

					function chunkAndUploadFile(native_file) {
						file.size = native_file.size;
						if (chunkSize) {
							browserPlus.FileAccess.chunk({file : native_file, chunkSize : chunkSize}, function(cr) {
								if (cr.success) {
									var chunks = cr.value, len = chunks.length;

									loadProgress = Array(len);

									for (var i = 0; i < len; i++) {
										loadProgress[i] = 0;
										chunkStack.push(chunks[i]);
									}

									uploadFile(0, len);
								}
							});
						} else {
							loadProgress = Array(1);
							chunkStack.push(native_file);
							uploadFile(0, 1);
						}
					}

					// Resize image if it's a supported format and resize is enabled
					if (resize && /\.(png|jpg|jpeg)$/i.test(file.name)) {
						BrowserPlus.ImageAlter.transform({
							file : nativeFile,
							quality : resize.quality || 90,
							actions : [{
								scale : {
									maxwidth : resize.width,
									maxheight : resize.height
								}
							}]
						}, function(res) {
							if (res.success) {
								chunkAndUploadFile(res.value.file);
							}
						});
					} else {
						chunkAndUploadFile(nativeFile);
					}
				});

				callback({success : true});
			}

			// Check for browserplus object
			if (browserPlus) {
				browserPlus.init(function(res) {
					var services = [
						{service: "Uploader", version: "3"},
						{service: "DragAndDrop", version: "1"},
						{service: "FileBrowse", version: "1"},
						{service: "FileAccess", version: "2"}
					];

					if (resize) {
						services.push({service : 'ImageAlter', version : "4"});
					}

					if (res.success) {
						browserPlus.require({
							services : services
						}, function(sres) {
							if (sres.success) {
								setup();
							} else {
								callback();
							}
						});
					} else {
						callback();
					}
				});
			} else {
				callback();
			}
		}
	});
})(plupload);
/**
 * plupload.html5.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

// JSLint defined globals
/*global plupload:false, File:false, window:false, atob:false, FormData:false, FileReader:false */

(function(plupload) {
	var fakeSafariDragDrop;

	function readFile(file, callback) {
		var reader;

		// Use FileReader if it's available
		if ("FileReader" in window) {
			reader = new FileReader();
			reader.readAsDataURL(file);
			reader.onload = function() {
				callback(reader.result);
			};
		} else {
			return callback(file.getAsDataURL());
		}
	}

	function scaleImage(image_file, max_width, max_height, mime, callback) {
		var canvas, context, img, data, scale;

		readFile(image_file, function(data) {
			// Setup canvas and context
			canvas = document.createElement("canvas");
			canvas.style.display = 'none';
			document.body.appendChild(canvas);
			context = canvas.getContext('2d');

			// Load image
			img = new Image();
			img.onload = function() {
				var width, height, percentage;

				scale = Math.min(max_width / img.width, max_height / img.height);

				if (scale < 1) {
					width = Math.round(img.width * scale);
					height = Math.round(img.height * scale);
				} else {
					width = img.width;
					height = img.height;
				}

				// Scale image and canvas
				canvas.width = width;
				canvas.height = height;
				context.drawImage(img, 0, 0, width, height);

				// Remove data prefix information and grab the base64 encoded data and decode it
				data = canvas.toDataURL(mime);
				data = data.substring(data.indexOf('base64,') + 7);
				data = atob(data);

				// Remove canvas and execute callback with decoded image data
				canvas.parentNode.removeChild(canvas);
				callback({success : true, data : data});
			};

			img.src = data;
		});
	}

	/**
	 * HMTL5 implementation. This runtime supports these features: dragdrop, jpgresize, pngresize.
	 *
	 * @static
	 * @class plupload.runtimes.Html5
	 * @extends plupload.Runtime
	 */
	plupload.runtimes.Html5 = plupload.addRuntime("html5", {
		/**
		 * Returns a list of supported features for the runtime.
		 *
		 * @return {Object} Name/value object with supported features.
		 */
		getFeatures : function() {
			var xhr, hasXhrSupport, hasProgress, dataAccessSupport, sliceSupport, win = window;

			hasXhrSupport = hasProgress = dataAccessSupport = sliceSupport = false;

			if (win.XMLHttpRequest) {
				xhr = new XMLHttpRequest();
				hasProgress = !!xhr.upload;
				hasXhrSupport = !!(xhr.sendAsBinary || xhr.upload);
			}

			// Check for support for various features
			if (hasXhrSupport) {
				// Set dataAccessSupport only for Gecko since BlobBuilder and XHR doesn't handle binary data correctly
				dataAccessSupport = !!(File && (File.prototype.getAsDataURL || win.FileReader) && xhr.sendAsBinary);
				sliceSupport = !!(File && File.prototype.slice);
			}

			// Sniff for Safari and fake drag/drop
			fakeSafariDragDrop = navigator.userAgent.indexOf('Safari') > 0;

			return {
				// Detect drag/drop file support by sniffing, will try to find a better way
				html5: hasXhrSupport, // This is a special one that we check inside the init call
				dragdrop: win.mozInnerScreenX !== undefined || sliceSupport || fakeSafariDragDrop,
				jpgresize: dataAccessSupport,
				pngresize: dataAccessSupport,
				multipart: dataAccessSupport || !!win.FileReader || !!win.FormData,
				progress: hasProgress,
				chunking: sliceSupport || dataAccessSupport
			};
		},

		/**
		 * Initializes the upload runtime.
		 *
		 * @method init
		 * @param {plupload.Uploader} uploader Uploader instance that needs to be initialized.
		 * @param {function} callback Callback to execute when the runtime initializes or fails to initialize. If it succeeds an object with a parameter name success will be set to true.
		 */
		init : function(uploader, callback) {
			var html5files = {}, features;

			function addSelectedFiles(native_files) {
				var file, i, files = [], id;

				// Add the selected files to the file queue
				for (i = 0; i < native_files.length; i++) {
					file = native_files[i];

					// Store away gears blob internally
					id = plupload.guid();
					html5files[id] = file;

					// Expose id, name and size
					files.push(new plupload.File(id, file.fileName, file.fileSize));
				}

				// Trigger FilesAdded event if we added any
				if (files.length) {
					uploader.trigger("FilesAdded", files);
				}
			}

			// No HTML5 upload support
			features = this.getFeatures();
			if (!features.html5) {
				callback({success : false});
				return;
			}

			uploader.bind("Init", function(up) {
				var inputContainer, mimes = [], i, y, filters = up.settings.filters, ext, type, container = document.body;

				// Create input container and insert it at an absolute position within the browse button
				inputContainer = document.createElement('div');
				inputContainer.id = up.id + '_html5_container';

				// Convert extensions to mime types list
				for (i = 0; i < filters.length; i++) {
					ext = filters[i].extensions.split(/,/);

					for (y = 0; y < ext.length; y++) {
						type = plupload.mimeTypes[ext[y]];

						if (type) {
							mimes.push(type);
						}
					}
				}

				plupload.extend(inputContainer.style, {
					position : 'absolute',
					background : uploader.settings.shim_bgcolor || 'transparent',
					width : '100px',
					height : '100px',
					overflow : 'hidden',
					zIndex : 99999,
					opacity : uploader.settings.shim_bgcolor ? '' : 0 // Force transparent if bgcolor is undefined
				});

				inputContainer.className = 'plupload html5';

				if (uploader.settings.container) {
					container = document.getElementById(uploader.settings.container);
					container.style.position = 'relative';
				}

				container.appendChild(inputContainer);

				// Insert the input inide the input container
				inputContainer.innerHTML = '<input id="' + uploader.id + '_html5" ' +
											'style="width:100%;" type="file" accept="' + mimes.join(',') + '" ' +
											(uploader.settings.multi_selection ? 'multiple="multiple"' : '') + ' />';

				document.getElementById(uploader.id + '_html5').onchange = function() {
					// Add the selected files from file input
					addSelectedFiles(this.files);

					// Clearing the value enables the user to select the same file again if they want to
					this.value = '';
				};
			});

			// Add drop handler
			uploader.bind("PostInit", function() {
				var dropElm = document.getElementById(uploader.settings.drop_element);

				if (dropElm) {
					// Lets fake drag/drop on Safari by moving a inpit type file in front of the mouse pointer when we drag into the drop zone
					// TODO: Remove this logic once Safari has official drag/drop support
					if (fakeSafariDragDrop) {
						plupload.addEvent(dropElm, 'dragenter', function(e) {
							var dropInputElm, dropPos, dropSize;

							// Get or create drop zone
							dropInputElm = document.getElementById(uploader.id + "_drop");
							if (!dropInputElm) {
								dropInputElm = document.createElement("input");
								dropInputElm.setAttribute('type', "file");
								dropInputElm.setAttribute('id', uploader.id + "_drop");
								dropInputElm.setAttribute('multiple', 'multiple');

								dropInputElm.onchange = function() {
									// Add the selected files from file input
									addSelectedFiles(this.files);

									// Clearing the value enables the user to select the same file again if they want to
									this.value = '';
								};
							}

							dropPos = plupload.getPos(dropElm, document.getElementById(uploader.settings.container));
							dropSize = plupload.getSize(dropElm);

							plupload.extend(dropInputElm.style, {
								position : 'absolute',
								display : 'block',
								top : dropPos.y + 'px',
								left : dropPos.x + 'px',
								width : dropSize.w + 'px',
								height : dropSize.h + 'px',
								opacity : 0
							});

							dropElm.appendChild(dropInputElm);
						});

						return;
					}

					// Block browser default drag over
					plupload.addEvent(dropElm, 'dragover', function(e) {
						e.preventDefault();
					});

					// Attach drop handler and grab files
					plupload.addEvent(dropElm, 'drop', function(e) {
						var dataTransfer = e.dataTransfer;

						// Add dropped files
						if (dataTransfer && dataTransfer.files) {
							addSelectedFiles(dataTransfer.files);
						}

						e.preventDefault();
					});
				}
			});

			uploader.bind("Refresh", function(up) {
				var browseButton, browsePos, browseSize;

				browseButton = document.getElementById(uploader.settings.browse_button);
				browsePos = plupload.getPos(browseButton, document.getElementById(up.settings.container));
				browseSize = plupload.getSize(browseButton);

				plupload.extend(document.getElementById(uploader.id + '_html5_container').style, {
					top : browsePos.y + 'px',
					left : browsePos.x + 'px',
					width : browseSize.w + 'px',
					height : browseSize.h + 'px'
				});
			});

			uploader.bind("UploadFile", function(up, file) {
				var settings = up.settings, nativeFile, resize;

				function sendBinaryBlob(blob) {
					var chunk = 0, loaded = 0;

					function uploadNextChunk() {
						var chunkBlob = blob, xhr, upload, chunks, args, multipartDeltaSize = 0,
							boundary = '----pluploadboundary' + plupload.guid(), chunkSize, curChunkSize,
							dashdash = '--', crlf = '\r\n', multipartBlob = '', mimeType, url = up.settings.url;

						// File upload finished
						if (file.status == plupload.DONE || file.status == plupload.FAILED || up.state == plupload.STOPPED) {
							return;
						}

						// Standard arguments
						args = {name : file.target_name || file.name};

						// Only add chunking args if needed
						if (settings.chunk_size && features.chunking) {
							chunkSize = settings.chunk_size;
							chunks = Math.ceil(file.size / chunkSize);
							curChunkSize = Math.min(chunkSize, file.size - (chunk  * chunkSize));

							// Blob is string so we need to fake chunking, this is not
							// ideal since the whole file is loaded into memory
							if (typeof(blob) == 'string') {
								chunkBlob = blob.substring(chunk * chunkSize, chunk * chunkSize + curChunkSize);
							} else {
								// Slice the chunk
								chunkBlob = blob.slice(chunk * chunkSize, curChunkSize);
							}

							// Setup query string arguments
							args.chunk = chunk;
							args.chunks = chunks;
						} else {
							curChunkSize = file.size;
						}

						// Setup XHR object
						xhr = new XMLHttpRequest();
						upload = xhr.upload;

						// Do we have upload progress support
						if (upload) {
							upload.onprogress = function(e) {
								file.loaded = Math.min(file.size, loaded + e.loaded - multipartDeltaSize); // Loaded can be larger than file size due to multipart encoding
								up.trigger('UploadProgress', file);
							};
						}

						// Add name, chunk and chunks to query string on direct streaming
						if (!up.settings.multipart || !features.multipart) {
							url = plupload.buildUrl(up.settings.url, args);
						} else {
							args.name = file.target_name || file.name;
						}

						xhr.open("post", url, true);

						xhr.onreadystatechange = function() {
							var httpStatus, chunkArgs;

							if (xhr.readyState == 4) {
								// Getting the HTTP status might fail on some Gecko versions
								try {
									httpStatus = xhr.status;
								} catch (ex) {
									httpStatus = 0;
								}

								// Is error status
								if (httpStatus >= 400) {
									up.trigger('Error', {
										code : plupload.HTTP_ERROR,
										message : 'HTTP Error.',
										file : file,
										status : httpStatus
									});
								} else {
									// Handle chunk response
									if (chunks) {
										chunkArgs = {
											chunk : chunk,
											chunks : chunks,
											response : xhr.responseText,
											status : httpStatus
										};

										up.trigger('ChunkUploaded', file, chunkArgs);
										loaded += curChunkSize;

										// Stop upload
										if (chunkArgs.cancelled) {
											file.status = plupload.FAILED;
											return;
										}

										file.loaded = Math.min(file.size, (chunk + 1) * chunkSize);
									} else {
										file.loaded = file.size;
									}

									up.trigger('UploadProgress', file);

									// Check if file is uploaded
									if (!chunks || ++chunk >= chunks) {
										file.status = plupload.DONE;
										up.trigger('FileUploaded', file, {
											response : xhr.responseText,
											status : httpStatus
										});
									} else {
										// Still chunks left
										uploadNextChunk();
									}
								}
							}
						};

						// Set custom headers
						plupload.each(up.settings.headers, function(value, name) {
							xhr.setRequestHeader(name, value);
						});

						// Build multipart request
						if (up.settings.multipart && features.multipart) {
							// Has FormData support like Chrome 6+, Safari 5+, Firefox 4
							if (!xhr.sendAsBinary) {
								var data = new FormData();

								// Add multipart params
								plupload.each(plupload.extend(args, up.settings.multipart_params), function(value, name) {
									data.append(name, value);
								});

								// Add file and send it
								data.append(up.settings.file_data_name, chunkBlob);
								xhr.send(data);

								return;
							}

							// Gecko multipart request
							xhr.setRequestHeader('Content-Type', 'multipart/form-data; boundary=' + boundary);

							// Append multipart parameters
							plupload.each(plupload.extend(args, up.settings.multipart_params), function(value, name) {
								multipartBlob += dashdash + boundary + crlf +
									'Content-Disposition: form-data; name="' + name + '"' + crlf + crlf;

								multipartBlob += value + crlf;
							});

							mimeType = plupload.mimeTypes[file.name.replace(/^.+\.([^.]+)/, '$1')] || 'application/octet-stream';

							// Build RFC2388 blob
							multipartBlob += dashdash + boundary + crlf +
								'Content-Disposition: form-data; name="' + up.settings.file_data_name + '"; filename="' + file.name + '"' + crlf +
								'Content-Type: ' + mimeType + crlf + crlf +
								chunkBlob + crlf +
								dashdash + boundary + dashdash + crlf;

							multipartDeltaSize = multipartBlob.length - chunkBlob.length;
							chunkBlob = multipartBlob;
						} else {
							// Binary stream header
							xhr.setRequestHeader('Content-Type', 'application/octet-stream');
						}

						if (xhr.sendAsBinary) {
							xhr.sendAsBinary(chunkBlob); // Gecko
						} else {
							xhr.send(chunkBlob); // WebKit
						}
					}

					// Start uploading chunks
					uploadNextChunk();
				}

				nativeFile = html5files[file.id];
				resize = up.settings.resize;

				if (features.jpgresize) {
					// Resize image if it's a supported format and resize is enabled
					if (resize && /\.(png|jpg|jpeg)$/i.test(file.name)) {
						scaleImage(nativeFile, resize.width, resize.height, /\.png$/i.test(file.name) ? 'image/png' : 'image/jpeg', function(res) {
							// If it was scaled send the scaled image if it failed then
							// send the raw image and let the server do the scaling
							if (res.success) {
								file.size = res.data.length;
								sendBinaryBlob(res.data);
							} else {
								sendBinaryBlob(nativeFile.getAsBinary());
							}
						});
					} else {
						sendBinaryBlob(nativeFile.getAsBinary());
					}
				} else {
					sendBinaryBlob(nativeFile);
				}
			});

			callback({success : true});
		}
	});
})(plupload);
/**
 * plupload.html4.js
 *
 * Copyright 2010, Ryan Demmer
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

// JSLint defined globals
/*global plupload:false, window:false */

(function(plupload) {
	function getById(id) {
		return document.getElementById(id);
	}

	/**
	 * HTML4 implementation. This runtime has no special features it uses an form that posts files into an hidden iframe.
	 *
	 * @static
	 * @class plupload.runtimes.Html4
	 * @extends plupload.Runtime
	 */
	plupload.runtimes.Html4 = plupload.addRuntime("html4", {
		/**
		 * Returns a list of supported features for the runtime.
		 *
		 * @return {Object} Name/value object with supported features.
		 */
		getFeatures : function() {
			// Only multipart feature
			return {
				multipart: true
			};
		},

		/**
		 * Initializes the upload runtime.
		 *
		 * @method init
		 * @param {plupload.Uploader} uploader Uploader instance that needs to be initialized.
		 * @param {function} callback Callback to execute when the runtime initializes or fails to initialize. If it succeeds an object with a parameter name success will be set to true.
		 */
		init : function(uploader, callback) {
			uploader.bind("Init", function(up) {
				var container = document.body, iframe, url = "javascript", currentFile,
					input, currentFileId, IE = /MSIE/.test(navigator.userAgent), mimes = [],
					filters = up.settings.filters, i, ext, type, y;

				// Convert extensions to mime types list
				for (i = 0; i < filters.length; i++) {
					ext = filters[i].extensions.split(/,/);

					for (y = 0; y < ext.length; y++) {
						type = plupload.mimeTypes[ext[y]];

						if (type) {
							mimes.push(type);
						}
					}
				}

				mimes = mimes.join(',');

				function createForm() {
					var form, input, bgcolor;

					// Setup unique id for form
					currentFileId = plupload.guid();

					// Create form
					form = document.createElement('form');
					form.setAttribute('id', 'form_' + currentFileId);
					form.setAttribute('method', 'post');
					form.setAttribute('enctype', 'multipart/form-data');
					form.setAttribute('encoding', 'multipart/form-data');
					form.setAttribute("target", up.id + '_iframe');
					form.style.position = 'absolute';

					// Create input and set attributes
					input = document.createElement('input');
					input.setAttribute('id', 'input_' + currentFileId);
					input.setAttribute('type', 'file');
					input.setAttribute('accept', mimes);
					input.setAttribute('size', 1);

					// Set input styles
					plupload.extend(input.style, {
						width : '100%',
						height : '100%',
						opacity : 0
					});

					// Show the container if shim_bgcolor is specified
					bgcolor = up.settings.shim_bgcolor;
					if (bgcolor) {
						form.style.background = bgcolor;
					}

					// no opacity in IE
					if (IE) {
						plupload.extend(input.style, {
							filter : "alpha(opacity=0)"
						});
					}

					// add change event
					plupload.addEvent(input, 'change', function(e) {
						var element = e.target, name, files = [];

						if (element.value) {
							getById('form_' + currentFileId).style.top = -0xFFFFF + "px";

							// Get file name
							name = element.value.replace(/\\/g, '/');
							name = name.substring(name.length, name.lastIndexOf('/') + 1);

							// Push files
							files.push(new plupload.File(currentFileId, name));

							// Create and position next form
							createForm();

							// Fire FilesAdded event
							if (files.length) {
								uploader.trigger("FilesAdded", files);
							}
						}
					});

					// append to container
					form.appendChild(input);
					container.appendChild(form);

					up.refresh();
				}


				function createIframe() {
					// Create iframe and add it to the container
					iframe = document.createElement('iframe');
					iframe.setAttribute('src', url + ':""'); // javascript:"" for HTTPS issue on IE6, uses a variable to make an ignore for jslint
					iframe.setAttribute('id', up.id + '_iframe');
					iframe.setAttribute('name', up.id + '_iframe');
					iframe.style.display = 'none';

					container.appendChild(iframe);

					// Add IFrame onload event
					plupload.addEvent(iframe, 'load', function(e) {
						var n = e.target, el, result;

						// Ignore load event if there is no file
						if (!currentFile) {
							return;
						}

						try {
							el = n.contentWindow.document || n.contentDocument || window.frames[n.id].document;
						} catch (ex) {
							// Probably a permission denied error
							up.trigger('Error', {
								code : plupload.SECURITY_ERROR,
								message : 'Security error.',
								file : currentFile
							});

							return;
						}

						// Get result
						result = el.documentElement.innerText || el.documentElement.textContent;

						// Assume no error
						if (result) {
							currentFile.status = plupload.DONE;
							currentFile.loaded = 1025;
							currentFile.percent = 100;

							up.trigger('UploadProgress', currentFile);
							up.trigger('FileUploaded', currentFile, {
								response : result
							});
						}
					});

					// Upload file
					up.bind("UploadFile", function(up, file) {
						var form, input;

						// File upload finished
						if (file.status == plupload.DONE || file.status == plupload.FAILED || up.state == plupload.STOPPED) {
							return;
						}

						// Get the form and input elements
						form = getById('form_' + file.id);
						input = getById('input_' + file.id);

						// Set input element name attribute which allows it to be submitted
						input.setAttribute('name', up.settings.file_data_name);

						// Store action
						form.setAttribute("action", up.settings.url);

						// Append multipart parameters
						plupload.each(plupload.extend({name : file.target_name || file.name}, up.settings.multipart_params), function(value, name) {
							var input = document.createElement('input');

							plupload.extend(input, {
								type : 'hidden',
								name : name,
								value : value
							});

							form.insertBefore(input, form.firstChild);
						});

						currentFile = file;

						// Hide the current form
						getById('form_' + currentFileId).style.top = -0xFFFFF + "px";

						form.submit();
						form.parentNode.removeChild(form);
					});
				}

				if (up.settings.container) {
					container = getById(up.settings.container);
					container.style.position = 'relative';
				}

				up.bind('StateChanged', function(up) {
					if (up.state == plupload.STARTED) {
						createIframe();
					}

					if (up.state == plupload.STOPPED) {
						window.setTimeout(function() {
							iframe.parentNode.removeChild(iframe);
						}, 0);
					}
				});

				// Refresh button, will reposition the input form
				up.bind("Refresh", function(up) {
					var browseButton, browsePos, browseSize;

					browseButton = getById(up.settings.browse_button);
					browsePos = plupload.getPos(browseButton, getById(up.settings.container));
					browseSize = plupload.getSize(browseButton);

					plupload.extend(getById('form_' + currentFileId).style, {
						top : browsePos.y + 'px',
						left : browsePos.x + 'px',
						width : browseSize.w + 'px',
						height : browseSize.h + 'px'
					});
				});

				// Remove files
				uploader.bind("FilesRemoved", function(up, files) {
					var i, n;

					for (i = 0; i < files.length; i++) {
						n = getById('form_' + files[i].id);
						n.parentNode.removeChild(n);
					}
				});

				// Create initial form
				createForm();
			});

			callback({success : true});
		}
	});
})(plupload);
