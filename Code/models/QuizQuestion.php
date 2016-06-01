<?php

/**
 * QuizQuestion
 * 
 * 
 */
class QuizQuestion extends PZ_BaseModel {

    static $belongs_to = array(
    );
    static $has_many = array(
    );

    /**
     * Get question row from database by id
     * 
     * @param int $question_id
     * @return QuizQuestion
     */
    public static function getQuestionInfoById($question_id) {
		if($question_id > 0){
			return self::find($question_id);
		}
    }

    /**
     * Get question row from database by quiz id
     * 
     * @param int $quiz_id
     * @return type
     */
    public static function getQuestionsByQuizId($quiz_id) {
		if($quiz_id > 0){
			return self::find_all_by_quiz_id($quiz_id);
		}
    }

		public static function getRandomizedQuestionsByQuizId($quiz_id,$random) {
			if($quiz_id > 0){
				if($random == 1){
					$sql = "SELECT * FROM `quiz_questions` WHERE quiz_id = ? order by RAND()";
					return self::find_by_sql($sql,[$quiz_id]);
				}else{
					return self::find_all_by_quiz_id($quiz_id);
				}
			}
		}

    /**
     * Get number of questions for quiz
     * 
     * @param type $quiz_id
     * @return QuizQuestion
     */
    public static function getNumberOfQuestions($quiz_id) {
		if($quiz_id > 0){
			$countQuestions = self::count(array('conditions' => array('quiz_id = ?', $quiz_id)));
			return $countQuestions;
		}
    }

    public static function deleteQuestion($question_id) {
		if($question_id > 0){
			$question = self::find($question_id);
			$question->delete();
		}
    }

    public static function createQuestion($params) {
		if(is_array($params) && !empty($params)){
			$count = self::getNumberOfQuestions($params['id']);
				if ($params['question'] != '') {
					$questions = new self();
					$questions->quiz_id = (int) $params['id'];
					$questions->question = $params['question'];
					$questions->type = $params['type'];
						if ($params['type'] == 'open-choices') {
							$questions->points = $params['sum_points'];
						}else{
							$questions->points = $params['points'];
						}
					$questions->number = $count + 1;
					$questions->save();
					$questionId = $questions->id;
				}
				
				if ($questionId > 0) {
					if ($params['type'] == 'open-choices') {
						QuizOpenAnswerChoices::createOpenChoicesQuestion($params, $questionId);
					} else {
						QuizAnswer::createAnswers($params, $questionId);
					}
				} else {
					return false;
				}
					return $questionId;
		}
    }

    public static function updateOrder($quiz_id) {
		if($quiz_id > 0){
			$questions = self::getQuestionsByQuizId($quiz_id);
			foreach ($questions as $number => $question) {
				self::query('UPDATE quiz_questions
							SET number = ?
							WHERE id = ?', [($number + 1), $question->id]);
			}
		}
    }

	public static function copyQuestion($params) {
		if(is_array($params) && !empty($params)){
			//get all questions for quiz which we want to copy
			$question = self::find($params['question_id']);
			$number_questions = self::getNumberOfQuestions($params['id']);
	
			//helper array for questions
			$tempQuestions = 0;
			//make new object from QuizQuestion
			$cloneQuestions = new self();
			//clone current question in helper array
			$tempQuestions = clone $question;
			//save old question id in variable before reset it
			$question_id = $question->id;
			//reset question id to zero
			$question->id = 0;
			//reset question number to zero
			$question->number = 0;
			//set question number to number of questions +1
			$question->number = $number_questions+1;
			//save new question in array and make row with question
			$newQuestions[$question_id] = $cloneQuestions->create($question->attributes());
	
				$answers = QuizAnswer::getQuestionAnswers($question_id, $question->type);
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

    public static function updateQuestion($params) {
		if(is_array($params) && !empty($params)){
			$question_id = (int) $params['id'];
			$question = self::find($question_id);
				if ($question) {
					$question->question = $params['question'];
					if ($params['type'] == 'open-choices') {
						$question->points = $params['sum_points'];
					}else{
						$question->points = $params['open_points'];
					}
					if ($question->save()) {
						if ($question->type == 'open-choices') {
							QuizOpenAnswerChoices::editOpenChoiceQuestion($params, $question_id);
						} else {
							QuizAnswer::editAnswers($params, $question->type);		  	
						}
					} else {
						return false;
					}
				}
			return true;
		}
    }

}