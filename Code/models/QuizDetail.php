<?php

/**
 * QuizDetail
 * 
 * 
 */
class QuizDetail extends PZ_BaseModel {

    static $belongs_to = array(
    );
    static $has_many = array(
    );

    /**
     * Create quiz
     * 
     * @param int $userId
     * @param array $params
     * @return boolean
     */
    public static function createQuiz($userId) {

        if ($userId > 0) {
            $quiz = new QuizDetail();
            $quiz->user_id = (int) $userId;
            $quiz->name = "Default quiz name";
            $quiz->description = "";
            $quiz->instruction = "";
            $quiz->type = "entry-level";
            $quiz->quiz_points = 0;
            $quiz->min_points = 0;
            $quiz->min_type = "";
            $quiz->save();
        return $quiz->id;
        }

    }

    /**
     * Edit quiz
     * 
     * @param int $id
     * @param array $details
     * @return boolean
     */
    public static function editQuiz($params) {
        if ($params['id'] && !empty($params['details']) && is_array($params['details'])) {
            $quiz = self::find($params['id']);
            if ($quiz) {
                $quiz->name = $params['details'][0]['value'];
                $quiz->description = $params['details'][1]['value'];
                $quiz->instruction = $params['details'][2]['value'];
                $quiz->type = $params['type'][0]['value'];
                if ($quiz->save()) {
                    return true;
                }
            }
        }
        return false;
    }


    public static function updateQuizStatusAndTime($params) {
        if (!empty($params)) {
            $quiz = self::find($params['id']);
            if ($quiz) {
                $quiz->min_points = $params['min_points'];
                $quiz->min_type = $params['min_type'];
                $quiz->active = $params['active'];
                if ($quiz->save()) {
                    return true;
                }
            }
        }
    
        return false;
    
    }

    /**
     * Delete quiz
     * 
     * @param int $id
     * @return boolean
     */
    public static function deleteQuiz($id) {

        if ((int) $id > 0) {
            $quiz = QuizDetail::find($id);
            if ($quiz->delete()) {
                return true;
            }
        }
        return false;

    }

    /**
     * Order quize
     * 
     * @param int $quiz_id
     * @param array $order
     */
    public static function orderQuiz($quiz_id, array $order) {
        if (!empty($order)) {
            foreach ($order as $key => $id) {
                $question = QuizQuestion::find_by_id_and_quiz_id($id, $quiz_id);
                $question->number = $key + 1;
                $question->save();
            }
        }

    }

    /**
     * Change quiz status (active or not active)
     * 
     * @param array $params
     * @return boolean
     */
    public static function changeQuizStatus(array $params) {
        if(!empty($params)){
            $quiz = self::find($params['id']);
            if ($quiz) {
                $quiz->active = (int) $params['status'];
                return $quiz->save();
            }
        }
        return false;
    }

    public static function makeCopy(array $params) {
        if(!empty($params)){
            //get current quiz
            $quiz = self::find($params['id']);
            // new QuizDetails object
            $cloneQuiz = new self;
            //clear quiz id to zero
            $quiz->id = 0;
            $quiz->active = 0;
            //create quiz
            $newQuiz = $cloneQuiz->create($quiz->attributes());
            //check if quiz is created
            if (!$newQuiz) {
                return false;
            }
            //get all questions for quiz which we want to copy
            $questions = QuizQuestion::find_all_by_quiz_id($params['id']);
            //helper array for questions
            $tempQuestions = [];
            //check for existing questions
            if (is_array($questions) && !empty($questions)) {
                foreach ($questions as $key => $question) {
                    //make new object from QuizQuestion
                    $cloneQuestions = new QuizQuestion();
                    //clone current question in helper array
                    $tempQuestions[$key] = clone $question;
                    //save old question id in variable before reset it
                    $question_id = $question->id;
                    //reset question id to zero
                    $question->id = 0;
                    //change quiz id to copied quiz id
                    $question->quiz_id = $newQuiz->id;
                    //save new question in array and make row with question
                    $newQuestions[$question_id] = $cloneQuestions->create($question->attributes());
                }
                //check if questions exits
                if (is_array($tempQuestions) && !empty($tempQuestions)) {
                    foreach ($tempQuestions as $question) {
                            $answers = QuizAnswer::getQuestionAnswers($question->id, $question->type);
                            if (is_array($answers) && !empty($answers)) {
                                foreach ($answers as $key => $answer) {
                                    if ($question->type == 'open-choices') {
                                        $cloneAnswers = new QuizOpenAnswerChoices();
                                    } else {
                                        $cloneAnswers = new QuizAnswer();
                                    }
    
                                    $answer->id = 0;
                                    $answer->question_id = $newQuestions[$answer->question_id]->id;
                                    $cloneAnswers->create($answer->attributes());
                                }
                            }
                    }
                }
            }
            return true;
        }
    }

    public static function getQuiz($quiz_id) {
        if($quiz_id > 0){
            return self::find($quiz_id);
        }
    }

    public static function getQuizType($quiz_id) {
        if($quiz_id > 0){
            return QuizDetail::find('all', ['select' => 'type', 'conditions' => ['id = ?', $quiz_id]]);
        }
    }

     public static function updateQuizMaxPoints($quiz_id,$quiz_points) { 
        if ($quiz_id) {
            $quiz = self::find($quiz_id);
            $quiz->quiz_points = $quiz_points;
            return $quiz->save();
        }
            return false;
     }

    public static function getQuizTotalPoints($quiz_id){
				if($quiz_id > 0){
						$sql = 'SELECT SUM(a.points) as sum FROM quiz_questions as a
                    WHERE a.quiz_id =?',[$quiz_id];
						$question_points = self::find_by_sql($sql);
						return $question_points[0]->sum ;
				}
		}

    public static function deactivateQuiz($quiz_id){
				if($quiz_id > 0){
						$sql = 'SELECT id FROM quiz_courses as a
                    WHERE a.quiz_id =?',[$quiz_id];

            $quiz_courses = self::find_by_sql($sql);
						if(empty($quiz_courses)){
                $quiz = self::find_by_id($quiz_id);
                $quiz->active = 0;
                $quiz->save();
            }else{
                return false;
            }
				}
		}
    
}
