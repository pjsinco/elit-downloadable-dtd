jQuery(window).load(function() {

  init();

  function init() {
    positionHoverScreen();

    jQuery('.downloadable__select').on('change', function(evt) {
      var $downloadable = jQuery(this).closest('.downloadable');
      var allImages = $downloadable.data('elitDownloadablePaths');
      var selectedImageId = allImages[0][evt.currentTarget.value];
    
      updateAssetPath($downloadable, selectedImageId.abs_path);
      updateImage($downloadable, selectedImageId.url)
      updateUrl($downloadable, selectedImageId.url);
    });
    
    jQuery(window).resize(positionHoverScreen);
  }

  /**
   * Update the displayed image
   *
   */
  function updateImage($downloadable, url) {
    var $image = $downloadable.find('figure img');
    var newImageSrc = url.split(window.location.hostname);

    if (newImageSrc.length) {
      $image.one('load', function() {
        var newWidth = jQuery(this).width();
        var newHeight = jQuery(this).height();

        $downloadable.find('.downloadable__screen').css({
          width:  newWidth + 'px',
          height: newHeight + 'px',
          margin: '0 auto',
        })

      }).attr('src', newImageSrc[1]);
    }
  }
  
  /**
   *  Update '?asset=' query parameter on download links
   * 
   */
  function updateAssetPath($downloadable, assetPath) {
    var assetString = '?asset=';
    var $anchorTag = $downloadable.find('figure > a');
    var hrefPath = $anchorTag.attr('href').split(assetString)[0];
    var newPath = hrefPath + assetString + assetPath;
    $anchorTag.attr('href', newPath);
    $downloadable.find('figcaption > a').first().attr('href', newPath);
  }
  
  /**
   * Update url for viewing the image at actual size
   *
   */
  function updateUrl($downloadable, url) {
    var $actualSize = $downloadable.find('#actualSize');
    $actualSize.attr('href', url);
  }
  
  /**
   * Set the position for the download overlay that appears 
   * when the .downloadble element is hovered over.
   *
   */
  function positionHoverScreen() {
  
    jQuery('.downloadable').each(function() {
  
      var $image = jQuery(this).find('figure > a > img');

      jQuery(this).find('.downloadable__screen').css({
        width:  $image.css('width'),
        height: $image.css('height'),
        margin: '0 auto',
      });
    });
  }
});
