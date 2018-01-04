<?php 
/*
Plugin Name: Elit Downloadable DTD
Plugin URI:  
Description: Make images and other assets downloadable 
Version:  1.0.0
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
          'ids' => '',
          'display-id' => '',
          //'rel-path' => '',
          //'name' => '',
          'meta-tag' => '',
          'meta-link' => '',
          'meta-audience' => '',
          //'filetype' => '',
          //'dimensions' => '',
          //'filesize' => '',
          'images' => array(),
        ),
        $atts
      );

      $shortcode_atts = elit_format_atts( $shortcode_atts );

      if ( elit_downloadable_is_image( $shortcode_atts ) ) {

        $ids = array_map( 'trim', explode( ',', $shortcode_atts['ids'] ) );

        $shortcode_atts['default_image_id'] = $ids[0];

        foreach ($ids as $id) {

          $image = wp_get_attachment_image_src( $id, 'full' );
          
          if ( $image ) {
            $shortcode_atts['images'][$id]['url'] = $image[0];
            $shortcode_atts['images'][$id]['width'] = $image[1];
            $shortcode_atts['images'][$id]['height'] = $image[2];
            $shortcode_atts['images'][$id]['abs_path'] = get_attached_file( $id );
          }
        }

      } else {
        // Handle document

        $id = reset($shortcode_atts['ids']);

        $image = wp_get_attachment_image_src( $shortcode_atts['display_id'], 'full' );

        $shortcode_atts['images'][0]['url'] = $image[0];
        $shortcode_atts['images'][0]['width'] = $image[1];
        $shortcode_atts['images'][0]['height'] = $image[2];
        $shortcode_atts['images'][0]['abs_path'] = get_attached_file( $id );
      }


      $shortcode_atts = 
        elit_downloadable_get_atts( $shortcode_atts, 
                                    elit_downloadable_is_image( $shortcode_atts ) );

      if ( ! $shortcode_atts ) { 
        return;
      }

      $markup = elit_downloadable_markup( $shortcode_atts );

      return $markup;
    }
    add_shortcode( 'downloadable', 'elit_downloadable_shortcode' );

    /**
     * Return the first values in the array
     * 
     * @param string $ids The ids to parse, eg "785, 786"
     * @return string
     */
    function elit_downloadable_get_first_id( $ids ) {
      $all_ids = array_map( 'trim', explode( ',', $ids ) );
      return $all_ids[0];
    }

    /**
     * Determines whether the requested asset is an image.
     *
     * @param array $atts The shortcode attributes
     * @return boolean Whether the asset is an image
     */
    function elit_downloadable_is_image( $atts ) {

      $first_id = elit_downloadable_get_first_id( $atts['ids'] );

      return empty( $atts['display_id'] ) || $atts['display_id'] == $first_id;
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
     * @param  2d array   $image Image info returned from wp_get_attachment_image_src()
     * @param  boolean $atts  Whether the downloadable asset is an image
     * @return array   The final shortcode attributes for the downloadable asset
     */
    function elit_downloadable_get_atts( $atts, $downloadable_is_image = true ) {

      if ( ! ( $atts && ! empty( $atts['images'] ) ) ) {
        return false;
      }

      $default_image = reset( $atts['images'] );
      $default_image_id = $atts['default_image_id'];

      $image_url = $default_image['url'];
      
      if ( $downloadable_is_image ) {

        //$image_path = get_attached_file( $default_image_id );

        $atts['display_id'] = $default_image_id;
        $atts['filetype']   = 
          strtoupper( elit_downloadable_get_image_type( $default_image['abs_path'] ) );
        //$atts['filesize']   = 
          //elit_downloadable_human_filesize( filesize( $image_path ), 0 );
        $atts['dimensions'] = 
          elit_downloadable_format_dimensions($default_image['width'], $default_image['height']);
        $atts['rel_path']       = parse_url( $image_url, PHP_URL_PATH );
        //$atts['name']       = basename( $image_url );

      } else {

        $image_path = get_attached_file( $atts['display_id'] );

        $asset_path = get_attached_file( $atts['ids'] );
        $asset_path_parts = explode( '.', basename($asset_path) );

        $atts['filetype'] = strtoupper( array_pop( $asset_path_parts ) );
        //$atts['filesize'] = elit_downloadable_human_filesize( filesize( $asset_path ), 0 );
        $atts['rel_path']     = parse_url( $image_url, PHP_URL_PATH );
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
    function elit_downloadable_format_dimensions( $width, $height ) {
      if ( empty( $width ) || empty( $height )) {
        return;
      }

      return sprintf('%dx%d', $width, $height);
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
    function elit_downloadable_human_filesize( $bytes, $decimals = 2 ) {

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
    function elit_downloadable_get_image_type( $image_url)  {

      $image_info = getimagesize( $image_url );

      if ( $image_info ) {
        $parts = explode( '/', $image_info['mime'] );
        return array_pop( $parts );
      }

      return null;
    }

    /**
     * Load the scripts and styles.
     *
     * @return void
     */
    function elit_downloadable_enqueue() {

      $css_file = 'elit-downloadable.css';
      $css_path = "public/styles/$css_file";
      $js_file = 'elit-downloadable-bundle.js';
      $js_path = "public/scripts/$js_file";

      wp_enqueue_script(
        'elit_downloadable_scripts',
        plugins_url( $js_path, __FILE__ ),
        array( 'jquery' ),
        filemtime( plugin_dir_path(__FILE__) . "/" . $js_path ),
        true
      );

      wp_enqueue_style(
        'elit_downloadable_styles',
        plugins_url( $css_path, __FILE__ ),
        array(),
        filemtime( plugin_dir_path(__FILE__) . "/" . $css_path ),
        'all'
      );
    }

    /**
     * Generate the HTML markup for the downloadable asset.
     *
     * @param array $atts The shortcode attributes
     * @return string The HTML markup
     */
    function elit_downloadable_markup( $atts )
    {
      extract( $atts );

      $download_path = plugins_url( "download.php", __FILE__ ) .  '?asset=';

      $all_ids = array_map( 'trim', explode( ',', $ids ) );
      $first_id = $all_ids[0];

      if ( $first_id != $display_id ) {

        // Downloadable is a non-image
        $download_path .= get_attached_file( $ids );
      } else {
    
        // Downloadable is an image
        $download_path .= get_attached_file( $default_image_id );
      }

      $markup  = "<div class='downloadable' data-elit-downloadable-paths='[" . json_encode( $atts['images'] ) . "]'>";
      if ( ! empty( $meta_tag ) ):
        $markup .= "  <h3>$meta_tag</h3>";
      endif;
      $markup .= "   <figure>";
      $markup .= "     <a href=\"$download_path\">";
      $markup .= "       <img src='$rel_path'>";
      $markup .= "       <div class='downloadable__screen' >";
      $markup .= "         <div><i class='downloadable__icon--dllight'></i>";
      $markup .= "         Download</div>";
      $markup .= "       </div>";
      $markup .= "     </a>";
      $markup .= "   </figure>";
      $markup .= "   <figcaption>";


      if ( elit_downloadable_is_image( $atts ) ):
        $markup .= "     <p class='downloadable__note'>";
        $markup .= "       <a class='hide' id='actualSize' href='$rel_path' target='_blank'>View actual size <i class='downloadable__icon--link'></i><br /></a>";
        $markup .= "     </p>";
      endif;
      $markup .= "     <p class='downloadable__description'>";


      if ( count( $images ) > 1 ) {

        $markup .= "       <label for='size'>Select size</label>";
        $markup .= "       <select name='size' class='downloadable__select'>";
          
        foreach ($images as $key => $image) {

          $option_text = 
            elit_downloadable_format_dimensions( $image['width'], $image['height'] );

          $markup .= "<option value='$key'>$option_text pixels</option>";
        }

        $markup .= "       </select>";

      } else {

        if ( ! empty( $dimensions ) ) {
          $markup .= "       <span>Dimensions: </span>$dimensions pixels<br>";
        }
      }
      $markup .= "       <span class='downloadable__note'><a href='mailto:asnyder@osteopathic.org?subject=" . rawurlencode('OMED Marketing Materials') . "'>Request additional sizes <i class='downloadable__icon--email'></i></a></span>";






      $markup .= "       <span>Format: </span>$filetype<br>";
      if ( ! empty( $filesize ) ):
        $markup .= "       <span>File Size: </span>$filesize<br>";
      endif;
      if ( ! empty( $meta_link ) ):
        $markup .= "       <span>Link to: </span>$meta_link<br>";
      endif;
      if ( ! empty( $meta_audience ) ):
        $markup .= "       <span>Audience: </span>$meta_audience<br>";
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
