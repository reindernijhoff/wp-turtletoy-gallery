<?php

/*
Plugin Name: Turtletoy Gallery
Plugin URI: https://github.com/reindernijhoff/wp-turtletoy-gallery
Description: Creates and update a gallery with Turtletoy turtles based on a query.
Version: 0.1
Author: Reinder Nijhoff
Author URI: https://reindernijhoff.net/
*/

$turtletoy_db_version = '1.0';

function turtletoy_install() {
	global $wpdb;
	global $turtletoy_db_version;

	$table_name = $wpdb->prefix . 'turtletoy';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id varchar(255) NOT NULL,
  		expires datetime NOT NULL,
  		data mediumtext NOT NULL,
		PRIMARY KEY (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'turtletoy_db_version', $turtletoy_db_version );
}

function turtletoy_curl_get_contents($url) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	$data = curl_exec($ch);
	curl_close($ch);

	return $data;
}

function turtletoy_do_query($query, $timeout = 60*60) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'turtletoy';

	$timeout += intval(rand(0, $timeout)); // prevent that all cached items get invalid at the same time

	$json = '';

	$dbkey = $query;

	$cached = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %s AND expires > NOW()", $dbkey) );
	if ($cached) {
		$json = $cached->data;
	} else {
		$url = 'https://turtletoy.net/api/v1/' . $query;
		$json = turtletoy_curl_get_contents($url);

		$wpdb->query( $wpdb->prepare( "REPLACE INTO $table_name( id, data, expires ) VALUES ( %s, %s, NOW() + INTERVAL %d SECOND )", $dbkey, $json, $timeout ) );
	}

	$obj = json_decode($json, true);
	return $obj;
}

function turtletoy_list($atts) {
	$a = shortcode_atts( array(
		'username' => false,
		'query' => '',
		'columns' => 2,
		'hideusername' => 0
	), $atts );

	$username = $a['username'];

	$list = turtletoy_do_query($a['query']);
	$results = $list["turtles"];

	$html = '<ul class="wp-block-gallery columns-' . $a['columns'] . ' is-cropped">';

	$start = microtime(true);

	foreach ($results as $key => $turtle) {
		$info = $turtle;

		$html .= turtletoy_layout_turtle($info, $a['hideusername']);

		if (microtime(true) - $start > 15) {
			break;
		}
	}


	$html .= '</ul>';	 
    return $html;
}

function turtletoy_layout_turtle($info, $hideusername) {
	$html = '<li class="blocks-gallery-item"><figure>';
	$html .= '<a href="https://turtletoy.net/turtle/' . $info['turtle_id'] . '" title="' . htmlentities($info['title'] . ' by ' . $info['user_id']) . "&#10;&#10;" .  str_replace("\n", '&#10;', htmlentities($info['description'])) .'">';
	$html .= '<img src="' . $info['img'] . '" style="width:100%" alt="' . htmlentities($info['title'] . ' by ' . $info['user_id']) . '">';
	$html .= '<figcaption>' . $info['title'] . (!$hideusername?'<br/>by ' . $info['user_id']:'') . '</figcaption>';
	$html .= '</a>';
	$html .= '</figure></li>';

	return $html;
}

register_activation_hook( __FILE__, 'turtletoy_install' );
add_shortcode('turtletoy-list', 'turtletoy_list');