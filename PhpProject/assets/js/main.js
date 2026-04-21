$(document).ready(function() {
    'use strict';
    const $sidebar = $('.sidebar');
    const $topbar = $('.topbar');
    const $toggleBtn = $('.menu-toggle');
    const $main = $('.main');

        $toggleBtn.on('click', function(e) {
        e.stopPropagation();
        $sidebar.toggleClass('show');
    });
    
    $(document).on('click', function(e) {
        if (!$sidebar.is(e.target) && 
            !$sidebar.has(e.target).length && 
            !$toggleBtn.is(e.target) && 
            !$toggleBtn.has(e.target).length) {
            
            if ($(window).width() < 992) {
                $sidebar.removeClass('show');
            }
        }
    });
    
    $(window).on('resize', function() {
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

    
});