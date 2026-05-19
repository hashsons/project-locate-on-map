jQuery(function ($) {
    let mediaFrame;

    function updateGalleryInput() {
        const ids = [];
        $('#plm_gallery_grid .plm-gallery-item').each(function () {
            ids.push($(this).data('id'));
        });
        $('#plm_gallery_ids').val(ids.join(','));
    }

    $('#plm_add_gallery_images').on('click', function (e) {
        e.preventDefault();

        if (mediaFrame) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media({
            title: 'Select Project Gallery Images',
            button: { text: 'Add Images' },
            multiple: true,
            library: { type: 'image' }
        });

        mediaFrame.on('select', function () {
            const attachments = mediaFrame.state().get('selection').toJSON();

            attachments.forEach(function (attachment) {
                const thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

                if ($('#plm_gallery_grid .plm-gallery-item[data-id="' + attachment.id + '"]').length) {
                    return;
                }

                $('#plm_gallery_grid').append(
                    '<div class="plm-gallery-item" data-id="' + attachment.id + '">' +
                    '<img src="' + thumb + '" alt="">' +
                    '<button type="button" class="plm-remove-image" title="Remove">×</button>' +
                    '</div>'
                );
            });

            updateGalleryInput();
        });

        mediaFrame.open();
    });

    $(document).on('click', '.plm-remove-image', function () {
        $(this).closest('.plm-gallery-item').remove();
        updateGalleryInput();
    });

    if ($('#plm_admin_map').length && typeof L !== 'undefined') {
        let lat = parseFloat($('#plm_lat').val()) || 25.4052;
        let lng = parseFloat($('#plm_lng').val()) || 55.5136;

        const map = L.map('plm_admin_map').setView([lat, lng], 12);
        const marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        setTimeout(function () {
            map.invalidateSize();
        }, 300);

        function setLatLng(newLat, newLng) {
            $('#plm_lat').val(Number(newLat).toFixed(7));
            $('#plm_lng').val(Number(newLng).toFixed(7));
            marker.setLatLng([newLat, newLng]);
            map.setView([newLat, newLng], 15);
        }

        function renderSuggestions(results) {
            const box = $('#plm_location_suggestions').empty();

            if (!results || !results.length) {
                box.html('<div class="plm-suggestion-empty">No location found. Try full address with city and country, or add latitude/longitude manually.</div>').show();
                return;
            }

            results.slice(0, 10).forEach(function (item) {
                $('<div class="plm-suggestion-item"></div>')
                    .text(item.name)
                    .data('lat', item.lat)
                    .data('lng', item.lng)
                    .data('name', item.name)
                    .appendTo(box);
            });

            box.show();
        }

        function searchNominatim(query) {
            return $.getJSON('https://nominatim.openstreetmap.org/search', {
                q: query,
                format: 'jsonv2',
                addressdetails: 1,
                namedetails: 1,
                extratags: 1,
                dedupe: 0,
                limit: 10
            }).then(function (results) {
                return (results || []).map(function (item) {
                    return {
                        name: item.display_name,
                        lat: parseFloat(item.lat),
                        lng: parseFloat(item.lon)
                    };
                });
            });
        }

        function searchPhoton(query) {
            return $.getJSON('https://photon.komoot.io/api/', {
                q: query,
                limit: 10
            }).then(function (data) {
                return ((data && data.features) ? data.features : []).map(function (feature) {
                    const p = feature.properties || {};
                    const c = feature.geometry && feature.geometry.coordinates ? feature.geometry.coordinates : [];
                    const parts = [p.name, p.street, p.city, p.state, p.country].filter(Boolean);
                    return {
                        name: parts.join(', '),
                        lat: parseFloat(c[1]),
                        lng: parseFloat(c[0])
                    };
                }).filter(function (item) {
                    return item.name && !isNaN(item.lat) && !isNaN(item.lng);
                });
            });
        }

        function uniqueResults(results) {
            const seen = {};
            return results.filter(function (item) {
                const key = (item.name || '').toLowerCase() + item.lat.toFixed(4) + item.lng.toFixed(4);
                if (seen[key]) return false;
                seen[key] = true;
                return true;
            });
        }

        function runLocationSearch(query) {
            query = (query || '').trim();

            if (query.length < 3) {
                $('#plm_location_suggestions').hide().empty();
                return;
            }

            $('#plm_location_suggestions').html('<div class="plm-suggestion-empty">Searching locations...</div>').show();

            $.when(searchNominatim(query), searchPhoton(query))
                .done(function (nomResults, photonResults) {
                    const combined = uniqueResults([].concat(nomResults || [], photonResults || []));
                    renderSuggestions(combined);
                })
                .fail(function () {
                    searchNominatim(query)
                        .done(renderSuggestions)
                        .fail(function () {
                            searchPhoton(query).done(renderSuggestions).fail(function () {
                                renderSuggestions([]);
                            });
                        });
                });
        }

        marker.on('dragend', function () {
            const pos = marker.getLatLng();
            $('#plm_lat').val(pos.lat.toFixed(7));
            $('#plm_lng').val(pos.lng.toFixed(7));
        });

        map.on('click', function (e) {
            setLatLng(e.latlng.lat, e.latlng.lng);
        });

        let debounce;
        $('#plm_address').on('input', function () {
            clearTimeout(debounce);
            const query = $(this).val();
            debounce = setTimeout(function () {
                runLocationSearch(query);
            }, 650);
        });

        $('#plm_find_location').on('click', function () {
            runLocationSearch($('#plm_address').val());
        });

        $(document).on('click', '.plm-suggestion-item', function () {
            const item = $(this);
            $('#plm_address').val(item.data('name'));
            setLatLng(parseFloat(item.data('lat')), parseFloat(item.data('lng')));
            $('#plm_location_suggestions').hide().empty();
        });

        $('#plm_lat, #plm_lng').on('change', function () {
            const newLat = parseFloat($('#plm_lat').val());
            const newLng = parseFloat($('#plm_lng').val());

            if (!isNaN(newLat) && !isNaN(newLng)) {
                marker.setLatLng([newLat, newLng]);
                map.setView([newLat, newLng], 15);
            }
        });
    }
});
