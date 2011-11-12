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
	import org.flowplayer.util.Log;
    import org.flowplayer.util.ObjectConverter;

    /**
	 * @author api
	 */
	internal class PluginMethodHelper {

		private static var log:Log = new Log("org.flowplayer.model::PluginMethodHelper");

		public static function getMethod(_methods:Array, externalName:String):PluginMethod {
			for (var i : Number = 0; i < _methods.length; i++) {
				var method:PluginMethod = _methods[i];
				if (method.externalName == externalName) {
					return method;
				}
			}
			return null;
		}
		
		public static function invokePlugin(callable:Callable, plugin:Object, methodName:String, args:Array):Object {
			var method:PluginMethod = callable.getMethod(methodName);
			if (! method) {
				throw new Error("Plugin does not have the specified method '" + methodName + "'");
			}
			if (method.isGetter) {
				log.debug("calling getter '" + method.internalName + "', of callable object " + callable);
				return convert(method, plugin[method.internalName]);
			}
			if (method.isSetter) {
				log.debug("calling setter '" + method.internalName + "', of callable object " + callable);
				plugin[method.internalName] = args[0];
				return undefined;
			}
			log.debug("calling method '" + method.internalName + "', of callable object " + callable);
			return convert(method, plugin[method.internalName].apply(plugin, args));
		}

        private static function convert(method:PluginMethod, param:Object):Object {
            log.debug(method.internalName + ", convertResult " + method.convertResult);
            return method.convertResult ? new ObjectConverter(param).convert() : param;
        }

		public static function methodNames(_methods:Array):Array {
			var result:Array = new Array();
			for (var i:Number = 0; i < _methods.length; i++) {
				result.push(PluginMethod(_methods[i]).externalName);
			}
			return result;
		}
	}
}
