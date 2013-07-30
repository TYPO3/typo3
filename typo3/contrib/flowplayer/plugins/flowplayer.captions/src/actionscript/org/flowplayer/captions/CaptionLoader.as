/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * By: Anssi Piirainen, <api@iki.fi>
 *
 * Copyright (c) 2008-2011 Flowplayer Oy
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */
package org.flowplayer.captions {
    import flash.utils.Dictionary;

    import org.flowplayer.captions.parsers.CaptionParser;
    import org.flowplayer.captions.parsers.JSONParser;
    import org.flowplayer.captions.parsers.SRTParser;
    import org.flowplayer.captions.parsers.TTXTParser;
    import org.flowplayer.controller.ResourceLoader;
    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ErrorCode;
    import org.flowplayer.model.Playlist;
    import org.flowplayer.util.Log;
    import org.flowplayer.view.ErrorHandler;
    import org.flowplayer.view.FlowStyleSheet;
    import org.flowplayer.view.Flowplayer;

    internal class CaptionLoader implements CaptionLoading, ErrorHandler {
        private var log:Log = new Log(this);
        private var _playlist:Playlist;
        private var _totalCaptions:int;
        private var _player:Flowplayer;
        private var _numCaptionsLoaded:int;
        private var _captionData:Dictionary;
        private var _allLoaded:Boolean = false;
        private var _loadedCallback:Function;
        private var _rootStyle:FlowStyleSheet;
        private var _config:Config;

        public function CaptionLoader(player:Flowplayer, playlist:Playlist,  config:Config) {
            _player = player;
            _playlist = playlist;
            _captionData = new Dictionary();
            _config = config;
        }

        public function set rootStyle(style:FlowStyleSheet):void {
            _rootStyle = style;
        }

        public function parse():void {
            for (var o:Object in _captionData) {
                var clip:Clip = o as Clip;
                parseCuePoints(clip,  _captionData[clip]);
            }
        }

        /**
         * Load captions for an individual clip
         * @param clip
         * @param loadedCallback
         */
        public function loadClipCaption(clip:Clip, loadedCallback:Function):void {
            _loadedCallback = loadedCallback;

            //reset to allow for callback completion
            _allLoaded = false;
            _numCaptionsLoaded--;

            log.debug("Reloading captions for clip " + clip);

            loadCaptionFile(clip, clip.getCustomProperty("captionUrl") as String);
        }

        public function load(loadedCallback:Function):void {
            _loadedCallback = loadedCallback;
            _numCaptionsLoaded = 0;
            _totalCaptions = 0;

            // count files
            iterateCaptions(function (clip:Clip):void {
                _totalCaptions++;
            });
            // load files
            iterateCaptions(function(clip:Clip):void {
            	if (clip.getCustomProperty("captions")) {
                    _captionData[clip] = clip.getCustomProperty("captions");
                    checkAllLoaded();
            	} else {
            		loadCaptionFile(clip, clip.getCustomProperty("captionUrl") as String);
            	}
            });
        }

        /**
         * Joel Hulen - April 20, 2009
         * Modified loadCaptionFile to add the fileExtension parameter.
         */
        protected function loadCaptionFile(clip:Clip, captionFile:String = null):void {
            var loader:ResourceLoader = _player.createLoader();
            loader.errorHandler = this;

            if (captionFile) {
                log.info("loading captions from file " + captionFile);
                loader.addTextResourceUrl(captionFile);
            }

            loader.load(null, function(loader:ResourceLoader):void {
                _captionData[clip] = loader.getContent(captionFile);
                checkAllLoaded();
            });
        }

        protected function parseCuePoints(clip:Clip, captionData:*):void
        {
            log.debug("captions file loaded, parsing cuepoints");
            var parser:CaptionParser = createParser(clip, captionData);

            // remove all existing cuepoints
            clip.removeCuepoints(function(cue:Object):Boolean {
                return cue.hasOwnProperty("__caption");
            });

            clip.addCuepoints(parser.parse(captionData));
        }

        protected function doAddCaptions(clip:Clip, captions:Array):void {
        	parseCuePoints(clip, captions);
            _numCaptionsLoaded++;
            log.debug(_numCaptionsLoaded + " clip captions out of " + _totalCaptions + " loaded");
            if (_numCaptionsLoaded == _totalCaptions && ! _allLoaded) {
            	log.debug("all caption files loaded, executing callback");
                _allLoaded = true;
                _loadedCallback();
            }
        }

		private function iterateCaptions(callback:Function):void {
            var clips:Array = _playlist.clips;
            for (var i:Number = 0; i < clips.length; i++) {
                var clip:Clip = _playlist.clips[i] as Clip;
                var captions:Array = clip.customProperties ? clip.getCustomProperty("captions") as Array : null;
                if (clip.getCustomProperty("captions") || clip.getCustomProperty("captionUrl")) {
                    callback(clip);
                }
            }
        }

        private function checkAllLoaded():void {
            _numCaptionsLoaded++;
            log.debug(_numCaptionsLoaded + " captions files out of " + _totalCaptions + " loaded");
            if (_numCaptionsLoaded >= _totalCaptions && ! _allLoaded) {
                log.debug("all captions loaded, dispatching onLoad()");
                _allLoaded = true;
                _loadedCallback();
            }
        }

        internal function createParser(clip:Clip,  captionData:Object):CaptionParser {
            var parser:CaptionParser;
            var parserType:String = getParserType(clip,  captionData);
            log.debug("createParser(), parser type is '" + parserType + "'");

			if (parserType == "subrip") {
				parser = new SRTParser(_config.template);

			} else if (parserType == "json") {
				parser = new JSONParser(_config.template);

			} else if (parserType == "tt") {
				parser = new TTXTParser(_config.template);
				TTXTParser(parser).simpleFormatting = _config.simpleFormatting;

			} else {
				throw new Error("Unrecognized captions file extension");
			}

            parser.styles = _rootStyle;
            return parser;
        }

        private function getParserType(clip:Clip, captionData:Object):String {
            var type:Object = clip.getCustomProperty("captionFormat");
            if (type) return String(type);

            if (String(captionData).charAt(0) == "1") return "subrip";
            if (captionData is Array || captionData.toString().indexOf('[') == 0) return "json";
            if (new XML(captionData).localName() == "tt") return "tt";
            return null;
        }

        // called when caption file load fails
        public function handleError(error:ErrorCode, info:Object = null, throwError:Boolean = true):void {
            log.warn("failed to load captions file: " + info);
            checkAllLoaded();
        }

        public function showError(message:String):void {
        }

        public function get loaded():Boolean {
            return _allLoaded;
        }
    }
}
