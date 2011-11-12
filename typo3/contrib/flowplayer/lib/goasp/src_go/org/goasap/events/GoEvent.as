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
package org.goasap.events {
	import flash.events.Event;
	
	/**
	 * Standard event set for all playable Go classes.
	 * 
	 * @author Moses Gunesch
	 */
	public class GoEvent extends Event
	{
	    /**
	     * Indicates a playable instance is starting.
	     * 
	     * @eventType playableStart     
	     */
	    public static const START : String = 'playableStart';
		
		
	    /**
	     * Indicates a playable instance is updating.
	     * 
	     * @eventType playableUpdate
	     */
	    public static const UPDATE : String = 'playableUpdate';
		
		
	    /**
	     * Indicates a playable instance was paused.
	     * 
	     * @eventType playableUpdate
	     */
	    public static const PAUSE : String = 'playablePause';
		
		
	    /**
	     * Indicates a playable instance was restarted from a paused state.
	     * 
	     * @eventType playableUpdate
	     */
	    public static const RESUME : String = 'playableResume';
		
		
	    /**
	     * Indicates a playable instance has completed a cycle or loop and is starting the next one.
	     * 
	     * @eventType playableUpdate
	     */
	    public static const CYCLE : String = 'playableCycle';
		
		
	    /**
	     * Indicates a playable instance was manually stopped.
	     * 
	     * @eventType playableStop
	     */
	    public static const STOP : String = 'playableStop';
		
		
	    /**
	     * Indicates a playable instance that can end on its own has successfully finished.
	     * 
	     * @eventType playableComplete
	     * @see #STOP
	     */
	    public static const COMPLETE : String = 'playableComplete';


		/**
		 * Enables additional objects or data to be packaged in any GoEvent. This provides some
		 * general flexibility, but subclass GoEvent to define specific conventions when possible.
		 */
		public var extra : *;
		
		
		/**
		 * @param type The event type; indicates the action that triggered the event.
		 * @param bubbles Specifies whether the event can bubble up the display list hierarchy.
		 * @param cancelable Specifies whether the behavior associated with the event can be prevented.
		 */
		public function GoEvent(type:String, bubbles:Boolean=false, cancelable:Boolean=false)
		{
			super(type, bubbles, cancelable);
		}
	}
}
