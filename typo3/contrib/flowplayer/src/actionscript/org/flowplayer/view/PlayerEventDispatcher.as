package org.flowplayer.view {
	import org.flowplayer.flow_internal;
    import org.flowplayer.model.ErrorCode;
    import org.flowplayer.model.EventDispatcher;
	import org.flowplayer.model.PlayerError;
	import org.flowplayer.model.PlayerEvent;
	import org.flowplayer.model.PlayerEventType;
	
	import flash.utils.Dictionary;	
	
	use namespace flow_internal;
	
	/**
	 * @author anssi
	 */
	public class PlayerEventDispatcher extends EventDispatcher {
		
		/**
		 * Dispatches a player event of the specified type.
		 */
		public function dispatch(eventType:PlayerEventType, info:Object = null, dispatchExternal:Boolean = true):void {
            doDispatchEvent(new PlayerEvent(eventType, info), dispatchExternal);
        }

		/**
		 * Dispatches a player event.
		 */
		public function dispatchEvent(event:PlayerEvent):void {
			doDispatchEvent(event, true);
		}
		
		public function dispatchError(error:ErrorCode, info:Object = null):void {
			doDispatchErrorEvent(new PlayerEvent(error.eventType, error, info), true);
		}

		/**
		 * Dispatches the specified event to the before phase listeners.
		 */
		public function dispatchBeforeEvent(event:PlayerEvent):Boolean {
			return doDispatchBeforeEvent(event, true);
		}

        /**
         * Adds a onLoad event listener. The event is triggered when the player has been loaded and initialized.
         * @param listener
         * @param add if true the listener is addes, otherwise removed
         * @see PlayerEventType
         */
        public function onLoad(listener:Function):void {
            setListener(PlayerEventType.LOAD, listener);
        }

        /**
         * Adds a onUnload event listener. The event is triggered when the player is closed. Note that this event
         * will be only triggered when the player is embedded using the flowplayer.js script.
         * @param listener
         * @param add if true the listener is addes, otherwise removed
         * @see PlayerEventType
         */
        public function onUnload(listener:Function):void {
            setListener(PlayerEventType.UNLOAD, listener);
        }

		/**
		 * Add a fullscreen-enter event listener for the "before phase" of this event.
		 */
		public function onBeforeFullscreen(listener:Function):void {
			setListener(PlayerEventType.FULLSCREEN, listener, null, true);
		}

		/**
		 * Adds a fullscreen-enter event listener. The event is fired when the player goes to
		 * the fullscreen mode.
		 * @param listener
		 * @see PlayerEventType
		 */
		public function onFullscreen(listener:Function):void {
			log.debug("adding listener for fullscreen " + PlayerEventType.FULLSCREEN);
			setListener(PlayerEventType.FULLSCREEN, listener);
		}
		
		/**
		 * Adds a fullscreen-exit event listener. The event is fired when the player exits from
		 * the fullscreen mode.
		 * @param listener
		 * @see PlayerEventType
		 */
		public function onFullscreenExit(listener:Function):void {
			setListener(PlayerEventType.FULLSCREEN_EXIT, listener);
		}
		
		/**
		 * Adds a volume mute event listener. The event is fired when the volume is muted
		 * @param listener
		 * @see PlayerEventType
		 */
		public function onMute(listener:Function):void {
			setListener(PlayerEventType.MUTE, listener);
		}
		
		/**
		 * Adds a volume un-mute event listener. The event is fired when the volume is unmuted
		 * @param listener
		 * @see PlayerEventType
		 */
		public function onUnmute(listener:Function):void {
			setListener(PlayerEventType.UNMUTE, listener);
		}
		
		/**
		 * Adds a volume event listener. The event is fired when the volume level is changed.
		 * @param listener
		 * @see PlayerEventType
		 */
		public function onVolume(listener:Function):void {
			setListener(PlayerEventType.VOLUME, listener);
		}

        /**
         * Adds a mouse over listener.
         * @param listener
         * @return
         */
        public function onMouseOver(listener:Function):void {
            setListener(PlayerEventType.MOUSE_OVER, listener);
        }

        /**
         * Adds a mouse over listener.
         * @param listener
         * @return
         */
        public function onMouseOut(listener:Function):void {
            setListener(PlayerEventType.MOUSE_OUT, listener);
        }

		override protected function get cancellableEvents():Dictionary {
			return PlayerEventType.cancellable;
		}

		override protected function get allEvents():Dictionary {
			return PlayerEventType.all;
		}
	}
}
