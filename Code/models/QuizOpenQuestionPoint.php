<?php

/**
 * QuizOpenQuestionPoint
 * 
 * 
 */
class QuizOpenQuestionPoint extends PZ_BaseModel {

    static $belongs_to = array(
    );
    static $has_many = array(
    );

    /**
     * Get points
     * 
     * @param int $question_id
     * @return QuizOpenQuestionPoint
     */
    public static function getPoints($question_id) {

        return self::find_by_question_id($question_id);

    }

    /*
     * Add open question
     * 
     * @param array $params
     * @param int $questionId
     */
    public static function addOpenQuestionPoints($params, $questionId, $quiz_id) {

        if (!empty($questionId) && $params['type'] == "open" && $quiz_id > 0 && is_array($params)) {
            if (empty($params['points'])) {
                $point = 0;
            } else {
                $point = $params['points'];
            }
            $points = new self();
            $points->question_id = $questionId;
            $points->quiz_id = $quiz_id;
            $points->points = $point;
            $points->save();
        }

    }
    /**
     * Edit open question
     * 
     * @param array $params
     * @param string $type
     */
    public static function editOpenQuestion($params, $type) {
        if(is_array($params) && !empty($params)){
            $question_id = (int) $params['id'];
            if ($type == "open") {
                if (empty($params['open_points'])) {
                    $point = 0;
                } else {
                    $point =$params['open_points'];
                }
    
                $question = self::find_by_question_id($question_id);
                $question->points = $point;
                $question->save();
            }
        }
    }

}
