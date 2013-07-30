/*
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * By: Daniel Rossi, <electroteque@gmail.com>
 *     Anssi Piirainen, <api@iki.fi>
 *
 * Copyright (c) 2009 Electroteque Multimedia
 * Copyright (c) 2009-2011 Flowplayer Oy
 *
 * Released under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */
package org.flowplayer.captions {
    import flash.utils.*;

    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ClipEvent;
    import org.flowplayer.model.Cuepoint;
    import org.flowplayer.model.PlayerEvent;
    import org.flowplayer.model.Plugin;
    import org.flowplayer.model.PluginModel;
    import org.flowplayer.util.Log;
    import org.flowplayer.util.PropertyBinder;
    import org.flowplayer.view.Flowplayer;

    /**
     * A Subtitling and Captioning Plugin. Supports the following:
     * <ul>
     * <li>Loading subtitles from the Timed Text or Subrip format files.</li>
     * <li>Styling text from styles set in the Time Text format files.</li>
     * <li>Loading subtitles or cuepoints from a JSON config.</li>
     * <li>Loading subtitles or cuepoints from embedded FLV and MP4 cuepoints.</li>
     * <li>Controls an external content plugin.</li>
     * </ul>
     * <p>
     * To setup an external subtitle caption file the config would look like so:
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
    public class CaptionPlugin implements Plugin {
        private var log:Log = new Log(this);
        private var _player:Flowplayer;
        private var _model:PluginModel;
        private var _captionView:CaptionViewDelegate;
        private var _config:Config;
        private var _currentCaption:Caption;
        private var _loader:CaptionLoading;

        /**
         * Sets the plugin model. This gets called before the plugin
         * has been added to the display list and before the player is set.
         * @param plugin
         */
        public function onConfig(plugin:PluginModel):void {
            _model = plugin;
            _config = new PropertyBinder(new Config(), null).copyProperties(plugin.config) as Config;
            if (! _config.captionTarget) {
                throw Error("No captionTarget defined in the configuration");
            }
        }

        /**
         * Sets the Flowplayer interface. The interface is immediately ready to use, all
         * other plugins have been loaded an initialized also.
         * @param player
         */
        public function onLoad(player:Flowplayer):void {
            log.debug("onLoad");
            _player = player;

            addListeners();
            _player.onLoad(onPlayerInitialized);

            if (hasCaptions()) {
                _loader = new CaptionLoader(_player, _player.playlist, _config);
                _loader.load(function():void {
                    parseIfLoadedAndViewAvailable();
                    _model.dispatchOnLoad();
                });
            } else {
                _model.dispatchOnLoad();
            }
        }

        [External]
        public function loadCaptions(clipIndex:int, captions:*):void {
            if (! captions) return;
            log.info("loading captions from " + captions);

            Clip(_player.playlist.clips[clipIndex]).setCustomProperty("captionUrl", captions);

           if (!_loader) {
              _loader = new CaptionLoader(_player, _player.playlist, _config);
           }

            //#574 re-load captions for the clip not the entire playlist.
            _loader.loadClipCaption(Clip(_player.playlist.clips[clipIndex]),function():void {
                    parseIfLoadedAndViewAvailable();

            });
        }



        private function parseIfLoadedAndViewAvailable():void {
            log.debug("parseIfLoadedAndViewAvailable(), loaded? " + (_loader && _loader.loaded) + ", view available? " + (_captionView as Boolean));
            if (_captionView && _loader && _loader.loaded) {
                log.debug("parseIfLoadedAndViewAvailable(), about to start parsing");
                _loader.rootStyle = _captionView.style;
                _loader.parse();
            }
        }

        private function onPlayerInitialized(event:PlayerEvent):void {
            log.debug("onPlayerInitialized()");
            _captionView = new CaptionViewDelegate(this, _player,  _config);
            parseIfLoadedAndViewAvailable();
        }

        private function addListeners():void {
            _player.playlist.onPause(function(event:ClipEvent):void {
                if (! _currentCaption) return;
                _currentCaption.clearDurationInterval();
            });

            _player.playlist.onResume(function(event:ClipEvent):void {
                if (! _currentCaption) return;
                _currentCaption.resumeDurationInterval(_player.status.time, clearCaption);
            });

            _player.playlist.onStop(function(event:ClipEvent):void {
                clearCaption();
            });
            _player.playlist.onSeek(function(event:ClipEvent):void {
                clearCaption();
            });
            _player.playlist.onCuepoint(onCuepoint);

            _player.playlist.commonClip.onNetStreamEvent(onNetStreamCaption);

        }
        protected function clearCaption(clearHTML:Boolean = true):void {
            if (_currentCaption == null) return;

            _currentCaption.clearDurationInterval();
            _currentCaption = null;

            if (clearHTML)
                _captionView.html = "";
        }

        protected function captionsDisabledForClip(clip:Clip):Boolean {
            if (! clip.getCustomProperty("showCaptions")) return false;
            return ! clip.getCustomProperty("showCaptions");
        }

        protected function onNetStreamCaption(event:ClipEvent):void {
            log.debug("onNetStreamCaption()");
            if (event.info != "onTextData") return;

            var clip:Clip = event.target as Clip;
            if (captionsDisabledForClip(clip)) return;

            var data:Object = event.info2;
            log.debug("onNetStreamCaption() data: ", data);

            var text:String = data['text'];

            if (! data.hasOwnProperty('text')) {
                return;
            }

            if (clip.customProperties && clip.customProperties.hasOwnProperty("captionsTrackFilter")) {
                var captionsTrackFilter:String = clip.customProperties['captionsTrackFilter'];
                var filterKey:String = captionsTrackFilter.substr(0, captionsTrackFilter.indexOf('='));
                var filterValue:String = captionsTrackFilter.substr(captionsTrackFilter.indexOf('=') + 1);

                if (data.hasOwnProperty(filterKey) && (data[filterKey] + "") != filterValue) {
                    log.debug("Skipping " + text + ", " + filterKey + " filtered out : " + (data[filterKey] + "") + " != " + filterValue);
                    return;
                }
            }
            text = text.replace(/\n/, '<br>');
            _captionView.html = "<p>" + text + "</p>";
        }

        protected function onCuepoint(event:ClipEvent):void {
            log.debug("onCuepoint", event.info.parameters);

            //#449 for manually created cuepoints without text do not create a caption.
            if (!event.info.parameters.text) return;

            var clip:Clip = event.target as Clip;
            if (captionsDisabledForClip(clip)) {
                log.debug("captions disabled for clip " + clip);
                return;
            }

            if (clip.customProperties && clip.customProperties.hasOwnProperty("captionUrl")) {
                var cue:Object = event.info;
                if (! cue.hasOwnProperty("captionType") ||  cue["captionType"] != "external") {
                    // we are using a captions file and this cuepoint is not from the file,
                    // it is propably and embedded cuepoint
                    //#449 check for empty captionType property here for manual or embedded cuepoints.
                    var captionType:String = cue.hasOwnProperty("captionType") ? cue["captionType"] : "";
                    log.debug("ignoring cuepoint with captionType " + captionType);
                    return;
                }
            }



            clearCaption(false);
            _currentCaption = event.info.parameters is Caption ? event.info.parameters : createCaption(event.info,  event.info.parameters);
            setViewStyleForCaption();
            var html:String = _currentCaption.getHtml(event.info as Cuepoint);
            log.debug("caption html is " + html);
            _captionView.html = html;
            _currentCaption.setDurationInterval(_player.status.time, clearCaption);
        }

        private function createCaption(cue:Object,  cueInfo:Object):Caption {
           // var cueText:String = cueInfo.hasOwnProperty("text") ? cueInfo.text : "";
            return new Caption(_config.template, cue.time, cueInfo.duration, cueInfo.text, null);
        }

        private function setViewStyleForCaption():void {
            var bgColor:String = (_captionView.style.getStyle("." + _currentCaption.style).backgroundColor ? _captionView.style.getStyle("." + _currentCaption.style).backgroundColor
                    : _captionView.style.rootStyle.backgroundColor);
            log.debug("bgColor: " + bgColor);
            _captionView.css({backgroundColor: bgColor});
        }

        private function hasCaptions():Boolean {
            var clips:Array = _player.playlist.clips;
            for (var i:Number = 0; i < clips.length; i++) {
                var clip:Clip = clips[i] as Clip;
                if (clip.customProperties && (clip.getCustomProperty("captions") || clip.getCustomProperty("captionUrl"))) {
                    return true;
                }
            }
            return false;
        }

        public function getDefaultConfig():Object {
            return { bottom: 25, width: '80%'};
        }

        // for testing (see the test folder)
        internal function set config(value:Config):void {
            _config = value;
        }
    }
}
