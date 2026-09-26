(function ($) {
    'use strict';

    var zc = {
        ajaxUrl: zekoLoveCalls.ajaxUrl,
        nonce: zekoLoveCalls.nonce,
        currency: zekoLoveCalls.currency || 'USD',
        pageUrl: zekoLoveCalls.pageUrl || '',
        messagesUrl: zekoLoveCalls.messagesUrl || '',
        i18n: zekoLoveCalls.i18n || {}
    };

    function zlAjax(action, data) {
        return $.post(zc.ajaxUrl, $.extend({ action: action, _wpnonce: zc.nonce }, data));
    }

    function toast(type, msg) {
        var icon = type === 'success' ? '\u2713' : '\u2717';
        var bg = type === 'success' ? '#10b981' : '#ef4444';
        var $t = $('<div style="position:fixed;top:20px;right:20px;z-index:1000000;background:' + bg + ';color:#fff;padding:12px 20px;border-radius:10px;font-size:14px;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,0.2)">' + msg + '</div>');
        $('body').append($t);
        setTimeout(function () { $t.fadeOut(300, function () { $t.remove(); }); }, 3500);
    }

    function money(amount) {
        return zc.currency + ' ' + Number(amount).toFixed(2);
    }

    /* ============================================================
     * Explore tab
     * ============================================================ */
    var exploreState = { page: 1, search: '', type: '', order: 'created_at' };

    function loadExplore() {
        var $results = $('.zl-explore-results');
        $results.html('<div class="zl-loading">' + (zc.i18n.loading || 'Loading…') + '</div>');
        zlAjax('zeko_love_calls_get_listings', {
            page: exploreState.page,
            search: exploreState.search,
            call_type: exploreState.type,
            orderby: exploreState.order
        }).done(function (r) {
            if (!r.success) {
                $results.html('<div class="zl-empty-state"><h3>' + (r.data && r.data.message ? r.data.message : zc.i18n.error) + '</h3></div>');
                return;
            }
            $results.html(r.data.html);
            renderPagination(r.data.pages, r.data.page);
        });
    }

    function renderPagination(pages, current) {
        var $p = $('.zl-pagination');
        $p.empty().attr('data-current', current);
        if (pages <= 1) return;
        for (var i = 1; i <= pages; i++) {
            var $b = $('<button type="button" class="zl-btn zl-btn-secondary zl-btn-sm' + (i === current ? ' active' : '') + '">' + i + '</button>');
            $b.data('page', i);
            $p.append($b);
        }
    }

    $(document).on('click', '.zl-pagination button', function () {
        exploreState.page = $(this).data('page');
        loadExplore();
    });

    $(document).on('input', '.zl-explore-search', $.debounce ? $.debounce(350, function () {
        exploreState.search = $(this).val().trim();
        exploreState.page = 1;
        loadExplore();
    }) : function () {
        var self = this;
        clearTimeout(self._t);
        self._t = setTimeout(function () {
            exploreState.search = $(self).val().trim();
            exploreState.page = 1;
            loadExplore();
        }, 350);
    });

    $(document).on('change', '.zl-explore-type', function () {
        exploreState.type = $(this).val();
        exploreState.page = 1;
        loadExplore();
    });

    $(document).on('change', '.zl-explore-order', function () {
        exploreState.order = $(this).val();
        exploreState.page = 1;
        loadExplore();
    });

    /* ============================================================
     * Tabs
     * ============================================================ */
    $(document).on('click', '.zl-calls-tabs .zl-tab', function () {
        var $tabs = $(this).closest('.zl-tabs');
        $tabs.find('.zl-tab').removeClass('active');
        $(this).addClass('active');
        var panel = $(this).data('tab');
        $('.zl-calls-panel').hide();
        $('.zl-calls-panel[data-panel="' + panel + '"]').show();

        if (panel === 'explore') loadExplore();
        if (panel === 'my-listings') loadMyListings();
        if (panel === 'bookings') loadBookings();
        if (panel === 'earnings') loadEarnings();
    });

    $(document).on('click', '.zl-subtabs .zl-tab', function () {
        var $tabs = $(this).closest('.zl-subtabs');
        $tabs.find('.zl-tab').removeClass('active');
        $(this).addClass('active');
        var sub = $(this).data('subtab');
        $('.zl-bookings-panel').hide();
        $('.zl-bookings-' + sub).show();
    });

    /* ============================================================
     * My Listings
     * ============================================================ */
    function loadMyListings() {
        var $container = $('.zl-my-listings');
        $container.html('<div class="zl-loading">' + (zc.i18n.loading || 'Loading…') + '</div>');
        zlAjax('zeko_love_calls_get_my_listings', {}).done(function (r) {
            if (!r.success) {
                $container.html('<div class="zl-empty-state"><h3>' + (r.data && r.data.message ? r.data.message : zc.i18n.error) + '</h3></div>');
                return;
            }
            $container.html(r.data.html);
        });
    }

    function openListingForm(listing) {
        var $wrap = $('.zl-listing-form-wrap');
        $wrap.show();
        $('.zl-form-error').hide();
        if (listing) {
            $('.zl-listing-form-title').text(zc.i18n.editListing || 'Edit listing');
            $('.zl-form-listing-id').val(listing.listing_id);
            $('.zl-form-title').val(listing.title || '');
            $('.zl-form-description').val(listing.description || '');
            $('.zl-form-call-type').val(listing.call_type || 'video');
            $('.zl-form-provider').val(listing.provider || 'google_meet');
            $('.zl-form-price').val(listing.price_per_minute || '1.00');
            $('.zl-form-min').val(listing.min_duration || 15);
            $('.zl-form-max').val(listing.max_duration || 60);
            $('.zl-form-cover').val(listing.cover_photo_url || '');
            $('.zl-form-youtube').val(listing.youtube_url || '');
            $('.zl-form-vimeo').val(listing.vimeo_url || '');
            $('.zl-form-intro-video').val(listing.intro_video_url || '');
            $('.zl-form-whatsapp').val(listing.whatsapp_phone || '');
            $('.zl-form-join').val(listing.join_url || '');
            renderSlotRows(listing.slots || []);
        } else {
            $('.zl-listing-form-title').text(zc.i18n.newListing || 'New listing');
            $('.zl-listing-form-wrap .zl-form-grid input:not(.zl-form-price), .zl-listing-form-wrap .zl-form-grid textarea').val('');
            $('.zl-form-listing-id').val(0);
            $('.zl-form-price').val('1.00');
            $('.zl-form-min').val(15);
            $('.zl-form-max').val(60);
            $('.zl-form-call-type').val('video');
            $('.zl-form-provider').val('google_meet');
            renderSlotRows([]);
        }
    }

    function renderSlotRows(slots) {
        var $rows = $('.zl-slot-rows');
        $rows.empty();
        var days = [zc.i18n.sun || 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', zc.i18n.sat || 'Sat'];
        if (!slots.length) {
            slots = [{ day_of_week: 0, start_time: '18:00', end_time: '20:00' }];
        }
        slots.forEach(function (slot) {
            var $row = $('<div class="zl-slot-row">' +
                '<select class="zl-input zl-slot-day">' + days.map(function (d, i) {
                    return '<option value="' + i + '"' + (String(slot.day_of_week) === String(i) ? ' selected' : '') + '>' + d + '</option>';
                }).join('') + '</select>' +
                '<input type="time" class="zl-input zl-slot-start" value="' + (slot.start_time || '18:00') + '" />' +
                '<input type="time" class="zl-input zl-slot-end" value="' + (slot.end_time || '20:00') + '" />' +
                '<button type="button" class="zl-btn zl-btn-danger zl-btn-sm zl-slot-remove">\u00d7</button>' +
                '</div>');
            $rows.append($row);
        });
    }

    $(document).on('click', '.zl-new-listing-btn', function () {
        openListingForm(null);
    });

    $(document).on('click', '.zl-listing-form-cancel', function () {
        $('.zl-listing-form-wrap').hide();
    });

    $(document).on('click', '.zl-add-slot', function () {
        renderSlotRows($('.zl-slot-row').map(function () {
            return {
                day_of_week: $(this).find('.zl-slot-day').val(),
                start_time: $(this).find('.zl-slot-start').val(),
                end_time: $(this).find('.zl-slot-end').val()
            };
        }).get().concat([{ day_of_week: 0, start_time: '18:00', end_time: '20:00' }]));
    });

    $(document).on('click', '.zl-slot-remove', function () {
        $(this).closest('.zl-slot-row').remove();
    });

    $(document).on('click', '.zl-edit-listing', function () {
        var id = $(this).data('listing-id');
        zlAjax('zeko_love_calls_get_listings', { listing_id: id, for_edit: 1 }).done(function (r) {
            if (r.success && r.data.listing) {
                openListingForm(r.data.listing);
            } else {
                toast('error', (r.data && r.data.message) || zc.i18n.error);
            }
        });
    });

    $(document).on('click', '.zl-listing-form-save', function () {
        var $btn = $(this);
        $('.zl-form-error').hide();
        var slots = $('.zl-slot-row').map(function () {
            return {
                day_of_week: $(this).find('.zl-slot-day').val(),
                start_time: $(this).find('.zl-slot-start').val(),
                end_time: $(this).find('.zl-slot-end').val()
            };
        }).get();

        zlAjax('zeko_love_calls_save_listing', {
            listing_id: $('.zl-form-listing-id').val(),
            title: $('.zl-form-title').val(),
            description: $('.zl-form-description').val(),
            call_type: $('.zl-form-call-type').val(),
            provider: $('.zl-form-provider').val(),
            price_per_minute: $('.zl-form-price').val(),
            min_duration: $('.zl-form-min').val(),
            max_duration: $('.zl-form-max').val(),
            cover_photo_url: $('.zl-form-cover').val(),
            intro_video_url: $('.zl-form-intro-video').val(),
            youtube_url: $('.zl-form-youtube').val(),
            vimeo_url: $('.zl-form-vimeo').val(),
            whatsapp_phone: $('.zl-form-whatsapp').val(),
            join_url: $('.zl-form-join').val(),
            slots: JSON.stringify(slots)
        }).done(function (r) {
            if (!r.success) {
                var $err = $('.zl-form-error');
                $err.show().find('p').text((r.data && r.data.message) || zc.i18n.error);
                return;
            }
            $('.zl-listing-form-wrap').hide();
            toast('success', r.data.message);
            loadMyListings();
            if (r.data.needs_plan) {
                openPlanModal(r.data.listing_id);
            }
        });
    });

    $(document).on('click', '.zl-delete-listing', function () {
        var $row = $(this).closest('.zl-my-listing');
        if (!window.confirm(zc.i18n.confirmDelete)) return;
        zlAjax('zeko_love_calls_delete_listing', { listing_id: $row.data('listing-id') }).done(function (r) {
            if (!r.success) {
                toast('error', (r.data && r.data.message) || zc.i18n.error);
                return;
            }
            toast('success', r.data.message);
            loadMyListings();
        });
    });

    $(document).on('click', '.zl-set-status', function () {
        var $row = $(this).closest('.zl-my-listing');
        var status = $(this).data('status');
        zlAjax('zeko_love_calls_set_listing_status', { listing_id: $row.data('listing-id'), status: status }).done(function (r) {
            if (!r.success) {
                if (r.data && r.data.needs_plan) {
                    openPlanModal($row.data('listing-id'));
                    return;
                }
                toast('error', (r.data && r.data.message) || zc.i18n.error);
                return;
            }
            toast('success', r.data.message);
            loadMyListings();
        });
    });

    /* ============================================================
     * Plan purchase
     * ============================================================ */
    function openPlanModal(listingId) {
        var $wrap = $('.zl-plan-modal-wrap');
        var $list = $('.zl-plan-list');
        var $err = $('.zl-plan-modal .zl-form-error');
        $err.hide();
        $list.html('<div class="zl-loading">' + (zc.i18n.loading || 'Loading…') + '</div>');
        $wrap.show();
        $wrap.data('listing-id', listingId);
        zlAjax('zeko_love_calls_get_plans', {}).done(function (r) {
            if (!r.success) {
                $list.html('<div class="zl-empty-state"><h3>' + (r.data && r.data.message ? r.data.message : zc.i18n.noPlans) + '</h3></div>');
                return;
            }
            $list.html(r.data.html);
        });
    }

    $(document).on('click', '.zl-plan-cancel', function () {
        $('.zl-plan-modal-wrap').hide();
    });

    $(document).on('click', '.zl-buy-plan-btn', function () {
        var $card = $(this).closest('.zl-plan-card');
        var $wrap = $('.zl-plan-modal-wrap');
        var $err = $('.zl-plan-modal .zl-form-error');
        $err.hide();
        zlAjax('zeko_love_calls_buy_plan', {
            plan_id: $card.data('plan-id'),
            listing_id: $wrap.data('listing-id')
        }).done(function (r) {
            if (!r.success) {
                $err.show().find('p').text((r.data && r.data.message) || zc.i18n.error);
                return;
            }
            $wrap.hide();
            toast('success', r.data.message);
            loadMyListings();
            refreshBalance();
        });
    });

    function refreshBalance() {
        var $pill = $('.zl-wallet-pill strong');
        if (!$pill.length) return;
        zlAjax('zeko_love_calls_get_earnings', {}).done(function (r) {
            if (r.success) $pill.text(r.data.balance);
        });
    }

    /* ============================================================
     * Bookings
     * ============================================================ */
    function loadBookings() {
        $('.zl-bookings-buyer').html('<div class="zl-loading">' + (zc.i18n.loading || 'Loading…') + '</div>');
        $('.zl-bookings-seller').html('<div class="zl-loading">' + (zc.i18n.loading || 'Loading…') + '</div>');
        zlAjax('zeko_love_calls_get_my_bookings', {}).done(function (r) {
            if (!r.success) return;
            $('.zl-bookings-buyer').html(r.data.buyer);
            $('.zl-bookings-seller').html(r.data.seller);
        });
    }

    $(document).on('click', '.zl-cancel-booking', function () {
        var $row = $(this).closest('.zl-booking-row');
        if (!window.confirm(zc.i18n.confirmCancel)) return;
        zlAjax('zeko_love_calls_cancel_booking', { booking_id: $row.data('booking-id') }).done(function (r) {
            if (!r.success) { toast('error', (r.data && r.data.message) || zc.i18n.error); return; }
            toast('success', r.data.message);
            loadBookings();
            refreshBalance();
        });
    });

    $(document).on('click', '.zl-confirm-booking', function () {
        var $row = $(this).closest('.zl-booking-row');
        zlAjax('zeko_love_calls_confirm_booking', { booking_id: $row.data('booking-id') }).done(function (r) {
            if (!r.success) { toast('error', (r.data && r.data.message) || zc.i18n.error); return; }
            toast('success', r.data.message);
            loadBookings();
        });
    });

    $(document).on('click', '.zl-complete-booking', function () {
        var $row = $(this).closest('.zl-booking-row');
        zlAjax('zeko_love_calls_complete_booking', { booking_id: $row.data('booking-id') }).done(function (r) {
            if (!r.success) { toast('error', (r.data && r.data.message) || zc.i18n.error); return; }
            toast('success', r.data.message);
            loadBookings();
            refreshBalance();
        });
    });

    var reviewBookingId = 0;

    $(document).on('click', '.zl-review-booking', function () {
        reviewBookingId = $(this).closest('.zl-booking-row').data('booking-id');
        $('.zl-star').removeClass('active').first().addClass('active');
        $('.zl-review-comment').val('');
        $('.zl-review-modal-wrap').show();
    });

    $(document).on('click', '.zl-review-cancel', function () {
        $('.zl-review-modal-wrap').hide();
    });

    $(document).on('click', '.zl-star', function () {
        var val = $(this).data('value');
        $('.zl-star').each(function () {
            $(this).toggleClass('active', $(this).data('value') <= val);
        });
    });

    $(document).on('click', '.zl-review-submit', function () {
        var rating = $('.zl-star.active').length || 5;
        zlAjax('zeko_love_calls_submit_review', {
            booking_id: reviewBookingId,
            rating: rating,
            comment: $('.zl-review-comment').val()
        }).done(function (r) {
            if (!r.success) { toast('error', (r.data && r.data.message) || zc.i18n.error); return; }
            $('.zl-review-modal-wrap').hide();
            toast('success', r.data.message);
            loadBookings();
        });
    });

    /* ============================================================
     * Earnings
     * ============================================================ */
    function loadEarnings() {
        $('.zl-tx-list').html('<div class="zl-loading">' + (zc.i18n.loading || 'Loading…') + '</div>');
        zlAjax('zeko_love_calls_get_earnings', {}).done(function (r) {
            if (!r.success) return;
            $('.zl-earnings-balance-amount').text(money(r.data.balance));
            var s = r.data.stats || {};
            var statsHtml =
                '<div class="zl-earning-stat"><span>' + (zc.i18n.listings || 'Listings') + '</span><strong>' + (s.listings || 0) + '</strong></div>' +
                '<div class="zl-earning-stat"><span>' + (zc.i18n.minutes || 'Minutes sold') + '</span><strong>' + (s.minutes || 0) + '</strong></div>' +
                '<div class="zl-earning-stat"><span>' + (zc.i18n.views || 'Views') + '</span><strong>' + (s.views || 0) + '</strong></div>';
            $('.zl-earnings-stats').html(statsHtml);
            $('.zl-tx-list').html(r.data.html);
        });
    }

    /* ============================================================
     * Listing detail booking
     * ============================================================ */
    function pad(n) { return (n < 10 ? '0' : '') + n; }

    function timesInSlot(slot, duration) {
        var out = [];
        var start = slot.start_time.split(':').map(Number);
        var end = slot.end_time.split(':').map(Number);
        var t = start[0] * 60 + start[1];
        var endM = end[0] * 60 + end[1];
        while (t + duration <= endM) {
            out.push(pad(Math.floor(t / 60)) + ':' + pad(t % 60));
            t += 15;
        }
        return out;
    }

    $(document).on('change', '.zl-booking-date', function () {
        var $form = $(this).closest('.zl-booking-form');
        var $time = $form.find('.zl-booking-time');
        var slots = $('.zl-listing-detail').data('slots') || [];
        var $err = $form.find('.zl-booking-error');
        $err.hide();
        var date = new Date($(this).val() + 'T00:00:00');
        if (isNaN(date.getTime())) {
            $time.html('<option value="">—</option>');
            return;
        }
        var day = date.getDay();
        var duration = parseInt($form.find('.zl-booking-duration').val(), 10) || 15;
        var options = [];
        slots.forEach(function (slot) {
            if (String(slot.day_of_week) !== String(day)) return;
            timesInSlot(slot, duration).forEach(function (t) { options.push(t); });
        });
        var seen = {};
        options = options.filter(function (t) { return seen[t] ? false : (seen[t] = true); });

        var html = '<option value="">' + (zc.i18n.selectTime || 'Select time') + '</option>';
        options.forEach(function (t) { html += '<option value="' + t + '">' + t + '</option>'; });
        $time.html(html);
    });

    $(document).on('change', '.zl-booking-duration', function () {
        var $form = $(this).closest('.zl-booking-form');
        var $date = $form.find('.zl-booking-date');
        if ($date.val()) $date.trigger('change');
        updateTotal($form);
    });

    $(document).on('change', '.zl-booking-time', function () {
        updateTotal($(this).closest('.zl-booking-form'));
    });

    function updateTotal($form) {
        var price = parseFloat($form.data('price') || '0');
        var duration = parseInt($form.find('.zl-booking-duration').val(), 10) || 0;
        var $total = $form.find('.zl-booking-total');
        if (price > 0 && duration > 0) {
            $total.text(money((price * duration).toFixed(2)));
        } else {
            $total.text('');
        }
    }

    $(document).on('click', '.zl-book-btn', function () {
        var $form = $(this).closest('.zl-booking-form');
        var $err = $form.find('.zl-booking-error');
        $err.hide();
        var $listing = $('.zl-listing-detail');
        var date = $form.find('.zl-booking-date').val();
        var time = $form.find('.zl-booking-time').val();
        var duration = parseInt($form.find('.zl-booking-duration').val(), 10) || 0;

        if (!date || !time || !duration) {
            $err.show().find('p').text(zc.i18n.selectTime || 'Please choose a date, time and duration.');
            return;
        }
        var startAt = date + ' ' + time + ':00';
        zlAjax('zeko_love_calls_book', {
            listing_id: $listing.data('listing-id'),
            start_at: startAt,
            duration: duration
        }).done(function (r) {
            if (!r.success) {
                $err.show().find('p').text((r.data && r.data.message) || zc.i18n.error);
                return;
            }
            $form.html('<p class="zl-booking-note">' + r.data.message + '</p>');
            refreshBalance();
            setTimeout(function () { window.location.href = zc.pageUrl + '?tab=bookings'; }, 2500);
        });
    });

    /* ============================================================
     * Init
     * ============================================================ */
    $(function () {
        var activeTab = $('.zl-calls-tabs .zl-tab.active').data('tab') || 'explore';
        $('.zl-calls-panel').hide();
        $('.zl-calls-panel[data-panel="' + activeTab + '"]').show();
        if (activeTab === 'explore') loadExplore();
        if (activeTab === 'my-listings') loadMyListings();
    });
})(jQuery);
