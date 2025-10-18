<?php
/**
 * 
 * NOTICE: This software is free for both personal and commercial use. However, in accordance
 * with the GPL 3.0 license, any modifications or enhancements made to this software must be
 * shared and submitted to the repository. Please review the LICENSE file for more details.
 * 
 * Plugin URI:        https://github.com/nkonstas/wordpress-quiz
 * Author URI:        https://github.com/nkonstas/wordpress-quiz
 * License URI:       https://github.com/nkonstas/wordpress-quiz/blob/main/LICENSE
 */

namespace KDQuizPlugin;

if (!defined('ABSPATH')) {
    exit;
}

//
// Shared data
//

class Shared {
    private static $instance = null;
    private $strings;

    private function __construct() {
        // Central place to keep the text override definitions in sync.
        $this->strings = [
            [
                'key' => 'text_wrong_answer',
                'option_name' => 'kdquiz_text_wrong_answer',
                'description' => 'Wrong Answer',
                'default_value' => 'Sorry, wrong answer.'
            ],
            [
                'key' => 'text_correct_answer',
                'option_name' => 'kdquiz_text_correct_answer',
                'description' => 'Correct Answer',
                'default_value' => 'Correct Answer!'
            ],
            [
                'key' => 'text_next_question',
                'option_name' => 'kdquiz_text_next_question',
                'description' => 'Next Question',
                'default_value' => 'Next Question'
            ],
            [
                'key' => 'text_next_view_score',
                'option_name' => 'kdquiz_text_next_view_score',
                'description' => 'View Score',
                'default_value' => 'View Your Score'
            ],
            [
                'key' => 'kdquiz_text_score_grade',
                'option_name' => 'kdquiz_text_score_grade',
                'description' => 'Score Grade',
                'default_value' => 'Grade'
            ],
            [
                'key' => 'kdquiz_text_score_grade_a',
                'option_name' => 'kdquiz_text_score_grade_a',
                'description' => 'Grade A',
                'default_value' => 'A'
            ],
            [
                'key' => 'kdquiz_text_score_grade_b',
                'option_name' => 'kdquiz_text_score_grade_b',
                'description' => 'Grade B',
                'default_value' => 'B'
            ],
            [
                'key' => 'kdquiz_text_score_grade_c',
                'option_name' => 'kdquiz_text_score_grade_c',
                'description' => 'Grade C',
                'default_value' => 'C'
            ],
            [
                'key' => 'kdquiz_text_score_grade_f',
                'option_name' => 'kdquiz_text_score_grade_f',
                'description' => 'Grade F',
                'default_value' => 'F'
            ],
            [
                'key' => 'kdquiz_text_score_percentage',
                'option_name' => 'kdquiz_text_score_percentage',
                'description' => 'Score Percentage',
                'default_value' => 'Your Score'
            ],
            [
                'key' => 'kdquiz_text_score_grade_a_message',
                'option_name' => 'kdquiz_text_score_grade_a_message',
                'description' => 'Grade A Message',
                'default_value' => 'Excellent work! You have a strong understanding of the material.'
            ],
            [
                'key' => 'kdquiz_text_score_grade_b_message',
                'option_name' => 'kdquiz_text_score_grade_b_message',
                'description' => 'Grade B Message',
                'default_value' => 'Good job! You\'ve grasped most of the concepts well.'
            ],
            [
                'key' => 'kdquiz_text_score_grade_c_message',
                'option_name' => 'kdquiz_text_score_grade_c_message',
                'description' => 'Grade C Message',
                'default_value' => 'Not bad, but there\'s room for improvement. Keep learning!'
            ],
            [
                'key' => 'kdquiz_text_score_grade_f_message',
                'option_name' => 'kdquiz_text_score_grade_f_message',
                'description' => 'Grade F Message',
                'default_value' => 'Looks like you need a bit more practice. Don\'t give up!'
            ],
        ];
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Shared();
        }

        return self::$instance;
    }

    public function getStrings() {
        return $this->strings;
    }
}
