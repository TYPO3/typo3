/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * By: Daniel Rossi, <electroteque@gmail.com>
 * Copyright (c) 2009 Electroteque Multimedia
 *
 * Copyright 2009 Joel Hulen, loading of captions files from URLs without a file extension
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */
package org.flowplayer.captions {
    import flash.events.MouseEvent;
    import flash.utils.*;

    import org.flowplayer.captions.parsers.CaptionParser;
    import org.flowplayer.captions.parsers.JSONParser;
    import org.flowplayer.captions.parsers.SRTParser;
    import org.flowplayer.captions.parsers.TTXTParser;
    import org.flowplayer.controller.ResourceLoader;
    import org.flowplayer.layout.LayoutEvent;
    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ClipEvent;
    import org.flowplayer.model.ClipEventType;
    import org.flowplayer.model.DisplayPluginModel;
    import org.flowplayer.model.PlayerEvent;
    import org.flowplayer.model.Plugin;
    import org.flowplayer.model.PluginModel;
    import org.flowplayer.util.PropertyBinder;
    import org.flowplayer.view.AbstractSprite;
    import org.flowplayer.view.FlowStyleSheet;
    import org.flowplayer.view.Flowplayer;
    import org.flowplayer.view.Styleable;
    import org.flowplayer.model.PlayerEventType;
    
    

    /**
     * A Subtitling and Captioning Plugin. Supports the following:
     * <ul>
     * <li>Loading subtitles from the Timed Text or Subrip format files.</li>
     * <li>Styling text from styles set in the Time Text format files.</li>
     * <li>Loading subtitles or cuepoints from a JSON config.</li>
     * <li>Loading subtitles or cuepoints from embedded FLV cuepoints.</li>
     * <li>Controls an external content plugin.</li>
     * <li>Working with the Javascript captions plugin, it enables a scrolling cuepoint thumbnail menu.</li>
     * </ul>
     * <p>
     * To setup an external subtitle caption file the config would look like so:
     *
     * captionType: 'external'
     *
     * For Timed Text
     *
     * captionUrl: 'timedtext.xml'
     *
     * For Subrip
     *
     * captionUrl: 'subrip.srt'
     *
     * <p>
     * To enable the captioning to work properly a caption target must link to a content plugin like so:
     *
     * captionTarget: 'content'
     *
     * Where content is the config for a loaded content plugin.
     *
     * <p>
     *
     * To be able to customised the subtitle text a template string is able to tell the captioning plugin
     * which text property is to be used for the subtitle text which is important for embedded cuepoints. It also
     * enables to add extra properties to the text like so:
     *
     * template: '{text} {time} {custom}'
     *
     * <p>
     * To enable simple formatting of text if Timed Text has style settings,
     * only "fontStyle", "fontWeight" and "textAlign" properties are able to be set like so:
     *
     * simpleFormatting: true
     *
     * @author danielr, Anssi Piirainen (api@iki.fi)
     */
    public class Caption extends AbstractSprite implements Plugin, Styleable {
        private var _player:Flowplayer;
        private var _model:PluginModel;
        private var _captionView:*;
        private var _config:Config;
        private var _styles:FlowStyleSheet;
        private var _viewModel:DisplayPluginModel;
        private var _template:String;
        private var _button:CCButton;
        private var _totalCaptions:int;
        private var _numCaptionsLoaded:int;
        private var _initialized:Boolean;

        private var _currentCaption:Object;
        private var _captionHeightRatio:Number;
        private var _captionWidthRatio:Number;
        private var _captionFontSizes:Object;

		private var _captionViewWidth:int = 0;

        /**
         * Sets the plugin model. This gets called before the plugin
         * has been added to the display list and before the player is set.
         * @param plugin
         */
        public function onConfig(plugin:PluginModel):void {

            _model = plugin;
            _config = new PropertyBinder(new Config(), null).copyProperties(plugin.config) as Config;
           
        }

        
        public function hasCaptions():Boolean {
            var clips:Array = _player.playlist.clips;
            for (var i:Number = 0; i < clips.length; i++) {
                var clip:Clip = clips[i] as Clip;
                if (clip.customProperties && (clip.getCustomProperty("captions") || clip.getCustomProperty("captionUrl"))) {
                    return true;
                }
            }
            return false;

        }

        /**
         * Sets the Flowplayer interface. The interface is immediately ready to use, all
         * other plugins have been loaded an initialized also.
         * @param player
         */
        public function onLoad(player:Flowplayer):void {
            log.debug("onLoad");
            _initialized = false;
            _player = player;
            _player.playlist.onCuepoint(onCuepoint);

			_player.playlist.commonClip.onNetStreamEvent(onNetStreamCaption);

            if (! _config.captionTarget) {
                throw Error("No captionTarget defined in the configuration");
            }
            _viewModel = _player.pluginRegistry.getPlugin(_config.captionTarget) as DisplayPluginModel;
            _captionView = _viewModel.getDisplayObject();
			
            _player.onLoad(onPlayerInitialized);

            if (hasCaptions()) {
            	loadClipCaptions();
            } else {
                _model.dispatchOnLoad();
            }
        }

        private function initializeRatios():void {
            _captionHeightRatio = _captionView.height / _player.screen.getDisplayObject().height;
            _captionWidthRatio = _captionView.width / _player.screen.getDisplayObject().width;
        }

        private function onPlayerInitialized(event:PlayerEvent):void {
            initCaptionView();

            log.debug("button", _config.button);
            if (_config.button) {
                _button = new CCButton(_player, _config.button["label"]);
                _player.addToPanel(_button, _config.button);

                _button.isDown = _viewModel.visible;
                _button.clickArea.addEventListener(MouseEvent.CLICK, function(event:MouseEvent):void {
                    _button.isDown = _player.togglePlugin(_config.captionTarget);
                });
            }

            if (_viewModel.visible)
                initializeRatios();
            else
            {
                _captionView.alpha = 0;
                _player.togglePlugin(_config.captionTarget);
                initializeRatios();
                _player.togglePlugin(_config.captionTarget);
                _captionView.alpha = 1;
            }

            _player.playlist.onPause(function(event:ClipEvent):void {
                if (_currentCaption != null)
                    clearInterval(_currentCaption.captionInterval);
            });

            _player.playlist.onResume(function(event:ClipEvent):void {
                if (_currentCaption != null)
                {
                    var newDuration:Number = _currentCaption.endTime - _player.status.time;
                    if (newDuration > 0)
                        _currentCaption.captionInterval = setInterval(clearCaption, newDuration);
                }
            });

            _player.playlist.onStop(function(event:ClipEvent):void {
                clearCaption();
            });
            _player.playlist.onSeek(function(event:ClipEvent):void {
                clearCaption();
            });

			_player.onBeforeFullscreen(function(event:PlayerEvent):void {
				_captionViewWidth = _captionView.width;
			});
            _player.onFullscreen(resizeCaptionView);
            _player.onFullscreenExit(resizeCaptionView);


        }

        private function resizeCaptionView(event:PlayerEvent):void
        {
            var newWidth:Number = _player.screen.getDisplayObject().width * _captionWidthRatio;
            var newHeight:Number = _player.screen.getDisplayObject().height * _captionHeightRatio;

		//	log.info("resizing, width:" +_player.screen.getDisplayObject().width + " * "+_captionWidthRatio+" = "+ newWidth);
		//	log.info("resizing, width:" +_player.screen.getDisplayObject().height + " * "+_captionHeightRatio+" = "+ newHeight);

            if (event.type == (PlayerEventType.FULLSCREEN).name)
            {
                _captionFontSizes = {};
                var styleNames:Array = _captionView.style.styleSheet.styleNames;
                for (var i:int = 0; i < styleNames.length; i++)
                {
                    if (_captionView.style.getStyle(styleNames[i]).fontSize)
                    {
                        var style:Object = _captionView.style.getStyle(styleNames[i]);

                        _captionFontSizes[styleNames[i]] = style.fontSize;

					//	log.info ("current font size "+style.fontSize+", ratio "+ newWidth+"/"+_captionView.width+" = "+(newWidth / _captionViewWidth));
                        style.fontSize = style.fontSize * newWidth / _captionViewWidth;
                        _captionView.style.setStyle(styleNames[i], style);
                    }
                }
            }
            else
            {    // setting back fontsizes ..
                for (var styleName:String in _captionFontSizes)
                {
                    style = _captionView.style.getStyle(styleName);
                    style.fontSize = _captionFontSizes[styleName];
                    _captionView.style.setStyle(styleName, style);
                }
            }

            var newY:Number = _captionView.y;
            if (newY > _player.screen.getDisplayObject().height / 2)
                newY = _captionView.y - (newHeight - _captionView.height);

            var newX:Number = _captionView.x - (newWidth - _captionView.width);

            _player.css(_config.captionTarget, {y: newY, x: newX, height: newHeight, width: newWidth});
        }

        private function onPlayerResized(event:LayoutEvent):void {
            log.debug("onPlayerResized");
            _button.x = _captionView.x + _captionView.width + 3;
            _button.y = _captionView.y;
        }

        
        private function loadClipCaptions():void {
            // count files
            iterateCaptions(function (clip:Clip):void {
                _totalCaptions++;
            });
            // load files
            iterateCaptions(function(clip:Clip):void {
            	if (clip.getCustomProperty("captions")) {
            		loadCaption(clip, clip.getCustomProperty("captions") as Array);
            	} else {
            		loadCaptionFile(clip, clip.getCustomProperty("captionUrl") as String);
            	}
            });
        }
        
		
		private function iterateCaptions(callback:Function):void {
            var clips:Array = _player.playlist.clips;
            for (var i:Number = 0; i < clips.length; i++) {
                var clip:Clip = _player.playlist.clips[i] as Clip;
                var captions:Array = clip.customProperties ? clip.getCustomProperty("captions") as Array : null;
                if (clip.getCustomProperty("captions") || clip.getCustomProperty("captionUrl")) {
                    callback(clip);
                }
            }
        }

        /**
         * Loads a new stylesheet and changes the style from the loaded sheet.
         * @param clipIndex
         * @param captionURL the URL to load the caption file from
         * @param fileExtension optional file extension to be used if captionURL does not use an extension, one of
         * 'xml', 'srt', 'tx3g', 'qtxt'
         */
        [External]
        public function loadCaptions(clipIndex:int, captionsUrl:String = null):void {
            if (! captionsUrl) return;
            log.info("loading captions from " + captionsUrl);
            loadCaptionFile(_player.playlist.clips[clipIndex], captionsUrl);
        }
        
        [External]
        public function addCaptions(clipIndex:int, captions:Array):void {
            if (! captions) return;
            log.info("loading captions from " + captions);
            loadCaption(_player.playlist.clips[clipIndex], captions);
        }

        /**
         * Sets style properties.
         */
        public function css(styleProps:Object = null):Object {
            var result:Object = _captionView.css(styleProps);
            return result;
        }
        
        protected function loadCaption(clip:Clip, captions:Array):void {
        	parseCuePoints(clip, captions);
            _numCaptionsLoaded++;
            log.debug(_numCaptionsLoaded + " clip captions out of " + _totalCaptions + " loaded");
            if (_numCaptionsLoaded == _totalCaptions && ! _initialized) {
            	log.debug("all caption files loaded, dispatching onLoad()");
                _initialized = true;
                _model.dispatchOnLoad();
            }
        }

        /**
         * Joel Hulen - April 20, 2009
         * Modified loadCaptionFile to add the fileExtension parameter.
         */
        protected function loadCaptionFile(clip:Clip, captionFile:String = null):void {
            var loader:ResourceLoader = _player.createLoader();

            if (captionFile) {
                log.info("loading captions from file " + captionFile);
                loader.addTextResourceUrl(captionFile);
            }

            loader.load(null, function(loader:ResourceLoader):void {
                parseCuePoints(clip, loader.getContent(captionFile));

                _numCaptionsLoaded++;
                log.debug(_numCaptionsLoaded + " captions files out of " + _totalCaptions + " loaded");
                if (_numCaptionsLoaded == _totalCaptions && ! _initialized) {
                    log.debug("all captions loaded, dispatching onLoad()");
                    _initialized = true;
                    _model.dispatchOnLoad();
                }
            });
        }

        protected function parseCuePoints(clip:Clip, captionData:*):void
        {
            log.debug("captions file loaded, parsing cuepoints");
            var parser:CaptionParser = createParser(captionData);

            // remove all existing cuepoints
            clip.removeCuepoints(function(cue:Object):Boolean {
                return cue.hasOwnProperty("__caption");
            });

            try {
            	clip.addCuepoints(parser.parse(captionData));
                //clip.addCuepoints(parser.parse(_captions.length > 0 ? _captions : captionData));
            } catch (e:Error) {
                log.error("parseCuePoints():" + e.message);
            }
            _captionView.style = parser.styles;
        }

        private function createParser(captionData:Object):CaptionParser {
            	var parser:CaptionParser;
            	
            	if (new XML(captionData).localName() == "tt") {
                    log.debug("parsing Timed Text captions");
                    parser = new TTXTParser();
                    TTXTParser(parser).simpleFormatting = _config.simpleFormatting;
                } else if (String(captionData).charAt(0) == "1") {
                    log.debug("parsing SubRip captions");
                    parser = new SRTParser();
            	} else if (captionData is Array || captionData.toString().indexOf('[')) {
                	parser = new JSONParser();
                } else {
                    throw new Error("Unrecognized captions file extension");
                }
            parser.styles = _captionView.style;
            return parser;
        }


        protected function parseTemplate(values:Object):String
        {
            for (var key:String in values) {

                if (typeof values[key] == 'object')
                {
                    parseTemplate(values[key]);
                } else {
                    _template = _template.replace("{" + key + "}", values[key]);
                }
            }

            if (values.time >= 0) {
                _template = _template.replace("{time}", values.time);
            }

            return _template;
        }

        protected function clearCaption(clearHTML:Boolean = true):void
        {
            if (_currentCaption == null) return;

            clearInterval(_currentCaption.captionInterval);
            _currentCaption = null;

            if (clearHTML)
                _captionView.html = "";
        }

		protected function captionsDisabledForClip(clip:Clip):Boolean {
			return clip.customProperties && clip.customProperties.hasOwnProperty("showCaptions") && ! clip.customProperties["showCaptions"];
		}

		protected function onNetStreamCaption(event:ClipEvent):void {
			if ( event.info != "onTextData" )
				return;

			var clip:Clip = event.target as Clip;
			var data:Object = event.info2;
			var text:String = data['text'];
			
			if ( captionsDisabledForClip(clip) )
				return;
			
			if ( ! data.hasOwnProperty('text') )
				return;

            if (clip.customProperties && clip.customProperties.hasOwnProperty("captionsTrackFilter")) {
				var captionsTrackFilter:String = clip.customProperties['captionsTrackFilter'];
				var filterKey:String = captionsTrackFilter.substr(0, captionsTrackFilter.indexOf('='));
				var filterValue:String = captionsTrackFilter.substr(captionsTrackFilter.indexOf('=')+1);
				
				if ( data.hasOwnProperty(filterKey) && (data[filterKey]+"") != filterValue ) {
					log.debug("Skipping "+ text + ", "+filterKey+" filtered out : "+(data[filterKey]+"")+" != "+filterValue);
					return;
				}
			}

			text = text.replace(/\n/, '<br>');

            _captionView.html = "<p>" + text + "</p>";
			
		}

        protected function onCuepoint(event:ClipEvent):void {
            log.debug("onCuepoint", event.info.parameters);

            var clip:Clip = event.target as Clip;
            if ( captionsDisabledForClip(clip) )
				return;
				
            if (clip.customProperties && clip.customProperties.hasOwnProperty("captionUrl")) {
                var cue:Object = event.info;
                if (! cue.hasOwnProperty("captionType") || cue["captionType"] != "external") {
                    // we are using a captions file and this cuepoint is not from the file
                    return;
                }
            }

            clearCaption(false);

            _template = _config.template;
            var bgColor:String = (_captionView.style.getStyle("." + event.info.parameters.style).backgroundColor ? _captionView.style.getStyle("." + event.info.parameters.style).backgroundColor
                    : _captionView.style.rootStyle.backgroundColor);

            _captionView.css({backgroundColor: bgColor});
            var text:String = (_template ? parseTemplate(event.info) : event.info.parameters.text);
            text = text.replace(/\n/, '<br>');

            _captionView.html = "<p class='" + event.info.parameters.style + "'>" + text + "</p>";
            if (Number(event.info.parameters.end) > 0)
            {
                _currentCaption = {
                    captionInterval: setInterval(clearCaption, Number(event.info.parameters.end)),
                    beginTime: _player.status.time,
                    endTime: _player.status.time + Number(event.info.parameters.end)
                };
            }
        }

        protected function initCaptionView():void {
            log.debug("creating content view");
            if (_config.captionTarget)
            {
                log.info("Loading caption target plugin: " + _config.captionTarget);

                if (_config.autoLayout)
                {
                    _captionView.css(getDefaultConfig());
                }
            } else {
                throw new Error("No caption target specified, please configure a Content plugin instance to be used as target");
            }
        }


        public override function set alpha(value:Number):void {
            super.alpha = value;
            if (!_captionView) return;
            _captionView.alpha = value;
        }

        public function getDefaultConfig():Object {
            return { bottom: 25, width: '80%'};
        }

        public function animate(styleProps:Object):Object {
            return _captionView.animate(styleProps);
        }

        public function onBeforeCss(styleProps:Object = null):void {
        }

        public function onBeforeAnimate(styleProps:Object):void {
        }
    }
}
