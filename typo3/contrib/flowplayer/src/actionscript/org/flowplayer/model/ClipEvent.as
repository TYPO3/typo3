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
    import org.flowplayer.util.ObjectConverter;

	/**
	 * @author anssi
	 */
	public class ClipEvent extends AbstractEvent  {

		public function ClipEvent(eventType:EventType, info:Object = null, info2:Object = null, info3:Object = null) {
			super(eventType, info, info2, info3);
		}

		public override function clone():Event {
			return new ClipEvent(eventType, info);
		}

		public override function toString():String {
			return formatToString("ClipEvent", "type", "info");
		}
				
		protected override function get externalEventArgument():Object {
            if (eventType == ClipEventType.PLAYLIST_REPLACE) {
                return (target as ClipEventSupport).clips;
            }
            if (eventType == ClipEventType.CLIP_ADD) {
                return info2 || (target as ClipEventSupport).clips[info];
            }
			if (target is Clip) {
				return Clip(target).index;
			}
			return target;
		}
				
		protected override function get externalEventArgument2():Object {
			if (eventType == ClipEventType.CUEPOINT) {
				return Cuepoint(info).callbackId;
			} 
			if ([ClipEventType.START, ClipEventType.UPDATE, ClipEventType.METADATA, ClipEventType.METADATA_CHANGED, ClipEventType.RESUME, ClipEventType.BEGIN].indexOf(eventType) >= 0) {
				return target;
			}
			return super.externalEventArgument2;
		}
				
		protected override function get externalEventArgument3():Object {
            if (eventType == ClipEventType.CLIP_ADD ) {
                return null;
            }
			if (eventType == ClipEventType.CUEPOINT) {
				return info is DynamicCuepoint ? info : Cuepoint(info).time;
			}
			return super.externalEventArgument3;
		}
	}
}
