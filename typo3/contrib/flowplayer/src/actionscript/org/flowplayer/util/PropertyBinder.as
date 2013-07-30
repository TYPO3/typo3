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
    import flash.utils.describeType;
    import flash.utils.getQualifiedClassName;
	
	import org.flowplayer.util.Log;	

	/**
	 * PropertyBinder is used to populate object's properties by copying values
	 * from other objects. The target object should be an instance of a class that contains
	 * accessor or setter functions for the properties that are found in the source.
	 * 
	 * @author api
	 */
	public class PropertyBinder {

		private var log:Log = new Log(this);
		private var _object:Object;
        private var _objectDesc:XML;
		private var _extraProps:String;

		/**
		 * Creates a new property binder for the specified target object.
		 * @param object the target object into which the properties will be copid to
		 * @param extraProps a property name for all properties for which the target does not provide an accessor or a setter function
		 */
		public function PropertyBinder(object:Object, extraProps:String = null) {
			log.info("created for " + getQualifiedClassName(object));
			_object = object;
			_extraProps = extraProps;
            _objectDesc = describeType(_object);
		}
		
		public function copyProperties(source:Object, overwrite:Boolean = true):Object {
			if (! source) return _object;
			log.debug("copyProperties, overwrite = " + overwrite + (_extraProps ? ", extraprops will be set to " + _extraProps : ""));
			for (var prop:String in source) {
				if (overwrite || ! hasValue(_object, prop)) {

					copyProperty(prop, source[prop]);
				}
			}
			log.debug("done with " + getQualifiedClassName(_object));
			return _object;
		}

        public function copyProperty(prop:String, value:Object, convertType:Boolean = false):void {
            log.debug("copyProperty() " + prop + ": " + value);
            var setter:String = "set" + prop.charAt(0).toUpperCase() + prop.substring(1);
            var method:XMLList = _objectDesc.method.(@name == setter);
            if (method.length() == 1) {
                try {
                    _object[setter](convertType ? toType(value, method.@type) : value);
                    log.debug("successfully initialized property '" + prop + "' to value '" + value +"'");
                    return;
                } catch (e:Error) {
                    log.debug("unable to initialize using " + setter);
                }
            }

            var property:XMLList = _objectDesc.*.(hasOwnProperty("@name") && @name == prop);
            if (property.length() == 1) {
                try {
                    log.debug("trying to set property '" + prop + "' directly");
                    _object[prop] = convertType ? toType(value, property.@type) : value;
                    log.debug("successfully initialized property '" + prop + "' to value '" + value + "'");
                    return;
                } catch (e:Error) {
                    log.debug("unable to set to field / using accessor");
                }
            }

            if (_extraProps) {
                log.debug("setting to extraprops " + _extraProps + ", prop " + prop + " value " + value);
                configure(_object, _extraProps || "customProperties", prop, value);
            } else {
                log.debug("skipping property '" + prop + "', value " + value);
            }
        }

        private function toType(value:Object, type:String):Object {
            log.debug("toType() " + type);
            if (type == "Boolean") return value == "true";
            if (type == "Number") return Number(value);
            return value;
        }
		
		private function hasValue(obj:Object, prop:String):Boolean {

			if (objHasValue(obj, prop)) {
				return true;
			} else if (_extraProps) {
				return objHasValue(obj[_extraProps], prop);
			}
			return false;
		}

        private function objHasValue(obj:Object, prop:String):Boolean {
            //fix for #225
            if (obj == null) return false;
            try {

                var value:Object = obj[prop];
                if (value is Number) {
                    return value >= 0;
                }
                if (value is Boolean) {
                    return true;
                }
                return value != null;
            } catch (ignore:Error) {}

            // some flowplayer classes implement hasValue() (for example DisplayPropertiesImpl)
            try {
                return obj.hasValue(prop);
            } catch (ignore:Error) { }

			return false;
		}

		private function configure(configurable:Object, configProperty:String, prop:String, value:Object):void {
			var config:Object = configurable[configProperty] || new Object();
			config[prop] = value;
			configurable[configProperty] = config;
		}
	}
}
