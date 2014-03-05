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
package org.goasap.interfaces {
	
	/**
	 * Makes any object compatible with GoEngine.
	 * 
	 * @author Moses Gunesch
	 */
	public interface IUpdatable 
	{
		
		/**
		 * Perform updates on a pulse.
		 * 
		 * @param currentTime	A clock time that should be used instead of getTimer
		 * 						in performing update calculations. (The value is usually 
		 * 						not more than a couple milliseconds different than getTimer
		 * 						but using it tightly syncs all items in the timer group
		 * 						and can make a perceptible difference.)
		 */
		function update (currentTime : Number) : void;
		
		
		/**
		 * Defines the pulse on which update is called. 
		 * 
		 * @return	A number of milliseconds for Timer-based updates or GoEngine.ENTER_FRAME (-1)  
		 * 			for updates synced to the Flash Player's framerate. 
		 */
		function get pulseInterval() : int;
	}
}
