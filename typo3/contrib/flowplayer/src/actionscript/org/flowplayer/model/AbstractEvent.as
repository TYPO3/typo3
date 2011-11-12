package org.flowplayer.model {
	import flash.events.Event;
	import flash.external.ExternalInterface;
	
import flash.utils.getQualifiedClassName;
    import mx.utils.object_proxy;
import org.flowplayer.flow_internal;
    import org.flowplayer.util.Log;
import org.flowplayer.util.ObjectConverter;
	
		
	
		
	
	
	
	
		
		

	use namespace flow_internal;
	/**
	 * @author anssi
	 */
	public class AbstractEvent extends Event {
        protected var log:Log = new Log(this);
		private var _info:Object;
		private var _info2:Object;
        private var _info3:Object;
        private var _info4:Object;
        private var _info5:Object;
		private var _eventType:EventType;
		private var _target:Object;
		private var _propagationStopped:Boolean;
		private var _isDefaultPrevented:Boolean;

		public function AbstractEvent(eventType:EventType, info:Object = null, info2:Object = null, info3:Object = null, info4:Object = null, info5:Object = null) {
			super(eventType.name);
			this._eventType = eventType;
			this._info = info;
			this._info2 = info2;
            this._info3 = info3;
            this._info4 = info4;
            this._info5 = info5;
			_target = target;
            log.debug(_info + ", " + _info2 + ", " + _info3 + ", " + _info4 + ", " + _info5);
		}

        public function get error():ErrorCode {
            return _info as ErrorCode;
        }

		public function isCancellable():Boolean {
			return _eventType.isCancellable;
		}

		public override function clone():Event {
			return new AbstractEvent(_eventType, _info);
		}

		public override function toString():String {
			return formatToString("AbstractEvent", "type", "target", "info", "info2", "info3", "info4", "info5");
		}
		
		public function get info():Object {
			return _info;
		}
		
		override public function get target():Object {
			if (_target) return _target;
			return super.target;
		}
		
		public function set target(target:Object):void {
			_target = target;
		}
		
		public function get eventType():EventType {
			return _eventType;
		}
		
		override public function stopPropagation():void {
			_propagationStopped = true;
		}
		
		override public function stopImmediatePropagation():void {
			_propagationStopped = true;
		}

		public function isPropagationStopped():Boolean {
			return _propagationStopped;
		}

        flow_internal function fireErrorExternal(playerId:String):void {
            try {
                ExternalInterface.call(
                    "flowplayer.fireEvent",
                    playerId || ExternalInterface.objectID, getExternalName(eventType.name, false), ErrorCode(_info).code, ErrorCode(_info).message + info2 ? ": " + info2 : "");
            } catch (e:Error) {
                log.error("Error in fireErrorExternal() "+ e);
            }
        }

		flow_internal function fireExternal(playerId:String, beforePhase:Boolean = false):Boolean {
            log.debug("fireExternal " + getExternalName(eventType.name, beforePhase) + ", " + externalEventArgument + ", " + externalEventArgument2 + ", " + externalEventArgument3 + "," + externalEventArgument4 + ", " + externalEventArgument5);
			if (!ExternalInterface.available) return true;
			// NOTE: externalEventArgument3 is not converted!
            try {
                var returnVal:Object = ExternalInterface.call(
                    "flowplayer.fireEvent",
                    playerId || ExternalInterface.objectID, getExternalName(eventType.name, beforePhase), convert(externalEventArgument), convert(externalEventArgument2), externalEventArgument3, externalEventArgument4, externalEventArgument5);
            } catch (e:Error) {
                log.error("Error in fireExternal() " + e);                
            }
			if (returnVal + "" == "false") return false;
			return true;
		}
		
		private function convert(objToConvert:Object):Object {
            if (_eventType.custom) return objToConvert;
			return new ObjectConverter(objToConvert).convert();
		}

//		private function jsonize(externalEventArgument:Object):String {
//			if (externalEventArgument is String) return externalEventArgument as String;
//			return JSON.encode(externalEventArgument);
//		}

		protected function getExternalName(name:String, beforePhase:Boolean):String {
			if (! beforePhase) return name;
			if (! name.indexOf("on") == 0) return "onBefore" + name;
			return "onBefore" + name.substr(2);
		}

		protected function get externalEventArgument():Object {
			return target;
		}
		
		protected function get externalEventArgument2():Object {
			return _info;
		}
		
		protected function get externalEventArgument3():Object {
			return _info2;
		}

        protected function get externalEventArgument4():Object {
            return _info3;
        }

        protected function get externalEventArgument5():Object {
            return _info4;
        }

		override public function isDefaultPrevented():Boolean {
			return _isDefaultPrevented;
		}
		
		override public function preventDefault():void {
			_isDefaultPrevented = true;
		}

        public function get info2():Object {
            return _info2;
        }

        public function get info3():Object {
            return _info3;
        }

        public function get info4():Object {
            return _info4;
        }

        public function get info5():Object {
            return _info5;
        }
	}
}
