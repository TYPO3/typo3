/*    
 *    Copyright 2008 Anssi Piirainen
 *
 *    This file is part of FlowPlayer.
 *
 *    FlowPlayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    FlowPlayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with FlowPlayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.model {
    import org.flowplayer.model.PluginEventDispatcher;
	import org.flowplayer.model.PluginModel;	

	/**
	 * @author api
	 */
	public class PluginModelImpl extends PluginEventDispatcher implements PluginModel {

		private var _methods:Array = new Array();
		private var _pluginObject:Object;
		private var _name:String;
		private var _config:Object;
        private var _builtIn:Boolean;
        private var _url:String;

		public function PluginModelImpl(pluginObject:Object, name:String) {
			_pluginObject = pluginObject;
			_name = name;
		}

		public function clone():Cloneable {
			var clone:PluginModelImpl = new PluginModelImpl(_pluginObject, name);
			clone.config = config;
			clone.methods = _methods;
			return clone;
		}
		
		public function get pluginObject():Object {
			return _pluginObject;
		}
		
		public function set pluginObject(pluginObject:Object):void {
			_pluginObject = pluginObject;
		}
		
		[Value]
		override public function get name():String {
			return _name;
		}
		
		public function set name(name:String):void {
			_name = name;
		}
		
		[Value]
		public function get config():Object {
			return _config;
		}
		
		public function set config(config:Object):void {
			_config = config;
		}
		
		public function addMethod(method:PluginMethod):void {
			_methods.push(method);
		}
		
		public function getMethod(externalName:String):PluginMethod {
			return PluginMethodHelper.getMethod(_methods, externalName);
		}
		
		public function invokeMethod(externalName:String, args:Array = null):Object {
			return PluginMethodHelper.invokePlugin(this, _pluginObject, externalName, args);
		}
		
		[Value(name="methods")]
		public function get methodNames():Array {
			return PluginMethodHelper.methodNames(_methods);
		}
		
		public function set methods(methods:Array):void {
			_methods = methods;
		}
		
		public function toString():String {
			return "[PluginModelImpl] '" + name + "'";
		}

        [Value(name="builtIn")]
        public function get isBuiltIn():Boolean {
            return _builtIn;
        }

        public function set isBuiltIn(value:Boolean):void {
            _builtIn = value;
        }

        [Value]
        public function get url():String {
            return _url;
        }

        public function set url(url:String):void {
            _url = url;
        }
    }
}
