<?php
     require_once(non_web_php . "MushDB.php");

     function do_shortcode_add_a_team($args) {

        $team = TEAM;

        $add_team_html = <<<ADD_TEAM
        <form method="get" id="new_team_form" action="add-a-team">'
            <label for="{$team}">Musher name:</label>
            <input type="text" id="{$team}" name="{$team}"><br>
            <input type="submit" value="Register my team">
        </form>
        ADD_TEAM;

        return $add_team_html;
     }
?>