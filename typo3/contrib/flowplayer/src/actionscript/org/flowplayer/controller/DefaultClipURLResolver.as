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
    import flash.events.NetStatusEvent;
import flash.net.NetConnection;
	
	import org.flowplayer.controller.ClipURLResolver;
	import org.flowplayer.model.Clip;	

	/**
	 * @author api
	 */
	public class DefaultClipURLResolver implements ClipURLResolver {

        private var _clip:Clip;
        private var _failureListener:Function;

        public function resolve(provider:StreamProvider, clip:Clip, successListener:Function):void {
            _clip = clip;
            if (successListener != null) {
                successListener(clip);
            }
        }


		public function set onFailure(listener:Function):void {
			_failureListener = listener;
		}

        public function handeNetStatusEvent(event:NetStatusEvent):Boolean {
            return true;
        }
    }
}
