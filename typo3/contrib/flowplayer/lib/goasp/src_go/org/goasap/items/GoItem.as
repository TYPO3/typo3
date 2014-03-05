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
package org.goasap.items {
	import org.goasap.GoEngine;
	import org.goasap.PlayableBase;
	import org.goasap.interfaces.IUpdatable;	

	/**
	 * Abstract base animation class for other base classes like LinearGo and PhysicsGo.
	 * 
	 * <p>This class extends PlayableBase to add features common to any animation item, 
	 * either linear or physics (LinearGo and PhysicsGo both extend this class).
	 * Animation items add themselves to GoEngine to run on a pulse, so the IUpdatable
	 * interface is implemented here, although update() needs to be subclassed.</p>
	 * 
	 * <p>Animation items should individually implement the standard <code>useRounding</code>
	 * and <code>useRelative</code> options. Three user-accessible class default settings
	 * are provided for those and <code>pulseInterval</code>, while play-state constants
	 * live in the superclass PlayableBase.</p>
	 * 
	 * @author Moses Gunesch
	 */
	public class GoItem extends PlayableBase implements IUpdatable
	{
		// -== Settable Class Defaults ==-
		
		/**
		 * Class default for the instance property <code>pulseInterval</code>.
		 * 
		 * <p>GoEngine.ENTER_FRAME seems to run the smoothest in real-world contexts.
		 * The open-source TweenBencher utility shows that timer-based framerates like 
		 * 33 milliseconds can perform best for thousands of simultaneous animations, 
		 * but in practical contexts timer-based animations tend to stutter.</p>
		 * 
		 * @default GoEngine.ENTER_FRAME
		 * @see #pulseInterval
		 */
		public static var defaultPulseInterval : Number = GoEngine.ENTER_FRAME;
		
		/**
		 * Class default for the instance property <code>useRounding</code>.
		 * @default false
		 * @see #useRounding
		 */
		public static var defaultUseRounding : Boolean = false;
		
		/**
		 * Class default for the instance property <code>useRelative</code>.
		 * @default false
		 * @see #useRelative
		 */
		public static var defaultUseRelative : Boolean = false;
		
		/**
		 * Alters the play speed for instances of any subclass that factors 
		 * this value into its calculations, such as LinearGo.
		 * 
		 * <p>A setting of 2 should result in half-speed animations, while a setting
		 * of .5 should double animation speed. Note that changing this property at 
		 * runtime does not usually affect already-playing items.</p>
		 * 
		 * <p>This property is a Go convention, and all subclasses of GoItem (on the 
		 * LinearGo base class level, but not on the item level extending LinearGo) 
		 * need to implement it individually.</p>
		 * @default 1
		 */
		public static var timeMultiplier : Number = 1;
		
		// -== Public Properties ==-
		
		/**
		 * Required by IUpdatable: Defines the pulse on which <code>update</code> is called.
		 * 
		 * <p> 
		 * Can be a number of milliseconds for Timer-based updates or 
		 * <code>GoEngine.ENTER_FRAME</code> (-1) for updates synced to the 
		 * Flash Player's framerate. If not set manually, the class 
		 * default <code>defaultPulseInterval</code> is adopted.
		 * </p>
		 * 
		 * @see #defaultPulseInterval
		 * @see org.goasap.GoEngine#ENTER_FRAME GoEngine.ENTER_FRAME
		 */
		public function get pulseInterval() : int {
			return _pulse;
		}
		public function set pulseInterval(interval:int) : void {
			if (_state==STOPPED && (interval >= 0 || interval==GoEngine.ENTER_FRAME)) {
				_pulse = interval;
			}
		}
		
		/**
		 * CONVENTION ALERT: <i>This property is considered a Go convention, and subclasses must 
		 * implement it individually by calling the correctValue() method on all calculated values 
		 * before applying them to targets.</i> 
		 * 
		 * <p>The correctValue method fixes NaN's as 0 and applies Math.round if useRounding is active.</p>
		 * 
		 * @see correctValue()
		 * @see LinearGo#onUpdate()
		 */
		public var useRounding			: Boolean = defaultUseRounding;
		
		/**
		 * CONVENTION ALERT: <i>This property is considered a Go convention, and subclasses must implement 
		 * it individually.</i> Indicates that values should be treated as relative instead of absolute.
		 * 
		 * <p>When true, user-set values should be calculated as 
		 * relative to their existing value ("from" vs. "to"), when possible. 
		 * See an example in the documentation for <code>LinearGo.start</code>.
		 * </p>
		 * <p>
		 * Items that handle more than one property at once, such as a bezier
		 * curve, might want to implement a useRelative option for each property 
		 * instead of using this overall item property, which is included here
		 * to define a convention for standard single-property items.
		 * </p>
		 * 
		 * @see #defaultUseRelative
		 */
		public var useRelative			: Boolean = defaultUseRelative;
		
		
		// -== Protected Properties ==-
		
		/**
		 * @private
		 */
		protected var _pulse			: int = defaultPulseInterval;
		
		// -== Public Methods ==-
		
		/**
		 * Constructor.
		 */
		public function GoItem() {
			super();
		}
		
		/**
		 * IMPORTANT: <i>Subclasses need to implement this functionality 
		 * individually</i>. When updating animation targets, always call
		 * <code>correctValue</code> on results first. This corrects any
		 * NaN's to 0 and applies Math.round if <code>useRounding</code> 
		 * is active.
		 * 
		 * <p>For example, a LinearGo <code>onUpdate</code> method might contain:</p>
		 * <pre>
		 * target[ prop ] = correctValue(start + (change * _position));
		 * </pre> 
		 * 
		 * @see #useRounding
		 * @see #defaultUseRounding
		 */
		public function correctValue(value:Number):Number 
		{
			if (isNaN(value)) 
				return 0;
			
			if (useRounding) // thanks John Grden
				return value = ((value%1==0) 
								? value
								: ((value%1>=.5)
									? int(value)+1
									: int(value)));
			
			return value;
		}
		
		/**
		 * Required by IUpdatable: Perform updates on a pulse.
		 * 
		 * <p>The <i>currentTime</i> parameter enables tight visual syncing of groups of items. 
		 * To ensure the tightest possible synchronization, do not set any internal start-time
		 * vars in the item until the first update() call is received, then set to the currentTime
		 * provided by GoEngine. This ensures that groups of items added in a for-loop all have the
		 * exact same start times, which may otherwise differ by a few milliseconds.</p>
		 * 
		 * @param currentTime	A clock time that should be used instead of getTimer
		 * 						to store any start-time vars on the first update call
		 * 						and for performing update calculations. The value is usually 
		 * 						no more than a few milliseconds different than getTimer,
		 * 						but using it tightly syncs item groups visually.
		 */
		public function update(currentTime : Number) : void {
			// override this method.
		}
	}
}
