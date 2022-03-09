<?php

  namespace IronPawsLLC;

  defined( 'ABSPATH' ) || exit;



  require_once "wp-defs.php";
  require_once plugin_dir_path(__FILE__) . 'includes/debug.php';
  require_once plugin_dir_path(__FILE__) . 'includes/logon.php';
  require_once plugin_dir_path(__FILE__) . 'includes/autoloader.php';

  function do_shortcode_modify_teams() {
    $logon_form = ensure_loggedon();
    if (!is_null($logon_form)) {
      return $logon_form;
    }
  }

  function do_shortcode_modify_teams_db() {
      
      $team = sanitize_text_field($_GET[TEAM_NAME]);
      if (empty($team)) {
          write_log(__FUNCTION__ . ': Team name is invalid');
          return _e("Team $team is invalid", "ironpaws");
      }

      $db = new Mush_DB();

      return set_race_team($db, $team);
  };

  function set_race_team(Mush_DB $db, $person) { 
    $teams_path = plugins_url('modify_teams.php', __FILE__);
    $teams_selections_html = '<form method="get" id="team" action="' 
    . $teams_path . '">';

    $teams = new Teams();

    return $teams->get_mushers_teams($db);
      
    return $teams_selections_html;
  }
?>