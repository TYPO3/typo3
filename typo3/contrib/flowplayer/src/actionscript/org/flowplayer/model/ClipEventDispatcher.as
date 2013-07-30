package org.flowplayer.model {
	import flash.utils.Dictionary;
	
	import org.flowplayer.flow_internal;
	import org.flowplayer.model.ClipEvent;
	import org.flowplayer.model.EventDispatcher;	

	use namespace flow_internal;
	
	/**
	 * ClipEventDispatcher is used to attach listeners for ClipEvents and for dispatching ClipEvents.
	 * 
	 * @see ClipEvent
	 */
	public class ClipEventDispatcher extends EventDispatcher {
		
		public function dispatch(eventType:ClipEventType, info:Object = null, info2:Object = null, info3:Object = null):void {
        	doDispatchEvent(new ClipEvent(eventType, info, info2, info3), false);
        }

		public function dispatchError(error:ClipError, info:Object = null):void {
			doDispatchErrorEvent(new ClipEvent(error.eventType, error, info), false);
		}
		
		public function dispatchEvent(event:ClipEvent):void {
			doDispatchEvent(event, false);
		}

		public function dispatchBeforeEvent(event:AbstractEvent):Boolean {
			return doDispatchBeforeEvent(event, false);
		}
		
		public function onUpdate(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.UPDATE, listener, clipFilter, false, addToFront);
		}

		public function onBeforeAll(listener:Function, clipFilter:Function = null):void {
			setListener(null, listener, clipFilter, true);
		}

		public function onAll(listener:Function, clipFilter:Function = null):void {
			setListener(null, listener, clipFilter);
		}
		
		public function onConnect(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.CONNECT, listener, clipFilter, false, addToFront);
		}

		/**
		 * Adds a listener for the start event.
		 * 
		 * @param listener the listener to add
		 * @param clipFilter a clip filter function, the listener is only added if the filter function returns true for a clip
		 * @param addToFront if <code>true</code> the listener is added to the front of the listener list so that it will get notified before the listeners that had been added before this one 
		 */
		public function onStart(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.START, listener, clipFilter, false, addToFront);
		}

		public function onMetaData(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.METADATA, listener, clipFilter, false, addToFront);
		}

        public function onMetaDataChange(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
            setListener(ClipEventType.METADATA_CHANGED, listener, clipFilter, false, addToFront);
        }

		public function onBeforeBegin(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.BEGIN, listener, clipFilter, true, addToFront);
		}

		public function onBegin(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.BEGIN, listener, clipFilter, false, addToFront);
		}

		public function onBeforePause(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.PAUSE, listener, clipFilter, true, addToFront);
		}

		public function onPause(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.PAUSE, listener, clipFilter, false, addToFront);
		}

		public function onBeforeResume(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.RESUME, listener, clipFilter, true, addToFront);
		}

		public function onResume(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.RESUME, listener, clipFilter, false, addToFront);
		}

		public function onBeforeStop(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.STOP, listener, clipFilter, true, addToFront);
		}

		public function onStop(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.STOP, listener, clipFilter, false, addToFront);
		}

		public function onFinish(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.FINISH, listener, clipFilter, false, addToFront);
		}
		
		public function onBeforeFinish(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.FINISH, listener, clipFilter, true, addToFront);
		}

		public function onCuepoint(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.CUEPOINT, listener, clipFilter, false, addToFront);
		}

		public function onBeforeSeek(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.SEEK, listener, clipFilter, true, addToFront);
		}

		public function onSeek(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.SEEK, listener, clipFilter, false, addToFront);
		}

		public function onBufferEmpty(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.BUFFER_EMPTY, listener, clipFilter, false, addToFront);
		}

		public function onBufferFull(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.BUFFER_FULL, listener, clipFilter, false, addToFront);
		}

		public function onBufferStop(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.BUFFER_STOP, listener, clipFilter, false, addToFront);
		}

		public function onLastSecond(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.LAST_SECOND, listener, clipFilter, false, addToFront);
		}

		public function onNetStreamEvent(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.NETSTREAM_EVENT, listener, clipFilter, false, addToFront);
		}

		public function onConnectionEvent(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.CONNECTION_EVENT, listener, clipFilter, false, addToFront);
		}

		public function onError(listener:Function, clipFilter:Function = null, addToFront:Boolean = false):void {
			setListener(ClipEventType.ERROR, listener, clipFilter, false, addToFront);
		}

        public function onPlaylistReplace(listener:Function, addToFront:Boolean = false):void {
            setListener(ClipEventType.PLAYLIST_REPLACE, listener, null, false, addToFront);
        }

        public function onClipAdd(listener:Function, addToFront:Boolean = false):void {
            setListener(ClipEventType.CLIP_ADD, listener, null, false, addToFront);
        }
        
        public function onResized(listener:Function, addToFront:Boolean = false):void {
            setListener(ClipEventType.CLIP_RESIZED, listener, null, false, addToFront);
        }

        public function onPlayStatus(listener:Function, addToFront:Boolean = false):void {
            setListener(ClipEventType.PLAY_STATUS, listener, null, false, addToFront);
        }

        public function onSwitch(listener:Function, addToFront:Boolean = false):void {
            setListener(ClipEventType.SWITCH, listener, null, false, addToFront);
        }

        public function onSwitchFailed(listener:Function, addToFront:Boolean = false):void {
            setListener(ClipEventType.SWITCH_FAILED, listener, null, false, addToFront);
        }

        public function onSwitchComplete(listener:Function, addToFront:Boolean = false):void {
            setListener(ClipEventType.SWITCH_COMPLETE, listener, null, false, addToFront);
        }

		/**
		 * Adds a StageVideo state change event listener. The event is fired when the player uses or discards StageVideo
		 * @param listener
		 * @see PlayerEventType
		 */
		public function onStageVideoStateChange(listener:Function, addToFront:Boolean = false):void {
			setListener(ClipEventType.STAGE_VIDEO_STATE_CHANGE, listener, null, false, addToFront);
		}

		override protected function get cancellableEvents():Dictionary {
			return ClipEventType.cancellable;
		}

		override protected function get allEvents():Dictionary {
			return ClipEventType.all;
		}
	}
}
