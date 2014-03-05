
package org.goasap.interfaces {
	import flash.events.IEventDispatcher;
	
	/**
	 * Defines the portion of the IPlayable interface used by the PlayableBase
	 * class, which provides a standard set of play-state constants used in Go.
	 * 
	 * @author Moses Gunesch
	 */
	public interface IPlayableBase extends IEventDispatcher {

		/**
		 * Normally this should only return one of the standard play-state
		 * constants defined in the PlayableBase class.
		 */
		function get state () : String;

		/**
		 * An arbitrary id value for the convenient identification of any
		 * playable instance. 
		 * 
		 * PlayableBase sets this property to an instance-count by default,
		 * which can be overwritten in program code with any value.
		 */
		function get playableID () : *;
		function set playableID (value: *) : void;
	}
}
