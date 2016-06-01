<?php

/**
 * QuizAnswer
 * 
 * 
 */
class QuizAnswer extends PZ_BaseModel {

    static $belongs_to = array(
    );
    static $has_many = array(
    );

    public static function setAnswers($questions) {

        $structData = [];
        if (!empty($questions)) {
            foreach ($questions as $question) {
                $structData[$question->id]['question'] = $question;
                $structData[$question->id]['answers'] = self::getChoices($question->id, $question->type);
            }
        }
        return $structData;
    }

	public static function setRandomizedAnswers($questions,$random) {
		if($random == 1){
			$structData = [];
	
			if (!empty($questions)) {
	
				foreach ($questions as $question) {
	
					$structData[$question->id]['question'] = $question;
					$structData[$question->id]['answers'] = self::getRandomizedChoices($question->id, $question->type,$random);
	
				}
			}
			return $structData;
		}else{
			return self::setAnswers($questions);
		}
    }

    public static function getChoices($question_id, $type) {
				if($question_id > 0){
						$answers = self::getQuestionAnswers($question_id, $type);
		
						$result = [];
						if (!empty($answers)) {
								foreach ($answers as $answer) {
										$result[] = $answer;
								}
						}
						return $result;
				}
    }

		public static function getRandomizedChoices($question_id, $type, $random) {
				if($question_id > 0){
						if($random == 1){
							$answers = self::getRandomizedQuestionAnswers($question_id, $type,$random);
					
							$result = [];
							if (!empty($answers)) {
								foreach ($answers as $answer) {
									$result[] = $answer;
								}
							}
							return $result;
						}else{
							return self::getChoices($question_id, $type);
						}
				}
		}


    //get all answers for current question
    public static function getQuestionAnswers($question_id, $type) {
				if($question_id > 0){
						if ($type != "open-choices") {
								$sql = "SELECT answer, a.number, a.id, a.correct, a.points, a.question_id
												FROM quiz_answers AS a
												INNER JOIN
														quiz_questions AS b
												ON
														a.question_id = b.id
												WHERE a.question_id = ? ORDER by number
												";
						} else {
								$sql = 'SELECT a.number, a.id, a.choice, a.points, a.question_id
												FROM quiz_open_answer_choices AS a
												INNER JOIN
														quiz_questions AS b
												ON
														a.question_id = b.id
												WHERE a.question_id = ? ORDER by number';
						}
						return self::find_by_sql($sql, [$question_id]);
				}
    }

		public static function getRandomizedQuestionAnswers($question_id, $type = '',$random) {
				if($question_id > 0){
						if($random == 1){
							if ($type != "open-choices") {
								$sql = "SELECT answer, a.number, a.id, a.correct, a.points, a.question_id
															FROM quiz_answers AS a
															INNER JOIN
																	quiz_questions AS b
															ON
																	a.question_id = b.id
															WHERE a.question_id = ? ORDER by RAND()
															";
							} else {
								$sql = 'SELECT a.number, a.id, a.choice, a.points, a.question_id
															FROM quiz_open_answer_choices AS a
															INNER JOIN
																	quiz_questions AS b
															ON
																	a.question_id = b.id
															WHERE a.question_id = ? ORDER by RAND()';
							}
							return self::find_by_sql($sql, [$question_id]);
						}else{
							return self::getQuestionAnswers($question_id, $type);
						}
				}
    }

		public static function getQuestionTrueAnswers($question_id, $type = '') {
				if($question_id > 0){
						if ($type != "open-choices") {
								$sql = "SELECT answer, a.number, a.id, a.correct, a.points, a.question_id
												FROM quiz_answers AS a
												INNER JOIN
														quiz_questions AS b
												ON
														a.question_id = b.id
												WHERE a.question_id = ? AND a.correct = 1 ORDER by number
												";
						} else {
								$sql = 'SELECT a.number, a.id, a.choice, a.points, a.question_id
												FROM quiz_open_answer_choices AS a
												INNER JOIN
														quiz_questions AS b
												ON
														a.question_id = b.id
												WHERE a.question_id = ? ORDER by number';
						}
						return self::find_by_sql($sql, [$question_id]);
				}
    }

    public static function getQuestionAnswerById($answer_id, $type = '') {
		if($answer_id > 0){
			if ($type != "open-choices") {
				$answers = self::find($answer_id);
			} else {
				$answers = QuizOpenAnswerChoices::find($answer_id);
			}
				return $answers;
		}
    }

    //get number of answers for current question 
    public static function answersNumber($question_id) {
		if($question_id > 0){
			$countAnswers = self::count(array('conditions' => array('question_id = ?', $question_id)));
			return $countAnswers;
		}
    }

    public static function createAnswers($params, $questionId) {
		if(is_array($params) && !empty($params) && $questionId > 0){
			if($params['type'] != "open"){
				$number_true = count($params['check']);
				$points = ($params['points'] / $number_true);
				$points = sprintf('%.2f', $points);
					if ($number_true != 0) {
						$i = 0;
						$count = $number_true - 1;
						if (!empty($params['answers'])) {
							foreach ($params['answers'] as $number => $answer) {
								$point = 0;
								$answer_id = explode("_", $answer['name']);
								//if id of the answer in answer's name is equal with id of the correct /checked/ answers then answer is correct 
									if ($count >= 0 && $params['check'][$i]['name'] == $answer_id[1]) {
										//$point = $params['points'][$i]['value'];
										$point = $points;
										if (empty($point)) {
											$point = 0;
										}
											$correct = 1;
											$i +=1;
											$count -= 1;
									} else {
										$correct = 0;
									}
						
									if ($answer['value'] != '') {
						
										$selfAnswer = new self();
										$selfAnswer->question_id = (int) $questionId;
										$selfAnswer->answer = $answer['value'];
										$selfAnswer->number = $number + 1;
										$selfAnswer->correct = $correct;
										$selfAnswer->points = $point;
										$selfAnswer->save();
									}
							}
						}
					}
			}
		}
	}
    

    public static function editAnswers($params, $type) {
		if(is_array($params) && !empty($params)){
			$question_id = (int) $params['id'];
			if ($type != "open") {
				//get count of true answers				
				$number_true = count($params['status']);
				//initialize loop counter
				$points = ($params['points'] / $number_true);
				$points = sprintf('%.2f', $points);
				$i = 0;
				//get count of true answers minus one
				$count = $number_true - 1;
				// loop answers
					if (!empty($params['answers'])) {
						foreach ($params['answers'] as $number => $answer) {
							//reset point variable
							$point = 0;
							//split answer name by underscore
							$answer_id = explode("_", $answer['name']);
							//if id of the answer in answer's name is equal with id of the correct /checked/ answers then answer is correct 
								if ($count >= 0 && $params['status'][$i]['name'] == $answer_id[1]) {
									//if points field is empty then poins are 0
									$point = $points;
									if (empty($point)) {
										$point = 0;
									}
									//check if is not empty point for this answer
									if (!empty($params['points'][$i]['value'])) {
										//assign to $point varible point for this answer
										$point = $params['points'][$i]['value'];
									}
									//if answer is true set 1 in DB
									$correct = 1;
									//update counter
									$i +=1;
									//update counter
									$count -= 1;
								} else {
									$correct = 0;
								}
								//if name contains underscore then split it by it
								if (strpos($answer['name'], '_') !== false) {
									//split $answer['name'] by underscore
									$splited_name = explode("_", $answer['name']);
									//get first part of name (before underscore)
									$label = $splited_name[0];
								} else {
									$label = $answer['name'];
								}
		
								//if label == "answer" then new answer is added
								//if label == "answers" we edit created answer
								if ($label != "answer") {
								//edit answer
									$answers = self::find($answer_id[1]);
									if ($answers) {
										$answers->answer = $answer['value'];
										$answers->number = $number + 1;
										$answers->correct = $correct;
										$answers->points = $point;
										$answers->save();
									}
								} else {
									//if answer is not empty and new field is added
									if ($answer['value'] != '') {
										//create new answer
										$cr_new_answers = new self();
										$cr_new_answers->question_id = $question_id;
										$cr_new_answers->answer = $answer['value'];
										$cr_new_answers->number = $number + 1;
										$cr_new_answers->correct = $correct;
										$cr_new_answers->points = $point;
										$cr_new_answers->save();
									}
								}
						}
					}
			}
		}
    }

    public static function deleteAnswer($answer_id, $type) {
		if($answer_id > 0){
			$answer = self::getQuestionAnswerById($answer_id, $type);
			if ($type != "open-choices") {
				$answerDelete = self::find($answer_id);
				$answerDelete->delete();
			} else {
				$answerDelete = QuizOpenAnswerChoices::find($answer_id);
				$answerDelete->delete();
			}
			self::updateAnswersOrder($answer->question_id, $type);
		}
    }

    public static function updateAnswersOrder($question_id, $type) {
		if($question_id > 0){
			$answers = self::getQuestionAnswers($question_id, $type);
			if (!empty($answers)) {
				if ($type != "open-choices") {
					foreach ($answers as $number => $answer) {
							self::query('UPDATE `quiz_answers`
										SET number = ?
										WHERE id = ?', [($number + 1), $answer->id]);
					}
				} else {
					foreach ($answers as $number => $answer) {
						self::query('UPDATE `quiz_open_answer_choices`
									SET number = ?
									WHERE id = ?', [($number + 1), $answer->id]);
					}
				}
			}
		}
	}

}
