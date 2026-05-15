(function ($) {
    'use strict';

    // CREATOR_CONFIG is defined inline in creator-home.php
    const AJAX_URL     = CREATOR_CONFIG.ajaxUrl;
    const COMMENTS_URL = CREATOR_CONFIG.commentsUrl;

    // ── 1. Status filter tabs ─────────────────────────────────────────────────
    $('#statusFilter .btn').on('click', function () {
        $('#statusFilter .btn').removeClass('active');
        $(this).addClass('active');
        applyFilters();
    });

    // ── 2. Live search ────────────────────────────────────────────────────────
    $('#postSearch').on('input', applyFilters);

    function applyFilters() {
        const filter = $('#statusFilter .btn.active').data('filter');
        const search = $('#postSearch').val().toLowerCase().trim();
        let visible  = 0;

        $('#postsBody .blog-card').each(function () {
            const ok = ((filter === 'all') || ($(this).data('status') === filter))
                    && ((search === '')     || ($(this).data('title') || '').includes(search));
            $(this).toggle(ok);
            if (ok) visible++;
        });

        $('#noResults').toggleClass('d-none', visible > 0);
    }

    // ── 3. Update filter badge counts ─────────────────────────────────────────
    function updateFilterCounts() {
        const total     = $('#postsBody .blog-card').length;
        const published = $('#postsBody .blog-card[data-status="published"]').length;
        const drafts    = $('#postsBody .blog-card[data-status="draft"]').length;

        $('[data-filter="all"]       .badge').text(total);
        $('[data-filter="published"] .badge').text(published);
        $('[data-filter="draft"]     .badge').text(drafts);
    }

    // ── 4. AJAX status toggle ─────────────────────────────────────────────────
    $(document).on('click', '.toggle-status-btn', function () {
        const $btn      = $(this);
        const postId    = $btn.data('post-id');
        const current   = $btn.data('status');
        const newStatus = current === 'published' ? 'draft' : 'published';

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post(AJAX_URL, { post_id: postId, new_status: newStatus }, function (res) {
            if (res.success) {
                const $badge = $('#badge-' + postId);
                $badge.removeClass('gulfguide-badge--success gulfguide-badge--warning')
                      .addClass(newStatus === 'published' ? 'gulfguide-badge--success' : 'gulfguide-badge--warning');
                $('#badge-icon-' + postId).attr('class', 'ph-fill ' + (newStatus === 'published' ? 'ph-check-circle' : 'ph-clock'));
                $('#badge-text-' + postId).text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));

                $btn.closest('.blog-card').data('status', newStatus).attr('data-status', newStatus);

                const newIcon  = newStatus === 'published' ? 'ph-arrow-counter-clockwise' : 'ph-paper-plane-tilt';
                const newLabel = newStatus === 'published' ? 'Unpublish' : 'Publish';
                $btn.data('status', newStatus).prop('disabled', false)
                    .html('<i class="ph ' + newIcon + '"></i> ' + newLabel);

                applyFilters();
                updateFilterCounts();
                Swal.fire({ icon: 'success', title: 'Updated', text: 'Post is now ' + newStatus + '.', timer: 1600, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not update.' });
                const origIcon  = current === 'published' ? 'ph-arrow-counter-clockwise' : 'ph-paper-plane-tilt';
                const origLabel = current === 'published' ? 'Unpublish' : 'Publish';
                $btn.prop('disabled', false).html('<i class="ph ' + origIcon + '"></i> ' + origLabel);
            }
        }, 'json').fail(function () {
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Please try again.' });
            const origIcon  = current === 'published' ? 'ph-arrow-counter-clockwise' : 'ph-paper-plane-tilt';
            const origLabel = current === 'published' ? 'Unpublish' : 'Publish';
            $btn.prop('disabled', false).html('<i class="ph ' + origIcon + '"></i> ' + origLabel);
        });
    });

    // ── 5. View comments (AJAX) ───────────────────────────────────────────────
    $(document).on('click', '.view-comments-btn', function () {
        const $btn   = $(this);
        const postId = $btn.data('post-id');
        const $panel = $('#comments-panel-' + postId);
        const $body  = $('#comments-body-'  + postId);

        if ($panel.hasClass('d-none')) {
            $panel.removeClass('d-none');
            if (!$panel.data('loaded')) {
                $.get(COMMENTS_URL, { post_id: postId }, function (html) {
                    $body.html(html);
                    $panel.data('loaded', true);
                }).fail(function () {
                    $body.html('<p class="text-danger text-center">Could not load comments.</p>');
                });
            }
        } else {
            $panel.addClass('d-none');
        }
    });

    // ── 6. Delete confirmation ────────────────────────────────────────────────
    $(document).on('submit', '.delete-post-form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const title = $form.find('input[name="post_title"]').val();
        Swal.fire({
            title: 'Delete Post',
            text: 'Are you sure you want to delete "' + title + '"?',
            icon: 'warning',
            showCancelButton:   true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor:  '#6c757d',
            confirmButtonText:  'Yes, delete it',
            cancelButtonText:   'Cancel'
        }).then(function (r) { if (r.isConfirmed) $form[0].submit(); });
    });

    // ── 7. Flash message on load ──────────────────────────────────────────────
    if (CREATOR_CONFIG.flash) {
        $(function () {
            Swal.fire({
                icon:  CREATOR_CONFIG.flash.code === 'success' ? 'success' : 'error',
                title: CREATOR_CONFIG.flash.code === 'success' ? 'Success' : 'Error',
                text:  CREATOR_CONFIG.flash.msg,
                timer: 2500,
                showConfirmButton: false
            });
        });
    }

})(jQuery);
