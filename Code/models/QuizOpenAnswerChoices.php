<?php

/**
 * QuizOpenAnswerChoices
 */
class QuizOpenAnswerChoices extends PZ_BaseModel {

    static $belongs_to = array(
    );
    static $has_many = array(
    );

    public static function createOpenChoicesQuestion($params, $questionId) {

        if (!empty($params['answers']) && $questionId > 0) {

            foreach ($params['answers'] as $number => $choice) {
                if (!empty($choice['value'])) {
                    if (empty($params['points'][$number]['value'])) {
                        $point = 0;
                    } else {
                        $point = $params['points'][$number]['value'];
                    }

                    $openAnswerChoice = new self();
                    $openAnswerChoice->question_id = $questionId;
                    $openAnswerChoice->choice = $choice['value'];
                    $openAnswerChoice->points = $point;
                    $openAnswerChoice->number = $number + 1;
                    $openAnswerChoice->save();
                }
            }
        }

    }

    public static function editOpenChoiceQuestion($params, $question_id) {

        if (!empty($params['answers']) && $question_id > 0) {
            foreach ($params['answers'] as $number => $answer) {

                $point = (int) $params['points'][$number]['value'];

                $splited_name = explode("_", $answer['name']);

                if ($splited_name[0] != "answer") {
                    $label = $splited_name[1];
                } else {
                    $label = $splited_name[0];
                }

                if ($label != "answer") {
                    //update question
                    $answers = self::find($label);
                    $answers->choice = $answer['value'];
                    $answers->number = $number + 1;
                    $answers->points = $point;
                    $answers->save();
                } else {
                    //add new question
                    if ($answer['value'] != '') {
                        $cr_new_answers = new self();
                        $cr_new_answers->question_id = $question_id;
                        $cr_new_answers->choice = $answer['value'];
                        $cr_new_answers->number = $number + 1;
                        $cr_new_answers->points = $point;
                        $cr_new_answers->save();
                    }
                }
            }
        }

    }

    public static function getOpenChoiceQuestions($quiz_id) {
        if($quiz_id > 0){
            $data = array();
            $sql = 'SELECT *,b.choice, b.points, b.question_id
                        FROM `quiz_questions` as a
                        LEFT JOIN `quiz_open_answer_choices` as b ON a.id = b.question_id
                        WHERE a.quiz_id =? AND a.type = "open-choices"',[$course_id];
    
            $openChoices = self::find_by_sql($sql);
            foreach($openChoices as $choice){
                $data[$choice->number]['choices'][$choice->question_id] = $choice->choice;
                $data[$choice->number]['points'][$choice->question_id] = $choice->points;
            }
            return $data;
        }
    }

    public static function sumOpenChoiceAnswersLength($quiz_id) {
        if($quiz_id > 0){
            $data = array();
            $choices  = self::getOpenChoiceQuestions($quiz_id);
            if(!empty($choices)){
                foreach($choices as $question){
                    foreach($question['choices'] as $question_id => $answer){
                        if(empty($data[$question_id])){
                            $data[$question_id] = 0;
                        }
                        $answer_length = mb_strlen(trim(strip_tags($answer)), 'UTF-8');
                        $data[$question_id] += $answer_length;
                    }
                }
                return $data;
            }
        }
    }

}
