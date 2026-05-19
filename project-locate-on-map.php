<?php
/**
 * Plugin Name: Project Locate on Map
 * Description: Add projects with galleries and map locations, then display them on an elegant full-screen Leaflet map archive and single project pages.
 * Version: 1.0.2
 * Author: hashsons
 * Text Domain: project-locate-on-map
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PLM_VERSION', '1.0.2');
define('PLM_PATH', plugin_dir_path(__FILE__));
define('PLM_URL', plugin_dir_url(__FILE__));

class Project_Locate_On_Map {

    public function __construct() {
        add_action('init', array($this, 'register_project_cpt'));
        add_action('init', array($this, 'register_project_taxonomy'));

        add_action('add_meta_boxes', array($this, 'add_project_meta_boxes'));
        add_action('save_post_plm_project', array($this, 'save_project_meta'), 10, 2);

        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_assets'));

        add_filter('single_template', array($this, 'single_template'));
        add_filter('archive_template', array($this, 'archive_template'));

        add_action('admin_menu', array($this, 'settings_page'));
        add_filter('manage_plm_project_posts_columns', array($this, 'project_columns'));
        add_action('manage_plm_project_posts_custom_column', array($this, 'project_column_content'), 10, 2);

        add_shortcode('project_locate_map', array($this, 'archive_shortcode'));
    }

    public function register_project_cpt() {
        $labels = array(
            'name'               => __('All Projects', 'project-locate-on-map'),
            'singular_name'      => __('Project', 'project-locate-on-map'),
            'menu_name'          => __('Projects Map', 'project-locate-on-map'),
            'add_new'            => __('Add New', 'project-locate-on-map'),
            'add_new_item'       => __('Add New Project', 'project-locate-on-map'),
            'edit_item'          => __('Edit Project', 'project-locate-on-map'),
            'new_item'           => __('New Project', 'project-locate-on-map'),
            'view_item'          => __('View Project', 'project-locate-on-map'),
            'search_items'       => __('Search Projects', 'project-locate-on-map'),
            'not_found'          => __('No projects found', 'project-locate-on-map'),
            'not_found_in_trash' => __('No projects found in trash', 'project-locate-on-map'),
        );

        register_post_type('plm_project', array(
            'labels'             => $labels,
            'public'             => true,
            'has_archive'        => 'projects-map',
            'rewrite'            => array('slug' => 'project'),
            'menu_icon'          => 'dashicons-location-alt',
            'supports'           => array('title', 'editor', 'thumbnail', 'comments', 'excerpt'),
            'show_in_rest'       => true,
        ));
    }

    public function register_project_taxonomy() {
        register_taxonomy('plm_project_category', 'plm_project', array(
            'labels' => array(
                'name'          => __('Category', 'project-locate-on-map'),
                'singular_name' => __('Category', 'project-locate-on-map'),
                'menu_name'     => __('Category', 'project-locate-on-map'),
            ),
            'hierarchical'      => true,
            'show_admin_column' => true,
            'rewrite'           => array('slug' => 'project-category'),
            'show_in_rest'      => true,
        ));
    }

    public function add_project_meta_boxes() {
        add_meta_box(
            'plm_project_location',
            __('Project Location', 'project-locate-on-map'),
            array($this, 'location_meta_box_html'),
            'plm_project',
            'normal',
            'high'
        );

        add_meta_box(
            'plm_project_gallery',
            __('Project Gallery Images', 'project-locate-on-map'),
            array($this, 'gallery_meta_box_html'),
            'plm_project',
            'normal',
            'default'
        );
    }

    public function location_meta_box_html($post) {
        wp_nonce_field('plm_save_project_meta', 'plm_project_nonce');

        $address = get_post_meta($post->ID, '_plm_address', true);
        $lat     = get_post_meta($post->ID, '_plm_lat', true);
        $lng     = get_post_meta($post->ID, '_plm_lng', true);

        if (!$lat) $lat = '25.4052';
        if (!$lng) $lng = '55.5136';
        ?>
        <div class="plm-admin-box">
            <p class="plm-help-text">Type a location, choose a result, or drag/click the marker on the map. Latitude and longitude will save automatically.</p>

            <label for="plm_address"><strong>Location / Address</strong></label>
            <div class="plm-location-search-row">
                <input type="text" id="plm_address" name="plm_address" value="<?php echo esc_attr($address); ?>" class="widefat" placeholder="Example: Al Mowaihat, Ajman, UAE or full address with city/country" autocomplete="off" />
                <button type="button" class="button button-secondary" id="plm_find_location">Find Location</button>
            </div>
            <div id="plm_location_suggestions" class="plm-suggestions"></div>
            <p class="plm-help-text">Tip: If a place is not found, type a more complete address like area + city + country, or add latitude/longitude manually.</p>

            <div class="plm-location-grid">
                <p>
                    <label for="plm_lat"><strong>Latitude</strong></label>
                    <input type="text" id="plm_lat" name="plm_lat" value="<?php echo esc_attr($lat); ?>" class="widefat" />
                </p>
                <p>
                    <label for="plm_lng"><strong>Longitude</strong></label>
                    <input type="text" id="plm_lng" name="plm_lng" value="<?php echo esc_attr($lng); ?>" class="widefat" />
                </p>
            </div>

            <div id="plm_admin_map" class="plm-admin-map"></div>
        </div>
        <?php
    }

    public function gallery_meta_box_html($post) {
        $gallery = get_post_meta($post->ID, '_plm_gallery_ids', true);
        $ids = $gallery ? array_filter(array_map('absint', explode(',', $gallery))) : array();
        ?>
        <div class="plm-admin-box">
            <input type="hidden" id="plm_gallery_ids" name="plm_gallery_ids" value="<?php echo esc_attr(implode(',', $ids)); ?>">
            <button type="button" class="button button-primary" id="plm_add_gallery_images">Add Gallery Images</button>
            <p class="plm-help-text">Upload/select multiple images from Media Library. Drag order is not included in v1, but images can be removed anytime.</p>
            <div id="plm_gallery_grid" class="plm-gallery-grid">
                <?php foreach ($ids as $id) :
                    $thumb = wp_get_attachment_image_src($id, 'thumbnail');
                    if (!$thumb) continue;
                ?>
                    <div class="plm-gallery-item" data-id="<?php echo esc_attr($id); ?>">
                        <img src="<?php echo esc_url($thumb[0]); ?>" alt="">
                        <button type="button" class="plm-remove-image" title="Remove">×</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function save_project_meta($post_id, $post) {
        if (!isset($_POST['plm_project_nonce']) || !wp_verify_nonce($_POST['plm_project_nonce'], 'plm_save_project_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, '_plm_address', sanitize_text_field($_POST['plm_address'] ?? ''));
        update_post_meta($post_id, '_plm_lat', sanitize_text_field($_POST['plm_lat'] ?? ''));
        update_post_meta($post_id, '_plm_lng', sanitize_text_field($_POST['plm_lng'] ?? ''));

        $gallery_ids = sanitize_text_field($_POST['plm_gallery_ids'] ?? '');
        $gallery_ids = implode(',', array_filter(array_map('absint', explode(',', $gallery_ids))));
        update_post_meta($post_id, '_plm_gallery_ids', $gallery_ids);
    }

    public function admin_assets($hook) {
        global $post_type;

        if ($post_type !== 'plm_project') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);

        wp_enqueue_style('plm-admin', PLM_URL . 'assets/css/admin.css', array('leaflet'), PLM_VERSION);
        wp_enqueue_script('plm-admin', PLM_URL . 'assets/js/admin.js', array('jquery', 'leaflet'), PLM_VERSION, true);

        wp_localize_script('plm-admin', 'PLM_ADMIN', array(
            'nonce' => wp_create_nonce('plm_admin_nonce')
        ));
    }

    public function frontend_assets() {
        if (is_singular('plm_project') || is_post_type_archive('plm_project') || is_page() || is_tax('plm_project_category')) {
            wp_enqueue_style('leaflet', PLM_URL . 'assets/vendor/leaflet/leaflet.css', array(), PLM_VERSION);
            wp_enqueue_script('leaflet', PLM_URL . 'assets/vendor/leaflet/leaflet.js', array(), PLM_VERSION, true);

            wp_enqueue_style('plm-frontend', PLM_URL . 'assets/css/frontend.css', array('leaflet'), PLM_VERSION);
            wp_enqueue_script('plm-frontend', PLM_URL . 'assets/js/frontend.js', array('jquery', 'leaflet'), PLM_VERSION, true);
        }
    }

    public function single_template($template) {
        if (is_singular('plm_project')) {
            $plugin_template = PLM_PATH . 'templates/single-plm_project.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    public function archive_template($template) {
        if (is_post_type_archive('plm_project') || is_tax('plm_project_category')) {
            $plugin_template = PLM_PATH . 'templates/archive-plm_project.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    public function settings_page() {
        add_submenu_page(
            'edit.php?post_type=plm_project',
            __('Settings', 'project-locate-on-map'),
            __('Settings', 'project-locate-on-map'),
            'manage_options',
            'plm-settings',
            array($this, 'settings_page_html')
        );
    }

    public function settings_page_html() {
        ?>
        <div class="wrap plm-settings-wrap">
            <h1>Project Locate on Map - Settings</h1>
            <div class="plm-settings-card">
                <h2>Shortcode</h2>
                <code>[project_locate_map]</code>
                <p>Add this shortcode on any WordPress page to show the full map archive layout.</p>
            </div>

            <div class="plm-settings-card">
                <h2>Default Archive URL</h2>
                <p>Your archive page should be available at:</p>
                <code><?php echo esc_url(home_url('/projects-map/')); ?></code>
                <p>If it shows 404, go to <strong>Settings → Permalinks</strong> and click <strong>Save Changes</strong>.</p>
            </div>

            <div class="plm-settings-card">
                <h2>Instructions</h2>
                <ol>
                    <li>Go to <strong>Projects Map → Add New</strong>.</li>
                    <li>Add title, content, featured image, gallery images, category, and location.</li>
                    <li>Type the address and choose a suggestion. You can also click/drag marker on map.</li>
                    <li>Publish the project.</li>
                    <li>Open <strong>/projects-map/</strong> or use shortcode <code>[project_locate_map]</code>.</li>
                </ol>
            </div>

            <div class="plm-settings-card">
                <h2>Map Technology</h2>
                <p>This plugin uses Leaflet with OpenStreetMap tiles. No paid API key is required.</p>
            </div>
        </div>
        <?php
    }

    public function project_columns($columns) {
        $columns['plm_location'] = __('Location', 'project-locate-on-map');
        return $columns;
    }

    public function project_column_content($column, $post_id) {
        if ($column === 'plm_location') {
            $address = get_post_meta($post_id, '_plm_address', true);
            $lat = get_post_meta($post_id, '_plm_lat', true);
            $lng = get_post_meta($post_id, '_plm_lng', true);
            echo esc_html($address ?: 'No location');
            if ($lat && $lng) {
                echo '<br><small>' . esc_html($lat . ', ' . $lng) . '</small>';
            }
        }
    }

    public static function get_projects_data() {
        $query = new WP_Query(array(
            'post_type'      => 'plm_project',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        $projects = array();

        while ($query->have_posts()) {
            $query->the_post();

            $id = get_the_ID();
            $lat = get_post_meta($id, '_plm_lat', true);
            $lng = get_post_meta($id, '_plm_lng', true);

            if (!$lat || !$lng) {
                continue;
            }

            $gallery_raw = get_post_meta($id, '_plm_gallery_ids', true);
            $gallery_ids = $gallery_raw ? array_filter(array_map('absint', explode(',', $gallery_raw))) : array();
            $gallery = array();

            foreach ($gallery_ids as $gid) {
                $thumb = wp_get_attachment_image_src($gid, 'medium');
                $full = wp_get_attachment_image_src($gid, 'large');
                if ($thumb && $full) {
                    $gallery[] = array(
                        'thumb' => $thumb[0],
                        'full'  => $full[0],
                    );
                }
            }

            $projects[] = array(
                'id'          => $id,
                'title'       => get_the_title(),
                'excerpt'     => wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 18),
                'short'       => wp_trim_words(wp_strip_all_tags(get_the_content()), 12),
                'link'        => get_permalink(),
                'lat'         => (float) $lat,
                'lng'         => (float) $lng,
                'address'     => get_post_meta($id, '_plm_address', true),
                'image'       => get_the_post_thumbnail_url($id, 'medium_large') ?: PLM_URL . 'assets/images/placeholder.svg',
                'gallery'     => $gallery,
            );
        }

        wp_reset_postdata();

        return $projects;
    }

    public function archive_shortcode($atts) {
        ob_start();
        include PLM_PATH . 'templates/archive-content.php';
        return ob_get_clean();
    }
}

new Project_Locate_On_Map();

register_activation_hook(__FILE__, function() {
    $plm_instance = new Project_Locate_On_Map();
    $plm_instance->register_project_cpt();
    $plm_instance->register_project_taxonomy();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
