jQuery(document).ready(function($) {

  //$select = $('#downloadableSize');

  $('.downloadable figure').each(function(i, d) {

    console.log($(this).data('elitDownloadablePaths'));

  });

  jQuery('.downloadable__select').change(function(evt) {
console.dir(evt.currentTarget.value);
  });

});
