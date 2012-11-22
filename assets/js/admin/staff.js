jQuery(function($) {
    var locationList = $('div#location_list li');
    locationList.click(function() {
        var locationId = $(this).attr('data-location-id');
        $('div#timetable > div').each(function(i, el) {
            if ($(this).attr('data-location-id') === locationId) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        locationList.each(function(i, el) {
            if ($(this).attr('data-location-id') === locationId) {
                $(this).addClass('current');
            } else {
                $(this).removeClass('current');
            }
        });
    });
    $('div#timetable tr').each(function(i, el) {
        var row = $(this);
        row.find('input[type=checkbox]').click(function() {
            if ($(this).is(':checked')) {
                row.find('select').removeAttr('disabled');
            } else {
                row.find('select').attr('disabled', 'disabled');
            }
        });
    });
});