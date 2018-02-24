<?php
$path = '/Users/thomasaudunhus/Sites/servebolt.com/';
include($path."wp-load.php");

require_once('../optimize-db/transients-cleaner.php');

echo servebolt_transient_delete();