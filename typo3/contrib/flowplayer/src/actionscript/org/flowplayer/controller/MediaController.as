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

package org.flowplayer.controller {
	import org.flowplayer.model.ClipEventType;
	import org.flowplayer.model.State;
	import org.flowplayer.model.Status;		

	/**
	 * @author anssi
	 */
	public interface MediaController {

		/**
		 * Handles the specified event. This function also dispatches
		 * the specified event when the given event has been successfully
		 * initiated. For example, the PlayEventType.START event is dispatched
		 * when the media playback has been initiated without errors.
		 * 
		 * @param event the type of the event to be handled, the event's before phase
		 * has been already processed, and the event cannot be canceled at this point any more
		 * @param params parameters related to this event
		 */
		function onEvent(event:ClipEventType, params:Array = null):void;
		
		/**
		 * Gets the status of this controller.
		 */
		function getStatus(state:State):Status;

		/**
		 * Gets the current playhead time.
		 */
		function get time():Number;
    }
}
