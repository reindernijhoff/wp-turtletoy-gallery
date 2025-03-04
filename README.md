# Wordpress Turtletoy Gallery Plugin

Turtletoy Gallery allows you to display a collection of “turtles” generated by [Turtletoy](https://turtletoy.net). With a simple shortcode, you can fetch and show external images without uploading them to your own server.

Features include:

* Shortcode-driven galleries.
* Columns and layout configuration.
* Ability to limit how many images/turtles to show.
* Option to hide the original creator’s username.

You can find a live demo of this plugin [here](https://reindernijhoff.net/turtletoy/).

## Installation 

* Upload the plugin files to the /wp-content/plugins/turtletoy-gallery directory, or install the plugin through the WordPress “Plugins” screen directly.
* Activate the plugin through the “Plugins” screen in WordPress.
* Add the `[turtletoy-list query="turtle/browse/love/"]` shortcode (or any other query) to your posts or pages to display a Turtletoy gallery.

## Usage 

Insert the `[turtletoy-list]` shortcode wherever you want the gallery to appear.

Example:

```
[turtletoy-list query="turtle/browse/love/"]
```

To show only turtles from a specific user:

```
[turtletoy-list query="user/USERNAME/love/"]
```

## Optional Attributes 

* query – Required. The query term or user filter.
* columns – Optional; default=2. Number of columns (1, 2, 3, or 4).
* limit – Optional; default=0 (unlimited). Maximum number of turtles to show if set > 0.
* hideusername – Optional; default=0. Set to 1 to hide the turtle’s username.

## External services

This plugin connects to the Turtletoy API to fetch a list of all turtles that are displayed in the gallery.

It sends the optional arguments of the short code to perform the query.
This service is provided by Turtletoy: [Terms of Service and Privacy Policy](https://turtletoy.net/terms).

## Frequently Asked Questions 

### Why are images loaded from an external source? 

Turtletoy Gallery is designed to hotlink images from an external API and avoid storing them locally. This keeps your WordPress Media Library clean and ensures you always display the latest images from Turtletoy.

### Does this plugin store any images or data on my server? 

No. We do not store any images locally, nor do we collect personal data.

