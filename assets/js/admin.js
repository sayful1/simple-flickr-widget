(function ($) {
    "use strict";
    var simpleFlickrWidgetAdmin = $('#simpleFlickrWidgetAdmin');
    var isResponsiveChecked = $('input.is_responsive_checked');
    var noResponsiveColumn = $('.no_responsive_column');
    var responsiveColumns = $('.responsive_columns');

    isResponsiveChecked.on('click', function () {
        var isChecked = $(this).prop('checked');
        if (isChecked) {
            noResponsiveColumn.slideUp();
            responsiveColumns.slideDown();
        } else {
            responsiveColumns.slideUp();
            noResponsiveColumn.slideDown();
        }
    });
})(jQuery);