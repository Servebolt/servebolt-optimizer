<?php
define('SERVEBOLT_WPVULNDB_WP', 'https://wpvulndb.com/api/v2/wordpresses/');
define('SERVEBOLT_WPVULNDB_PLUGIN', 'https://wpvulndb.com/api/v2/plugins/');
define('SERVEBOLT_VULNWP_UPDATE_RATE', 172800);


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

	$wp_vuln = get_transient('servebolt_wpvulndb_wp_'.$version);
	if( $wp_vuln  === false ){
		$get_wp_vuln = sb_getUrlContent(SERVEBOLT_WPVULNDB_WP, $version);
		$wpvul = json_decode($get_wp_vuln, true);
		if($wpvuln !== 0){
			set_transient('servebolt_wpvulndb_wp_'.$version, $wpvul, SERVEBOLT_VULNWP_UPDATE_RATE);
		}
	}

	if(is_array($wp_vuln)):
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
	    else:
		    //
	endif;

    $wpvulnerabilities = $wpvuln;

	if($cli === true){
		// TODO: Add WP CLI Support
	}else{
		return $wpvulnerabilities;
	}
}

function servebolt_vuln_plugins($cli = false){

	$all_plugins = get_plugins();

	$checked_plugins = array ();
    $i = 0;
	foreach ($all_plugins as $key => $plugin){
		if (strpos($key, "/") !== FALSE) {
			list($folder, $file) = explode("/", $key);
		}else{
			$folder = $key;
			$file = '';
		}

		$plugin_vul = get_transient('servebolt_wpvildb_'.$folder);
		if( $plugin_vul  === false ){
			$get_plugin_vul = sb_getUrlContent(SERVEBOLT_WPVULNDB_PLUGIN, $folder);
			$plugin_vul = json_decode($get_plugin_vul, true);
			set_transient('servebolt_wpvildb_'.$folder, $plugin_vul, SERVEBOLT_VULNWP_UPDATE_RATE);
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

				$fixedin = array_column($plugin_db['vulnerabilities'],'fixed_in');
				usort($fixedin, 'version_compare');
				$thisplugin['latest_vuln_version'] = end($fixedin);

				$thisplugin['all_vulnerabilities'] = $plugin_db['vulnerabilities'];

			}else{
				$thisplugin['latest_vuln_version'] = '';
            }
			if(version_compare($thisplugin['active_version'],$thisplugin['latest_vuln_version']) < 0){
				$thisplugin['is_vulnerable'] = intval(true);
				$i++;
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
	$checked_plugins['num_of_vuln'] = $i;
	if($cli === true){
		// TODO: Add WP CLI Support
    }else{
		return $checked_plugins;
    }

}

// create a scheduled event (if it does not exist already)
function servebolt_email_cronstarter() {
	if( !wp_next_scheduled( 'servebolt_sec_emails_hook' ) ) {
		wp_schedule_event( time(), 'weekly', 'servebolt_sec_emails_hook' );
	}
}

function servebolt_set_content_type(){
	return "text/html";
}

// here's the function we'd like to call with our cron job
function servebolt_security_emails() {
	add_filter( 'wp_mail_content_type','servebolt_set_content_type' );

    $wpvuln = servebolt_vuln_wp();
    $pluginvuln = servebolt_vuln_plugins();

    $critial = $wpvuln['is_critical'];
    $wpnum = $wpvuln['num_of_vulnerabilities'];
    $vulnerable = $wpvuln['is_vulnerable'];

    $pluginvulncount = $pluginvuln['num_of_vuln'];

	// components for our email
	$recepients = get_bloginfo('admin_email');
	$subject = '!! Servebolt Security Notice: '.get_bloginfo('name');
	$headers = array('Content-Type: text/html; charset=UTF-8');
	$emailcontent = __('<html><body>You have %s vulnerabilities in your WordPress, and %s vulnerabilities in your plugins. You should update as soon as possible.
                    </br>
                    </br>
                    This email is a service from <a href="https://servebolt.com?utm_source=wpplugin&utm_medium=email&utm_campaign=security-email">Servebolt.com - Amazingly fast hosting</a></body></html>', 'servebolt-wp');
	$message = sprintf($emailcontent,$wpnum, $pluginvulncount);

	// let's send it
    if($critial === 1 || $pluginvulncount > 0){
	    wp_mail($recepients, $subject, $message, $headers);
	    remove_filter( 'wp_mail_content_type','servebolt_set_content_type' );
    }else{
        die();
    }
}

// hook that function onto our scheduled event:
add_action ('servebolt_sec_emails_hook', 'servebolt_security_emails');


function servebolt_security_notice() {
	$wpvuln = servebolt_vuln_wp();
	$pluginvuln = servebolt_vuln_plugins();

	$critial = $wpvuln['is_critical'];
	$wpnum = $wpvuln['num_of_vulnerabilities'];
	$vulnerable = $wpvuln['is_vulnerable'];

	$pluginvulncount = $pluginvuln['num_of_vuln'];

	if($critial === 1 || $pluginvulncount > 0) {

		$class   = 'notice notice-error';
		$message = sprintf( __( 'You have %s WordPress vulnerabilities and %s plugin vulnerabilities. Update WordPress to stay safe!', 'servebolt-wp' ), $wpnum, $pluginvulncount );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
}
add_action( 'admin_notices', 'servebolt_security_notice' );
