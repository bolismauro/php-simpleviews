<?php

class SViews {
	private $template_dir="";
	private $ldelim="{{";
	private $rdelim="}}";
	
	public function __construct($template_dir="") {
		if (!empty($template_dir)) {
			$this->template_dir=$template_dir;
		} else {
			$this->template_dir=dirname(__FILE__);
		}
	}
	
	public function render($template_name, array $context=array()) {
		$tpl = file_get_contents($this->template_dir.DIRECTORY_SEPARATOR.$template_name);
		echo $tpl;
	}
	
}



?>
