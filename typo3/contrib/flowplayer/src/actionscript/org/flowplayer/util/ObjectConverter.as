package org.flowplayer.util {

    import flash.utils.describeType;

    import org.flowplayer.model.Extendable;

    public class ObjectConverter {
		private var _input:Object;
		protected var log:Log = new Log(this);

		public function ObjectConverter(value:*) {
			_input = value;
		}
		
		public function convert():Object {
			return process(_input);
		}

        public static function copyProps(source:Object, target:Object):Object {
            var value:*;
            for (var key:String in source) {
                value = source[key];
                if (value != null && !(value is Function)) {
                    target[key] = value;
                }
            }
            return target;
        }
		
		private function process(value:*):Object {
			if (value is String) {
				return value;
			} else if ( value is Number ) {
				return value;
			} else if ( value is Boolean ) {
				return value;
			} else if ( value is Array ) {
				return convertArray(value as Array);
			} else if ( value is Object && value != null ) {
				return convertObject(value);
			}
            return value;
		}
		
		private function convertArray(a:Array):Array {
			var arr:Array = new Array();
			for (var i:int = 0; i < a.length; i++) {
				arr.push(process(a[i]));	
			}
			return arr;
		}
		
		private function convertObject(o:Object):Object {
			var obj:Object = new Object();
			var classInfo:XML = describeType(o);
			log.debug("classInfo : " + classInfo.@name.toString());
			
			if (classInfo.@name.toString() == "Object") {
                copyProps(o, obj);
			} else { // o is a class instance
				// Loop over all of the *annotated* variables and accessors in the class and convert
				var exposed:XMLList = classInfo.*.(hasOwnProperty("metadata") && metadata.@name=="Value");
				for each (var v:XML in exposed) {
					if (o[v.@name] != null) {
						var key2:String = v.metadata.arg.@key == "name" ? v.metadata.arg.@value : v.@name.toString();
						obj[key2] = process(o[v.@name]);
					}
				}
                if (o is Extendable) {
                    copyProps(Extendable(o).customProperties, obj);
                }
			}
			return obj;
		}
		
		public function convertKey():String {
			var reg:RegExp = /-/g;
			return _input.replace(reg, '_');
		}

		
	}
	
}
