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

	/**
	 * @author api
	 */
	public class Status {
		private var _state:State;
		private var _clip:Clip;
		private var _time:Number;
		private var _bufferStart:Number;
		private var _bufferEnd:Number;
		private var _bytesTotal:Number;
		private var _allowRandomSeek:Boolean;
		private var _muted:Boolean;
		private var _volume:Number;

		public function Status(state:State, clip:Clip, time:Number, bufferStart:Number, bufferEnd:Number, fileSize:Number, muted:Boolean, volume:Number, allowRandomSeek:Boolean = false) {
			_state = state;
			_clip = clip
			_time = time || 0;
			_bufferStart = bufferStart || 0;
			_bufferEnd = bufferEnd || 0;
			_bytesTotal = fileSize || 0;
			_allowRandomSeek = allowRandomSeek;
			_muted = muted;
			_volume = volume;
		}

		/**
		 * Has the clip been played dompletely?
		 * @return <code>true</code if the clip has been played,
		 */
		public function get ended():Boolean {
			return (_clip.type == ClipType.IMAGE && _clip.duration == 0) || (_clip.played && (_clip.duration - _time <= 1));
		} 
		
		public function get clip():Clip {
			return _clip;
		}
		
		[Value]		
		public function get time():Number {
			return _time;
		}
		
		[Value]		
		public function get bufferStart():Number {
			return _bufferStart;
		}
		
		[Value]		
		public function get bufferEnd():Number {
			return _bufferEnd;
		}
		
		public function get bytesTotal():Number {
			return _bytesTotal;
		}
		
		public function toString():String {
			return "[PlayStatus] time " + _time + ", buffer: [" + _bufferStart +	", " + _bufferEnd + "]";
		}
		
		public function get allowRandomSeek():Boolean {
			return _allowRandomSeek;
		}
		
		[Value]		
		public function get muted():Boolean {
			return _muted;
		}
		
		[Value]		
		public function get volume():Number {
			return _volume;
		}
		
		[Value]		
		public function get state():int {
			return _state.code;
		}

		public function getState():State {
			return _state;
		}
		
		public function set clip(clip:Clip):void {
			_clip = clip;
		}
	}
}
