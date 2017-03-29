<?php

  /**
   * @helpfrom http://stackoverflow.com/questions/11090272/
   *   how-can-i-force-an-image-download-in-the-browser
   */


  if (empty($_GET['asset'])) {
    header( 'HTTP/1.0 404 Not Found' );
    return;
  }

  $file_path = $_GET['asset'];
  $basename = basename( $file_path );

  $mime = ( $mime = getimagesize( $file_path ) ) ? $mime['mime'] : mime_content_type( $file_path );
  $size = filesize( $file_path );
  $file = fopen( $file_path, 'rb' );
  
  if ( ! ($mime && $size && $file ) ) {
    // error
    return;
  }

  header("Content-type: $mime");
  header("Content-length: $size");
  header("Content-disposition: attachment; filename=$basename");
  header("Content-transfer-encoding: binary");
  header("Cache-control: must-revalidate; post-check=0; pre-check=0");
  fpassthru($file);

?>


