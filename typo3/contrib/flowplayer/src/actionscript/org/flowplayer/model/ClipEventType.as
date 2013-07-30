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
	import flash.utils.Dictionary;
	
import org.flowplayer.flow_internal;

	public class ClipEventType extends EventType {
		
		public static const CONNECT:ClipEventType = new ClipEventType("onConnect");
		public static const BEGIN:ClipEventType = new ClipEventType("onBegin");
		public static const METADATA:ClipEventType = new ClipEventType("onMetaData");
        public static const METADATA_CHANGED:ClipEventType = new ClipEventType("onMetaDataChange");
		public static const START:ClipEventType = new ClipEventType("onStart");
		public static const PAUSE:ClipEventType = new ClipEventType("onPause");
		public static const RESUME:ClipEventType = new ClipEventType("onResume");
		public static const STOP:ClipEventType = new ClipEventType("onStop");
		public static const FINISH:ClipEventType = new ClipEventType("onFinish");
		public static const CUEPOINT:ClipEventType = new ClipEventType("onCuepoint");
        public static const SEEK:ClipEventType = new ClipEventType("onSeek");
        public static const SWITCH:ClipEventType = new ClipEventType("onSwitch");
        public static const SWITCH_FAILED:ClipEventType = new ClipEventType("onSwitchFailed");
        public static const SWITCH_COMPLETE:ClipEventType = new ClipEventType("onSwitchComplete");

		public static const BUFFER_EMPTY:ClipEventType = new ClipEventType("onBufferEmpty");
		public static const BUFFER_FULL:ClipEventType = new ClipEventType("onBufferFull");
		public static const BUFFER_STOP:ClipEventType = new ClipEventType("onBufferStop");
		public static const LAST_SECOND:ClipEventType = new ClipEventType("onLastSecond");
		public static const UPDATE:ClipEventType = new ClipEventType("onUpdate");
		public static const ERROR:ClipEventType = new ClipEventType("onError");
		public static const NETSTREAM_EVENT:ClipEventType = new ClipEventType("onNetStreamEvent");
		public static const CONNECTION_EVENT:ClipEventType = new ClipEventType("onConnectionEvent");
        public static const PLAY_STATUS:ClipEventType = new ClipEventType("onPlayStatus");

        public static const PLAYLIST_REPLACE:ClipEventType = new ClipEventType("onPlaylistReplace");
        public static const CLIP_ADD:ClipEventType = new ClipEventType("onClipAdd");
        public static const CLIP_RESIZED:ClipEventType = new ClipEventType("onResized");

		public static const STAGE_VIDEO_STATE_CHANGE:ClipEventType = new ClipEventType("onStageVideoStateChange");

		private static var _allValues:Dictionary;
        private static var _cancellable:Dictionary = new Dictionary();
        {
			_cancellable[BEGIN.name] = BEGIN;
			_cancellable[SEEK.name] = SEEK;
			_cancellable[PAUSE.name] = PAUSE;
			_cancellable[RESUME.name] = RESUME;
			_cancellable[STOP.name] = STOP;
			_cancellable[FINISH.name] = FINISH;
		}
	
		override public function get isCancellable():Boolean {
			return _cancellable[this.name];
		}
		
		public static function get cancellable():Dictionary {
			return _cancellable;
		}

		public static function get all():Dictionary {
			return _allValues;
		}

		/**
		 * Creates a new type.
		 */
		public function ClipEventType(name:String, custom:Boolean = false) {
			super(name, custom);
			if (! _allValues) {
				_allValues = new Dictionary();
			}
			_allValues[name] = this;
		}

        public static function forName(name:String):ClipEventType {
            return _allValues[name];
        }

		public function toString():String {
			return "[ClipEventType] '" + name + "'";
		}
		
		public function get playlistIsEventTarget():Boolean {
			return this == PLAYLIST_REPLACE || this == CLIP_ADD;
		}

    }
}
