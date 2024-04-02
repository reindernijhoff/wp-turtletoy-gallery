# Wordpress Turtletoy Gallery Plugin

This WordPress plugin enables a shortcode to add a gallery with [Turtletoy](https://turtletoy.net) turtles to your WordPress site. The gallery's content is based on a _query_ attribute of the shortcode and will update automatically.

You can find a live demo of this plugin [here](https://reindernijhoff.net/turtletoy/).

Note:
- This is (one of) the first WordPress plugins I have ever made. 

I don't want to DDoS Turtletoy, and I want a fast plugin. Therefore, a query's result will be cached for (at least) one day.

## Installation

Copy the _turtletoy_ directory into _wp-content/plugins_ and activate the plugin in the Admin.

## Basic usage

Add a _Turtletoy-list_ shortcode to your post or page. If you want to create a gallery with all turtles that match the query 'turtle/browse/love/', you use:

```
[turtletoy-list query="turtle/browse/love/"]
```

## Optional attributes

You can use the following (optional) attributes:

- *query* - The query term.
- *columns* (optional, default = 2) - Number of columns of the gallery. Values 1,2,3, and 4 are supported.
- *limit* (optional, default = 0) - Maximum number of turtles in the gallery if limit > 0.
