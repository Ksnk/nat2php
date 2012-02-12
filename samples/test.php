<?php
//ob_start();
require_once(dirname(__FILE__).'/../nat2php.class.php');
function pps(&$x,$default=''){if(empty($x))return $default; else return $x;}
/**
 * 
 * базовый класс - простой калькулятор
 * @author RMO
 *
 */
class calc {
	
	private $calc,$errors=array();
	public $result;
	/**
	 * функция скрипта - вызывается по конструкции time
	 */
	function c_getTime(){
		return time();
	}
	
	function error($s=null){
		if(is_null($s))
			return implode('\r\n',$this->errors);
		else	
			$this->errors[]=$s;
        return '';
	}

	function get_script($script,$execute=true){
		if(empty($this->calc)){
			$this->calc=new parser();
			$this->calc
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
				->newOpr('x','$x','TYPE_ID')
				->newOpr('time','$self->c_getTime()')
			;	
		}
		//compile it;
		$this->result = '';
		try{
			$this->calc->makelex($script);
			$this->result=$this->calc->mathcalc();
		} catch(Exception $e){
			$this->error($e->getMessage());
			//if($debug){
				echo '<pre>';print_r($parser);echo'</pre>';
			//}
			return null;
		}
		//execute it
		if (isset($_GET['debug']))
			echo'<pre>'.htmlspecialchars($this->result).'</pre>';
		if($execute) {
			return create_function('&$self,$x','return '.$this->result.';');
		} return null;
	}
}
	$calc=new calc();
	$fnc=$calc->get_script('x++ + ++x',true);
	
	
	if(!is_null($fnc))
		echo $fnc($calc,3);
	
