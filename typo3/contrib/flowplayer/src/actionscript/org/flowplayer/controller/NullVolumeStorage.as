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
	
	/**
	 * @author api
	 */
	internal class NullVolumeStorage implements VolumeStorage {
		private var log:Log = new Log(this);

		public function NullVolumeStorage() {
			log.warn("not allowed to store data on this machine");		
		}
		
		public function persist():void {
		}
		
		public function get volume():Number {
			return 1;
		}
		
		public function get muted():Boolean {
			return false;
		}
		
		public function set volume(value:Number):void {
		}
		
		public function set muted(value:Boolean):void {
		}
	}
}
