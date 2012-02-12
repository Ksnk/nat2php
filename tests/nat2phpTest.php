<?php

//require_once 'PHPUnit/Framework/TestCase.php';
require_once(dirname(__FILE__).'/../nat2php.class.php');

function pps(&$x,$default=''){if(empty($x))return $default; else return $x;}

class nat2php_test extends PHPUnit_Framework_TestCase {
	
	function &createParser(){
        $parser = new nat_parser();
        return $parser ->newOp2('- +',3)
				->newOp2('* /',5)
				->newOp2('|| && >> <<',4)
				->newOp2('^',7,'pow(%s,%s)')
				->newOp1('-','-(%s)')
				->newOp1('tan abs sin cos')
				->newOpr('pi','pi()')
				->newOpr('x','$x')
				->newOpr('time','$self->c_getTime()')
			;	
	}
	
	function c_getTime(){
		return time();
	}
	
	function &createParser2(){
        $parser = new nat_parser();
        return $parser 
        	->newOp2('- +',3)
			->newOp2('- +',3)
			->newOp2('* /',5)
			->newOp2('|| && >> <<',4)
			->newOp2('^',7,'pow(%s,%s)')
			->newOp1('--','--%s')
			->newOp1('- +')
			->newOp1('++','++%s')
			->newOpS('++ --')
			->newFunc('tan abs sin cos')
			->newOpr('pi','pi()')
			->newOpr('x','$x')
			->newOpr('time','$self->c_getTime()')
        ;	
	}
	
	function run_script($s,$x=1){
        $parser = $this->createParser();
		
		$parser->makelex($s);
		$fnc=	$parser->mathcalc();
		if(!empty($_GET['debug']))
		  echo $fnc.'<hr>';
		$fnc=create_function('&$self,$x','return '.$fnc.';');
		
		return $fnc($this,$x);
	}
	function run_script2($s,$x=1){
        $parser = $this->createParser2();
		
		$parser->makelex($s);
		$fnc=$parser->mathcalc()	;
		if(!empty($_GET['debug']))
      echo $fnc.'<hr>';
    $fnc=create_function('&$self,$x','return '.$fnc.';');
		return $fnc($this,$x);
	}
	
    function testCreate_parser() {
        $parser = new nat_parser();
        $this->assertFalse(empty($parser));
	}
    function testCreate_parser_initCalc() {
		$parser=$this->createParser();
        $this->assertFalse(empty($parser));
	}
    function testCompilation1() {
        $s='1';
		$this->assertEquals($this->run_script($s),1);
	}
    function testCompilation2() {
        $s='1+1';
		$this->assertEquals($this->run_script($s),1+1);
	}
    function testCompilation3() {
        $s='1+1*2';
		$this->assertEquals($this->run_script($s),1+1*2);
	}
    function testCompilation4() {
        $s='1*1+2';
		$this->assertEquals($this->run_script($s),1*1+2);
	}
    function testCompilation5() {
        $s='1*1+2^2';
		$this->assertEquals($this->run_script($s),1*1+pow(2,2));
	}
	function testCompilation6() {
        $s='pi/2';
		$this->assertEquals($this->run_script($s),pi()/2);
	}
	function testCompilation7() {
        $s='sin(pi/2)';
		$this->assertEquals($this->run_script($s),sin(pi()/2));
	}

    /**
     * @cover nothing
     * @expectedException CompilationException
     */
	function testCompilation8() {
        $s='sinx(pi/2)';
		$this->run_script($s);
	}
    /**
     * @cover nothing
     * @expectedException CompilationException
     */
	function testCompilation9() {
        $s='sin(pii/x)';
		$this->run_script($s);
	}
    /**
     * @cover nothing
     * @expectedException CompilationException
     */
	function testCompilation10() {
        $s='sin(pi/x';
		$this->run_script($s);
	}
	function testCompilation11() {
        $s='x+1';
		$this->assertEquals($this->run_script($s),2);
	}
	function testCompilation12() {
        $s='-x+1';
		$this->assertEquals($this->run_script($s),0);
	}
	function testCompilation13() {
        $s='1+-x';
		$this->assertEquals($this->run_script($s),0);
	}
	function testCompilation14() {
        $s='234+234*2323*(3+4)+2^(3+4)';
		$this->assertEquals($this->run_script($s),234+234*2323*(3+4)+pow(2,3+4));
	}
	function testCompilation15() {
        $s='tan(x)+x+4*x^3+2*x+300';
		$this->assertEquals($this->run_script($s,1.34),tan(1.34)+1.34+4*pow(1.34,3)+2*1.34+300);
	}
	function testCompilation16() {
        $s='1+-----1';
		$this->assertEquals($this->run_script($s),1+-(-(-(-(-1)))));
	}
	function testCompilation17() {
        $s='1+ - - - - - -1';
		$this->assertEquals($this->run_script($s),1+ - - - - - -1);
	}
	function testCompilation18() {
        $s='((((((((((((((((((((1))))))))))))))))))))+1';
		$this->assertEquals($this->run_script($s),2);
	}
	function testCompilation19() {
        $s='x++ + ++x';
        $x=3;
		$this->assertEquals($this->run_script2($s,3),$x++ + ++$x);
	}
	function testCompilation20() {
        $s='++x*x++';
        $x=3;
		$this->assertEquals($this->run_script2($s,3),++$x * $x++);
	}
  function testCompilation21() {
    $s='1+2+3';
    $x=3;
    $this->assertEquals($this->run_script2($s,3),1+2+3);
  }
}

?>