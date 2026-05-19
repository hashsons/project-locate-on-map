jQuery(function ($) {
    let lightboxItems = [];
    let currentIndex = 0;

    function leafletReady(callback) {
        if (typeof L !== 'undefined') {
            callback();
            return;
        }

        let attempts = 0;
        const timer = setInterval(function () {
            attempts++;
            if (typeof L !== 'undefined') {
                clearInterval(timer);
                callback();
            }
            if (attempts > 25) {
                clearInterval(timer);
                console.warn('Project Locate on Map: Leaflet map library did not load.');
            }
        }, 200);
    }

    function initArchiveMap() {
        leafletReady(function () {
            const wrapper = $('#plm_archive_app');
            if (!wrapper.length) return;

            const projects = wrapper.data('projects') || [];
            const mapEl = document.getElementById('plm_archive_map');
            if (!mapEl || mapEl.dataset.plmReady === '1') return;
            mapEl.dataset.plmReady = '1';

            const defaultCenter = projects.length ? [projects[0].lat, projects[0].lng] : [25.4052, 55.5136];
            const map = L.map(mapEl, { zoomControl: true }).setView(defaultCenter, 10);
            const markers = {};

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const bounds = [];

            projects.forEach(function (project) {
                if (!project.lat || !project.lng) return;

                const marker = L.marker([project.lat, project.lng]).addTo(map);
                markers[project.id] = marker;
                bounds.push([project.lat, project.lng]);

                marker.bindPopup(
                    '<div class="plm-popup">' +
                    '<strong>' + escapeHtml(project.title) + '</strong>' +
                    '<p>' + escapeHtml(project.short || '') + '</p>' +
                    '<a href="' + project.link + '">Read more</a>' +
                    '</div>'
                );

                marker.on('mouseover', function () {
                    marker.openPopup();
                    $('.plm-project-card').removeClass('is-active');
                    $('.plm-project-card[data-project-id="' + project.id + '"]').addClass('is-active');
                });
            });

            if (bounds.length > 1) {
                map.fitBounds(bounds, { padding: [70, 70] });
            } else if (bounds.length === 1) {
                map.setView(bounds[0], 14);
            }

            setTimeout(function () { map.invalidateSize(true); }, 250);
            setTimeout(function () { map.invalidateSize(true); }, 900);
            $(window).on('resize', function () {
                setTimeout(function () { map.invalidateSize(true); }, 150);
            });

            $(document).on('click', '.plm-location-link', function (e) {
                e.preventDefault();
                const id = $(this).closest('.plm-project-card').data('project-id');
                const project = projects.find(function (p) { return parseInt(p.id) === parseInt(id); });

                if (!project || !markers[id]) return;

                map.setView([project.lat, project.lng], 16);
                markers[id].openPopup();
                $('.plm-project-card').removeClass('is-active');
                $('.plm-project-card[data-project-id="' + id + '"]').addClass('is-active');

                if (window.innerWidth < 780) {
                    $('html, body').animate({ scrollTop: $('#plm_archive_map').offset().top }, 350);
                }
            });

            $('.plm-sidebar-toggle').on('click', function () {
                wrapper.toggleClass('is-sidebar-closed');
                setTimeout(function () {
                    map.invalidateSize(true);
                }, 360);
            });
        });
    }

    function initSingleMap() {
        leafletReady(function () {
            const el = $('#plm_single_map');
            if (!el.length) return;

            const mapEl = document.getElementById('plm_single_map');
            if (!mapEl || mapEl.dataset.plmReady === '1') return;
            mapEl.dataset.plmReady = '1';

            const lat = parseFloat(el.data('lat'));
            const lng = parseFloat(el.data('lng'));
            const title = el.data('title') || '';

            if (isNaN(lat) || isNaN(lng)) return;

            const map = L.map('plm_single_map').setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            L.marker([lat, lng]).addTo(map).bindPopup(escapeHtml(title)).openPopup();

            setTimeout(function () { map.invalidateSize(true); }, 250);
            setTimeout(function () { map.invalidateSize(true); }, 900);
        });
    }

    function collectLightboxItemsFromContext(trigger) {
        lightboxItems = [];

        let scope = trigger.closest('.plm-mini-gallery');
        if (!scope.length) {
            scope = trigger.closest('.plm-gallery-grid-front');
        }
        if (!scope.length) {
            scope = trigger.parent();
        }

        scope.find('.plm-lightbox-trigger').each(function (index) {
            const src = $(this).attr('href') || $(this).data('full');
            if (!src) return;

            $(this).attr('data-plm-index', lightboxItems.length);
            lightboxItems.push({
                src: src,
                title: $(this).attr('title') || $(this).find('img').attr('alt') || ''
            });
        });

        return parseInt(trigger.attr('data-plm-index'), 10) || scope.find('.plm-lightbox-trigger').index(trigger);
    }

    function updateLightbox() {
        if (!lightboxItems.length) return;

        const item = lightboxItems[currentIndex];
        $('#plm_lightbox img').attr('src', item.src).attr('alt', item.title || '');
        $('#plm_lightbox_caption').text(item.title || '');
        $('#plm_lightbox_count').text((currentIndex + 1) + ' / ' + lightboxItems.length);

        if (lightboxItems.length <= 1) {
            $('.plm-lightbox-prev, .plm-lightbox-next, #plm_lightbox_count').hide();
        } else {
            $('.plm-lightbox-prev, .plm-lightbox-next, #plm_lightbox_count').show();
        }
    }

    function openLightbox(trigger) {
        currentIndex = collectLightboxItemsFromContext(trigger);

        if (currentIndex < 0) currentIndex = 0;
        if (currentIndex >= lightboxItems.length) currentIndex = lightboxItems.length - 1;

        updateLightbox();
        $('#plm_lightbox').addClass('is-open');
        $('body').addClass('plm-lightbox-open');
    }

    function closeLightbox() {
        $('#plm_lightbox').removeClass('is-open');
        $('body').removeClass('plm-lightbox-open');
    }

    function nextImage() {
        if (!lightboxItems.length) return;
        currentIndex = (currentIndex + 1) % lightboxItems.length;
        updateLightbox();
    }

    function prevImage() {
        if (!lightboxItems.length) return;
        currentIndex = (currentIndex - 1 + lightboxItems.length) % lightboxItems.length;
        updateLightbox();
    }

    function initLightbox() {
        if ($('#plm_lightbox').length) return;

        $('body').append(
            '<div class="plm-lightbox" id="plm_lightbox" aria-hidden="true">' +
                '<button class="plm-lightbox-close" type="button" aria-label="Close">×</button>' +
                '<button class="plm-lightbox-nav plm-lightbox-prev" type="button" aria-label="Previous image">‹</button>' +
                '<div class="plm-lightbox-stage">' +
                    '<img src="" alt="">' +
                    '<div class="plm-lightbox-meta">' +
                        '<span id="plm_lightbox_caption"></span>' +
                        '<span id="plm_lightbox_count"></span>' +
                    '</div>' +
                '</div>' +
                '<button class="plm-lightbox-nav plm-lightbox-next" type="button" aria-label="Next image">›</button>' +
            '</div>'
        );

        $(document).on('click', '.plm-lightbox-trigger', function (e) {
            e.preventDefault();
            openLightbox($(this));
        });

        $(document).on('click', '.plm-lightbox-close', closeLightbox);
        $(document).on('click', '.plm-lightbox-next', function (e) {
            e.stopPropagation();
            nextImage();
        });
        $(document).on('click', '.plm-lightbox-prev', function (e) {
            e.stopPropagation();
            prevImage();
        });

        $(document).on('click', '#plm_lightbox', function (e) {
            if ($(e.target).is('#plm_lightbox')) {
                closeLightbox();
            }
        });

        $(document).on('keyup', function (e) {
            if (!$('#plm_lightbox').hasClass('is-open')) return;

            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowRight') nextImage();
            if (e.key === 'ArrowLeft') prevImage();
        });
    }

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>"']/g, function (match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[match];
        });
    }

    initArchiveMap();
    initSingleMap();
    initLightbox();
});
