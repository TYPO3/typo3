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

package org.flowplayer.util {
	/**
	 * @author api
	 */
	public class LogConfiguration {
		private var _level:String = "error";
		private var _filter:String = "*";
        private var _trace:Boolean = false;
		
		public function get level():String {
			return _level;
		}
		
		public function set level(level:String):void {
			_level = level;
		}
		
		public function get filter():String {
			return _filter;
		}
		
		public function set filter(filter:String):void {
			_filter = filter;
		}

        public function get trace():Boolean {
            return _trace;
        }

        public function set trace(val:Boolean):void {
            _trace = val;
        }
    }
}
