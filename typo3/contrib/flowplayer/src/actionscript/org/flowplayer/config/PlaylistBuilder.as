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

package org.flowplayer.config {
	import org.flowplayer.flow_internal;
	import org.flowplayer.model.Clip;
	import org.flowplayer.model.Cuepoint;
	import org.flowplayer.model.NullClip;
	import org.flowplayer.model.Playlist;
	import org.flowplayer.util.Log;
	import org.flowplayer.util.PropertyBinder;
	import org.flowplayer.util.URLUtil;	

	use namespace flow_internal;

	/**
	 * @author anssi
	 */
	internal class PlaylistBuilder {
        private static const NESTED_PLAYLIST:String = "playlist";
        private var log:Log = new Log(this);
        private var _clipObjects:Array;
        private var _commonClipObject:Object;
        private var _commonClip:Clip;
        private var _playerId:String;
        private var _playlistFeed:String;


        /**
         * Creates a new PlayListBuilder
         * @param playerId
         * @param playlist
         * @param commonClip
         */
		public function PlaylistBuilder(playerId:String, playlist:Object, commonClip:Object) {
			_playerId = playerId;
			_commonClipObject = commonClip;
            if (playlist is Array) {
                _clipObjects = playlist as Array;
            }
		}

        /**
         * Sets a playlist feed to be used to create the playlist.
         * @param feed
         * @return
         */
        public function set playlistFeed(feed:String):void {
            _playlistFeed = feed;            
        }


        public function createPlaylist():Playlist {
            if (_commonClipObject) {
                _commonClip = createClip(_commonClipObject);
            }
            var playList:Playlist = new Playlist(_commonClip);

            if (_playlistFeed) {
                parse(_playlistFeed, playList, _commonClipObject);
            } else if (_clipObjects && _clipObjects.length > 0) {
                playList.setClips(createClips(_clipObjects));
            } else if (_commonClip) {
                playList.addClip(createClip(_commonClipObject));
            }

            return playList;
        }

		public function createClips(clipObjects:Object):Array {

            if (clipObjects is String) {
                return new RSSPlaylistParser().parse(clipObjects as String, null, _commonClipObject);
            }

			var clips:Array = new Array();
			for (var i : Number = 0; i < (clipObjects as Array).length; i++) {
				var clipObj:Object = (clipObjects as Array)[i];
				if (clipObj is String) {
					clipObj = { url: clipObj };
				}
				clips.push(createClip(clipObj));
			}
			return clips;
		}

        public function createClip(clipObj:Object, isChild:Boolean = false):Clip {
            log.debug("createClip, from ", clipObj);
            if (! clipObj) return null;
            if (clipObj is String) {
                clipObj = { url: clipObj };
            }
            setDefaults(clipObj);
            var url:String = clipObj.url;
            var baseUrl:String = clipObj.baseUrl;
            var fileName:String = url;
            if (URLUtil.isCompleteURLWithProtocol(url)) {
                var lastSlashIndex:Number = url.lastIndexOf("/");
                baseUrl = url.substring(0, lastSlashIndex);
                fileName = url.substring(lastSlashIndex + 1);
            }
            var clip:Clip = Clip.create(clipObj, fileName, baseUrl);
            new PropertyBinder(clip, "customProperties").copyProperties(clipObj) as Clip;
            if (isChild || clipObj.hasOwnProperty("position")) {
                return clip;
            }
                  
            if (clipObj.hasOwnProperty(NESTED_PLAYLIST)) {
                addChildClips(clip, clipObj[NESTED_PLAYLIST]);
            } else if (_commonClipObject && _commonClipObject.hasOwnProperty(NESTED_PLAYLIST)) {
                addChildClips(clip, _commonClipObject[NESTED_PLAYLIST]);
            }
            return clip;
        }

        private function addChildClips(clip:Clip, children:Array):void {
            for (var i:int = 0; i < children.length; i++) {
                var child:Object = children[i];
                if (! child.hasOwnProperty("position")) {
                    if (i == 0) {
                        child["position"] = 0;
                    }
                    else if (i == children.length -1) {
                        child["position"] = -1;
                    }
                    else {
                        throw new Error("position not defined in a nested clip");
                    }
                }
                clip.addChild(createClip(child, true));
            }
        }

        public function createCuepointGroup(cuepoints:Array, callbackId:String, timeMultiplier:Number):Array {
            log.debug("createCuepointGroup(), creating " + cuepoints.length + " cuepoints");
            var cues:Array = new Array();
            for (var i:Number = 0; i < cuepoints.length; i++) {
                var cueObj:Object = cuepoints[i];
                var cue:Object = createCuepoint(cueObj, callbackId, timeMultiplier);
                cues.push(cue);
            }
            return cues;
        }
		
		private function setDefaults(clipObj:Object):void {
			if (clipObj == _commonClipObject) return;
			
			for (var prop:String in _commonClipObject) {
				if (! clipObj.hasOwnProperty(prop) && prop != NESTED_PLAYLIST) {
					clipObj[prop] = _commonClipObject[prop];
				}
			}
		}

		private function createCuepoint(cueObj:Object, callbackId:String, timeMultiplier:Number):Object {
            log.debug("createCuepoint(), creating cuepoint from: ", cueObj);
			if (cueObj is Number) return new Cuepoint(roundTime(cueObj as int, timeMultiplier), callbackId);
			if (! cueObj.hasOwnProperty("time")) throw new Error("Cuepoint does not have time: " + cueObj);
			var cue:Object = Cuepoint.createDynamic(roundTime(cueObj.time, timeMultiplier), callbackId);
            var parameters:Object = {};
            for (var prop:String in cueObj) {
                if (prop == "parameters") {

                    for (var paramName:String in cueObj[prop]) {
                        parameters[paramName] = cueObj[prop][paramName];
                    }
                    cue["parameters"] = parameters;
                } else if (prop != "time") {
                    cue[prop] = cueObj[prop];
                    log.debug("added prop " + prop, cueObj[prop]);
                }

//				log.debug("added cynamic property " + prop + ", to value " + cue[prop]);
			}
			return cue;
		}
		
		private function roundTime(time:int, timeMultiplier:Number):int {
			return Math.round(time * timeMultiplier / 100) * 100;
		}

        private function parse(document:String, playlist:Playlist, commonClipObj:Object):void {
			var playlist:Playlist = playlist;
            if (document.indexOf("[") == 0) {
            	var clips:Object = ConfigParser.parse(document);
            	playlist.setClips(createClips(clips));
            } else {
            	new RSSPlaylistParser().parse(document, playlist, commonClipObj);
            }
        }

        public function get playlistFeed():String {
            return _playlistFeed;
        }
    }
}
