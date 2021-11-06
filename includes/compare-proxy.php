<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    // Compares whatever is put in to the constructor
    class Compare_Proxy implements Comparable {
        public ?Comparable $comparator;

        function __construct($comparator) {
            $this->comparator = $comparator;
        }

        function compareTo(Comparable $other): int {
            return $this->comparator->compareTo($other);
        }
    }
?>