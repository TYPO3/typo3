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
	import flash.display.DisplayObject;

    import org.flowplayer.model.Cloneable;
	
	/**
	 * @author api
	 */
	public class DisplayPluginModelImpl extends DisplayPropertiesImpl implements DisplayPluginModel {
		private var _config:Object;
		private var _methods:Array = new Array();
        private var _builtIn:Boolean;
        private var _url:String;

		public function DisplayPluginModelImpl(disp:DisplayObject, name:String, setDefaults:Boolean = true):void {
			super(disp, name, setDefaults);
		}

		public function addMethod(method:PluginMethod):void {
			_methods.push(method);
		}
		
		public function getMethod(externalName:String):PluginMethod {
			return PluginMethodHelper.getMethod(_methods, externalName);
		}
		
		public function invokeMethod(externalName:String, args:Array = null):Object {
			return PluginMethodHelper.invokePlugin(this, getDisplayObject(), externalName, args);
		}
		
		public function get config():Object {
			return _config;
		}
		
		public function set config(config:Object):void {
			_config = config;
		}
		
		public function set visible(visible:Boolean):void {
			super.display = visible ? "block" : "none";
		}

        override protected function copyFields(from:DisplayProperties, to:DisplayPropertiesImpl):void {
            super.copyFields(from, to);
            DisplayPluginModelImpl(to).config = DisplayPluginModelImpl(from).config;
            DisplayPluginModelImpl(to).methods = DisplayPluginModelImpl(from).methods;
            DisplayPluginModelImpl(to).isBuiltIn = DisplayPluginModelImpl(from).isBuiltIn;
        }

        public override function clone():Cloneable {
			var copy:DisplayPluginModelImpl = new DisplayPluginModelImpl(getDisplayObject(), name);
            copyFields(this, copy);
            return copy;
		}

        public function get methods():Array {
            return _methods;
        }

        public function set methods(values:Array):void {
            _methods = values;
        }

		[Value(name="methods")]
		public function get methodNames():Array {
			return PluginMethodHelper.methodNames(_methods);
		}
		
		public function get pluginObject():Object {
			return getDisplayObject();
		}

		public function set pluginObject(pluginObject:Object):void {
			setDisplayObject(pluginObject as DisplayObject);
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
