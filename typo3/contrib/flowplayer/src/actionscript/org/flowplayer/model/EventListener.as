/*    
 *    Copyright 2008 Anssi Piirainen
 *
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
	import org.flowplayer.util.Assert;	
	import org.flowplayer.util.Log;	
	
	/**
	 * @author api
	 */
	internal class EventListener {
		
		private var log:Log = new Log(this);
		private var _listener:Function;
		private var _clipFilter:Function;

		public function EventListener(listener:Function, clipFilter:Function) {
			_listener = listener;
			_clipFilter = clipFilter;
		}		
		
		public function notify(event:AbstractEvent):Boolean {
			Assert.notNull(event.target, "event target cannot be null");
			if (_clipFilter != null) {
				log.debug("clip filter returns " + _clipFilter(event.target as Clip));
			}
			if (_clipFilter != null && event.target && ! _clipFilter(event.target as Clip)) {
				log.debug(event + " was filtered out for this listener");
				 return false;
			}
			log.debug("notifying listener for event " + event);
			_listener(event);
			return true;
		}
		
		public function get listener():Function {
			return _listener;
		}
	}
}
