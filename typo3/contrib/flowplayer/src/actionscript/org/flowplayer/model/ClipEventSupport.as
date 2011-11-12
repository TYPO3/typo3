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
	import org.flowplayer.flow_internal;	
	use namespace flow_internal;
	/**
	 * @author api
	 */
	public class ClipEventSupport extends ClipEventDispatcher {
		private var _clips:Array;
		private var _commonClip:Clip;

		public function ClipEventSupport(commonClip:Clip, clips:Array = null) {
			_commonClip = commonClip;
			_clips = clips;
		}
		
		flow_internal function setClips(clips:Array):void {
			_clips = clips;
		}

        flow_internal function get allClips():Array {
            return _clips;
        }

        public function get clips():Array {
			return _clips.filter(function (item:*, index:int, array:Array):Boolean {
                return ! Clip(item).isInStream;
            });
		}
		
		public static function typeFilter(type:ClipType):Function {
			return function(clip:Clip):Boolean { return clip.type == type; };
		}

		override flow_internal function setListener(event:EventType, listener:Function, clipFilter:Function = null, beforePhase:Boolean = false, addToFront:Boolean = false):void {
			var eventType:ClipEventType = event as ClipEventType;
			if (eventType && eventType.playlistIsEventTarget) {
				super.setListener(eventType, listener, clipFilter, beforePhase, addToFront);
			} else {
				_commonClip.setListener(eventType, listener, clipFilter, beforePhase, addToFront);
			}
		}

		override internal function removeListener(event:EventType, listener:Function, beforePhase:Boolean = false):void {
			var eventType:ClipEventType = event as ClipEventType;
			if (eventType.playlistIsEventTarget) {
				super.removeListener(event, listener, beforePhase);
			} else {
				_commonClip.removeListener(event, listener, beforePhase);
			}
		}

        public function get childClips():Array {
            var result:Array = new Array();
            for (var i:int = 0; i < _clips.length; i++) {
                result = result.concat(Clip(_clips[i]).playlist);
            }
            return result;
        }
	}
}
