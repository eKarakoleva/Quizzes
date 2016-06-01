<?php

/**
 * QuizSolveAnswers
 * 
 * 
 */
class QuizSolveOpenAnswers extends PZ_BaseModel {

    static $belongs_to = array(
    );
    static $has_many = array(
    );

    public static function saveSolvedAnswers($params,$try) {
        if($params != "" ){
            $questions = new self();
            $questions->try_id = (int) $try;
            $questions->question_id = (int) $params['question_id'];
            $questions->answer = $params['answer'];
            $questions->points = $params['points'];
            $questions->type = $params['type'];
            $questions->save();
        }
    }

    public static function updateSolvedAnswers($comments, $points, $quiz_id, $try_id) {
        if(!empty($comments) && !empty($points)){

            foreach($points as $answer_id => $point){
                self::query('UPDATE quiz_solve_open_answers SET points=? WHERE id=?', [$point, $answer_id]);
            }
            
            foreach($comments as $number => $comment){
                if($comment['name'] != $quiz_id){
                    self::query('UPDATE quiz_solve_open_answers SET comment=? WHERE id=?', [$comment['value'], $comment['name']]);
                }else{
                    QuizSolveTries::updateTeacherComment($try_id, $comment['value']);
                }
            }
       }
    }


    public static function getComments($try_id){
        if($try_id != 0){
            $tries = self::find_all_by_try_id($try_id);
            if(!empty($tries)){
                $data = array();
                foreach($tries as $try){
                    $data[$try->question_id] = $try->comment;
                }
                return $data;
            }
        }
    }

}