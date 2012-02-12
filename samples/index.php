<?php
//ob_start();
require_once('../nat2php.class.php');
function pps(&$x,$default=''){if(empty($x))return $default; else return $x;}
session_start();
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
				->newOp1('tan abs sin cos')
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
			if($debug){
				echo '<pre>';print_r($parser);echo'</pre>';
			}
			return null;
		}
		//execute it
		if($execute) {
			return create_function('&$self,$x','return '.$this->result.';');
		} return null;
	}
}


if($_SERVER['REQUEST_METHOD']=='POST'){
	function strips(&$el) {
	  if (is_array($el))
	    foreach($el as $k=>$v)
	      strips($el[$k]);
	  else $el = stripslashes($el);
	}
	if (get_magic_quotes_gpc()) {
		foreach(array('_GET','_POST','_COOKIE','_REQUEST',"_SERVER['PHP_AUTH_USER']","_SERVER['PHP_AUTH_PW']") as $v)
			if(isset($$v))strips($$v);
	}
	$_SESSION['input']=pps($_POST['input']);
//	$_SESSION['post']=print_r($_POST,true);
	$calc= new calc();
	$_SESSION['run']=!isset($_POST['run']);
	ob_start();
	$fnc=$calc->get_script($_SESSION['input'],$_SESSION['run']);
	if(!is_null($fnc))
		$_SESSION['output']= $fnc($calc,1);
	else	
		$_SESSION['output']='';
	$_SESSION['dddebug']=ob_get_contents();
	ob_end_clean();
		
	$_SESSION['debug']=$calc->result;
	$_SESSION['errors']=htmlspecialchars($calc->error());
	if(!empty($_SESSION['errors']) || isset($_POST['debug'])){
		$_SESSION['ddebug']=print_r($calc,true);
		$_SESSION['errors'].='<br>'.htmlspecialchars($_SESSION['input'])
			.'<br>'
			.'123456789<font color="red">0</font>'
			.'123456789<font color="red">0</font>'
			.'123456789<font color="red">0</font>'
			.'123456789<font color="red">0</font>'
			.'123456789<font color="red">0</font>'
			.'123456789<font color="red">0</font>'
		;
	} else {
		$_SESSION['ddebug']='';//print_r($_POST,true);
	}
//	echo 'http://'.$_SERVER["SERVER_NAME"].$_SERVER['REQUEST_URI'];
	header('location:http://'.$_SERVER["SERVER_NAME"].$_SERVER['REQUEST_URI']);
	exit;	
}

if($_GET['act']=='graph'){
	// выдаем картинку
	//if(!empty($_SESSION['graph'])){
	header("Content-type: image/png");
	$im = @imagecreate(301, 301)
	   or die("Cannot Initialize new GD image stream");
	$background_color = imagecolorallocate($im, 255,255,255);// just a magic, no need to draw!
	$text_color = imagecolorallocate($im, 233, 14, 91);
	// 
	// рисуем сетку координат
	//
	$gray_color = imagecolorallocate($im, 0xc0, 0xc0, 0xc0);
	for($i=0;$i<301;$i+=10){
		imageline($im,$i,0,$i,301,$gray_color);
		imageline($im,0,$i,301,$i,$gray_color);
	}
	
	//
	// функция
	//
	$calc=new calc();
	$fnc=create_function('&$self,$x','return '.$_SESSION['debug'].';');
	
	$maxx=-100000;$maxy=-100000;
	$minx=100000;$miny=100000;
	$width=300;	$height=300;
	// вычисляем функцию на нужном диапазоне
	$points=array();
	for($i=-150;$i<=150;$i++){
		$y=$fnc($calc,$i);
		array_push($points,$i,$y);
		$maxy=max($maxy,$y);
		$maxx=max($maxx,$i);
		$miny=min($miny,$y);
		$minx=min($minx,$i);
	}
	
	$zerox=min(max(-130,150+$minx),130);
	$zeroy=min(max(-130,150+$miny),130);
	function x($x){
		global $zerox,$width;
		return $x = $width / 2 -$zerox+$x;
	}
	function y($y){
		global $zeroy,$height;
		return $height / 2 - $y + $zeroy;
	}
	//
	//
	//
	$green_color = imagecolorallocate($im, 0x0, 0xff, 0x0);
	imageline($im,x($zerox),0,x($zerox),301,$green_color);
	imageline($im,0,x($zeroy),301,x($zeroy),$green_color);
	
	// 
	// рисуем оси координат
	//
	$red_color = imagecolorallocate($im, 0xff, 0x0, 0x0);
	$x=x(array_shift($points));$y=y(array_shift($points));
	do {
		$xx=x(array_shift($points));$yy=y(array_shift($points));
		imageline($im,$x,$y,$xx,$yy,$red_color);
		$x=$xx;$y=$yy;
	} while(count($points)>0);
	
/*	imageline($im,$i,0,$i,301,$gray_color);
	imagestring($im, 1, 5, 5,  "A Simple Text String", $text_color);*/
	imagepng($im);
	imagedestroy($im);
	exit;
	//}
}

//ob_end_flush();

?><!doctype html><head><title>калькулятор</title>
<style>
label {
	display:block; width:400px; clear:both;
}
label textarea{
	float:right;
	width:300px;
}
label input{
	float:right;
	width:300px;
}
</style>
</head>
<body>
<form action="" method="post"><pre style="overflow: auto; width: 100%;"><?=$_SESSION['errors']?></pre>
<table>
	<tr>
		<td><label><textarea name="input"><?=htmlspecialchars($_SESSION['input'])?>
</textarea>Calculator:</label> <label><textarea><?=htmlspecialchars($_SESSION['debug'])?>
</textarea>Debug:</label> <label> <textarea><?=htmlspecialchars($_SESSION['output'])?></textarea>
		Output:<input type="checkbox" name="run" value="1"
		<?=$_SESSION['run']?'':"checked"?>>
		<input type="checkbox" name="debug" value="1">
		</label> <label> <input
			type="submit">отправить:</label></td>
		<td>
		<div style="width: 300px; float: right;"><img src="?act=graph"></div>
		</td>
	</tr>
</table>
</form>
<pre style="overflow: auto; width: 100%;"><?=htmlspecialchars($_SESSION['dddebug'])?></pre>
<pre style="overflow: auto; width: 100%;"><?=htmlspecialchars($_SESSION['ddebug'])?></pre>

</body>
