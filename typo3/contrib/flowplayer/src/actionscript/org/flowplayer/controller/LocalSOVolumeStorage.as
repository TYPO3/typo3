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
	import org.flowplayer.util.Log;	
	import org.flowplayer.controller.VolumeStorage;
	
	import flash.net.SharedObject;		

	/**
	 * @author api
	 */
	internal class LocalSOVolumeStorage implements VolumeStorage {
		private var _storedVolume:SharedObject;
		private var log:Log = new Log(this);

		public function LocalSOVolumeStorage(storedVolume:SharedObject) {
            log.debug("in constructor");
			_storedVolume = storedVolume;
		}

		public static function create():VolumeStorage {
			try { 
				return new LocalSOVolumeStorage(SharedObject.getLocal("org.flowplayer"));
			} catch (e:Error) {
				return new NullVolumeStorage();
			}
			return null; 
		}
		
		public function persist():void {
            log.debug("persisting volume " + _storedVolume.data.volume);
			try {
				_storedVolume.flush();
			} catch (e:Error) {
				log.error("unable to persist volume");
			}
		}

		public function get volume():Number {
            log.debug("get volume " + _storedVolume.data.volume);
            if (_storedVolume.size == 0) return 0.5;
			return getVolume(_storedVolume.data.volume);
		}
		
		public function get muted():Boolean {
			return _storedVolume.data.volumeMuted;
		}
		
		public function set volume(value:Number):void {
			_storedVolume.data.volume = value;
		}
		
		public function set muted(value:Boolean):void {
			_storedVolume.data.volumeMuted = value;
		}
		
		private function getVolume(volumeObj:Object):Number {
            if (volumeObj == 0) return 0;
			if (!volumeObj is Number) return 0.5;
			if (isNaN(volumeObj as Number)) return 0.5;
			if (volumeObj as Number > 1) return 1;
			if (volumeObj as Number < 0) return 0;
			return volumeObj as Number;
		}
	}
}
