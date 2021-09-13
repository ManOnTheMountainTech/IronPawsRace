<?php
  namespace IronPaws;

  defined( 'ABSPATH' ) || exit;

  //require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';

  class Links {
    // Creates a picture followed by an href to the provided wordpress page slug
    // @param-> The icon path from the plugins directory, such as 
    //  ironpaws/img/icons/dogs/sleds/blah.svg
    // @param-> A wordpress page slug
    // @return -> string: HTML
    static function make_icon_then_link(string $icon, string $wp_page_slug) {
      $url_path = get_permalink( \wc_get_page_id( $wp_page_slug ));

      $next_steps = Strings::NEXT_STEPS;

      // IE6/7 - non tables way:
      // 
      // <a href="$teams_path" style="display:inline-flex;flex-direction:row;aligns-items:center;vertical-align:center;line-height:5rem;height:5rem;">
      return <<<LINK
      <p>$next_steps</p>
      <a href="$url_path" class="img-a">
        <img 
          src="{$icon}" 
          alt="A musher pulling their dog on a sled">
        <p class="p-aligned">Lookup the teams that are assigned to this race</p>
      </a>
      LINK;
    }
  }
?>