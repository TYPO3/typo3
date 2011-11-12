/*    
 *    Copyright 2008 Anssi Piirainen
 *
 *    This file is part of FlowPlayer.
 *
 *    FlowPlayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    FlowPlayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with FlowPlayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.controller {
import flash.utils.Dictionary;
import org.flowplayer.util.Log;
import org.flowplayer.config.Config;
import org.flowplayer.controller.NetStreamCallbacks;
import org.flowplayer.model.Clip;
import org.flowplayer.model.ClipEventType;

	/**
	 * @author api
	 */
	public dynamic class NetStreamClient implements NetStreamCallbacks {

		private var log:Log = new Log(this);
		private var _config:Config;
		private var _clip:Clip;
        private var _previousUrl:String;

		public function NetStreamClient(clip:Clip, config:Config, streamCallbacks:Dictionary) {
			_clip = clip;
			_config = config;
            for (var key:Object in streamCallbacks) {
                addStreamCallback(key as String, streamCallbacks[key]);
            }
		} 

		public function onMetaData(infoObject:Object):void {
			log.info("onMetaData, current clip " + _clip);

            log.debug("onMetaData, data for clip " + _clip + ":");
            var metaData:Object = new Object();
            for (var key:String in infoObject) {
				if ( key == "duration" && ! isNewFile() && _clip && _clip.metaData && _clip.metaData.duration ) {
					log.debug ("Already got duration, reusing old one");
					metaData.duration = _clip.metaData.duration;
					continue;
				}
	
                log.debug(key + ": " + infoObject[key]);
                metaData[key] = infoObject[key];
            }
            _clip.metaData = metaData;

            if (metaData.cuePoints && isNewFile()) {
                log.debug("clip has embedded cuepoints");
                _clip.addCuepoints(_config.createCuepoints(metaData.cuePoints, "embedded", _clip.cuepointMultiplier));
            }

            _previousUrl = _clip.url;
            _clip.dispatch(ClipEventType.METADATA);
            log.info("metaData parsed and injected to the clip");
        }

        private function isNewFile():Boolean {
            if (! _previousUrl) return true;
            return _clip.url != _previousUrl;
        }

		public function onXMPData(infoObject:Object):void {
			_clip.dispatchNetStreamEvent("onXMPData", infoObject);
		}
		
		public function onCaption(cps:String, spk:Number):void {
			_clip.dispatchNetStreamEvent("onCaption", { 'cps': cps, 'spk': spk });
		}
		
		public function onCaptionInfo(infoObject:Object):void {
			_clip.dispatchNetStreamEvent("onCaptionInfo", infoObject);
		}
		
		public function onImageData(infoObject:Object):void {
			_clip.dispatchNetStreamEvent("onImageData", infoObject);
		}
		
		public function RtmpSampleAccess(infoObject:Object):void {
			_clip.dispatchNetStreamEvent("RtmpSampleAccess", infoObject);
		}
		
		public function onTextData(infoObject:Object):void {
			_clip.dispatchNetStreamEvent("onTextData", infoObject);
		}

        private function addStreamCallback(name:String, listener:Function):void {
            log.debug("registering callback " + name);
            this[name] = listener;
        }

        public function registerCallback(name:String):void {
            _clip.dispatchNetStreamEvent("registerCallback", name);
        }
        
//
//        public function onCuePoint(infoObject:Object):void {
//            _clip.dispatchNetStreamEvent("onCuePoint", infoObject);
//        }
    }
}
