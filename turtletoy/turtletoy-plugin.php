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

function turtletoy_do_query($query, $timeout = 60 * 60) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'turtletoy';

	$timeout += intval(rand(0, $timeout)); // prevent that all cached items get invalid at the same time

	$data = '';

	$dbkey = $query;

	$cached = get_transient($dbkey);
	if ($cached) {
		$data = $cached;
	} else {
		$url = 'https://turtletoy.net/api/v1/' . $query;
		$data = turtletoy_curl_get_contents($url);
		$json = json_decode($data);

		if (json_last_error() == JSON_ERROR_NONE) {
			// add license to each object
			foreach ($json->objects as $value) {
				// fetch json from https://turtletoy.net/api/v1/turtle/ id /license
				$license = json_decode(turtletoy_curl_get_contents('https://turtletoy.net/api/v1/turtle/' . $value->object_id . '/license'));
				$value->license = $license->url;
			}
			$data = json_encode($json);

			set_transient($dbkey, $data, $timeout);
		}
	}

	return json_decode($data, TRUE);
}

function turtletoy_list($atts) {
	$a = shortcode_atts(array('username'     => FALSE,
	                          'query'        => '',
	                          'columns'      => 2,
	                          'limit'        => 0,
	                          'hideusername' => 0), $atts);

	$username = $a['username'];
	$limit = $a['limit'];

	$list = turtletoy_do_query($a['query']);
	$results = $list["objects"];

	$html = '<ul class="wp-block-gallery columns-' . $a['columns'] . ' is-cropped">';

	$start = microtime(TRUE);

	$count = 0;
	$ldJSON = array();
	foreach ($results as $key => $turtle) {
		$info = $turtle;

		$html .= turtletoy_layout_turtle($info, $a['hideusername']);
		$ldJSON[] = turtletoy_ld_json($info);
		if (microtime(TRUE) - $start > 15) {
			break;
		}

		$count++;
		if ($limit > 0 && $count >= $limit) {
			break;
		}
	}


	$html .= '</ul>';

	$html .= '<script type="application/ld+json">' . json_encode($ldJSON) . '</script>';

	return $html;
}

function turtletoy_ld_json($info) {
	return array("@context"           => "https://schema.org",
	             "@type"              => "ImageObject",
	             "name"               => $info['title'],
	             "caption"            => $info['title'],
	             "creator"            => array("@type"      => "Person",
	                                           "name"       => $info['user_id'],
	                                           "identifier" => $info['user_id'],
	                                           "url"        => "https://turtletoy.net/user/" . $info['user_id']),
	             "description"        => $info['description'],
	             "image"              => "https://turtletoy.net/thumbnail/" . $info['object_id'] . ".jpg?v=" . $info['version'],
	             "thumbnail"          => "https://turtletoy.net/thumbnail/" . $info['object_id'] . ".jpg?v=" . $info['version'],
	             "contentUrl"         => "https://turtletoy.net/thumbnail/" . $info['object_id'] . ".jpg?v=" . $info['version'],
	             "sameAs"             => "https://turtletoy.net/turtle/" . $info['object_id'],
	             "url"                => "https://turtletoy.net/turtle/" . $info['object_id'],
	             "dateCreated"        => $info['date_published'],
	             "identifier"         => $info['object_id'],
	             "material"           => "Turtle graphics",
	             "genre"              => "Generative art",
	             "commentCount"       => $info['comments'],
	             "copyrightHolder"    => array("@type"      => "Person",
	                                           "name"       => $info['user_id'],
	                                           "identifier" => $info['user_id'],
	                                           "url"        => "https://turtletoy.net/user/" . $info['user_id']),
	             "copyrightYear"      => date('Y'),
	             "copyrightNotice"    => "© " . date('Y') . " " . $info['user_id'] . " - Turtletoy",
	             "creditText"         => "© " . date('Y') . " " . $info['user_id'] . " - Turtletoy",
	             "acquireLicensePage" => "https://turtletoy.net/terms",
	             "license"            => $info['license']);
}

function turtletoy_layout_turtle($info, $hideusername) {
	$html = '<li class="blocks-gallery-item"><figure>';
	$html .= '<a href="' . $info['url'] . '" title="' . htmlentities($info['title'] . ' by ' . $info['user_id']) . '">';
	$html .= '<picture>';
	$html .= '<source type="image/webp" srcset="' . $info['webp'] . '" />';
	$html .= '<img src="' . $info['img'] . '" alt="' . str_replace("\n",
	                                                               '&#10;',
	                                                               htmlentities($info['description'])) . '" width="512" height="512" />';
	$html .= '</picture>';
	$html .= '<figcaption>' . $info['title'] . (!$hideusername ? '<br/>by ' . $info['user_id'] : '') . '</figcaption>';
	$html .= '</a>';
	$html .= '</figure></li>';

	return $html;
}

register_activation_hook(__FILE__, 'turtletoy_install');
add_shortcode('turtletoy-list', 'turtletoy_list');
