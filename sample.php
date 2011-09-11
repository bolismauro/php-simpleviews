<?php
/* Copyright (c) 2011 Simone Lusenti
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once('sviews.class.php');
$s = new SViews();
echo $s->render('sample.thtml',
	array('var1' => 'value 1',
			'var2' => 'value 2',
			'myarray' => array('a' => 1,
								'b' => 2,
								'c' => 3),
			'myclass1' => new MyClass1(),
			'myclass2' => new MyClass2(),
			'myclass3' => new MyClass3()
			));

class MyClass1 {
	private $baz = 'Oh my god!';
	public function getBaz() {
		return $this->baz;
	}
}

class MyClass2 {
	private $baz = 'Foo bar';
	
	public function baaz() {
		return $this->baz;
	}
}

class MyClass3 {
	private $baaaz = 'Y NO?';
	public function get($name) {
		if ($name=='baaaz') {
			return $this->baaaz;
		} else {
			return '(MyClass3: unknown value)';
		}
	}
}

?>