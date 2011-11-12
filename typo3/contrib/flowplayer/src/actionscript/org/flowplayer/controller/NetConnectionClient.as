/*    
 *    Copyright (c) 2008-2011 Flowplayer Oy *
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
	import org.flowplayer.model.ClipEventType;	
	import org.flowplayer.controller.ConnectionCallbacks;
	import org.flowplayer.model.Clip;	

	/**
	 * @author  api
	 */
	public dynamic class NetConnectionClient implements ConnectionCallbacks {
		private var log:Log = new Log(this);
		private var _clip:Clip;


//		public function onBWCheck(...rest):void {
//			log.debug("received onBWCheck " + _clip);
//			_clip.dispatch(ClipEventType.CONNECTION_EVENT, "onBWCheck");
//		}
//
//		public function onBWDone(...rest):void {
//			log.debug("received onBWDone");
//			_clip.dispatch(ClipEventType.CONNECTION_EVENT, "onBWDone", rest.length > 0 ? rest[0] : null);
//		}
		
		public function onFCSubscribe(infoObject:Object):void {
			_clip.dispatch(ClipEventType.CONNECTION_EVENT, "onFCSubscribe", infoObject);
		}

        public function get clip():Clip {
            return _clip;
        }

        public function set clip(val:Clip):void {
            _clip = val;
        }

        public function addConnectionCallback(name:String, listener:Function):void {
            log.debug("registering callback " + name);
            this[name] = listener;
//            this[name] = function(infoObj:Object = null):void {
//                log.debug("received callback " + name);
//                _clip.dispatch(ClipEventType.CONNECTION_EVENT, name, infoObj);
//            }
        }

        public function registerCallback(name:String):void {
            _clip.dispatch(ClipEventType.CONNECTION_EVENT, "registerCallback", name);
        }
    }
}
