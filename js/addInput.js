$(function() {
    var scntDiv = $('#urlDiv');
    var i = $('#urlDiv p').size() + 1;

    $('#addUrl').on('click', function() {
        $('<p>' + i + ' - <label for="url"><input type="text" id="url" size="60" name="url' + i +'" value="" placeholder="https://www.brocku.ca/file.pdf" /></label></p>').appendTo(urlDiv);
        i++;
        return false;
    });
});
