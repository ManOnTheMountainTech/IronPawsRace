<?php
  // Load wordpress regardless of where it is located. Remember, it could be
  // in any subfolder.
  defined( 'ABSPATH' ) || exit;

  namespace IronPaws;

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

    function makeListItemHTML(array $team_idxs) {
      return '<em>' . $team_idxs[Teams::TEAM_NAME_ID] . '<br>';
    }

    function makeClosingHTML() {
      return "<p>";
    }
  }
?>