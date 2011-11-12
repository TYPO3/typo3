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

	public class State {
		public static const WAITING:State = new State(1, "Waiting");
		public static const BUFFERING:State = new State(2, "Buffering");
		public static const PLAYING:State = new State(3, "Playing");
		public static const PAUSED:State = new State(4, "Paused");
		public static const ENDED:State = new State(5, "Ended");

		private static var enumCreated:Boolean;
		{ enumCreated = true; 
		}
		private var _name:String;
		private var _code:Number;

		public function State(code:Number, name:String) {
			if (enumCreated)
				throw new Error("Cannot create ad-hoc State instances");
			_code = code;
			_name = name;
		}
		
		public function toString():String {
			return "State: " + _code + ", '" + _name + "'";
		}
		
		public function get code():Number {
			return _code;
		}
	}
}
