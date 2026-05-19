<?php
if (!defined('ABSPATH')) exit;

$projects = Project_Locate_On_Map::get_projects_data();
?>
<div id="plm_archive_app" class="plm-archive-app" data-projects='<?php echo esc_attr(wp_json_encode($projects)); ?>'>
    <div id="plm_archive_map" class="plm-archive-map"></div>

    <aside class="plm-sidebar">
        <div class="plm-sidebar-head">
            <h1>Projects Location Map</h1>
            <p>Explore all projects by map location. Click “Location on map” to highlight the marker.</p>
        </div>

        <div class="plm-project-list">
            <?php if (!empty($projects)) : ?>
                <?php foreach ($projects as $project) : ?>
                    <article class="plm-project-card" data-project-id="<?php echo esc_attr($project['id']); ?>">
                        <a class="plm-project-feature" href="<?php echo esc_url($project['link']); ?>">
                            <img src="<?php echo esc_url($project['image']); ?>" alt="<?php echo esc_attr($project['title']); ?>">
                        </a>

                        <div class="plm-card-body">
                            <h3><a href="<?php echo esc_url($project['link']); ?>"><?php echo esc_html($project['title']); ?></a></h3>
                            <p><?php echo esc_html($project['excerpt']); ?></p>

                            <?php if (!empty($project['gallery'])) : ?>
                                <div class="plm-mini-gallery">
                                    <?php foreach ($project['gallery'] as $image) : ?>
                                        <a class="plm-lightbox-trigger" href="<?php echo esc_url($image['full']); ?>">
                                            <img src="<?php echo esc_url($image['thumb']); ?>" alt="">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="plm-card-actions">
                                <a href="#" class="plm-location-link">Location on map</a>
                                <a href="<?php echo esc_url($project['link']); ?>" class="plm-read-more">Read More →</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="plm-project-card">
                    <div class="plm-card-body">
                        <h3>No projects found</h3>
                        <p>Please add projects with latitude and longitude from the WordPress dashboard.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <button type="button" class="plm-sidebar-toggle" aria-label="Toggle project list">‹</button>
</div>
