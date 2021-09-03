$(document).ready(function () {
    $("#div-lift-fields #main-ui-square-container").on('DOMSubtreeModified', function () {
        var resultFields = [];
        $('#div-lift-fields #main-ui-square-container').find('.main-ui-square').each(function () {
            resultFields.push($(this).attr("data-item"));
        });
        $('#LIFT_FIELDS').val(JSON.stringify(resultFields));
    });

    $("#div-day-of-week #main-ui-square-container").on('DOMSubtreeModified', function () {
        var resultDays = [];
        $('#div-day-of-week #main-ui-square-container').find('.main-ui-square').each(function () {
            resultDays.push($(this).attr("data-item"));
        });
        $('#DAY_OF_WEEK').val(JSON.stringify(resultDays));
    });
});





