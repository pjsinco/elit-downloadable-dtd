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

      $shortcode_atts = shortcode_atts(
        array(
          'url' => '',
          'meta-tag' => '',
          'meta-link' => '',
          'meta-audience' => '',
        ),
        $atts
      );

      $markup = elit_markup();

      return $markup;
    }

    add_shortcode( 'downloadable', 'elit_downloadable_shortcode' );

    function elit_markup( )
    {
      $markup = <<<EOT
        
        <div class="downloadable">
          <h3>
            Web banner
          </h3>
          <figure>
            <img src="images/save-the-date-omed-2017.png">
            <a class="downloadable__screen" href="download.php?img=save-the-date-omed-2017.png">
              <p>
                <i class="fa fa-download" aria-hidden="true"></i>
                Download
              </p>
            </a>
          </figure>
          <figcaption>
            <p class="downloadable__note">
              <a href="images/save-the-date-omed-2017.png" target="_blank">View actual size </a><i class="fa fa-external-link"></i>
            </p>
            <p class="downloadable__description">
              <span>Size</span>600x311 pixels<br>
              <span>Format</span>JPEG<br>
              <span>File Size</span>135k<br>
              <span>Link to</span>https://omed.osteopathic.org<br>
            </p>
            <a href="download.php?img=save-the-date-omed-2017.png">
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

