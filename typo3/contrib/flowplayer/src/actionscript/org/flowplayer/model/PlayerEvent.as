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
	import flash.events.Event;	

	/**
	 * Event related to the whole player.
	 * @author api
	 */
	public class PlayerEvent extends AbstractEvent {

		public function PlayerEvent(eventType:EventType, info:Object = null, info2:Object = null, info3:Object = null) {
			super(eventType, info, info2, info3);
		}
		
		public static function load(eventObject:Object = null):PlayerEvent {
			return new PlayerEvent(PlayerEventType.LOAD, eventObject);
		}
		
		public static function keyPress(eventObject:Object = null):PlayerEvent {
			return new PlayerEvent(PlayerEventType.KEYPRESS, eventObject);
		}

		public static function mute(eventObject:Object = null):PlayerEvent {
			return new PlayerEvent(PlayerEventType.MUTE, eventObject);
		}

		public static function unMute(eventObject:Object = null):PlayerEvent {
			return new PlayerEvent(PlayerEventType.UNMUTE, eventObject);
		}

		public static function volume(eventObject:Object = null):PlayerEvent {
			return new PlayerEvent(PlayerEventType.VOLUME, eventObject);
		}

		public static function fullscreen(eventObject:Object = null):PlayerEvent {
			return new PlayerEvent(PlayerEventType.FULLSCREEN, eventObject);
		}

		public static function fullscreenExit(eventObject:Object = null):PlayerEvent {
			return new PlayerEvent(PlayerEventType.FULLSCREEN_EXIT, eventObject);
		}

		public static function mouseOver(eventObject:Object = null):PlayerEvent {
			return new PlayerEvent(PlayerEventType.MOUSE_OVER, eventObject);
		}

		public static function mouseOut(eventObject:Object = null):PlayerEvent {
			return new PlayerEvent(PlayerEventType.MOUSE_OUT, eventObject);
		}

		public override function clone():Event {
			return new PlayerEvent(eventType, info);
		}

		public override function toString():String {
			return formatToString("PlayerEvent", "type", "info");
		}
				
		protected override function get externalEventArgument():Object {
			return info;
		}
				
		protected override function get externalEventArgument2():Object {
			return info2;
		}
				
		protected override function get externalEventArgument3():Object {
			return info3;
		}
				
		protected override function get externalEventArgument4():Object {
			return null;
		}
	}
}
