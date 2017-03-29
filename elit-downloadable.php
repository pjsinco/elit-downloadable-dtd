<?php 
/*
Plugin Name: Elit Downloadable
Plugin URI:  
Description: Make images and other assets downloadable
Version:  1.0.1
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

    function elit_downloadable_shortcode( $atts = array(), $content = null ) {

      $atts = array_change_key_case( (array)$atts, CASE_LOWER );

      elit_downloadable_enqueue();

      $shortcode_atts = shortcode_atts(
        array(
          'downloadable-id' => '',
          'display-id' => '',
          'path' => '',
          'name' => '',
          'tag' => '',
          'link' => '',
          'audience' => '',
          'filetype' => '',
          'dimensions' => '',
          'filesize' => '',
        ),
        $atts
      );

      $shortcode_atts = elit_format_atts( $shortcode_atts );


      if ( is_image( $shortcode_atts ) ) {

        $image = wp_get_attachment_image_src( $shortcode_atts['downloadable_id'], 'full' );

        if ( ! $image ) {
          return;
        }

        $shortcode_atts = get_atts( $shortcode_atts, $image, true );

      } else {
        // Handle document

        $image = wp_get_attachment_image_src( $shortcode_atts['display_id'], 'full' );

        if ( ! $image ) {
          return;
        }

        $shortcode_atts = get_atts( $shortcode_atts, $image, false );
      }


      $markup = elit_markup( $shortcode_atts, $image_url );

      return $markup;
    }
    add_shortcode( 'downloadable', 'elit_downloadable_shortcode' );

    /**
     * Determines whether the requested asset is an image.
     *
     */
    function is_image( $atts ) {

      return empty( $atts['display_id'] ) || 
             $atts['display_id'] == $atts['downloadable_id'];
    }

    /**
     * Change a hyphen to an underscore in the keys to an array.
     *
     */
    function elit_format_atts( $atts ) {
      
      return array_combine(
        array_map(function($key) use ($atts) { 
          return str_replace('-', '_', $key);
        }, array_keys($atts)), 
        array_values($atts)
      );
    }
  
    function get_atts( $atts, $image, $downloadable_is_image = true ) {

      $image_url = $image[0];
      
      if ( $downloadable_is_image ) {

        $image_path = get_attached_file( $atts['downloadable_id'] );

        $atts['display_id'] = $atts['downloadable_id'];
        $atts['filetype']   = strtoupper( elit_get_image_type( $image_url ) );
        $atts['filesize']   = elit_human_filesize( filesize( $image_path ), 0 );
        $atts['dimensions'] = elit_format_dimensions($image[1], $image[2]);
        $atts['path']       = parse_url( $image_url, PHP_URL_PATH );
        $atts['name']       = basename( $image_url );

      } else {

        $image_path = get_attached_file( $atts['display_id'] );

        $asset_path = get_attached_file( $atts['downloadable_id'] );
        $asset_path_parts = explode( '.', basename($asset_path) );

        $atts['filetype'] = strtoupper( array_pop( $asset_path_parts ) );
        $atts['filesize'] = elit_human_filesize( filesize( $asset_path ), 0 );
        $atts['path']     = parse_url( $image_url, PHP_URL_PATH );
      }

      return $atts;
      
    }

    function elit_format_dimensions( $width, $height ) {
      if ( empty( $width ) || empty( $height )) {
        return;
      }

      return sprintf('%dx%d pixels', $width, $height);
    }

    /**
     * @source http://php.net/manual/en/function.filesize.php
     *
     */
    function elit_human_filesize( $bytes, $decimals = 2 ) {

      $factor = floor( ( strlen( $bytes ) - 1 ) / 3 );

      if ( $factor > 0 ) {
        $size = 'kmgt';
      }

      return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . @$size[$factor - 1] . 'b';
    }

    function elit_get_image_type( $image_url)  {

      $image_info = getimagesize( $image_url );

      if ( $image_info ) {
        $parts = explode( '/', $image_info['mime'] );
        return array_pop( $parts );
      }

      return null;
    }

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

    function elit_markup( $atts )
    {
      extract( $atts );
      $download_path = plugins_url( "download.php", __FILE__ ) . 
                       '?asset=' . get_attached_file( $downloadable_id );
        
      $markup  = "<div class='downloadable'>";
      if ( ! empty( $tag ) ):
        $markup .= "  <h3>$tag</h3>";
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
      if ( ! empty( $link ) ):
        $markup .= "       <span>Link to</span>$link<br>";
      endif;
      if ( ! empty( $audience ) ):
        $markup .= "       <span>Audience</span>$audience<br>";
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

