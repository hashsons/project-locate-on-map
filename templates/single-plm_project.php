<?php
if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()) :
    the_post();

    $id = get_the_ID();
    $address = get_post_meta($id, '_plm_address', true);
    $lat = get_post_meta($id, '_plm_lat', true);
    $lng = get_post_meta($id, '_plm_lng', true);

    $gallery_raw = get_post_meta($id, '_plm_gallery_ids', true);
    $gallery_ids = $gallery_raw ? array_filter(array_map('absint', explode(',', $gallery_raw))) : array();
    $hero = get_the_post_thumbnail_url($id, 'full') ?: PLM_URL . 'assets/images/placeholder.svg';
    ?>
    <main class="plm-single-wrap">
        <section class="plm-single-hero">
            <img src="<?php echo esc_url($hero); ?>" alt="<?php the_title_attribute(); ?>">
            <div class="plm-single-hero-content">
                <h1><?php the_title(); ?></h1>
                <?php if ($address) : ?>
                    <div class="plm-single-address">📍 <?php echo esc_html($address); ?></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="plm-single-content">
            <?php the_content(); ?>
        </section>

        <?php if (!empty($gallery_ids)) : ?>
            <section class="plm-single-gallery-section">
                <h2 class="plm-section-title">Project Gallery</h2>
                <div class="plm-gallery-grid-front">
                    <?php foreach ($gallery_ids as $gid) :
                        $thumb = wp_get_attachment_image_src($gid, 'large');
                        $full = wp_get_attachment_image_src($gid, 'full');
                        if (!$thumb || !$full) continue;
                    ?>
                        <a class="plm-lightbox-trigger" href="<?php echo esc_url($full[0]); ?>">
                            <img src="<?php echo esc_url($thumb[0]); ?>" alt="">
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($lat && $lng) : ?>
            <section class="plm-map-section">
                <h2 class="plm-section-title">Project Location</h2>
                <div id="plm_single_map" class="plm-single-map"
                    data-lat="<?php echo esc_attr($lat); ?>"
                    data-lng="<?php echo esc_attr($lng); ?>"
                    data-title="<?php echo esc_attr(get_the_title()); ?>"></div>
            </section>
        <?php endif; ?>

        <?php if (comments_open() || get_comments_number()) : ?>
            <section class="plm-comments">
                <?php comments_template(); ?>
            </section>
        <?php endif; ?>
    </main>
    <?php
endwhile;

get_footer();
