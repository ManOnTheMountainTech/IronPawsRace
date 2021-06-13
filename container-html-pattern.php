<?php
    defined( 'ABSPATH' ) || exit;

    namespace IronPaws;

    interface Container_HTML_Pattern {
        function makeOpeningHTML();
        function makeListItemHTML(array $team_idxs);
        function makeClosingHTML();
    }
?>