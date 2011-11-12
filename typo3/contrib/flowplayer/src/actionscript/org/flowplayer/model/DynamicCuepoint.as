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

package org.flowplayer.model {
	import org.flowplayer.model.Cuepoint;
	
	/**
	 * @author api
	 */
	internal dynamic class DynamicCuepoint extends Cuepoint {
		public function DynamicCuepoint(time:int, callbackId:String) {
			super(time, callbackId);
		}
		
		override protected function onClone(clone:Cuepoint):void {
			// copy dynamic properties
			for (var prop:String in this) {
                try {
                    clone[prop] = this[prop];
                } catch (e:Error) {
                    log.error("Error when cloning cuepoint " + e);
                }
            }
		}
	}
}
