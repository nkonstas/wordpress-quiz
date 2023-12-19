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

namespace KDQuiz;

//
// Shared data
//

class Shared {
    private static $instance = null;
    private $strings;

    private function __construct() {
        $this->strings = [
            [
                'id' => 'text_wrong_answer',
                'description' => 'Wrong Answer',
                'default_value' => 'Sorry, wrong answer.'
            ],
            [
                'id' => 'text_correct_answer',
                'description' => 'Correct Answer',
                'default_value' => 'Correct Answer!'
            ],
            [
                'id' => 'text_next_question',
                'description' => 'Next Question',
                'default_value' => 'Next Question'
            ],
            [
                'id' => 'text_next_view_score',
                'description' => 'View Score',
                'default_value' => 'View Your Score'
            ],
            [
                'id' => 'kd_quiz_text_score_grade',
                'description' => 'Score Grade',
                'default_value' => 'Grade'
            ],
            [
                'id' => 'kd_quiz_text_score_grade_a',
                'description' => 'Grade A',
                'default_value' => 'A'
            ],
            [
                'id' => 'kd_quiz_text_score_grade_b',
                'description' => 'Grade B',
                'default_value' => 'B'
            ],
            [
                'id' => 'kd_quiz_text_score_grade_c',
                'description' => 'Grade C',
                'default_value' => 'C'
            ],
            [
                'id' => 'kd_quiz_text_score_grade_f',
                'description' => 'Grade F',
                'default_value' => 'F'
            ],
            [
                'id' => 'kd_quiz_text_score_percentage',
                'description' => 'Score Percentage',
                'default_value' => 'Your Score'
            ],
            [
                'id' => 'kd_quiz_text_score_grade_a_message',
                'description' => 'Grade A Message',
                'default_value' => 'Excellent work! You have a strong understanding of the material.'
            ],
            [
                'id' => 'kd_quiz_text_score_grade_b_message',
                'description' => 'Grade B Message',
                'default_value' => 'Good job! You\'ve grasped most of the concepts well.'
            ],
            [
                'id' => 'kd_quiz_text_score_grade_c_message',
                'description' => 'Grade C Message',
                'default_value' => 'Not bad, but there\'s room for improvement. Keep learning!'
            ],
            [
                'id' => 'kd_quiz_text_score_grade_f_message',
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