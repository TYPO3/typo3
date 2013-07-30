/*    
 *    Copyright (c) 2008-2011 Flowplayer Oy *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Flowplayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Flowplayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.model {
    import flash.display.DisplayObject;
    import flash.media.Video;
    import flash.net.NetStream;
    import flash.utils.Dictionary;

    import org.flowplayer.controller.ClipURLResolver;
    import org.flowplayer.flow_internal;
    import org.flowplayer.util.ArrayUtil;
    import org.flowplayer.util.URLUtil;
    import org.flowplayer.util.VersionUtil;

    use namespace flow_internal;

	/**
	 * @inheritDoc
	 */
	public class Clip extends ClipEventDispatcher implements Extendable {

        // the main playlist where this clip belongs to
        private var _playlist:Playlist;
        private var _childPlaylist:TimedPlaylist;
        private var _preroll:Clip;
        private var _postroll:Clip;
        private var _parent:Clip;
		private var _cuepoints:Dictionary;
		private var _cuepointsInNegative:Array;
		private var _baseUrl:String;
		private var _url:String;
        private var _urlsByResolver:Array;
        private var _urlResolverObjects:Array;
		private var _type:ClipType;
		private var _start:Number;
        private var _position:Number = -100;
		private var _duration:Number = 0;
        private var _metaData:Object = undefined;
		private var _autoPlay:Boolean = true;
		private var _autoPlayNext:Boolean = false;
		private var _autoBuffering:Boolean;
		private var _scaling:MediaSize;
		private var _accelerated:Boolean;
		private var _smoothing:Boolean;
		private var _content:DisplayObject;
		private var _originalWidth:int;
		private var _originalHeight:int;
        private var _bufferLength:int;
        private var _backBufferLength:int;
		private var _played:Boolean;
		private var _provider:String;
		private var _extension:ExtendableHelper = new ExtendableHelper();
		private var _fadeInSpeed:int;
		private var _fadeOutSpeed:int;
		private var _live:Boolean;		
		private var _linkUrl:String;
		private var _linkWindow:String;
		private var _image:Boolean;
		private var _cuepointMultiplier:Number;
        private var _urlResolvers:Array;
        private var _connectionProvider:String;
        private var _seekableOnBegin:Object;
        private var _clipObject:Object;
        private var _netStream:NetStream;
        private var _startDispatched:Boolean;
        private var _currentTime:Number = 0;
        private var _endLimit:Number = 0;
        private var _encoding:Boolean = false;
        private var _stopLiveOnPause:Boolean = true;

        public function Clip() {
            _childPlaylist = new TimedPlaylist();

			_cuepoints = new Dictionary();
			_cuepointsInNegative = [];
            _urlsByResolver = [];
			_start = 0;
			_bufferLength = 3;
            _backBufferLength = 30;
			_scaling = MediaSize.FILLED_TO_AVAILABLE_SPACE;
			_provider = "http";
			_smoothing = true;
			_fadeInSpeed = 1000;
			_fadeOutSpeed = 1000;
			_linkWindow = "_self";
			_image = true;
			_cuepointMultiplier = 1000;
            //#416 enable seekableOnBegin to enable the scrubbar correctly when autobuffering.
            _seekableOnBegin = true;
			_accelerated = false;
		}

		public static function create(clipObj:Object, url:String, baseUrl:String = null):Clip {
			return init(new Clip(), clipObj, url, baseUrl);
		}

        /**
         * Use Playlist#addClip() to add child clips to the playlist. This is for internal use only.
         * @param clip
         * @return
         */
        public function addChild(clip:Clip):void {
            clip.parent = this;
            if (clip.isPreroll) {
                _preroll = clip;
            }
            if (clip.isPostroll) {
                _postroll = clip;
            }
            if (clip.isMidroll) {
                log.info("adding midstream clip " + clip + ", position " + clip.position + " to parent clip " + this);
                _childPlaylist.addClip(clip);
            }
        }

        private static function init(clip:Clip, clipObj:Object, url:String, baseUrl:String = null):Clip {
            clip._clipObject = clipObj;
            clip._url = url;
            clip._baseUrl = baseUrl;
            clip._autoPlay = true;
			return clip;
        }

        public function getParentPlaylist():Playlist {
            return _playlist;
        }

        public function setParentPlaylist(playlist:Playlist):void {
            _playlist = playlist;
            var children:Array = _childPlaylist.clips;
            if (_preroll) {
                children.push(_preroll);
            }
            if (_postroll) {
                children.push(_postroll);
            }
            for (var i:int = 0; i < children.length; i++) {
                var clip:Clip = Clip(children[i]); 
                clip.setParentPlaylist(playlist);
                clip.setEventListeners(playlist);
            }
        }

        internal function setEventListeners(playlist:Playlist):void {
            unbindEventListeners();
            onAll(playlist.commonClip.onClipEvent);
            onBeforeAll(playlist.commonClip.onBeforeClipEvent);
        }

        internal function unbindEventListeners():void {
            unbind(_playlist.commonClip.onClipEvent);
            unbind(_playlist.commonClip.onBeforeClipEvent, null, true);
        }

        [Value]
        public function get index():int {
            return _playlist.indexOf(this._parent || this);
        }

		[Value]
		public function get isCommon():Boolean {
            if (! _playlist) return false;
			return this == _playlist.commonClip;
		}

		public function addCuepoints(cuepoints:Array):void {
			for (var i:Number = 0; i < cuepoints.length; i++) {
				addCuepoint(cuepoints[i]);
			}
		}

        /**
         * Removes cuepoints from this clip
         * @param filter a filter function, that should return true for all cuepoints to be removed. takes in the cuepoint object.
         * @return
         */
        public function removeCuepoints(filter:Function = null):void {
            if (filter == null) {
                _cuepoints = new Dictionary();
                return;
            }
            for (var time:Object in _cuepoints) {
                var points:Array = _cuepoints[time];
                for (var i:int = 0; i < points.length; i++) {
                    if (filter(points[i] as Cuepoint)) {
                        delete _cuepoints[time];
                    }
                }
            }
        }

		public function addCuepoint(cue:Cuepoint):void {
			if (! cue) return;
			if (cue.time >= 0) {
				log.info(this + ": adding cuepoint to time " + cue.time)
				if (!_cuepoints[cue.time]) {
					_cuepoints[cue.time] = new Array();
				}
				// do not add if this same cuepoint *instance* is already there
				if ((_cuepoints[cue.time] as Array).indexOf(cue) >= 0) return;
				
				(_cuepoints[cue.time] as Array).push(cue);
			} else {
				log.info("storing negative cuepoint " + (this == commonClip ? "to common clip" : ""));
                _cuepointsInNegative.push(cue);
//				if (duration > 0) {
//					convertToPositive(cue);
//				} else {
//                    log.info("duration not available yet, storing negative cuepoint to be used when duration is set")
//					_cuepointsInNegative.push(cue);
//				}
			}
		}
		
		private function removeCuepoint(cue:Cuepoint):void {
			var points:Array = _cuepoints[cue.time];
			if (! points) return;
			var index:int = points.indexOf(cue);
			if (index >= 0) {
				log.debug("removing previous negative cuepoint at timeline time " + cue.time);
				points.splice(index, 1);
			}
		}

		public function getCuepoints(time:int, dur:Number = -1):Array {
			var result:Array = new Array();
			result = ArrayUtil.concat(result, _cuepoints[time]);
            result = ArrayUtil.concat(result, getNegativeCuepoints(time, this == commonClip ? dur : this.duration));
            if (this == commonClip) return result;
			result = ArrayUtil.concat(result, commonClip.getCuepoints(time, this.duration));
            if (result.length > 0) {
                log.info("found " + result.length + " cuepoints for time " + time);
            }
			return result;
		}

		private function getNegativeCuepoints(time:int, dur:Number):Array {
            if (dur <= 0) return [];
            var result:Array = new Array();
            for (var i:int = 0; i < _cuepointsInNegative.length; i++) {
                var positive:Cuepoint = convertToPositive(_cuepointsInNegative[i], dur);
                if (positive.time == time) {
                    log.info("found negative cuepoint corresponding to time " + time);
                    result.push(positive);
                }
            }
            return result;
        }
//
//		private function setNegativeCuepointTimes(duration:int):void {
//			log.debug("setNegativeCuepointTimes, transferring " + _cuepointsInNegative.length + " to timeline duration " + duration);
//			_previousPositives.forEach(
//				function(cue:*, index:int, array:Array):void {
//					removeCuepoint(cue as Cuepoint);
//				});
//			_previousPositives = new Array();
//
//			_cuepointsInNegative.forEach(
//				function(cue:*, index:int, array:Array):void {
//					convertToPositive(cue);
//				});
//		}
		
		private function convertToPositive(cue:Cuepoint, dur:Number):Cuepoint {
			var positive:Cuepoint = cue.clone() as Cuepoint; 
			positive.time = Math.round((dur * 1000 - Math.abs(Cuepoint(cue).time))/100) * 100;
			return positive;
		}

		[Value]
		public function get baseUrl():String {
			return _baseUrl;
		}

		public function set baseUrl(baseURL:String):void {
			this._baseUrl = baseURL;
		}

        [Value]
        public function get url():String {
            return getResolvedUrl() || _url;
        }

        [Value]
        public function get originalUrl():String {
            return _url;
        }

		public function set url(url:String):void {
			if (_url != url) {
				_metaData = null;
				_content = null;
			}
			this._url = url;
		}

        /**
         * Sets the resolved url-
         * @param resolver the resolver used in resolving
         * @param val
         */
        public function setResolvedUrl(resolver:ClipURLResolver, val:String):void {
            for (var i:int = 0; i < _urlsByResolver.length; i++) {
                var resolverAndUrl:Array = _urlsByResolver[i];
                if (resolver == resolverAndUrl[0]) {
                    resolverAndUrl[1] = val;
                    return;
                }
            }

            _urlsByResolver.push([resolver, val]);
        }

        /**
         * Gets the url that was resolved using the specified resolver.
         * @param resolver the resolver whose result to look up, if null returns the result of the most recent resolver that was executed.
         * null if no resolvers are in use, or if the url has not been resolved yet.
         * @return
         */
        public function getResolvedUrl(resolver:ClipURLResolver = null):String {
            if (resolver) {
                return findResolvedUrl(resolver);
            } else if (_urlsByResolver.length > 0) {
                var resolverAndUrl:Array = _urlsByResolver[_urlsByResolver.length - 1];
                return resolverAndUrl ? resolverAndUrl[1] as String : null;
            }
            return null;
        }


        [Value]
        public function get resolvedUrl():String {
            return getResolvedUrl();
        }

        private function findResolvedUrl(resolver:ClipURLResolver):String {
            for (var i:int = 0; i < _urlsByResolver.length; i++) {
                var resolverAndUrl:Array = _urlsByResolver[i];
                if (resolver == resolverAndUrl[0]) {
                    return resolverAndUrl[1] as String;
                }
            }
            return null;
        }

        /**
         * Gets the url that was resolved using the resolver that's before the specified resolver
         * in the resolver chain. URL resolvers should use this method to fetch the URL that is used as the starting
         * point in resolving.
         * @param resolver
         * @return
         */
        public function getPreviousResolvedUrl(resolver:ClipURLResolver):String {
            if (! _urlResolverObjects) throw new Error("Clip.urlResolverObjects is null");
            var pos:int = _urlResolverObjects.indexOf(resolver);
            if (pos > 0) {
                return findResolvedUrl(_urlResolverObjects[pos-1]);
            } else if (pos < 0) {
                throw new Error("Resolver " + resolver + " is not a registered URL Resolver in clip " + this);
            }
            return _url;
        }

        /**
         * Clears all resolved URLs.
         * @return
         */
        public function clearResolvedUrls():void {
            _urlsByResolver = [];
        }


        //#412 check for empty baseurl or else player url is appended and affects the url parsing.
        //#494 regression issued caused by #412, enable base url correctly.
		[Value]
		public function get completeUrl():String {
            return encodeUrl(URLUtil.completeURL(this._baseUrl, this.url));
		}

		//If the encoding is set property, uri encode for ut8 urls
        private function encodeUrl(url:String):String {
            if (!urlEncoding) return url;
            return encodeURI(url);
        }


		public function get type():ClipType {
            if (_type) {
                return _type;
            }
            if (_url && _url.indexOf("mp3:") >= 0) {
                return ClipType.AUDIO;
            }
			if (! _type && _url) {
				_type = ClipType.fromFileExtension(url);
			}
			if (_type) {
                return _type;
            }
            return ClipType.VIDEO;
		}

        public function get isFlashVideo():Boolean {
            return ClipType.isFlashVideo(_url);
        }

        [Value]
        public function get extension():String {
            return ClipType.getExtension(_url);
        }
		
		[Value(name="type")]
		public function get typeStr():String {
			return type ? type.type : ClipType.VIDEO.type;
		}

		public function setType(type:String):void {
			this._type = ClipType.resolveType(type);
		}
		
		public function set type(type:ClipType):void {
			_type = type;
		}

		[Value]
		public function get start():Number {
			return _start;
		}
		
		public function set start(start:Number):void {
			this._start = start;
		}
		
		public function set duration(value:Number):void {
			this._duration = value;
			log.info("clip duration set to " + value);
		}

        [Value]
        public function get duration():Number {
            if (_duration > 0) {
                return _duration;
            }
            var metadataDur:Number = durationFromMetadata;
            if (_start > 0 && metadataDur > _start) {
                return metadataDur - _start;
            }
            return metadataDur || 0;
        }

        [Value]
		public function get durationFromMetadata():Number {
			if (_metaData)
				return decodeDuration(_metaData.duration);
			return 0;
		}

        private function decodeDuration(duration:Object):Number {
            if (! duration) return 0;
            if (duration is Number) return duration as Number;
            if (! duration is String) return 0;
            var parts:Array = duration.split(".");

            // for some reason duration can have 3 part value, for example "130.000.123"
            if (parts.length >= 3) {
                return Number(parts[0] + "." + parts[1]);
            }
            return duration as Number;
        }
		
		public function set durationFromMetadata(value:Number):void {
            if (_metaData is Boolean && ! _metaData) {
                return;
            }
			if (! _metaData) {
				_metaData = new Object();
			}
			_metaData.duration = value;
		}

		[Value]
		public function get metaData():Object {
			return _metaData;
		}
		
		public function set metaData(metaData:Object):void {
         log.debug("received metadata", metaData);
			this._metaData = metaData;
		}
		
		[Value]
		public function get autoPlay():Boolean {
            if (isPreroll) return _parent._autoPlay;
            if (! _parent && preroll) return true;
            if (isPostroll) return true;
			return _autoPlay;
		}
		
		public function set autoPlay(autoPlay:Boolean):void {
			this._autoPlay = autoPlay;
		}
		
		[Value]
		public function get autoBuffering():Boolean {
			return _autoBuffering;
		}
		
		public function set autoBuffering(autoBuffering:Boolean):void {
			this._autoBuffering = autoBuffering; 
		}
		
		public function setContent(content:DisplayObject):void {
			if (_content && _content is Video && ! content) {
				log.debug("clearing video content");
				Video(_content).clear();
			}
			this._content = content;
		}
		
		public function getContent():DisplayObject {
			return _content;
		}

		public function setScaling(scaling:String):void {
			this.scaling = MediaSize.forName(scaling);
		}
		
		public function set scaling(scaling:MediaSize):void {
			this._scaling = scaling;
			
			log.debug("scaling : " + scaling + ", disptching update");

            if (_playlist) {
                _playlist.dispatch(ClipEventType.UPDATE);
            }
		}
		
		public function get scaling():MediaSize {
			return this._scaling;
		}

		[Value(name="scaling")]
		public function get scalingStr():String {
            if (! _scaling) return MediaSize.FILLED_TO_AVAILABLE_SPACE.value;
			return this._scaling.value;
		}

		public function toString():String {
			return "[Clip] '" + (provider == "http" ? completeUrl : url) + "'";
		}

		public function set originalWidth(width:int):void {
			this._originalWidth = width;
		}
		
		public function get originalWidth():int {
			if (type == ClipType.VIDEO) {
				if (_metaData && _metaData.width >= 0) {
					return _metaData.width;
				}
				if (! _content) {
//					log.warn("Getting originalWidth from a clip that does not have content loaded yet, returning zero");
					return 0;
				}
				return _content is Video ? (_content as Video).videoWidth : _originalWidth;
			}
			return _originalWidth;
		}

		public function set originalHeight(height:int):void {
			this._originalHeight = height;
		}
		
		public function get originalHeight():int {
			if (type == ClipType.VIDEO) {
				if (_metaData && _metaData.height >= 0) {
					return _metaData.height;
				}
				if (! _content) {
//					log.warn("Getting originalHeight from a clip that does not have content loaded yet, returning zero");
					return 0;
				}
				return _content is Video ? (_content as Video).videoHeight : _originalHeight;
			}
			return _originalHeight;
		}

		public function set width(width:int):void {
			if (! _content) {
				log.warn("Trying to change width of a clip that does not have media content loaded yet");
				return;
			}
			_content.width = width;
		}
		
		[Value]
		public function get width():int {
			return getWidth();
		}
		
		private function getWidth():int {
			if (! _content) {
				return 0;
			}
			return _content.width;
		}

		public function set height(height:int):void {
			if (! _content) {
				log.warn("Trying to change height of a clip that does not have media content loaded yet");
				return;
			}
			_content.height = height;
		}
		
		[Value]
		public function get height():int {
			return getHeight();
		}
		
		private function getHeight():int {
			if (! _content) {
//				log.warn("Getting height from a clip that does not have content loaded yet, returning zero");
				return 0;
			}
			return _content.height;
		}
		
        [Value]
        public function get bufferLength():int {
            return _bufferLength;
        }
		
        public function set bufferLength(bufferLength:int):void {
            _bufferLength = bufferLength;
        }

        [Value]
        public function get backBufferLength():int {
            return _backBufferLength;
        }

        public function set backBufferLength(bufferLength:int):void {
            _backBufferLength = bufferLength;
        }

		public function get played():Boolean {
			return _played;
		}
		
		public function set played(played:Boolean):void {
			_played = played;
		}
		
		[Value]
		public function get provider():String {
			if (type == ClipType.AUDIO && _provider == "http") return "audio";
            if (_url && _url.toLowerCase().indexOf("rtmp") == 0 && _provider == "http") return "rtmp";
            if (parent) return _provider + "Instream";
			return _provider;
		}

        public function get configuredProviderName():String {
            return _provider;
        }
		
		public function set provider(provider:String):void {
			_provider = provider;
		}
		
		[Value]
		public function get cuepoints():Array {
			var cues:Array = new Array();
			for each (var value:Object in _cuepoints) {
                var cues2:Array = value as Array;
                for each (var cue:Object in cues2) {
                    cues.push(cue);
                }
			}
			return cues;
		}
		
		public function set accelerated(accelerated:Boolean):void {
			_accelerated = accelerated;
		}
		
		[Value]
		public function get accelerated():Boolean {
			return _accelerated;
		}
		
		public function get useHWScaling():Boolean {
			return _accelerated && ! VersionUtil.hasStageVideo();
		}
		
		public function get useStageVideo():Boolean {
			return _accelerated && VersionUtil.hasStageVideo();
		}

		public function get isNullClip():Boolean {
			return false;
		}

		// common clip listens to events from the normal clips and redispatches		
		public function onClipEvent(event:ClipEvent):void {
			log.info("received onClipEvent, I am commmon clip: " + (this == _playlist.commonClip));
			doDispatchEvent(event, true);
			log.debug(this + ": dispatched play event with target " + event.target);
		}

		public function onBeforeClipEvent(event:ClipEvent):void {
			log.info("received onBeforeClipEvent, I am commmon clip: " + (this == _playlist.commonClip));
			doDispatchBeforeEvent(event, true);
			log.debug(this + ": dispatched before event with target " + event.target);
		}
		
		private function get commonClip():Clip {
            if (! _playlist) return null;
			return _playlist.commonClip;
		}
		
		public function get customProperties():Object {
			return _extension.props;
		}
		
		public function set customProperties(props:Object):void {
			_extension.props = props;
            _extension.deleteProp("cuepoints");
            _extension.deleteProp("playlist");
		}
		
		public function get smoothing():Boolean {
			return _smoothing;
		}
		
		public function set smoothing(smoothing:Boolean):void {
			_smoothing = smoothing;
		}
		
		public function getCustomProperty(name:String):Object {
			return _extension.getProp(name);
		}

		public function setCustomProperty(name:String, value:Object):void {
            if (name == "playlist") return;
            _extension.setProp(name, value);
		}
		
		[Value]				
		public function get fadeInSpeed():int {
			return _fadeInSpeed;
		}
		
		public function set fadeInSpeed(fadeInSpeed:int):void {
			_fadeInSpeed = fadeInSpeed;
		}
		
		[Value]		
		public function get fadeOutSpeed():int {
			return _fadeOutSpeed;
		}
		
		public function set fadeOutSpeed(fadeOutSpeed:int):void {
			_fadeOutSpeed = fadeOutSpeed;
		}
		
		[Value]		
		public function get live():Boolean {
			return _live;
		}
		
		public function set live(live:Boolean):void {
			_live = live;
		}
		
		[Value]		
		public function get linkUrl():String {
			return _linkUrl;
		}
		
		public function set linkUrl(linkUrl:String):void {
			if(URLUtil.isValid(linkUrl))
				_linkUrl = linkUrl;
		}
		
		[Value]		
		public function get linkWindow():String {
			return _linkWindow;
		}
		
		public function set linkWindow(linkWindow:String):void {
			_linkWindow = linkWindow;
		}
		
		protected function get cuepointsInNegative():Array {
			return _cuepointsInNegative;
		}
		
		/**
		 * Use the previous clip in the playlist as an image for this audio clip?
		 * This is only for audio clips.
		 */
		[Value]
		public function get image():Boolean {
			return _image;
		}
		
		public function set image(image:Boolean):void {
			_image = image;
		}
		
		public function get autoPlayNext():Boolean {
			return _autoPlayNext;
		}
		
		public function set autoPlayNext(autoPlayNext:Boolean):void {
			_autoPlayNext = autoPlayNext;
		}
		
        [Value]
		public function get cuepointMultiplier():Number {
			return _cuepointMultiplier;
		}
		
		public function set cuepointMultiplier(cuepointMultiplier:Number):void {
			_cuepointMultiplier = cuepointMultiplier;
		}
		
		public function dispatchNetStreamEvent(name:String, infoObject:Object):void {
			dispatch(ClipEventType.NETSTREAM_EVENT, name, infoObject);
		}

        public function get connectionProvider():String {
            return _connectionProvider;
        }

        public function set connectionProvider(val:String):void {
            _connectionProvider = val;
        }

        [Value]
        public function get urlResolvers():Array {
            return _urlResolvers;
        }

        public function setUrlResolvers(val:Object):void {
            _urlResolvers = val is Array ? val as Array : [val];
        }

        public function get seekableOnBegin():Boolean {
            if (_seekableOnBegin == null) {
                return isFlashVideo;
            }
            return _seekableOnBegin as Boolean;
        }

        public function set seekableOnBegin(val:Boolean):void {
            _seekableOnBegin = val;
        }

        public function get hasChildren():Boolean {
            return _childPlaylist.length > 0;
        }

        [Value]
        public function get playlist():Array {
            var result:Array = _childPlaylist.clips;
            if (_preroll) {
                result = [_preroll].concat(result);
            }
            if (_postroll) {
                result.push(_postroll);
            }
            return result;
        }

        public function removeChild(child:Clip):void {
            if (child == _preroll) {
                _preroll = null;
                return;
            }
            if (child == _postroll) {
                _postroll = null;
                return;
            }
            _childPlaylist.removeClip(child);
        }

        public function getMidroll(time:int):Clip {
            return _childPlaylist.getClipAt(time);
        }

        public function get preroll():Clip {
            return _preroll;
        }

        public function get postroll():Clip {
            return _postroll;
        }

        [Value]
        public function get isInStream():Boolean {
            return _parent != null;
        }

        public function get isMidroll():Boolean {
            if (isOneShot) return true;
            return _parent && _position > 0;
        }

        public function get isPreroll():Boolean {
            return _parent && _position == 0;
        }

        public function get isPostroll():Boolean {
            return _parent && _position == -1;
        }

        public function get parent():Clip {
            return _parent;
        }

        [Value]
        public function get parentUrl():String {
            return _parent ? _parent.url : null;
        }

        public function set parent(val:Clip):void {
            _parent = val;
        }

        [Value]
        public function get position():Number {
            return _position;
        }

        public function set position(val:Number):void {
            _position = val;
        }

        public function get isOneShot():Boolean {
            return _parent && position == -2;
        }

        flow_internal function get clipObject():Object {
            return _clipObject;
        }

        /**
         * Gets the NetStream object that is currently associated with this clip, or <code>null</code> if none is
         * currently associated.
         * @return
         */
        public function getNetStream():NetStream {
            return _netStream;
        }

        public function setNetStream(value:NetStream):void {
            _netStream = value;
        }

        public function set urlResolverObjects(urlResolverObjects:Array):void {
            _urlResolverObjects = urlResolverObjects;
        }

        public function get startDispatched():Boolean {
            return _startDispatched;
        }

        public function set startDispatched(value:Boolean):void {
            _startDispatched = value;
        }
        
        public function get currentTime():Number {
        	return _currentTime;
        }
        
        public function set currentTime(time:Number):void {
        	_currentTime = (_currentTime ==0 ? time + _start : time);
        }

        [Value]
        public function get endLimit():Number {
            return _endLimit;
        }

        public function set endLimit(value:Number):void {
            _endLimit = value;
        }
        
        public function set urlEncoding(value:Boolean):void {
        	_encoding = value;
        }
        
        [Value]
        public function get urlEncoding():Boolean {
        	return _encoding;
        }

        public function deleteCustomProperty(name:String):void {
            _extension.deleteProp(name);
        }

        public function get stopLiveOnPause():Boolean {
            return _stopLiveOnPause;
        }

        public function set stopLiveOnPause(value:Boolean):void {
            _stopLiveOnPause = value;
        }
    }
}
