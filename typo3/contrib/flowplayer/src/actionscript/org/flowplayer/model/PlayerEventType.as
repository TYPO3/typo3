package org.flowplayer.model {
	import flash.utils.Dictionary;
	
	import org.flowplayer.model.EventType;	
	/**
	 * @author anssi
	 */
	public class PlayerEventType extends EventType {
        public static const LOAD:PlayerEventType = new PlayerEventType("onLoad");
        public static const UNLOAD:PlayerEventType = new PlayerEventType("onUnload");
		public static const KEYPRESS:PlayerEventType = new PlayerEventType("onKeyPress");
				
		public static const MUTE:PlayerEventType = new PlayerEventType("onMute");
		public static const UNMUTE:PlayerEventType = new PlayerEventType("onUnmute");
		public static const VOLUME:PlayerEventType = new PlayerEventType("onVolume");
		public static const FULLSCREEN:PlayerEventType = new PlayerEventType("onFullscreen");
		public static const FULLSCREEN_EXIT:PlayerEventType = new PlayerEventType("onFullscreenExit");
		public static const MOUSE_OVER:PlayerEventType = new PlayerEventType("onMouseOver");
		public static const MOUSE_OUT:PlayerEventType = new PlayerEventType("onMouseOut");
		public static const ERROR:PlayerEventType = new PlayerEventType("onError");

		private static var _allValues:Dictionary;
		private static var _cancellable:Dictionary = new Dictionary();
		{
			_cancellable[KEYPRESS.name] = KEYPRESS;
			_cancellable[MUTE.name] = MUTE;
			_cancellable[UNMUTE.name] = UNMUTE;
			_cancellable[VOLUME.name] = VOLUME;
			_cancellable[FULLSCREEN.name] = FULLSCREEN;
		}		

		public function PlayerEventType(name:String) {
			super(name);
			if (! _allValues) {
				_allValues = new Dictionary();
			}
			_allValues[name] = this;
		}

		override public function get isCancellable():Boolean {
			return _cancellable[this.name];
		}
		
		public static function get cancellable():Dictionary {
			return _cancellable;
		}

		public static function get all():Dictionary {
			return _allValues;
		}

		public function toString():String {
			return "[PlayerEventType] '" + name + "'";
		}
	}
}
