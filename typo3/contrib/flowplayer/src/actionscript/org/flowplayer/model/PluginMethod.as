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

	/**
	 * @author api
	 */
	public class PluginMethod {
		private var _externalName:String;
		private var _internalName:String;
		private var _isGetter:Boolean;
		private var _isSetter:Boolean;
		private var _hasReturnValue:Boolean;
        private var _convertResult:Boolean;
		
		public static function method(externalName:String, pluginFunctionName:String, hasReturnValue:Boolean, convertResult:Boolean):PluginMethod {
			return new PluginMethod(externalName, pluginFunctionName, false, false, hasReturnValue, convertResult);
		}

		public static function setter(externalName:String, pluginFunctionName:String):PluginMethod {
			return new PluginMethod(externalName, pluginFunctionName, false, true);
		}

		public static function getter(externalName:String, pluginFunctionName:String, convertResult:Boolean):PluginMethod {
			return new PluginMethod(externalName, pluginFunctionName, true, false, true, convertResult);
		}		

		public function PluginMethod(externalName:String, pluginFunctionName:String, isGetter:Boolean = false,
                                     isSetter:Boolean = false, hasReturnValue:Boolean = false, convertResult:Boolean = false) {
			_externalName = externalName;
			_internalName = pluginFunctionName;
			if (_isGetter && isSetter) {
				throw new Error("PluginMethod cannot be a setter and a getter at the same time");
			}
			_isGetter = isGetter;
			_isSetter = isSetter;
			_hasReturnValue = hasReturnValue;
            _convertResult = convertResult;
		}

		public function get externalName():String {
			return _externalName;
		}
		
		public function get internalName():String {
			return _internalName;
		}
		
		public function get isGetter():Boolean {
			return _isGetter;
		}
		
		public function get isSetter():Boolean {
			return _isSetter;
		}
		
		public function get hasReturnValue():Boolean {
			return _hasReturnValue;
		}
		
		public function set hasReturnValue(hasReturnValue:Boolean):void {
			_hasReturnValue = hasReturnValue;
		}

        public function get convertResult():Boolean {
            return _convertResult;
        }
    }
}
