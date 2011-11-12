package org.flowplayer.model {
	import flash.utils.Dictionary;	
	
	/**
	 * @author anssi
	 */
	public class EventType {
		private var _name:String;
        private var _custom:Boolean;

		public function EventType(name:String, custom:Boolean = false) {
			_name = name;
            _custom = custom;
		}

		public function get isCancellable():Boolean {
			throw new Error("isCancellable() not overridden");
			return false;
		}
		
		public function get name():String {
			return _name;
		}
        
        public function get custom():Boolean {
            return _custom;
        }
    }
}
