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
	import org.osflash.thunderbolt.Logger;
	import flash.utils.getQualifiedClassName;	

	/**
	 * @author anssi
	 */
	public class Log {

		private static const LEVEL_DEBUG:int = 0;
		private static const LEVEL_WARN:int = 1;
		private static const LEVEL_INFO:int = 2;
		private static const LEVEL_ERROR:int = 3;
		private static const LEVEL_SUPPRESS:int = 4;
		
		private static var _level:int = LEVEL_ERROR;
		private static var _filter:String = "*";
		private static var _instances:Array = new Array();
        public static var traceEnabled:Boolean = false;

        private var _owner:String;
        private var _enabled:Boolean = true;

		public function Log(owner:Object) {
			_owner = owner is String ? owner as String : getQualifiedClassName(owner);
			_instances.push(this);
			enable();
		}
		
		private function enable():void {
			_enabled = checkFilterEnables(_owner);
		}
		
		private function checkFilterEnables(owner:String):Boolean {
			if (_filter == "*") return true;
			var className:String;
			var parts:Array = owner.split(".");
			var last:String = parts[parts.length - 1];
			var classDelimPos:int = last.indexOf("::"); 
			if (classDelimPos > 0) {
				className = last.substr(classDelimPos + 2);
				parts[parts.length -1] = last.substr(0, classDelimPos);
			}
			var packageName:String = "";
			for (var i:Number = 0; i < parts.length; i++) {
				packageName = i > 0 ? packageName + "." + parts[i] : parts[i];
				if (_filter.indexOf(parts[i] + ".*") >= 0) {
					return true;
				}
			}
			var result:Boolean = _filter.indexOf(packageName + "." + className) >= 0;
			return result;
		}

		public static function configure(config:LogConfiguration):void {
			level = config.level;
			filter = config.filter;
            traceEnabled = config.trace;
			for (var i:Number = 0; i < _instances.length; i++) {
				Log(_instances[i]).enable();
			}
		}

		public static function set level(level:String):void {
			if (level == "debug") 
				_level = LEVEL_DEBUG;
			else if (level == "warn")
				_level = LEVEL_WARN;
			else if (level == "info")
				_level = LEVEL_INFO;
			else if (level == "suppress")
				_level = LEVEL_SUPPRESS;
			else
				_level = LEVEL_ERROR;
		}
		
		public static function set filter(filterValue:String):void {
			_filter = filterValue;
		}		
		
		public function debug(msg:String = null, ...rest):void {
			if (!_enabled) return;
			if (_level <= LEVEL_DEBUG)
				write(Logger.debug, msg, "DEBUG", rest);
		}
		
		public function error(msg:String = null, ...rest):void {
			if (!_enabled) return;
			if (_level <= LEVEL_ERROR)
				write(Logger.error, msg, "ERROR", rest);
		}
		
		public function info(msg:String = null, ...rest):void {
			if (!_enabled) return;
			if (_level <= LEVEL_INFO)
				write(Logger.info, msg, "INFO", rest);
		}
		
		public function warn(msg:String = null, ...rest):void {
			if (!_enabled) return;
			if (_level <= LEVEL_WARN)
				write(Logger.warn, msg, "WARN", rest);
		}
		
		private function write(writeFunc:Function, msg:String, levelStr:String, rest:Array):void {
            if (traceEnabled) {
                doTrace(msg, levelStr, rest);
            }
			try {
				if (rest.length > 0)
					writeFunc(_owner + " : " + msg, rest);
				else
					writeFunc(_owner + " : " + msg);
			} catch (e:Error) {
				trace(msg);
				trace(e.message);
			}
		}

        private function doTrace(msg:String, levelStr:String, rest:Array):void {
            trace(_owner + ":: " + levelStr + ": " + msg);
        }
		
		public function get enabled():Boolean {
			return _enabled;
		}
		
		public function set enabled(enabled:Boolean):void {
			_enabled = enabled;
		}
		
		public function debugStackTrace(msg:String = null):void{
			if (!_enabled) return;
			if (_level <= LEVEL_DEBUG)
				try { throw new Error("StackTrace"); } catch (e:Error) { debug(msg, e.getStackTrace()); }
		}
	}
}
