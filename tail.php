<?php

function get_error_log() {
	$logdir = str_replace("/public", "/logs", $_SERVER["DOCUMENT_ROOT"]);
	$log = tail($logdir. "/ErrorLog", 50);

	echo '<div class="wrap">';
	     echo '<h2>'. __('Errorlog', 'servebolt-wp') .'</h2>';
	if ($log){
		$lines = explode(PHP_EOL, $log);
		$lines = array_reverse($lines);
		$lines = array_filter($lines);

		$output = '<p>'. __('This table lists the 50 last errors from todays logs/Errorlog', 'servebolt-wp') .':</p>';
		$output .= '<table class="wp-list-table widefat striped posts">
                <thead>
                    <tr>
                        <th>'. __('Timestamp', 'servebolt-wp') .'</th>
                        <th>'. __('IP', 'servebolt-wp') .'</th>
                        <th>'. __('Error', 'servebolt-wp') .'</th>
                    </tr>
                </thead>
					<tbody>';
		$output .= '';

		foreach ($lines as $line) {
			preg_match("/^\[(.*)\] (\[.*\] )(\[.*\] )(\[.*Client:([\d\.\:]+):.*\] )(.*)$/Ui", $line, $matches);
			$unixtime = strtotime($matches[1]);
			$date = date('H:i:s', $unixtime);
			$ip = $matches[5];
			$error = $matches[6];
			$output .= '<tr><td>'. $date .'</td>'; // time
			$output .= '<td>'. $ip .'</td>'; // ip
			$output .= '<td>'. $error .'</td>'; // error
			$output .= '</tr>';
		}
		$output .= '</tbody></table>';
		echo $output;
	}
	else {
		echo '<p>'. __('Your error log is empty', 'servebolt-wp') .'</p>';
	}
	echo '</div>';
}

function tail($filename, $lines = 50, $buffer = 4096){
	if(!is_file($filename)){
		return false;
	}
	if(!$f = fopen($filename, "rb")){
		return false;
	}

	fseek($f, -1, SEEK_END);
	if(fread($f, 1) != "\n") $lines -= 1;
	$output = '';
	$chunk = '';
	while(ftell($f) > 0 && $lines >= 0)
	{
		$seek = min(ftell($f), $buffer);
		fseek($f, -$seek, SEEK_CUR);
		$output = ($chunk = fread($f, $seek)).$output;
		fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
		$lines -= substr_count($chunk, "\n");
	}
	while($lines++ < 0)
	{
		$output = substr($output, strpos($output, "\n") + 1);
	}
	fclose($f);
	return $output;
}