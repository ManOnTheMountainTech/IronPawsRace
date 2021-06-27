<?php
  namespace IronPaws;

  defined( 'ABSPATH' ) || exit;

  require_once plugin_dir_path(__FILE__) . 'includes/wp-defs.php';
  require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
  require_once plugin_dir_path(__FILE__) . 'teams.php';

  use Automattic\WooCommerce\Client;
  use Automattic\WooCommerce\HttpClient\HttpClientException;

  class Fetch_Teams extends Teams {
    static function do_shortcode() {
      return (new Fetch_Teams())->get('fetch-teams');
    }

    function makeOpeningHTML() {
      $team_name_id = TEAM_NAME_ID;
      return "<h3>Below are your teams:</h3>";
    }

    function makeListItemHTML(array $params) {
      return '<em>' . $params[Teams::TEAM_NAME_ID] . '<br>';
    }

    function makeClosingHTML() {
      return "<p>";
    }
  }
?>