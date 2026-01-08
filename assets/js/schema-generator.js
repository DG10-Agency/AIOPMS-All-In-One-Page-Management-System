jQuery(document).ready(function ($) {
    // Select all functionality
    $('#select-all-pages').on('change', function () {
        $('input[name="selected_pages[]"]').prop('checked', this.checked);
    });

    // Update select all when individual checkboxes change
    $('input[name="selected_pages[]"]').on('change', function () {
        var total = $('input[name="selected_pages[]"]').length;
        var checked = $('input[name="selected_pages[]"]:checked').length;
        $('#select-all-pages').prop('checked', total === checked);
    });
});
