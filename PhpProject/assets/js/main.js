$(document).ready(function () {
    'use strict';
    const $sidebar = $('.sidebar');
    const $topbar = $('.topbar');
    const $toggleBtn = $('.menu-toggle');
    const $main = $('.main');

    $toggleBtn.on('click', function (e) {
        e.stopPropagation();
        $sidebar.toggleClass('show');
    });

    $(document).on('click', function (e) {
        if (!$sidebar.is(e.target) &&
                !$sidebar.has(e.target).length &&
                !$toggleBtn.is(e.target) &&
                !$toggleBtn.has(e.target).length) {

            if ($(window).width() < 992) {
                $sidebar.removeClass('show');
            }
        }
    });

    $(window).on('resize', function () {
        if ($(window).width() >= 992) {
            $sidebar.removeClass('show');
        }
    });

    setActiveMenuItem();

    function setActiveMenuItem() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        $('a[href*="' + currentPage + '"]').closest('.sidebar-nav-item').find('> a').addClass('active');
        $('a[href*="' + currentPage + '"]').closest('.sidebar-submenu').slideDown(0).show();
    }

    tinymce.init({
        selector: '#richtext-editor',
        plugins: [
            // Core editing features
            'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount',
            // Your account includes a free trial of TinyMCE premium features
            // Try the most popular premium features until Jun 13, 2026:
            'checklist', 'mediaembed', 'casechange', 'formatpainter', 'pageembed', 'a11ychecker', 'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'advtemplate', 'tinymceai', 'uploadcare', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags', 'autocorrect', 'typography', 'inlinecss', 'markdown', 'importword', 'exportword', 'exportpdf'
        ],
        toolbar: 'undo redo | tinymceai-chat tinymceai-quickactions tinymceai-review | blocks fontfamily fontsize | bold italic underline strikethrough | link media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography uploadcare | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
        tinycomments_mode: 'embedded',
        tinycomments_author: 'Author name',
        mergetags_list: [
            {value: 'First.Name', title: 'First Name'},
            {value: 'Email', title: 'Email'},
        ],
        tinymceai_token_provider: async () => {
            await fetch(`https://demo.api.tiny.cloud/1/5gfsvuofq84ucr8sxmw7pvpqf446jhe746wgmsy22wxydhu4/auth/random`, {method: "POST", credentials: "include"});
            return {token: await fetch(`https://demo.api.tiny.cloud/1/5gfsvuofq84ucr8sxmw7pvpqf446jhe746wgmsy22wxydhu4/jwt/tinymceai`, {credentials: "include"}).then(r => r.text())};
        },
        uploadcare_public_key: '602d115d77d3dd69fd16',
    });
    
    $('#advancedSearchToggle').on('change', function() {
        if ($(this).is(':checked')) {
            $('#advancedFiltersSection').removeClass('d-none');
        } else {
            $('#advancedFiltersSection').addClass('d-none');
            $('#country_id').val('');
            $('#date_from').val('');
            $('#date_to').val('');
        }
    });
});