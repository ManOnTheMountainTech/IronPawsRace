<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    interface Container_HTML_Pattern {
        function makeOpeningHTML();
        function makeListItemHTML(array $params);
        function makeClosingHTML();
    }
?>