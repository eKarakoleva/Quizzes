<?php

/**
 * QuizSolveAnswers
 * 
 * 
 */
class QuizSolveAnswers extends PZ_BaseModel {

    static $belongs_to = array(
    );
    static $has_many = array(
    );

    public static function setQuestionAnswers($quiz_id) {
        if($quiz_id > 0){
            $correct = 0;
            $questions = QuizQuestion::getQuestionsByQuizId($quiz_id);
           // $trueAnswers = QuizAnswer::getQuestionTrueAnswers($question_id, $type);
            $structData = [];
            if (is_array($questions) && !empty($questions)) {
    
                foreach ($questions as $question) {
                    $structData[$question->id]['question'] = $question;
                    $structData[$question->id]['answers'] = QuizAnswer::getChoices($question->id,$question->type);
                    $structData[$question->id]['true_answers'] = QuizAnswer::getQuestionTrueAnswers($question->id,$question->type);
                }
            }
    
            return $structData;
        }
    }



     public static function saveUserAnswers($quiz_id,$user_id, $answers) {
        if($quiz_id > 0 && $user_id > 0 && is_array($answers) && !empty($answers)){
            $try = QuizSolveTries::find_by_course_id_and_lesson_id_and_quiz_id_and_status($answers['course_id'],$answers['lesson_id'],$answers['id'],1);
            $delete_question_id = "";
            $teacher_quiz = self::setQuestionAnswers($quiz_id);
            //contains information for answers of type singlechoise and multichoise in teacher quiz
            $answersArr = array();
            //contains information for true answers in teacher quiz
            $trueAnswersArr = array();
            //contains information for open answers in teacher quiz
            $openAnswersArr = array();
            foreach($teacher_quiz as $q_id => $questions_info){
                foreach($questions_info['answers'] as $number =>$answer){
                    if( $questions_info['question']->type == 'multichoice' || $questions_info['question']->type == 'radio'){
                        $answersArr[$q_id][$answer->id] = $answer->points;
                    }
                }
                if( $questions_info['question']->type == 'open' || $questions_info['question']->type == 'open-choices'){
                    $openAnswersArr[$questions_info['question']->type][$q_id] = $answer->points;
                }
            }

            foreach($teacher_quiz as $q_id => $questions_info){
                foreach($questions_info['true_answers'] as $number =>$answer){
                    $trueAnswersArr[$answer->question_id][$answer->id] = $answer->points;
                }
            }

        if(isset($answers['answers']) && $answers['answers'] != ""){
            foreach($answers['answers'] as $question_id => $answers_id){
                //check if question exist in teacher's quiz
                if($question_id != ""){
                    if (array_key_exists($question_id, $answersArr)) {
                        if($answers_id != ""){
                            foreach($answers_id as $answer_id => $checked){
                                //check if answers for this question exist in teacher's quiz
                                if (array_key_exists($answer_id, $answersArr[$question_id])) {
                                    $delete_question_id = $question_id;
                                    //check if user answer is true
                                    if (array_key_exists($answer_id, $trueAnswersArr[$question_id])) {
                                        $correct = 1;
                                        }else{
                                        $correct = 0;
                                    }
                                        $questions = new self();
                                        $questions->try_id= $try->id;
                                        $questions->question_id = $question_id;
                                        $questions->answer_id = $answer_id;
                                        $questions->correct = $correct;
                                        $questions->points = $answersArr[$question_id][$answer_id];
                                        $questions->save();
                                }
                            }
                        }
                    }
                }
                //if question is saved in DB /is answered from student/ delete it from array with quiz answers
                if($delete_question_id != ""){
                    unset($answersArr[$delete_question_id]);
                }
            }
        }
            if(isset($answers['open_answers'])){
                if(!empty($answers['open_answers'])){
                    $params = array();
                    $params["user_id"] = $user_id;
                    $params["quiz_id"] = $quiz_id;
                    //save answers of type "open" and "open-choices"
                    self::saveOpenSolvedAnswers("open", $params, $answers,$openAnswersArr, $try->id,$quiz_id);
                    self::saveOpenSolvedAnswers("open-choices", $params, $answers, $openAnswersArr,$try->id,$quiz_id);
                    return 1;
                }
            }else{
                QuizSolveTries::updateStatus($try->id);
                return 0;
            }
        }
    }

    public function saveOpenSolvedAnswers($type, $params, $answers,$openAnswersArr,$try,$quiz_id) {
        $data = array();
        $points_sum = 0;
        if(isset($answers['open_answers'][$type])){
            foreach($answers['open_answers'][$type] as $question_id => $answer){
                if (array_key_exists($question_id, $openAnswersArr[$type])) {
                        $params["question_id"] = $question_id;
                        $params["answer"] = $answer;
                        $params["type"] = $type;
                        $params['points'] = 0;
        
                    if($type == 'open-choices'){
                        $points_sum = self::checkOpenChoiceAnswers($quiz_id,$answers,$question_id);
                        $params['points'] =$points_sum;
                        QuizSolveOpenAnswers::saveSolvedAnswers($params, $try);
                    }else{
                        QuizSolveOpenAnswers::saveSolvedAnswers($params, $try);
                    }
                }
            }
        }
    }

    public static function getStudentAnswers($try_id,$quiz_id){
        if($try_id > 0 && $quiz_id > 0){
            $sql = 'SELECT a.id, a.question_id, a.correct, a.points, a.answer_id, b.type
                        FROM quiz_solve_answers as a
                        LEFT JOIN `quiz_questions` as b ON a.question_id = b.id
                        WHERE a.try_id = ? ',[$try_id];
    
            $sql1 = 'SELECT a.id, a.type, a.question_id, a.points, a.answer
                        FROM quiz_solve_open_answers as a
                        LEFT JOIN `quiz_questions` as b ON a.question_id = b.id
                        WHERE a.try_id = ?',[$try_id];
    
            $sql3 = 'SELECT id,points
                        FROM quiz_questions as a
                        WHERE a.type = "open" AND a.quiz_id = ?',[$quiz_id];
    
            $sql4 = 'SELECT *
                        FROM quiz_questions as a
                        WHERE a.quiz_id = ? AND a.type="open-choices"',[$quiz_id];
    
            $student_answers = self::find_by_sql($sql);
            $teacher_open_points = self::find_by_sql($sql3);
            $teacher_open_choices_points = self::find_by_sql($sql4);
            $data = array();
            foreach($student_answers as $answer){
                $data[$answer->type][$answer->question_id][$answer->answer_id]['correct'] = $answer->correct;
                $data[$answer->type][$answer->question_id][$answer->answer_id]['points'] = $answer->points;
            }
    
            $student_open_answers = self::find_by_sql($sql1);
            foreach($student_open_answers as $answer){
                    $data[$answer->type][$answer->question_id]['points'] = $answer->points;
                    $data[$answer->type][$answer->question_id]['answer'] = $answer->answer;
                    $data[$answer->type][$answer->question_id]['id'] = $answer->id;
            }
            foreach($teacher_open_points as $question){
                $data['open'][$question->id]['max_points'] = $question->points;
            }
    
            foreach($teacher_open_choices_points as $choice){
                $data['open-choices'][$choice->id]['max_points'] = $choice->points;
            }
            return $data;
        }
    }

    public static function getStudentTotalPoints($try_id){
        if($try_id > 0){
            $total_points = 0;
            $sql = 'SELECT SUM(a.points) as points
                        FROM quiz_solve_answers as a
                        LEFT JOIN `quiz_solve_tries` as b ON b.id = a.try_id
                        WHERE a.try_id = ?',[$try_id];
    
            $sql1 = 'SELECT SUM(a.points) as points
                        FROM quiz_solve_open_answers as a
                        LEFT JOIN `quiz_solve_tries` as b ON b.id = a.try_id
                        WHERE a.try_id = ?',[$try_id];
    
            $student_close_answers_points = self::find_by_sql($sql);
            $student_open_answers_points = self::find_by_sql($sql1);

            $close_answer_points = $student_close_answers_points[0]->points;
            $open_answer_points = $student_open_answers_points[0]->points;
            if(empty($student_close_answers_points)){
                $close_answer_points = 0;
            }
            
            if(empty($student_open_answers_points)){
                $open_answer_points = 0;
            }
           return $close_answer_points + $open_answer_points;
        }   
    }

    public function checkOpenChoiceAnswers($quiz_id,$answers,$question_id){
        if($question_id > 0 && $quiz_id > 0 && is_array($answers) && !empty($answers)){
            $teacher_choices = QuizOpenAnswerChoices::getOpenChoiceQuestions($quiz_id);
            foreach($teacher_choices as $number => $choice){
                $data[$choice['choices'][$question_id]] = $choice['points'][$question_id];
            }
    
            $points_sum = 0;
            foreach($data as $word => $points){
            //whole student answer
                $ans = mb_strtolower($answers['open_answers']['open-choices'][$question_id]);
                if (mb_stripos($ans, trim(strip_tags($word))) !== FALSE){
                    $openChoiceLength = QuizOpenAnswerChoices::sumOpenChoiceAnswersLength($quiz_id);
                        if(!empty($openChoiceLength) && isset($openChoiceLength[$question_id])){
                            $answer_length = mb_strlen(trim(strip_tags($ans)), 'UTF-8');
                                if($openChoiceLength[$question_id]+2 >= $answer_length){
                                    $pos = strpos($ans, trim(strip_tags($word)));
                                    $word_len = mb_strlen(trim(strip_tags($word)), 'UTF-8');
    
                                    $character_before_answer = substr($ans, $pos-1, 1);
                                    $character_after_answer = substr($ans, $pos + $word_len, 1);
    
                                    if($pos == 0){
                                       $character_before_answer = " ";
                                    }
    
                                    if($character_after_answer == ""){
                                        $character_after_answer = " ";
                                    }
    
                                    if(($character_before_answer == " " || $character_before_answer == "," || $character_before_answer == "-") && ($character_after_answer == " " || $character_after_answer == "," || $character_after_answer == "-")) {
                                        $points_sum += $points;
                                    }
                                }
                        }
                }
            }
        return $points_sum;
        }
    }

}