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

	$cached = get_transient( $dbkey );
	if ($cached) {
		$json = $cached;
	} else {
		$url = 'https://turtletoy.net/api/v1/' . $query;
		$json = turtletoy_curl_get_contents($url);

		json_decode($json);
		if (json_last_error() != JSON_ERROR_NONE) {
    		set_transient( $dbkey, $json, $timeout );
		}
	}

	$obj = json_decode($json, true);
	return $obj;
}

function turtletoy_list($atts) {
	$a = shortcode_atts( array(
		'username' => false,
		'query' => '',
		'columns' => 2,
		'limit' => 0,
		'hideusername' => 0
	), $atts );

	$username = $a['username'];
	$limit = $a['limit'];

	$list = turtletoy_do_query($a['query']);
	$results = $list["objects"];

	$html = '<ul class="wp-block-gallery columns-' . $a['columns'] . ' is-cropped">';

	$start = microtime(true);

    $count = 0;
	foreach ($results as $key => $turtle) {
		$info = $turtle;

		$html .= turtletoy_layout_turtle($info, $a['hideusername']);

		if (microtime(true) - $start > 15) {
			break;
		}

		$count ++;
		if ($limit > 0 && $count >= $limit) {
		    break;
		}
	}


	$html .= '</ul>';	 
    return $html;
}

function turtletoy_layout_turtle($info, $hideusername) {
	$html = '<li class="blocks-gallery-item"><figure>';
	$html .= '<a href="' . $info['url'] . '" title="' . htmlentities($info['title'] . ' by ' . $info['user_id']) .'">';
	$html .= '<picture>';
	$html .= '<source type="image/webp" srcset="' . $info['webp'] . '" />';
	$html .= '<img src="' . $info['img'] . '" alt="' . str_replace("\n", '&#10;', htmlentities($info['description'])) . '" width="512" height="512" />';
	$html .= '</picture>';
	$html .= '<figcaption>' . $info['title'] . (!$hideusername?'<br/>by ' . $info['user_id']:'') . '</figcaption>';
	$html .= '</a>';
	$html .= '</figure></li>';

	return $html;
}

register_activation_hook( __FILE__, 'turtletoy_install' );
add_shortcode('turtletoy-list', 'turtletoy_list');
