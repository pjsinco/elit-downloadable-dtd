jQuery(window).load(function() {

  init();

  function init() {
    positionHoverScreen();

    jQuery('.downloadable__select').on('change', function(evt) {
      var $downloadable = jQuery(this).closest('.downloadable');
      var allImages = $downloadable.data('elitDownloadablePaths');
      var selectedImageId = allImages[0][evt.currentTarget.value];
    
      updateAssetPath($downloadable, selectedImageId.abs_path);
      updateUrl($downloadable, selectedImageId.url);
    });
    
    jQuery(window).resize(positionHoverScreen);
  }
  
  /**
   *  Update '?asset=' query parameter on download links
   * 
   */
  function updateAssetPath($downloadable, assetPath) {
    var assetString = '?asset=';
  
    var $newLink = $downloadable.find('.downloadable__screen');
    var hrefPath = $newLink.attr('href').split(assetString)[0];
    var newPath = hrefPath + assetString + assetPath;
    $newLink.attr('href', newPath);
    $downloadable.find('figcaption > a').attr('href', newPath);
  }
  
  /**
   * Update url for viewing the image at actual size
   *
   */
  function updateUrl($downloadable, url) {
    var $actualSize = $downloadable.find('.downloadable__note > a');
    $actualSize.attr('href', url);
  }
  
  /**
   * Set the position for the download overlay that appears 
   * when the .downloadble element is hovered over.
   *
   */
  function positionHoverScreen() {
  
    //$('.downloadable__screen').
  
    jQuery('.downloadable').each(function() {
  
  
      var $image = jQuery(this).find('figure > a > img');

      //$image.css('bottom', 'inherit');
      //$image.css('left', 'inherit');
      //$image.css('right', 'inherit');

      jQuery(this).find('.downloadable__screen').css({
        //bottom: '1rem',
        //left:   '50%',
        //right:  '1rem',
        width:  $image.css('width'),
        height: $image.css('height'),
        margin: '0 auto',
        //transform: 'translateX(-50%)',
      });
    });
  }
  
});
