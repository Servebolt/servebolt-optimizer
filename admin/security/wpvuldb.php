<?php


function sb_getUrlContent($url, $param){

	$geturl = $url.$param;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $geturl);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	$data = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return ($httpcode>=200 && $httpcode<300) ? $data : false;
}

function servebolt_vuln_wp($cli = false){
	global $wp_version;
	$version = str_replace('.', '', $wp_version);
	//$version = '491';

	$wp_vuln = get_transient('servebolt_wpvulndb_wp_'.$version);
	if( $wp_vuln  === false ){
		$get_wp_vuln = sb_getUrlContent('https://wpvulndb.com/api/v2/wordpresses/', $version);
		$wpvul = json_decode($get_wp_vuln, true);
		set_transient('servebolt_wpvulndb_wp_'.$version, $wpvul, 172800);
	}

	foreach ($wp_vuln as $key => $vuln){
	    $wpvuln = array ();
	    $wpvuln['current_version'] = $key;

		if(array_key_exists(0,$vuln['vulnerabilities'])){
			$wpvuln['is_vulnerable'] = intval(true);
		}else{
			$wpvuln['is_vulnerable'] = intval(false);
		}

		if(count($vuln['vulnerabilities']) > 3){
			$wpvuln['is_critical'] = intval(true);
		}else{
			$wpvuln['is_critical'] = intval(false);
		}
		$wpvuln['num_of_vulnerabilities'] = count($vuln['vulnerabilities']);
		$wpvuln['vulnerabilities'] = $vuln['vulnerabilities'];
    }

    $wpvulnerabilities = $wpvuln;

	if($cli === true){

	}else{
		return $wpvulnerabilities;
	}
}

function servebolt_vuln_plugins($cli = false){

	$all_plugins = get_plugins();

	$checked_plugins = array ();

	foreach ($all_plugins as $key => $plugin){
		if (strpos($key, "/") !== FALSE) {
			list($folder, $file) = explode("/", $key);
		}

		$plugin_vul = get_transient('servebolt_wpvildb_'.$folder);
		if( $plugin_vul  === false ){
			$get_plugin_vul = getUrlContent('https://wpvulndb.com/api/v2/plugins/', $folder);
			$plugin_vul = json_decode($get_plugin_vul, true);
			set_transient('servebolt_wpvildb_'.$folder, $plugin_vul, 172800);
		}

		if($plugin_vul){
			$plugin_db = $plugin_vul[$folder];
		}else{
			$plugin_db = '';
		}

		$thisplugin = array ();
		$thisplugin['name'] = $plugin['Name'];
		$thisplugin['is_active'] = is_plugin_active($key);
		$thisplugin['active_version'] = $plugin['Version'];

		if(!empty($plugin_db)){
			$thisplugin['in_wpculndb'] = intval(true);
			if(!empty($plugin_db['latest_version'])){
				$thisplugin['latest_version'] = $plugin_db['latest_version'];
			}else{
				$thisplugin['latest_version'] = false;
			}

			if(array_key_exists(0,$plugin_db['vulnerabilities'])){

				$latestVuln = array();
				$fixedin = array_column($plugin_db['vulnerabilities'],'fixed_in');
				usort($fixedin, 'version_compare');
				$thisplugin['latest_vuln_version'] = end($fixedin);

				$thisplugin['all_vulnerabilities'] = $plugin_db['vulnerabilities'];

			}else{
				$thisplugin['latest_vuln_version'] = '';
            }
			if(version_compare($thisplugin['active_version'],$thisplugin['latest_vuln_version']) < 0){
				$thisplugin['is_vulnerable'] = intval(true);
			}else{
				$thisplugin['is_vulnerable'] = intval(false);
			}


			if(version_compare($thisplugin['active_version'],$thisplugin['latest_version']) < 0){
				$thisplugin['update_available'] = intval(true);
			}else{
				$thisplugin['update_available'] = intval(true);
			}
		}else{
			$thisplugin['in_wpculndb'] = intval(false);
		}

		$checked_plugins[$folder] = $thisplugin;
	}
	if($cli === true){

    }else{
		return $checked_plugins;
    }

}
