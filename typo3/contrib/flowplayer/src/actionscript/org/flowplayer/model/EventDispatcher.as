/*    
 *    Copyright 2008 Anssi Piirainen
 *
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
	import flash.utils.Dictionary;
	
	import org.flowplayer.flow_internal;
	import org.flowplayer.util.Log;	
	use namespace flow_internal;

	/**
	 * @author api
	 */
	public class EventDispatcher {
		protected var log:Log = new Log(this);
		private var _beforeListeners:Dictionary = new Dictionary();
		private var _listeners:Dictionary = new Dictionary();
		protected static var _playerId:String;

		/**
		 * Unbinds the specified listener.
		 * 
		 * @param listener the listener to unbind
		 * @param event the type of the event from which the listener is removed, if <code>null</code> it's removed from all event types
		 * @param beforePhase if <code>true</code> the listener is removed from the before phase, otherwise it's removed from the normal event phase
		 */
		public final function unbind(listener:Function, event:EventType = null, beforePhase:Boolean = false):void {
			if (event) {
				removeListener(event, listener, beforePhase);
			} else {
				removeAllEventsListener(listener, beforePhase);
			}
		}

		flow_internal function setListener(event:EventType, listener:Function, clipFilter:Function = null, beforePhase:Boolean = false, addToFront:Boolean = false):void {
			if (event) {
				addListener(event, new EventListener(listener, clipFilter), beforePhase, addToFront);
			} else {
				log.debug("adding listeners, beforePhase " + beforePhase);
				addAllEventsListener(beforePhase ? cancellableEvents : allEvents, new EventListener(listener, clipFilter), beforePhase, addToFront);
			}
		}
		
		protected function get cancellableEvents():Dictionary {
			throw new Error("cancellableEvents should be overridden the subclass");
			return null;
		}
		
		protected function get allEvents():Dictionary {
			throw new Error("allEvents should be overridden the subclass");
			return null;
		}
		
		private function removeAllEventsListener(listener:Function, beforePhase:Boolean):void {
			for each (var type:Object in (beforePhase ? cancellableEvents : allEvents)) {
				removeListener(type as ClipEventType, listener, beforePhase);
			}
		}

		private function addAllEventsListener(events:Dictionary, listener:EventListener, beforePhase:Boolean, addToFront:Boolean = false):void {
			log.debug("addAllEventsListener, beforePhase " + beforePhase);
			for each (var type:Object in events) {
				addListener(type as ClipEventType, listener, beforePhase, addToFront);
			}
		}

		private function dispatchExternalEvent(event:AbstractEvent, beforePhase:Boolean = false):void {
			if (! _playerId) return;
			var externalReturnVal:Boolean = event.fireExternal(_playerId, beforePhase);
			if (! externalReturnVal) {
				log.debug("preventing default");
				event.preventDefault();
			}
		}
		/**
		 * Dispatches an event to the before phase listeners.
		 * @param event the event to dispatch
		 * @param fireExternal dispatch also to external plugins
		 * @return false if event propagation was stopped
		 */
		flow_internal final function doDispatchBeforeEvent(event:AbstractEvent, fireExternal:Boolean):Boolean {
			log.debug("doDispatchBeforeEvent, fireExternal " + fireExternal);
			if (! event.isCancellable()) {
				log.debug("event is not cancellable, will not fire event, propagation is allowed");
				return true;
			}
			if (event.target == null) {
				event.target = this;
			}
			if (fireExternal) {
				dispatchExternalEvent(event, true);
			}
			_dispatchEvent(event, _beforeListeners);
			return ! event.isDefaultPrevented();
		}

        /**
         * Dispatches the event to the action phase listeners.
         */
        flow_internal final function doDispatchEvent(event:AbstractEvent, fireExternal:Boolean):void {
            if (event.info is ErrorCode) {
                doDispatchErrorEvent(event, fireExternal);
                return;
            }
            if (event.target == null) {
                event.target = this;
            }
            if (fireExternal) {
                dispatchExternalEvent(event);
            }
            _dispatchEvent(event, _listeners);
        }

        /**
         * Dispatches an error event to the action phase listeners.
         */
        flow_internal final function doDispatchErrorEvent(event:AbstractEvent, fireExternal:Boolean):void {
            if (event.target == null) {
                event.target = this;
            }
            if (fireExternal) {
                event.fireErrorExternal(_playerId);
            }
            _dispatchEvent(event, _listeners);
        }

		private function _dispatchEvent(event:AbstractEvent, listenerDict:Dictionary):void {
			log.info(this + " dispatchEvent(), event " + event);
			var listeners:Array = listenerDict[event.eventType];
            var notified:Array = [];
			if (! listeners) {
				log.debug(this + ": dispatchEvent(): no listeners for event " + event.eventType + (listenerDict == _beforeListeners ? " in before phase" : ""));
				return;
			}
			for (var i : Number = 0; i < listeners.length; i++) {
				var listener:EventListener = listeners[i];
                if (notified.indexOf(listener) < 0) {
                    if (listener == null) {
                        log.error("found null listener");
                    }
					
					if ( CONFIG::debug ) {
						try {
							listener.notify(event);
						}	catch(e:Error) {
							log.error("Got error while dispatching " + event.eventType.name, e);
						}
					} else {
						listener.notify(event);
					}

                    
                    notified.push(listener);
                    if (event.isPropagationStopped()) {
                        return;
                    }
                }
			}
			return;			
		}
		
		private function addListener(event:EventType, listener:EventListener, beforePhase:Boolean, addToFront:Boolean = false):void {
			log.debug(this + ": adding listener for event " + event + (beforePhase ? " to before phase" : ""));
			var listenerDict:Dictionary = beforePhase ? _beforeListeners : _listeners;
			var listeners:Array = listenerDict[event];
			if (! listeners) {
				listeners = new Array();
				listenerDict[event] = listeners;
			}
			if (! hasListener(event, listener)) {
				if (addToFront) {
					listeners.splice(0, 0, listener);
				} else {
					listeners.push(listener);
				}
			}
		}

		internal function removeListener(event:EventType, listener:Function, beforePhase:Boolean = false):void {
			doRemoveListener(beforePhase ? _beforeListeners : _listeners, event, listener);
		}

		private function doRemoveListener(listenerDict:Dictionary, event:EventType, listener:Function):void {
			var listeners:Array = listenerDict[event]; 
			if (! listeners) return;
			for (var i : Number = 0; i < listeners.length; i++) {
				var eventListener:EventListener = listeners[i];
				if (eventListener.listener == listener) {
					listeners.splice(i, 1);
				}
			}
		}
		
		private function hasListener(event:EventType, listener:EventListener):Boolean {
			var listeners:Array = _listeners[event]; 
			if (! listeners) return false;
			for (var i : Number = 0; i < listeners.length; i++) {
				var eventListener:EventListener = listeners[i];
				if (eventListener.listener == listener.listener) {
					return true;
				}
			}
			return false;
		}
		
		public static function set playerId(playerId:String):void {
			_playerId = playerId;
		}
	}
}
