
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
package org.goasap.managers {
	import flash.utils.Dictionary;
	
	import org.goasap.interfaces.IManageable;
	import org.goasap.interfaces.IManager;	

	/**
	 * Calls <code>releaseHandling()</code> on currently-active items when 
	 * property-handling overlap is detected (like two tweens trying to set 
	 * the same sprite's x property), as new items are added to GoEngine. 
	 * 
	 * <p>To activate this manager call the following line one time:</p>
	 * <pre>GoEngine.addManager( new OverlapMonitor() );</pre>
	 * 
	 * {In the game of Go, a superko is a rule that prevents a potentially
	 *  infinite competition - ko - over the same space.}
	 *  
	 * @see org.goasap.interfaces.IManager IManager
	 * @see org.goasap.interfaces.IManageable IManageable
	 * @see org.goasap.GoEngine GoEngine
	 *  
	 * @author Moses Gunesch
	 */
	public class OverlapMonitor implements IManager
	{
		/**
		 * A set of Dictionaries by target object. Targets are indexed 
		 * because they are the primary point of overlap to check first.
		 */
		protected var handlers : Dictionary = new Dictionary(false);
		
		/**
		 * Tracks subdictionary lengths.
		 */
		protected var counts : Dictionary = new Dictionary(false);
		
		/**
		 * Sets an IManageable as reserving its target/property combinations.
		 * 
		 * @param handler		IManageable to reserve
		 */
		public function reserve(handler:IManageable):void
		{
			// =======================================================================================
			// Step-by-step: Items are 'reserved' or stored in a Dictionary.
			// When a new item says it's handling the same target as a stored item, the stored item
			// is asked whether the new item's properties conflict. If so, the old item is 'released' 
			// from its duties. (Tip: 'handlers' here are GoItems like tweens, not functions.)
			// =======================================================================================
			
			
			var targs:Array = handler.getActiveTargets();
			var props:Array = handler.getActiveProperties();
			if (!targs || !props || targs.length==0 || props.length==0)
				return;

			for each (var targ:Object in targs)
			{
				if (handlers[ targ ]==null) {
					// (I switched to using sub-dictionaries w/ counters, since it may be a hair faster than Array.
					//  Strong keys are fine here since GoEngine stores and will release() all active items.)
					handlers[ targ ] = new Dictionary(false);
					handlers[ targ ][ handler ] = true;
					counts[ targ ] = 1;
					continue;
				}
				
				var targ_handlers: Dictionary = (handlers[ targ ] as Dictionary); // as in, 'active tweens handling a same Sprite'
				if (targ_handlers[ handler ]) continue; // safety (handler already reserved)
				
				// keep before isHandling() tests
				targ_handlers[ handler ] = true;
				counts[ targ ] ++;
				
				for (var other:Object in targ_handlers) {
					if (other!=handler)
						if ((other as IManageable).isHandling(props)) { // Ask each existing handler to report overlap.
							(other as IManageable).releaseHandling();	// Items should stop themselves on this call.
							// GoEngine will then call release() back on this class which will clear the item out.
					}
				}
			}
		}
		
		/**
		 * Releases an IManageable from being monitored. Does not call releaseHandling() on instances,
		 * since this method is called after an instance has already removed itself from the engine.
		 * 
		 * @param handler	The IManageable to remove from internal lists.
		 */
		public function release(handler:IManageable):void
		{
			var targs:Array = handler.getActiveTargets();
			for each (var targ:Object in targs) {
				if (handlers[ targ ] && handlers[ targ ][ handler ]!=null) {
					delete handlers[ targ ][ handler ];
					counts[ targ ] --; // don't alter this syntax. (Flex doesn't like --counts[targ])
					if ( counts[ targ ] == 0 ) {
						delete handlers[ targ ];
						delete counts[ targ ];
					}
				}
			}
		}
	}
}
