/**
 * Copyright (c) 2007 Moses Gunesch
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
package org.goasap {
	import flash.events.EventDispatcher;
	import flash.utils.Dictionary;
	import flash.utils.getQualifiedClassName;
	
	import org.goasap.errors.InstanceNotAllowedError;
	import org.goasap.interfaces.IPlayableBase;	

	/**
	 * Top-level abstract base class for playable classes that provides a standard
	 * set of play-state constants and an instance playableID value.
	 * 
	 * <p>Playable classes in the Go system should normally extend this base class
	 * and implement the IPlayable interface. This is not mandatory since utilities
	 * normally reference playable items via the IPlayable datatype; However they also
	 * refer directly to the constants defined here, so those should be adhered to even
	 * if this class is not directly extended.</p> 
	 * 
	 * <p>Important memory management issue: Playable items that are not added to 
	 * GoEngine can get garbage collected during play. It is a convention of the Go
	 * system that such items store a reference to themselves during play that is 
	 * removed in stop(). See <a href="#_playRetainer">_playRetainer</a> for more.</p>
	 * 
	 * @author Moses Gunesch
	 */
	public class PlayableBase extends EventDispatcher implements IPlayableBase {
		
		// -== Standard Go Play-state Constants ==-
		
		/**
		 * Instance play is currently stopped.
		 */
		public static const STOPPED			: String = "STOPPED";
		
		/**
		 * Instance play is currently paused.
		 */
		public static const PAUSED			: String = "PAUSED";
		
		/**
		 * Instance is currently playing a delay, but has not started playing.
		 * Delays are a non-universal feature that must be custom-implemented,
		 * so some subclasses of PlayableBase don't use this constant. 
		 */
		public static const PLAYING_DELAY	: String = "PLAYING_DELAY";
		
		/**
		 * Instance play is currently playing.
		 */
		public static const PLAYING			: String = "PLAYING";
		

		// -== Public Properties ==-
		
		/**
		 * An arbitrary id value for the convenient identification of any
		 * instance, automatically set to an instance count by this class.
		 */
		public function get playableID() : * {
			return _id;
		}
		public function set playableID(value: *):void {
			_id = value;
		}

		/**
		 * Returns the value of one of this class' play-state constants.
		 * @see #STOPPED
		 * @see #PAUSED
		 * @see #PLAYING_DELAY
		 * @see #PLAYING
		 */
		public function get state() : String {
			return _state;
		}

		// -== Protected Properties ==-
		
		/**
		 * @private
		 */
		private static var _idCounter	: int = -1;
		/**
		 * @private
		 */
		protected var _state : String = STOPPED;
		/**
		 * @private
		 */
		protected var _id : *;
		/**
		 * Memory-management: Read this if you're subclassing PlayableBase but not adding your
		 * instance to GoEngine.
		 * 
		 * <p>Subclasses that do not add themselves to GoEngine during play should stash a 
		 * this-reference here in start() and delete it in stop. This prevents instance from 
		 * getting GC'd during play. For an example see SequenceBase's start and stop methods.</p>
		 * 
		 * <p>This step is not necessary if GoEngine.addItem is used, which keeps a live reference 
		 * during play.</p>
		 * 
		 * <p>This protected static var is just a convenience. You can mimic the technique of
		 * stashing a this-reference using any static property, to temporarily protect the object
		 * being referenced from garbage collection.</p>
		 * 
		 * @see org.goasap.utils.SequenceBase SequenceBase
		 */
		protected static var _playRetainer : Dictionary = new Dictionary(false);
		
		
		// -== Public Methods ==-
		
		/**
		 * Throws an InstanceNotAllowedError if directly instantiated, also sets a
		 * default playableID to an instance count number.
		 */
		public function PlayableBase() : void {
			var className:String = getQualifiedClassName(this);
			if (className.slice(className.lastIndexOf("::")+2) == "PlayableBase") {
				throw new InstanceNotAllowedError("PlayableBase");
			}
			playableID = ++ _idCounter;
		}

		/**
		 * Appends the regular toString value with the instance's playableID.
		 * 
		 * @return	String representation of this instance.
		 */
		override public function toString():String {
			var s:String = super.toString();
			var addLast:Boolean = (s.charAt(s.length-1)=="]");
			if (addLast) s = s.slice(0,-1);
			if (playableID is String) s += " playableID:\"" + playableID + "\"";
			else s += " playableID:" + playableID;
			if (addLast) s += "]";
			return s;
		}
	}
}
