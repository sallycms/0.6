<?php

class Util_String_Test extends Snap_UnitTestCase
{
	public function setUp() {}
	public function tearDown() {}
	
	protected function isInteger($expected, $value)
	{
		return $this->assertEqual(sly_Util_String::isInteger($value), $expected);
	}
	
	protected function startsWith($expected, $haystack, $needle)
	{
		return $this->assertEqual(sly_Util_String::startsWith($haystack, $needle), $expected);
	}
	
	protected function endsWith($expected, $haystack, $needle)
	{
		return $this->assertEqual(sly_Util_String::endsWith($haystack, $needle), $expected);
	}
	
	/* Sieht lächerlich aus, aber SnapTest wertet Assertions nur als Rückgabewert selbst aufgerufener Methoden aus. */
	
	public function testIsInteger1()  { return $this->isInteger(true, 5);           }
	public function testIsInteger2()  { return $this->isInteger(true, -5);          }
	public function testIsInteger3()  { return $this->isInteger(true, '1');         }
	public function testIsInteger4()  { return $this->isInteger(true, '901');       }
	public function testIsInteger5()  { return $this->isInteger(true, '-901');      }
	
	public function testIsInteger6()  { return $this->isInteger(false, 5.1);        }
	public function testIsInteger7()  { return $this->isInteger(false, true);       }
	public function testIsInteger8()  { return $this->isInteger(false, false);      }
	public function testIsInteger9()  { return $this->isInteger(false, null);       }
	public function testIsInteger10() { return $this->isInteger(false, '01');       }
	public function testIsInteger11() { return $this->isInteger(false, '1.5');      }
	public function testIsInteger12() { return $this->isInteger(false, '-1.5');     }
	public function testIsInteger13() { return $this->isInteger(false, '- 7');      }
	public function testIsInteger14() { return $this->isInteger(false, 'hello');    }
	public function testIsInteger15() { return $this->isInteger(false, '123hello'); }
	public function testIsInteger16() { return $this->isInteger(false, ' ');        }
	public function testIsInteger17() { return $this->isInteger(false, "\t");       }
	public function testIsInteger18() { return $this->isInteger(false, '');         }
	
	public function testStartsWith1()  { return $this->startsWith(true, '', '');             }
	public function testStartsWith2()  { return $this->startsWith(true, 'hallo', '');        }
	public function testStartsWith3()  { return $this->startsWith(true, 'hallo', 'hal');     }
	public function testStartsWith4()  { return $this->startsWith(true, '  hallo', '  hal'); }
	public function testStartsWith5()  { return $this->startsWith(true, '1123', '1');        }
	public function testStartsWith6()  { return $this->startsWith(true, 12, 1);              }
	
	public function testStartsWith7()  { return $this->startsWith(false, '', 'hallo');         }
	public function testStartsWith8()  { return $this->startsWith(false, 'hallo', 'hallo123'); }
	public function testStartsWith9()  { return $this->startsWith(false, 'hallo', '123');      }
	public function testStartsWith10() { return $this->startsWith(false, 'hallo', 'xyz');      }
	public function testStartsWith11() { return $this->startsWith(false, 'hallo', ' ');        }
	public function testStartsWith12() { return $this->startsWith(false, '  hallo', 0);        }
	
	public function testEndsWith1()  { return $this->endsWith(true, '', '');              }
	public function testEndsWith2()  { return $this->endsWith(true, 'hallo', '');         }
	public function testEndsWith3()  { return $this->endsWith(true, 'hallo', 'llo');      }
	public function testEndsWith4()  { return $this->endsWith(true, '  hallo', '  allo'); }
	public function testEndsWith5()  { return $this->endsWith(true, '1123', '23');        }
	public function testEndsWith6()  { return $this->endsWith(true, 12, 2);               }
	
	public function testEndsWith7()  { return $this->endsWith(false, '', 'hallo');         }
	public function testEndsWith8()  { return $this->endsWith(false, 'hallo', 'hallo123'); }
	public function testEndsWith9()  { return $this->endsWith(false, 'hallo', '123');      }
	public function testEndsWith10() { return $this->endsWith(false, 'hallo', 'xyz');      }
	public function testEndsWith11() { return $this->endsWith(false, 'hallo', ' ');        }
	public function testEndsWith12() { return $this->endsWith(false, '  hallo', 0);        }
}
