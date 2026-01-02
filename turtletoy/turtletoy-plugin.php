<?php

/*
Plugin Name: Turtletoy Gallery
Plugin URI: https://github.com/reindernijhoff/wp-turtletoy-gallery
Description: A WordPress plugin to display Turtletoy galleries.
Version: 1.0.1
Author: Reinder Nijhoff
Author URI: https://reindernijhoff.net/
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function turtletoy_install()
{
}

function turtletoy_fetch($url)
{
    $response = wp_remote_get($url);
    $body = wp_remote_retrieve_body($response);

    return $body;
}

function turtletoy_do_query($query, $timeout = 60 * 60)
{
    $data = '';

    $dbkey = 'turtletoy_' . $query;

    $cached = get_transient($dbkey);
    if ($cached) {
        $data = $cached;
    } else {
        $url = 'https://turtletoy.net/api/v1/' . $query;
        $data = turtletoy_fetch($url);
        $json = json_decode($data);

        if (json_last_error() == JSON_ERROR_NONE) {
            $data = json_encode($json);

            set_transient($dbkey, $data, $timeout + wp_rand(0, $timeout));
        }
    }

    return json_decode($data, TRUE);
}

function turtletoy_list($atts)
{
    $a = shortcode_atts(array('username' => FALSE,
        'query' => '',
        'columns' => 2,
        'limit' => 0,
        'hideusername' => 0), $atts);

    $username = $a['username'];
    $limit = $a['limit'];

    $list = turtletoy_do_query($a['query']);
    $results = $list["objects"];

    $html = '<ul class="wp-block-gallery columns-' . esc_attr($a['columns']) . ' is-cropped">';

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

    $html .= '<script type="application/ld+json">' . wp_json_encode($ldJSON) . '</script>';

    return $html;
}

function turtletoy_ld_json($info)
{
    return array("@context" => "https://schema.org",
        "@type" => "ImageObject",
        "name" => $info['title'],
        "caption" => $info['title'],
        "creator" => array("@type" => "Person",
            "name" => $info['user_id'],
            "identifier" => $info['user_id'],
            "url" => "https://turtletoy.net/user/" . $info['user_id']),
        "description" => $info['description'],
        "image" => "https://turtletoy.net/thumbnail/" . $info['object_id'] . ".jpg",
        "thumbnail" => "https://turtletoy.net/thumbnail/" . $info['object_id'] . ".jpg",
        "contentUrl" => "https://turtletoy.net/thumbnail/" . $info['object_id'] . ".jpg",
        "sameAs" => "https://turtletoy.net/turtle/" . $info['object_id'],
        "url" => "https://turtletoy.net/turtle/" . $info['object_id'],
        "dateCreated" => $info['date_published'],
        "identifier" => $info['object_id'],
        "material" => "Turtle graphics",
        "genre" => "Generative art",
        "commentCount" => $info['comments'],
        "copyrightHolder" => array("@type" => "Person",
            "name" => $info['user_id'],
            "identifier" => $info['user_id'],
            "url" => "https://turtletoy.net/user/" . $info['user_id']),
        "copyrightYear" => gmdate('Y'),
        "copyrightNotice" => "© " . gmdate('Y') . " " . $info['user_id'] . " - Turtletoy",
        "creditText" => "© " . gmdate('Y') . " " . $info['user_id'] . " - Turtletoy",
        "acquireLicensePage" => "https://turtletoy.net/terms",
        "license" => $info['license']);
}

// phpcs:disable
//
// We directly link images from the turtletoy.net domain, as users can update the preview images without notice.
function turtletoy_layout_turtle($info, $hideusername)
{
    $html = '<li class="blocks-gallery-item"><figure>';
    $html .= '<a href="' . esc_url($info['url']) . '" title="' . esc_attr($info['title'] . ' by ' . $info['user_id']) . '">';
    $html .= '<picture>';
    $html .= '<source type="image/webp" srcset="' . esc_url($info['webp']) . '" />';
    $html .= '<img src="' . esc_url($info['img']) . '" alt="' . esc_attr(str_replace("\n", '&#10;', $info['description'])) . '" width="512" height="512" />';
    $html .= '</picture>';
    $html .= '<figcaption>' . esc_html($info['title']) . (!$hideusername ? '<br/>by ' . esc_html($info['user_id']) : '') . '</figcaption>';
    $html .= '</a>';
    $html .= '</figure></li>';

    return $html;
}

// phpcs:enable

register_activation_hook(__FILE__, 'turtletoy_install');
add_shortcode('turtletoy-list', 'turtletoy_list');
