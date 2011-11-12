package org.flowplayer.model {
	import flash.utils.Dictionary;
	
	import org.flowplayer.flow_internal;
	import org.flowplayer.model.EventDispatcher;	

	use namespace flow_internal;
	/**
	 * @author anssi
	 */
	public class PluginEventDispatcher extends EventDispatcher {
		
		/**
		 * Dispatches a plugin event.
		 * @param eventType the type of the event to dispatch
		 * @param eventId the ID for the event, this the ID used to distinguis between diferent generic plugin events
		 * @param info optional info object, will be passed to JavaScript
		 * @param info2 optional info object, will be passed to JavaScript
		 * @see PluginEvent#id
		 */
		public function dispatch(eventType:PluginEventType, eventId:Object = null, info:Object = null, info2:Object = null, info3:Object = null):void {
                doDispatchEvent(new PluginEvent(eventType, name, eventId, info, info2, info3), true);
            }

		/**
		 * Dispatches an event of type PluginEventType.LOAD
         * @see PluginEventType#LOAD
         */
        public function dispatchOnLoad():void {
            dispatch(PluginEventType.LOAD);
        }


        /**
         * Dispatches a plugin error event.
         * @param error
         * @param info optional info object, will be passed to JavaScript
         * @see PluginEventType#ERROR
         */
        public function dispatchError(error:PluginError, info:Object = null):void {
            doDispatchErrorEvent(new PluginEvent(error.eventType as PluginEventType, name, error, info), true);
        }

        /**
         * Dispatches a plugin event in the before phase.
         *
         * @param eventType the type of the event to dispatch
         * @param eventId the ID for the event, this the ID used to distinguis between diferent generic plugin events
         * @param info optional info object, will be passed to JavaScript
         * @param info2 optional info object, will be passed to JavaScript
         * @return true if the event can continue, false if it was prevented
         * @see PluginEvent#id
         */
        public function dispatchBeforeEvent(eventType:PluginEventType, eventId:Object = null, info:Object = null, info2:Object = null, info3:Object = null):Boolean {
            return doDispatchBeforeEvent(new PluginEvent(eventType, name, eventId, info, info2, info3), true);
        }

        public function dispatchEvent(event:PluginEvent):void {
            doDispatchEvent(event, true);
        }

        public function onPluginEvent(listener:Function):void {
            setListener(PluginEventType.PLUGIN_EVENT, listener);
        }

        public function onBeforePluginEvent(listener:Function):void {
            setListener(PluginEventType.PLUGIN_EVENT, listener, null, true);
        }

		public function onLoad(listener:Function):void {
			setListener(PluginEventType.LOAD, listener);
		}

		public function onError(listener:Function):void {
			setListener(PluginEventType.ERROR, listener);
		}
		
		override protected function get cancellableEvents():Dictionary {
			return PluginEventType.cancellable;
		}

		override protected function get allEvents():Dictionary {
			return PluginEventType.all;
		}
		
		public function get name():String {
			return null;
		}
	}
}
