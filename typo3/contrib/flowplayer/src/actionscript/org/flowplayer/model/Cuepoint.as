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
import flash.utils.Dictionary;

    import org.flowplayer.util.Log;

    /**
	 * @author api
	 */
	public class Cuepoint implements Cloneable {
        protected var log:Log = new Log(this);
		private var _time:int;
        private var _callbackId:String;
        private var _lastFireTime:int = -1;
        private var _name:String;

        private var _parameters:Object = new Object();

		/**
		 * Creates a new cuepoint.
		 * @param time
		 * @param callbackId
		 */
		public function Cuepoint(time:int, callbackId:String) {
			_time = time;
			_callbackId = callbackId;
		}

		public static function createDynamic(time:int, callbackId:String):Cuepoint {
			return new DynamicCuepoint(time, callbackId);
		}

        [Value]
        public function get name():String {
            return _name;
        }

        public function set name(name:String):void {
            _name = name;
        }

		[Value]
		public function get time():int {
			return _time;
		}
		
		public function set time(time:int):void {
			_time = time;
		}
		
		public function toString():String {
			return "[Cuepoint] time " + _time;
		}
		
		public function get callbackId():String {
			return _callbackId;
		}
		
		public final function clone():Cloneable {
			var clone:Cuepoint = new Cuepoint(_time, callbackId);
			onClone(clone);
			return clone;
		}
		
		protected function onClone(clone:Cuepoint):void {
		}

        [Value]
		public function get lastFireTime():int {
			return _lastFireTime;
		}
		
		public function set lastFireTime(lastFireTime:int):void {
			_lastFireTime = lastFireTime;
		}


        public function addParameter(name:String, value:Object):void {
            _parameters[name] = value;
        }

        [Value]
        public function get parameters():Object {
            return _parameters;
        }

        public function set parameters(params:Object):void {
            _parameters = params;
        }
    }
}
