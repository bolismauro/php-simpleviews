<?php

require_once('sviews.class.php');
$s = new SViews();
echo $s->render('sample.thtml',array('var1'=>'value 1','var2'=>'value 2'));

?>