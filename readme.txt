=== Project Locate on Map ===
Contributors: hashsons
Tags: projects, map, leaflet, gallery, location
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.2

A WordPress plugin to add projects with galleries, map locations, single pages, comments, and a full-screen interactive archive map.

== Changelog ==
= 1.0.2 =
- Removed archive top/left gap and archive/sidebar border radius.
- Improved archive font with clean system UI typography.
- Made project cards cleaner and more separated from map/background.
- Fixed gallery popup slider to show only the images from the selected project.
- Added more reliable Leaflet/map initialization and resize fixes.

= 1.0.1 =
- Added gallery lightbox slider with next/previous arrows and keyboard navigation.
- Improved frontend typography to inherit theme font and look cleaner.
- Improved location search with Nominatim + Photon fallback and manual Find Location button.

== Features ==
- Custom post type: Projects Map
- Project category taxonomy
- Featured image support
- Gallery image uploader with delete option
- Location autocomplete using OpenStreetMap Nominatim
- Latitude and longitude fields
- Backend draggable/clickable map marker
- Single project page with content, gallery popup, map marker, and comments
- Archive page with full-screen map, all project markers, and 25% project list sidebar
- Sidebar toggle
- Shortcode: [project_locate_map]

== Installation ==
1. Upload the plugin ZIP in WordPress Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Go to Settings > Permalinks and click Save Changes.
4. Add projects from Projects Map > Add New.
5. View /projects-map/ or use shortcode [project_locate_map].

== Notes ==
This plugin uses Leaflet + OpenStreetMap tiles, so no paid API key is required.
