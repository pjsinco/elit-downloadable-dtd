<?php 
/*
Plugin Name: Elit Downloadable
Plugin URI:  
Description: Make images and other assets downloadable
Version:  1.0.3
Author: Patrick Sinco
Author URI: github.com/pjsinco
License: GPL2
*/

// if this file is called directly, abort
if (!defined('WPINC')) {
  die;
}

// Make download.php

// Create shortcode

function elit_downloadable_shortcode_init() {

  if ( ! shortcode_exists( 'downloadable' ) ) {

    
    /**
     * Create the shortcode.
     *
     * @param array $atts     The shortcode attributes
     * @return void
     */
    function elit_downloadable_shortcode( $atts = array() ) {

      $atts = array_change_key_case( (array)$atts, CASE_LOWER );

      elit_downloadable_enqueue();

      $shortcode_atts = shortcode_atts(
        array(
          'id' => '',
          'display-id' => '',
          'path' => '',
          'name' => '',
          'meta-tag' => '',
          'meta-link' => '',
          'meta-audience' => '',
          'filetype' => '',
          'dimensions' => '',
          'filesize' => '',
        ),
        $atts
      );

      $shortcode_atts = elit_format_atts( $shortcode_atts );


      if ( is_image( $shortcode_atts ) ) {

        $image = wp_get_attachment_image_src( $shortcode_atts['id'], 'full' );

      } else {
        // Handle document

        $image = wp_get_attachment_image_src( $shortcode_atts['display_id'], 'full' );

      }

      $shortcode_atts = elit_get_atts( $shortcode_atts, $image, is_image( $shortcode_atts ) );

      if ( ! $shortcode_atts ) { 
        return;
      }

      $markup = elit_markup( $shortcode_atts, $image_url );

      return $markup;
    }
    add_shortcode( 'downloadable', 'elit_downloadable_shortcode' );

    /**
     * Determines whether the requested asset is an image.
     *
     * @param array $atts The shortcode attributes
     * @return boolean Whether the asset is an image
     */
    function is_image( $atts ) {

      return empty( $atts['display_id'] ) || 
             $atts['display_id'] == $atts['id'];
    }

    /**
     * Change a hyphen to an underscore in the keys to an array.
     *
     * @param array $atts The shortcode attributes
     * @return array $atts The shortcode attributes with replaced keys
     */
    function elit_format_atts( $atts ) {
      
      return array_combine(
        array_map( function( $key ) use ( $atts ) { 
          return str_replace( '-', '_', $key );
        }, array_keys( $atts ) ), 
        array_values( $atts )
      );
    }
  
    /**
     * Fill in the shortcode attributes.
     *
     * @param  array   $atts  Shortcode attributes
     * @param  array   $image Image info returned from wp_get_attachment_image_src()
     * @param  boolean $atts  Whether the downloadable asset is an image
     * @return array   The final shortcode attributes for the downloadable asset
     */
    function elit_get_atts( $atts, $image, $downloadable_is_image = true ) {

      if ( ! ( $atts && $image ) ) {
        return false;
      }

      $image_url = $image[0];
      
      if ( $downloadable_is_image ) {

        $image_path = get_attached_file( $atts['id'] );

        $atts['display_id'] = $atts['id'];
        $atts['filetype']   = strtoupper( elit_get_image_type( $image_path ) );
        $atts['filesize']   = elit_human_filesize( filesize( $image_path ), 0 );
        $atts['dimensions'] = elit_format_dimensions($image[1], $image[2]);
        $atts['path']       = parse_url( $image_url, PHP_URL_PATH );
        $atts['name']       = basename( $image_url );

      } else {

        $image_path = get_attached_file( $atts['display_id'] );

        $asset_path = get_attached_file( $atts['id'] );
        $asset_path_parts = explode( '.', basename($asset_path) );

        $atts['filetype'] = strtoupper( array_pop( $asset_path_parts ) );
        $atts['filesize'] = elit_human_filesize( filesize( $asset_path ), 0 );
        $atts['path']     = parse_url( $image_url, PHP_URL_PATH );
      }

      return $atts;
      
    }

    /**
     * Format the dimensions for display.
     *
     * @param  int $width The width of the asset
     * @param  int $height The width of the asset
     * @return string The height and width formatted for display.
     */
    function elit_format_dimensions( $width, $height ) {
      if ( empty( $width ) || empty( $height )) {
        return;
      }

      return sprintf('%dx%d pixels', $width, $height);
    }

    /**
     * Translate the file size to more readable number.
     *
     * @param int $bytes The file size in bytes
     * @param int $decimals How many decimals to use in formatting
     * @return string The translated file size
     * @see http://php.net/manual/en/function.filesize.php
     *
     */
    function elit_human_filesize( $bytes, $decimals = 2 ) {

      $factor = floor( ( strlen( $bytes ) - 1 ) / 3 );

      if ( $factor > 0 ) {
        $size = 'kmgt';
      }

      return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . @$size[$factor - 1] . 'b';
    }

    /**
     * Determine the image type.
     *
     * @param string $url URL of the image
     * @return string The image type
     */
    function elit_get_image_type( $image_url)  {

      $image_info = getimagesize( $image_url );

      if ( $image_info ) {
        $parts = explode( '/', $image_info['mime'] );
        return array_pop( $parts );
      }

      return null;
    }

    /**
     * Load the styles.
     *
     * @return void
     */
    function elit_downloadable_enqueue() {

      $css_file = 'elit-downloadable.css';

      wp_enqueue_style(
        'elit_downloadable_styles',
        plugins_url( "public/styles/$css_file", __FILE__ ),
        array(),
        filemtime( plugin_dir_path(__FILE__) . "/public/styles/$css_file" ),
        'all'
      );
    }

    /**
     * Generate the HTML markup for the downloadable asset.
     *
     * @param array $atts The shortcode attributes
     * @return string The HTML markup
     */
    function elit_markup( $atts )
    {
      extract( $atts );

      $download_path = plugins_url( "download.php", __FILE__ ) . 
                       '?asset=' . get_attached_file( $id );
        
      $markup  = "<div class='downloadable'>";
      if ( ! empty( $meta_tag ) ):
        $markup .= "  <h3>$meta_tag</h3>";
      endif;
      $markup .= "   <figure>";
      $markup .= "     <img src='$path'>";
      $markup .= "     <a class='downloadable__screen' href=\"$download_path\">";
      $markup .= "       <p>";
      $markup .= "         <i class='downloadable__icon--dllight'></i>";
      $markup .= "         Download";
      $markup .= "       </p>";
      $markup .= "     </a>";
      $markup .= "   </figure>";
      $markup .= "   <figcaption>";
      if ( is_image( $atts ) ):
        $markup .= "     <p class='downloadable__note'>";
        $markup .= "       <a href='$path' target='_blank'>View actual size <i class='downloadable__icon--link'></i></a>";
        $markup .= "     </p>";
      endif;
      $markup .= "     <p class='downloadable__description'>";
      if ( ! empty( $dimensions ) ):
        $markup .= "       <span>Dimensions</span>$dimensions<br>";
      endif;
      $markup .= "       <span>Format</span>$filetype<br>";
      if ( ! empty( $filesize ) ):
        $markup .= "       <span>File Size</span>$filesize<br>";
      endif;
      if ( ! empty( $meta_link ) ):
        $markup .= "       <span>Link to</span>$meta_link<br>";
      endif;
      if ( ! empty( $meta_audience ) ):
        $markup .= "       <span>Audience</span>$meta_audience<br>";
      endif;
      $markup .= "     </p>";
      $markup .= "     <a href=\"$download_path\">";
      $markup .= "       <i class='downloadable__icon--dldark'></i>";
      $markup .= "       Download";
      $markup .= "     </a>";
      $markup .= "   </figcaption>";
      $markup .= " </div>";

      return $markup;
    }
  }
}
add_action( 'init' , 'elit_downloadable_shortcode_init' );

