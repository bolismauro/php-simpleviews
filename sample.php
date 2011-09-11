<?php

require_once('sviews.class.php');
$s = new SViews();
$s->render('templates/sample.html',array('var1'=>'value 1','var2'=>'value 2'));

?>