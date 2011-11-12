package org.flowplayer.model {
	import flash.utils.Dictionary;
	
	import org.flowplayer.model.EventType;	

	/**
	 * @author anssi
	 */
	public class PluginEventType extends EventType {
		
		public static const PLUGIN_EVENT:PluginEventType = new PluginEventType("onPluginEvent");		
		public static const LOAD:PluginEventType = new PluginEventType("onLoad");		
		public static const ERROR:PluginEventType = new PluginEventType("onError");		

		private static var _allValues:Dictionary;
		private static var _cancellable:Dictionary = new Dictionary();
		{
			_cancellable[PLUGIN_EVENT.name] = PLUGIN_EVENT;
		}

		public function PluginEventType(name:String) {
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
			return "[PluginEventType] '" + name + "'";
		}
	}
}
