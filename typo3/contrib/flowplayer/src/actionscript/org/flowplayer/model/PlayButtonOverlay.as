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
	import org.flowplayer.model.DisplayPropertiesImpl;	

	/**
	 * @author api
	 */
	public class PlayButtonOverlay extends DisplayPluginModelImpl {

		private var _fadeSpeed:int;
		private var _rotateSpeed:int;
		private var _label:String;
		private var _replayLabel:String;
		private var _buffering:Boolean;

		public function PlayButtonOverlay() {
			super(null, "play", false);
			// these are used initially before screen is arranged
			// once screen is availabe, these will be overridden
			top = "50%";
			left = "50%";
			width = "22%";
			height = "22%";
			display = "block";
			_buffering = true;
			_rotateSpeed = 50;
			_fadeSpeed = 500;
			_replayLabel = "Play again";
		}

        override public function clone():Cloneable {
            var copy:PlayButtonOverlay = new PlayButtonOverlay();
            copyFields(this, copy);
            copy.fadeSpeed = this.fadeSpeed;
            copy.rotateSpeed = this.rotateSpeed;
            copy.url = this.url;
            copy.label = this.label;
            copy.replayLabel = this.replayLabel;
            copy.buffering = this.buffering;
            return copy;
        }

        [Value]
		public function get fadeSpeed():int {
			return _fadeSpeed;
		}
		
		public function set fadeSpeed(fadeSpeed:int):void {
			_fadeSpeed = fadeSpeed;
		}
		
        [Value]
		public function get rotateSpeed():int {
			if (_rotateSpeed > 100) return 100;
			return _rotateSpeed;
		}
		
		public function set rotateSpeed(rotateSpeed:int):void {
			_rotateSpeed = rotateSpeed;
		}
		
        [Value]
		public function get label():String {
			return _label;
		}
		
		public function set label(label:String):void {
			_label = label;
		}
		
        [Value]
		public function get replayLabel():String {
			return _replayLabel;
		}
		
		public function set replayLabel(replayLabel:String):void {
			_replayLabel = replayLabel;
		}
		
        [Value]
		public function get buffering():Boolean {
			return _buffering;
		}
		
		public function set buffering(buffering:Boolean):void {
			_buffering = buffering;
		}
	}
}
