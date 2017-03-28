<?php 
/*
Plugin Name: Elit Downloadable
Plugin URI:  
Description: Make images and other assets downloadable
Version:  0.1.0
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
          'id' => '',
          'path' => '',
          'name' => '',
          'tag' => '',
          'link' => '',
          'audience' => '',
          'type' => '',
          'dimensions' => '',
          'filesize' => '',
        ),
        $atts
      );

      $image = wp_get_attachment_image_src( $shortcode_atts['id'], 'elit-super' );
      
      if ( ! $image ) {
        return;
      }

      $shortcode_atts = get_atts( $shortcode_atts, $image );

      $markup = elit_markup( $shortcode_atts, $image_url );

      return $markup;
    }
    add_shortcode( 'downloadable', 'elit_downloadable_shortcode' );
  
    function get_atts( $atts, $image ) {
      $image_url = $image[0];
      $image_path = get_attached_file( $shortcode_atts['id'] );

      $atts['type']       = strtoupper( elit_get_image_type( $image_url ) );
      $atts['filesize']   = elit_human_filesize( filesize( $image_path ), 0 );
      $atts['dimensions'] = elit_format_dimensions($image[1], $image[2]);
      $atts['path']       = parse_url( $image_url, PHP_URL_PATH );
      $atts['name']       = basename( $image_url );

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
      
      $markup = <<<EOT
        
        <div class="downloadable">
          <h3>
            Web banner
          </h3>
          <figure>
            <img src="$path">
            <a class="downloadable__screen" href="download.php?asset=$name">
              <p>
                <i class="fa fa-download" aria-hidden="true"></i>
                Download
              </p>
            </a>
          </figure>
          <figcaption>
            <p class="downloadable__note">
              <a href="$path" target="_blank">View actual size </a><i class="fa fa-external-link"></i>
            </p>
            <p class="downloadable__description">
              <span>Dimensions</span>$dimensions<br>
              <span>Format</span>$type<br>
              <span>File Size</span>$filesize<br>
              <span>Link to</span>$link<br>
            </p>
            <a href="download.php?asset=$name">
              <i class="fa fa-download" aria-hidden="true"></i>
              Download
            </a>
          </figcaption>
        </div>

EOT;
      return $markup;
    }
  }
}
add_action( 'init' , 'elit_downloadable_shortcode_init' );

