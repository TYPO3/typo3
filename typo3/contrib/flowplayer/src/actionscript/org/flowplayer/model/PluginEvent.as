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
	import org.flowplayer.flow_internal;
	import flash.external.ExternalInterface;
	
	use namespace flow_internal;
	/**
	 * @author anssi
	 */
	public class PluginEvent extends AbstractEvent {

		public static const PLUGIN_EVENT:String = "onPluginEvent";
        private var _id:Object;

		public function PluginEvent(eventType:PluginEventType, pluginName:String, id:Object = null, info:Object = null, info2:Object = null, info3:Object = null) {
            super(eventType, pluginName, info, info2, info3);
            _id = id;
		}
		
		override flow_internal function fireErrorExternal(playerId:String):void {
            try {
                ExternalInterface.call(
                    "flowplayer.fireEvent",
                    playerId || ExternalInterface.objectID, getExternalName(eventType.name, false), error.code, error.message + info2 ? ": " + info2 : "");
            } catch (e:Error) {
                log.error("Error in fireErrorExternal() "+ e);
            }
        }

		override public function get error():ErrorCode {
			return _id as ErrorCode;
		}
//
//		public override function clone():Event {
//			return new PluginEvent(eventType as PluginEventType, info.toString(), _id, info2);
//		}

		public override function toString():String {
			return formatToString("PluginEvent", "id", "info", "info2", "info3", "info4", "info5");
		}
		
		/**
		 * Gets the event Id.
		 */
		public function get id():Object {
			return _id;
		}

		protected override function get externalEventArgument():Object {
            return info;
        }

        protected override function get externalEventArgument2():Object {
            return _id;
		}

		protected override function get externalEventArgument3():Object {
			return info2;
		}

        protected override function get externalEventArgument4():Object {
            return info3;
        }

        protected override function get externalEventArgument5():Object {
            return info4;
        }
	}
}
