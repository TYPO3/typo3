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
	import org.flowplayer.model.ErrorCode;
	
	/**
	 * @author api
	 */
	public class PlayerError extends ErrorCode {
		
		public static const INIT_FAILED:PlayerError = new PlayerError(PlayerEventType.ERROR, 300, "Player initialization failed");
		public static const PLUGIN_LOAD_FAILED:PlayerError = new PlayerError(PlayerEventType.ERROR, 301, "Unable to load plugin");
		public static const PLUGIN_INVOKE_FAILED:PlayerError = new PlayerError(PlayerEventType.ERROR, 302, "Error when invoking plugin external method");
        public static const RESOURCE_LOAD_FAILED:PlayerError = new PlayerError(PlayerEventType.ERROR, 303, "Failed to load a resource");
        public static const INSTREAM_PLAY_NOTPLAYING:PlayerError = new PlayerError(PlayerEventType.ERROR, 304, "Cannot start instream playback, when not playing currently");

		public function PlayerError(eventType:EventType, code:int, message:String) {
			super(eventType, code, message);
		}
	}
}
