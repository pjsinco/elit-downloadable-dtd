jQuery(document).ready(function($) {

  //$select = $('#downloadableSize');

  $('.downloadable figure').each(function(i, d) {

    //console.log($(this).data('elitDownloadablePaths'));

  });

  $('.downloadable__select').change(function(evt) {

    var paths = $(this).closest('.downloadable').data('elitDownloadablePaths');

    var selectedImage = paths[0][evt.currentTarget.value];

console.dir(selectedImage);

  });

});
