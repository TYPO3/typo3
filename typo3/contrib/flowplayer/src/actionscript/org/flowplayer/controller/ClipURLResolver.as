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
	
	import org.flowplayer.model.Clip;	

	/**
	 * @author api
	 */
	public interface ClipURLResolver {
		
		/**
		 * Sets a listener that gets called if the resolve process fails.
		 */
		function set onFailure(listener:Function):void;
		
		/**
		 * Resolve the URL for the specified clip.
         * @param provider
		 * @param clip the clip to resolve
		 * @param successListener a listener function that gets notified when the URL has been resolved
		 * @see #onSuccess
		 */
		function resolve(provider:StreamProvider, clip:Clip, successListener:Function):void;

        /**
         * Called when a netStatusEvent is received.
         * @param event
         * @return if false, the streamProvider will ignore this event and will not send any events for it
         */
        function handeNetStatusEvent(event:NetStatusEvent):Boolean;
		
	}
}
