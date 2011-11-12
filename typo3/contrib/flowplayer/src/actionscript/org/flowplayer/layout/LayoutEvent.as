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

package org.flowplayer.layout {
	import flash.events.Event;		

	/**
	 * @author anssi
	 */
	public class LayoutEvent extends Event {
		
		public static const RESIZE:String = "resize";
		public var layout:Layout;
		
		public function LayoutEvent(type:String, layout:Layout, bubbles:Boolean = false, cancelable:Boolean = true) {
			super(type, bubbles, cancelable);
			this.layout = layout;
		}

		public override function clone():Event {
			return new LayoutEvent(type, layout, bubbles, cancelable);
		}
		
		public override function toString():String {
			return formatToString("ResizeEvent", "type", "layout", "bubbles", "cancelable", "eventPhase");
		}
	}
}
