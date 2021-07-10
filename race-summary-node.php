<?php
    namespace IronPaws;

    defined( 'ABSPATH' ) || exit;

    class Race_Summary_Node extends \SplHeap {
        public function compare(ScoreCard $score1, ScoreCard $score2)
        {
            if ($score1->score === $score2->score) return 0;
            return $score1.score < $score2.score ? -1 : 1;
        }
        
    }
?>