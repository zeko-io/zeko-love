(function ($) {
    'use strict';

    var zl = {
        ajaxUrl: zekoLove.ajaxUrl,
        nonce: zekoLove.nonce,
        i18n: zekoLove.i18n || {}
    };

    function zlAjax(action, data, $btn) {
        if ($btn && $btn.length) {
            $btn.prop('disabled', true).addClass('zl-loading-btn');
        }
        return $.post(zl.ajaxUrl, $.extend({
            action: action,
            _wpnonce: zl.nonce
        }, data)).fail(function (r) {
            var msg = zl.i18n.error || 'Something went wrong.';
            if (r.responseJSON && r.responseJSON.data && r.responseJSON.data.message) {
                msg = r.responseJSON.data.message;
            }
            showToast('error', msg);
        }).always(function () {
            if ($btn && $btn.length) {
                $btn.prop('disabled', false).removeClass('zl-loading-btn');
            }
        });
    }

    function showNotice($el, type, msg) {
        var cls = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="zl-notice notice ' + cls + ' inline"><p>' + msg + '</p></div>');
        $el.prepend($notice);
        setTimeout(function () {
            $notice.fadeOut(300, function () { $notice.remove(); });
        }, 4000);
    }

    function showToast(type, msg) {
        var icon = type === 'success' ? '\u2713' : type === 'error' ? '\u2717' : '\u2139';
        var bg = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#6b7280';
        var $toast = $('<div class="zl-toast" style="position:fixed;top:20px;right:20px;z-index:1000000;background:' + bg + ';color:#fff;padding:12px 20px;border-radius:10px;font-size:14px;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,0.2);display:flex;align-items:center;gap:8px;opacity:0;transform:translateY(-10px);transition:all 0.3s ease">' +
            '<span>' + icon + '</span> <span>' + msg + '</span></div>');
        $('body').append($toast);
        setTimeout(function () {
            $toast.css({ opacity: 1, transform: 'translateY(0)' });
        }, 10);
        setTimeout(function () {
            $toast.css({ opacity: 0, transform: 'translateY(-10px)' });
            setTimeout(function () { $toast.remove(); }, 300);
        }, 4000);
    }

    function showModal(title, contentHtml) {
        var $overlay = $('<div class="zl-modal-overlay"></div>');
        var $modal = $('<div class="zl-modal">' +
            '<div class="zl-modal-header">' +
            '<h3>' + title + '</h3>' +
            '<button type="button" class="zl-modal-close">&times;</button>' +
            '</div>' +
            '<div class="zl-modal-body">' + contentHtml + '</div>' +
            '</div>');
        $overlay.append($modal).appendTo('body');

        $overlay.on('click', function (e) {
            if (e.target === $overlay[0]) $overlay.remove();
        });
        $modal.find('.zl-modal-close').on('click', function () {
            $overlay.remove();
        });
        return $modal;
    }

    /* ---------- Profile Save ---------- */
    $(document).on('click', '.zl-save-profile', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $form = $btn.closest('form');
        var data = {};

        $form.find('input, select, textarea').each(function () {
            var $el = $(this);
            var name = $el.attr('name');
            if (!name || name === 'action' || name.indexOf('_nonce') !== -1 || name.indexOf('_wpnonce') !== -1) return;
            if ($el.is(':checkbox') || $el.is(':radio')) {
                if ($el.is(':checked')) {
                    if (name.slice(-2) === '[]') {
                        var arrName = name.slice(0, -2);
                        if (!data[arrName]) data[arrName] = [];
                        data[arrName].push($el.val());
                    } else {
                        data[name] = $el.val();
                    }
                }
            } else {
                data[name] = $el.val();
            }
        });

        zlAjax('zeko_love_save_profile', { fields: data }, $btn).done(function (r) {
            if (r.success) {
                showNotice($form, 'success', r.data.message || 'Profile saved.');
            } else {
                showNotice($form, 'error', r.data.message || 'Save failed.');
            }
        });
    });

    /* ---------- Interest Tags ---------- */
    $(document).on('click', '.zl-tag', function () {
        var $tag = $(this);
        $tag.toggleClass('zl-tag-selected');
        var $container = $tag.closest('.zl-tags-container');
        var $hidden = $container.find('.zl-tags-input');
        if (!$hidden.length) {
            $hidden = $('<input type="hidden" class="zl-tags-input" name="interests" value="[]">');
            $container.append($hidden);
        }
        var selected = [];
        $container.find('.zl-tag.zl-tag-selected').each(function () {
            selected.push($(this).data('value') || $(this).text().trim());
        });
        $hidden.val(JSON.stringify(selected));
    });

    /* ---------- Photo Upload ---------- */
    $(document).on('click', '.zl-upload-area', function () {
        var $area = $(this);
        if ($area.find('input[type="file"]').length) return;
        var $input = $('<input type="file" accept="image/*" multiple style="display:none">');
        $area.append($input);
        $input.trigger('click');
        $input.on('change', function () {
            var files = this.files;
            if (!files.length) return;
            var fd = new FormData();
            fd.append('action', 'zeko_love_upload_photo');
            fd.append('_wpnonce', zl.nonce);
            $.each(files, function (i, f) {
                fd.append('photos[]', f);
            });
            var $grid = $area.closest('.zl-photo-upload').find('.zl-photo-grid');
            if ($grid.length) {
                $grid.html('<div class="zl-loading-overlay"><div class="zl-loading"></div></div>');
            }
            $.ajax({
                url: zl.ajaxUrl,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false
            }).done(function (r) {
                if (r.success && r.data.html) {
                    $grid.html(r.data.html);
                } else {
                    showToast('error', r.data.message || 'Upload failed.');
                }
            }).fail(function () {
                showToast('error', zl.i18n.error || 'Upload failed.');
            });
        });
    });

    /* Drag-drop on upload area */
    $(document).on('dragover', '.zl-upload-area', function (e) {
        e.preventDefault();
        $(this).addClass('zl-dragover');
    });
    $(document).on('dragleave', '.zl-upload-area', function (e) {
        e.preventDefault();
        $(this).removeClass('zl-dragover');
    });
    $(document).on('drop', '.zl-upload-area', function (e) {
        e.preventDefault();
        var $area = $(this).removeClass('zl-dragover');
        var files = e.originalEvent.dataTransfer.files;
        if (!files.length) return;
        var fd = new FormData();
        fd.append('action', 'zeko_love_upload_photo');
        fd.append('_wpnonce', zl.nonce);
        $.each(files, function (i, f) {
            fd.append('photos[]', f);
        });
        var $grid = $area.closest('.zl-photo-upload').find('.zl-photo-grid');
        if ($grid.length) {
            $grid.html('<div class="zl-loading-overlay"><div class="zl-loading"></div></div>');
        }
        $.ajax({
            url: zl.ajaxUrl,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false
        }).done(function (r) {
            if (r.success && r.data.html) {
                $grid.html(r.data.html);
            } else {
                showToast('error', r.data.message || 'Upload failed.');
            }
        }).fail(function () {
            showToast('error', zl.i18n.error || 'Upload failed.');
        });
    });

    /* ---------- Photo Delete / Set Primary ---------- */
    $(document).on('click', '.zl-delete-photo', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var photoId = $btn.data('photo-id');
        if (!confirm(zl.i18n.confirm || 'Are you sure?')) return;
        zlAjax('zeko_love_delete_photo', { photo_id: photoId }, $btn).done(function (r) {
            if (r.success && r.data.html) {
                $btn.closest('.zl-photo-grid').html(r.data.html);
            } else {
                showToast('error', r.data.message || 'Delete failed.');
            }
        });
    });

    $(document).on('click', '.zl-set-primary', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var photoId = $btn.data('photo-id');
        zlAjax('zeko_love_set_primary_photo', { photo_id: photoId }, $btn).done(function (r) {
            if (r.success && r.data.html) {
                $btn.closest('.zl-photo-grid').html(r.data.html);
                showToast('success', r.data.message || 'Primary photo updated.');
            } else {
                showToast('error', r.data.message || 'Update failed.');
            }
        });
    });

    /* ---------- Browse / Search ---------- */
    var searchTimeout;
    var browsePage = 1;

    function renderPagination(page, pages) {
        var $nav = $('.zl-pagination');
        if (!$nav.length) return;
        if (pages <= 1) {
            $nav.html('');
            return;
        }
        var html = '';
        for (var i = 1; i <= pages; i++) {
            if (i === page) {
                html += '<span class="current">' + i + '</span>';
            } else {
                html += '<a href="#" data-page="' + i + '">' + i + '</a>';
            }
        }
        $nav.html(html);
    }

    function loadProfiles() {
        var $grid = $('.zl-browse-results');
        if (!$grid.length) return;
        var filters = {};
        $('.zl-search-filters input, .zl-search-filters select').each(function () {
            var $el = $(this);
            var name = $el.attr('name');
            if (name && $el.val() !== '') {
                filters[name] = $el.val();
            }
        });
        if (filters.name) {
            filters.search = filters.name;
            delete filters.name;
        }
        $grid.html('<div class="zl-loading-overlay"><div class="zl-loading"></div></div>');
        zlAjax('zeko_love_search_profiles', { filters: JSON.stringify(filters), page: browsePage }).done(function (r) {
            if (r.success && r.data.html) {
                $grid.html(r.data.html);
                renderPagination(r.data.page || 1, r.data.pages || 1);
            } else {
                $grid.html('<div class="zl-empty-state"><span class="dashicons dashicons-heart"></span><h3>' + (zl.i18n.noResults || 'No profiles found.') + '</h3></div>');
                renderPagination(1, 1);
            }
        }).fail(function () {
            $grid.html('<div class="zl-empty-state"><span class="dashicons dashicons-warning"></span><h3>' + (zl.i18n.error || 'Error loading profiles.') + '</h3></div>');
        });
    }

    $(document).on('input change', '.zl-search-filters input, .zl-search-filters select', function () {
        clearTimeout(searchTimeout);
        browsePage = 1;
        searchTimeout = setTimeout(loadProfiles, 300);
    });

    $(document).on('click', '.zl-search-btn', function (e) {
        e.preventDefault();
        browsePage = 1;
        loadProfiles();
    });

    $(document).on('click', '.zl-pagination a', function (e) {
        e.preventDefault();
        browsePage = parseInt($(this).data('page'), 10) || 1;
        loadProfiles();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    if ($('.zl-browse-results').length) {
        loadProfiles();
    }

    /* ---------- Like / Pass / Super Like ---------- */
    $(document).on('click', '.zl-like-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var profileId = $btn.data('profile-id');
        var $card = $btn.closest('.zl-profile-card');
        zlAjax('zeko_love_like_user', { profile_id: profileId }, $btn).done(function (r) {
            if (r.success) {
                $card.fadeOut(300, function () { $card.remove(); });
                showToast('success', zl.i18n.liked || 'Liked!');
                if (r.data && r.data.match) {
                    showModal(zl.i18n.itsAMatch || "It's a Match!", r.data.matchHtml || '<p>' + (zl.i18n.matchMessage || 'You matched!') + '</p>');
                }
            } else {
                showToast('error', r.data.message || 'Action failed.');
            }
        });
    });

    $(document).on('click', '.zl-pass-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var profileId = $btn.data('profile-id');
        var $card = $btn.closest('.zl-profile-card');
        zlAjax('zeko_love_pass_user', { profile_id: profileId }, $btn).done(function (r) {
            if (r.success) {
                $card.fadeOut(300, function () { $card.remove(); });
                showToast('info', zl.i18n.passed || 'Passed.');
            } else {
                showToast('error', r.data.message || 'Action failed.');
            }
        });
    });

    $(document).on('click', '.zl-superlike-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var profileId = $btn.data('profile-id');
        var $card = $btn.closest('.zl-profile-card');
        zlAjax('zeko_love_super_like_user', { profile_id: profileId }, $btn).done(function (r) {
            if (r.success) {
                $card.fadeOut(300, function () { $card.remove(); });
                showToast('success', zl.i18n.superLiked || 'Super Liked!');
                if (r.data && r.data.match) {
                    showModal(zl.i18n.itsAMatch || "It's a Match!", r.data.matchHtml || '<p>' + (zl.i18n.matchMessage || 'You matched!') + '</p>');
                }
            } else {
                showToast('error', r.data.message || 'Action failed.');
            }
        });
    });

    /* ---------- Matches Page ---------- */
    function loadMatches() {
        var $container = $('.zl-matches-container');
        if (!$container.length) return;
        var tab = $container.find('.zl-matches-tabs .nav-tab-active').data('tab') || 'matches';
        $container.find('.zl-matches-grid').html('<div class="zl-loading-overlay"><div class="zl-loading"></div></div>');
        zlAjax('zeko_love_get_matches', { tab: tab }).done(function (r) {
            if (r.success && r.data.html) {
                $container.find('.zl-matches-grid').html(r.data.html);
            } else {
                $container.find('.zl-matches-grid').html('<div class="zl-empty-state"><span class="dashicons dashicons-heart"></span><h3>' + (zl.i18n.noMatches || 'No matches yet.') + '</h3></div>');
            }
        });
    }

    $(document).on('click', '.zl-matches-tabs .nav-tab', function (e) {
        e.preventDefault();
        var $tab = $(this);
        $tab.closest('.zl-matches-tabs').find('.nav-tab').removeClass('nav-tab-active');
        $tab.addClass('nav-tab-active');
        loadMatches();
    });

    if ($('.zl-matches-container').length) {
        loadMatches();
    }

    /* Accept match button on matches */
    $(document).on('click', '.zl-accept-match', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var matchId = $btn.data('match-id');
        zlAjax('zeko_love_accept_match', { match_id: matchId }, $btn).done(function (r) {
            if (r.success) {
                showToast('success', r.data.message || 'Match accepted!');
                loadMatches();
            } else {
                showToast('error', r.data.message || 'Failed to accept.');
            }
        });
    });

    /* ---------- Messages ---------- */
    function loadConversations() {
        var $sidebar = $('.zl-conversations');
        if (!$sidebar.length) return;
        $sidebar.html('<div class="zl-loading-overlay"><div class="zl-loading"></div></div>');
        zlAjax('zeko_love_get_conversations', {}).done(function (r) {
            if (r.success && r.data.html) {
                $sidebar.html(r.data.html);
                var $messages = $('.zl-messages');
                var openConv = $messages.data('open-conv');
                if (openConv) {
                    $sidebar.find('.zl-conv-item').removeClass('active');
                    $sidebar.find('.zl-conv-item[data-conv-id="' + openConv + '"]').addClass('active');
                    loadThread(openConv);
                }
            } else {
                $sidebar.html('<div class="zl-empty-state"><p>' + (zl.i18n.noConversations || 'No conversations.') + '</p></div>');
            }
        });
    }

    function loadThread(convId) {
        var $thread = $('.zl-thread');
        if (!$thread.length) return;
        $thread.html('<div class="zl-loading-overlay"><div class="zl-loading"></div></div>');
        zlAjax('zeko_love_get_thread', { conversation_id: convId }).done(function (r) {
            if (r.success && r.data.html) {
                $thread.html(r.data.html);
                var $list = $thread.find('.zl-message-list');
                if ($list.length) {
                    $list.scrollTop($list[0].scrollHeight);
                }
                $thread.data('conv-id', convId);
                $thread.data('other-name', r.data.other_name || '');
            } else {
                $thread.html('<div class="zl-empty-state"><p>' + (zl.i18n.noMessages || 'No messages.') + '</p></div>');
            }
        });
    }

    $(document).on('click', '.zl-conv-item', function () {
        var $item = $(this);
        $item.closest('.zl-conversations').find('.zl-conv-item').removeClass('active');
        $item.addClass('active');
        loadThread($item.data('conv-id'));
    });

    function sendMessage($textarea) {
        var msg = $textarea.val().trim();
        if (!msg) return;
        var $thread = $('.zl-thread');
        var convId = $thread.data('conv-id');
        if (!convId) {
            showToast('error', zl.i18n.error || 'No conversation selected.');
            return;
        }
        var $form = $textarea.closest('.zl-send-form');
        var $btn = $form.find('.zl-send-btn');
        zlAjax('zeko_love_send_message', { conversation_id: convId, message: msg }, $btn).done(function (r) {
            if (r.success && r.data.html) {
                var $list = $thread.find('.zl-message-list');
                if ($list.length) {
                    $list.append(r.data.html);
                    $list.scrollTop($list[0].scrollHeight);
                } else {
                    $thread.append(r.data.html);
                }
                $textarea.val('');
            } else {
                showToast('error', r.data.message || 'Send failed.');
            }
        });
    }

    $(document).on('keydown', '.zl-send-form textarea', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage($(this));
        }
    });

    $(document).on('click', '.zl-send-btn', function (e) {
        e.preventDefault();
        var $textarea = $(this).closest('.zl-send-form').find('textarea');
        if ($textarea.length) {
            sendMessage($textarea);
        }
    });

    $(document).on('click', '.zl-icebreaker-chip', function () {
        var $thread = $(this).closest('.zl-thread');
        var $textarea = $thread.find('.zl-send-form textarea');
        if (!$textarea.length) return;
        var otherName = $thread.data('other-name') || '';
        var text = $(this).data('icebreaker') || '';
        $textarea.val((otherName ? 'Hey ' + otherName + '! ' : '') + text).focus();
    });

    if ($('.zl-conversations').length) {
        loadConversations();
    }

    /* ---------- Dates ---------- */
    function loadDates(tab) {
        var $container = $('.zl-dates-container');
        if (!$container.length) return;
        tab = tab || 'upcoming';
        $container.find('.zl-dates-list').html('<div class="zl-loading-overlay"><div class="zl-loading"></div></div>');
        zlAjax('zeko_love_get_dates', { tab: tab }).done(function (r) {
            if (r.success && r.data.html) {
                $container.find('.zl-dates-list').html(r.data.html);
            } else {
                $container.find('.zl-dates-list').html('<div class="zl-empty-state"><span class="dashicons dashicons-calendar"></span><h3>' + (zl.i18n.noDates || 'No dates scheduled.') + '</h3></div>');
            }
        });
    }

    $(document).on('click', '.zl-dates-tabs .nav-tab', function (e) {
        e.preventDefault();
        var $tab = $(this);
        $tab.closest('.zl-dates-tabs').find('.nav-tab').removeClass('nav-tab-active');
        $tab.addClass('nav-tab-active');
        loadDates($tab.data('tab'));
    });

    $(document).on('click', '.zl-date-accept', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var dateId = $btn.data('date-id');
        zlAjax('zeko_love_respond_to_date', { date_id: dateId, status: 'confirmed' }, $btn).done(function (r) {
            if (r.success) {
                showToast('success', r.data.message || 'Date confirmed!');
                loadDates($('.zl-dates-tabs .nav-tab-active').data('tab'));
            } else {
                showToast('error', r.data.message || 'Failed to confirm.');
            }
        });
    });

    $(document).on('click', '.zl-date-cancel', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var dateId = $btn.data('date-id');
        if (!confirm(zl.i18n.confirmCancel || 'Cancel this date?')) return;
        zlAjax('zeko_love_respond_to_date', { date_id: dateId, status: 'cancelled' }, $btn).done(function (r) {
            if (r.success) {
                showToast('success', r.data.message || 'Date cancelled.');
                loadDates($('.zl-dates-tabs .nav-tab-active').data('tab'));
            } else {
                showToast('error', r.data.message || 'Failed to cancel.');
            }
        });
    });

    $(document).on('click', '.zl-propose-date', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var matchId = $btn.data('match-id');
        var html = '<form class="zl-propose-form">' +
            '<div class="zl-form-group"><label>' + (zl.i18n.date || 'Date') + '</label><input type="date" name="date" class="zl-input" required></div>' +
            '<div class="zl-form-group"><label>' + (zl.i18n.time || 'Time') + '</label><input type="time" name="time" class="zl-input" required></div>' +
            '<div class="zl-form-group"><label>' + (zl.i18n.location || 'Location') + '</label><input type="text" name="location" class="zl-input"></div>' +
            '<div class="zl-form-group"><label>' + (zl.i18n.notes || 'Notes') + '</label><textarea name="notes" class="zl-input" rows="3"></textarea></div>' +
            '<div class="zl-form-group"><button type="submit" class="zl-btn zl-btn-primary">' + (zl.i18n.send || 'Send') + '</button></div>' +
            '</form>';
        var $modal = showModal(zl.i18n.proposeDate || 'Propose a Date', html);
        $modal.find('.zl-propose-form').on('submit', function (e) {
            e.preventDefault();
            var data = { match_id: matchId };
            $(this).find('input, textarea').each(function () {
                var $el = $(this);
                data[$el.attr('name')] = $el.val();
            });
            zlAjax('zeko_love_propose_date', data, $modal.find('.zl-btn-primary')).done(function (r) {
                if (r.success) {
                    $modal.closest('.zl-modal-overlay').remove();
                    showToast('success', r.data.message || 'Date proposed!');
                    loadDates($('.zl-dates-tabs .nav-tab-active').data('tab'));
                } else {
                    showToast('error', r.data.message || 'Failed to propose.');
                }
            });
        });
    });

    if ($('.zl-dates-container').length) {
        loadDates('upcoming');
    }

    /* ---------- Settings ---------- */
    $(document).on('click', '.zl-save-settings', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $form = $btn.closest('form');
        var data = {};
        $form.find('input, select, textarea').each(function () {
            var $el = $(this);
            var name = $el.attr('name');
            if (!name) return;
            if ($el.is(':checkbox') || $el.is(':radio')) {
                if ($el.is(':checked')) {
                    if (name.slice(-2) === '[]') {
                        var arrName = name.slice(0, -2);
                        if (!data[arrName]) data[arrName] = [];
                        data[arrName].push($el.val());
                    } else {
                        data[name] = $el.val();
                    }
                }
            } else {
                data[name] = $el.val();
            }
        });
        zlAjax('zeko_love_save_settings', { fields: data }, $btn).done(function (r) {
            if (r.success) {
                showNotice($form, 'success', r.data.message || 'Settings saved.');
            } else {
                showNotice($form, 'error', r.data.message || 'Save failed.');
            }
        });
    });

    /* Load settings */
    var $settingsForms = $('.zl-settings-form');
    if ($settingsForms.length) {
        zlAjax('zeko_love_get_settings', {}).done(function (r) {
            if (r.success && r.data.fields) {
                $settingsForms.each(function () {
                    var $form = $(this);
                    $.each(r.data.fields, function (key, val) {
                        var $field = $form.find('[name="' + key + '"]');
                        if ($field.length) {
                            if ($field.is(':checkbox') || $field.is(':radio')) {
                                $field.filter('[value="' + val + '"]').prop('checked', true);
                            } else {
                                $field.val(val);
                            }
                        }
                    });
                });
            }
        });
    }

    /* ---------- Profile Boost ---------- */
    $(document).on('click', '.zl-boost-profile', function (e) {
        e.preventDefault();
        var $btn = $(this);
        if (!confirm(zl.i18n.confirmBoost || 'Boost your profile? This will charge your wallet.')) return;
        $btn.prop('disabled', true).addClass('zl-btn-loading');
        zlAjax('zeko_love_boost_profile', {}, $btn).done(function (r) {
            if (r.success) {
                showToast('success', r.data.message || 'Profile boosted!');
                $btn.closest('.zl-card').replaceWith(
                    '<div class="zl-card zl-boost-card"><h2 class="zl-card-title">' + (zl.i18n.boostTitle || 'Profile Boost') + '</h2>' +
                    '<p class="zl-boost-status zl-boost-active"><span class="dashicons dashicons-star-filled"></span>' + (zl.i18n.boosted || 'Your profile is boosted!') + '</p></div>'
                );
            } else {
                showToast('error', r.data.message || 'Failed to boost.');
                $btn.prop('disabled', false).removeClass('zl-btn-loading');
            }
        }).fail(function () {
            $btn.prop('disabled', false).removeClass('zl-btn-loading');
        });
    });

    /* ---------- Credit Gifts ---------- */
    $(document).on('click', '.zl-gift-btn', function () {
        var profileId = $(this).data('profile-id');
        if (!profileId) return;
        var $modal = showModal('Send a Gift', '' +
            '<p class="zl-gift-hint">Send wallet credits to this member. They receive the full amount in their wallet.</p>' +
            '<div class="zl-form-group"><label>Amount (' + (zekoLove.currency || 'USD') + ')</label>' +
            '<input type="number" class="zl-input zl-gift-amount" min="0.01" step="0.01" placeholder="0.00" required></div>' +
            '<div class="zl-form-group"><label>Message (optional)</label>' +
            '<textarea class="zl-input zl-gift-message" rows="2" placeholder="A little something for you..."></textarea></div>' +
            '<div class="zl-gift-error zl-notice notice-error inline" style="display:none;margin:0 0 10px;"><p></p></div>' +
            '<div class="zl-actions" style="padding:0;"><button type="button" class="zl-btn zl-btn-primary zl-gift-submit" style="flex:none;width:100%;">Send Gift</button></div>');

        $modal.find('.zl-gift-submit').on('click', function () {
            var $btn = $(this);
            var amount = parseFloat($modal.find('.zl-gift-amount').val());
            if (!amount || amount <= 0) {
                $modal.find('.zl-gift-error').show().find('p').text('Please enter a valid amount.');
                return;
            }
            var message = $modal.find('.zl-gift-message').val();
            zlAjax('zeko_love_send_gift', { profile_id: profileId, amount: amount, message: message }, $btn).done(function (r) {
                if (r.success) {
                    $modal.closest('.zl-modal-overlay').remove();
                    showToast('success', r.data.message || 'Gift sent!');
                } else {
                    $modal.find('.zl-gift-error').show().find('p').text(r.data.message || 'Gift failed.');
                }
            });
        });
    });

    /* ---------- Settings Hub Tabs ---------- */
    $(document).on('click', '.zl-settings-tab', function () {
        var $tab = $(this);
        var tab = $tab.data('tab');
        $('.zl-settings-tab').removeClass('zl-settings-tab-active');
        $tab.addClass('zl-settings-tab-active');
        $('.zl-settings-panel').removeClass('zl-settings-panel-active');
        $('#zl-tab-' + tab).addClass('zl-settings-panel-active');
    });

    /* ---------- Payout method toggle ---------- */
    $(document).on('change', '.zl-payout-method', function () {
        var method = $(this).val();
        $('.zl-payout-group').each(function () {
            var $group = $(this);
            if ($group.data('method') === method) {
                $group.show();
            } else {
                $group.hide();
            }
        });
    });
    $(document).ready(function () {
        var $method = $('.zl-payout-method');
        if ($method.length) {
            $method.trigger('change');
        }
    });

    /* ---------- Save Payout Method ---------- */
    $(document).on('click', '.zl-save-payout', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $form = $btn.closest('form');
        var data = {};

        $form.find('input, select, textarea').each(function () {
            var $el = $(this);
            var name = $el.attr('name');
            if (!name || name === 'action' || name.indexOf('_nonce') !== -1) return;
            data[name] = $el.is(':checkbox') || $el.is(':radio') ? ($el.is(':checked') ? $el.val() : '') : $el.val();
        });

        zlAjax('zeko_love_save_payout', { fields: data }, $btn).done(function (r) {
            if (r.success) {
                showNotice($form, 'success', r.data.message || 'Payout method saved.');
            } else {
                showNotice($form, 'error', r.data.message || 'Save failed.');
            }
        });
    });

    /* ---------- Withdraw Balance ---------- */
    var zlOtpThreshold = 0;
    if (typeof zekoLove !== 'undefined') {
        zlOtpThreshold = parseFloat(zekoLove.withdraw_otp_threshold) || 0;
    }

    function zlUpdateOtpRow($form) {
        var $otp = $form.find('[data-zl-otp]');
        if (!$otp.length) return;
        var amt = parseFloat($form.find('[name="amount"]').val()) || 0;
        $otp.prop('hidden', !(zlOtpThreshold > 0 && amt >= zlOtpThreshold));
    }

    $(document).on('input change', '#zl-withdraw-amount', function () {
        zlUpdateOtpRow($(this).closest('form'));
    });

    $(document).on('click', '[data-zl-otp-send]', function () {
        var $btn = $(this);
        var $form = $btn.closest('form');
        var amount = $form.find('[name="amount"]').val();
        var $status = $form.find('.zl-otp-status');
        if (!amount || parseFloat(amount) <= 0) {
            if ($status.length) $status.text('Enter a valid amount.');
            return;
        }
        $btn.prop('disabled', true);
        if ($status.length) $status.text('Sending...');
        zlAjax('zeko_love_send_withdrawal_otp', { fields: { amount: amount } }, $btn).always(function () {
            $btn.prop('disabled', false);
        }).done(function (r) {
            if ($status.length) {
                var msg = r.data && r.data.message ? r.data.message : (r.message || '');
                $status.text(msg || (r.success ? 'Code sent. Check your email.' : 'Could not send a code right now.'));
            }
        }).fail(function () {
            if ($status.length) $status.text('Could not send a code right now.');
        });
    });

    $(document).on('click', '.zl-withdraw-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $form = $btn.closest('form');
        var amount = parseFloat($form.find('[name="amount"]').val());
        if (!amount || amount <= 0) {
            showNotice($form, 'error', 'Enter a valid amount.');
            return;
        }

        var payload = { fields: { amount: amount } };
        var otp = $form.find('[name="otp_code"]').val();
        if (otp) payload.fields.otp_code = otp;

        zlAjax('zeko_love_withdraw_funds', payload, $btn).done(function (r) {
            if (r.success) {
                showNotice($form, 'success', r.data.message || 'Withdrawal requested.');
                $form.find('[name="amount"]').val('');
                $form.find('[name="otp_code"]').val('');
                zlUpdateOtpRow($form);
            } else {
                showNotice($form, 'error', r.data.message || 'Withdrawal failed.');
            }
        });
    });

    zlUpdateOtpRow($('.zl-withdraw-btn').closest('form'));

    /* ---------- Unblock User ---------- */
    $(document).on('click', '.zl-unblock-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var targetId = $btn.data('id');
        if (!targetId) return;

        zlAjax('zeko_love_unblock_user', { target_id: targetId }, $btn).done(function (r) {
            if (r.success) {
                $btn.closest('.zl-blocked-item').remove();
                showToast('success', r.data.message || 'User unblocked.');
            } else {
                showToast('error', r.data.message || 'Unblock failed.');
            }
        });
    });

})(jQuery);
