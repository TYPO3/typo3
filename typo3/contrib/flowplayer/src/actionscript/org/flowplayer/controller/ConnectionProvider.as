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
	import org.flowplayer.model.Clip;
	
	/**
	 * @author api
	 */
	public interface ConnectionProvider {
		
		function set connectionClient(client:Object):void;
		
		/**
		 * Sets a listener that gets called if the connection fails.
		 * The function must have a parameter of type NetStatusEvent.
		 */
		function set onFailure(listener:Function):void;

        /**
         * Connects to the specified URL.
         * @param provider
         * @param clip
         * @param successListener
         * @param objectEncoding to be used in NetConnection.objectEncoding
         * @param rest
         * @return
         */
		function connect(provider:StreamProvider, clip:Clip, successListener:Function, objectEncoding: uint, connectionArgs:Array):void;

        /**
         * Called when a netStatusEvent is received.
         * @param event
         * @return if false, the streamProvider will ignore this event and will not send any events for it
         */
        function handeNetStatusEvent(event:NetStatusEvent):Boolean;
		
	}
}
