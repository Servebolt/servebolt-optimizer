<?php
$path = '/Users/thomasaudunhus/Sites/servebolt.com/';
include($path."wp-load.php");



$i = 1;

while($i <= 150) {
	$random = rand(0,9999999);
	$transient = 'tr_'.$random;
	$time = time() + 1;
	$transient_long = 'tr_long_'.$random;
	set_site_transient($transient, $random, 1);
	set_transient($transient, $random, 1);
	set_site_transient($transient_long, $random + 1, 60);
	set_transient($transient_long, $random + 1, 60);
	echo $i.' expires '.$time.'<br>';
	$i++;
}

