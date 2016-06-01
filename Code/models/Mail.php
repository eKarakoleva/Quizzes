<?php
class Mail extends PZ_BaseModel
{
	static $belongs_to = array(
			// array('products'),
			// array('currencies'),
	);
	
	static $has_many = array(
			// array('orders'),
			// array('product_packages'),
	);
	
	public function before_save() {
		$params = unserialize($this->params);
		$view = new Zend_View();
		$params['contacts_us_list'] = $view->serverUrl().$view->url(array(), 'static_contact');
		
		$this->assign_attribute('params', serialize($params));
	}
	public static function teacherFeedback($params, $receiver, $realm) {
        $mail = new Mail;
        $mail->subject = $params['subject'];

        if (Zend_Validate::is($receiver, 'EmailAddress')) {
			$mail->email = $receiver;

		} else {
			$mail->user_id = $receiver;
		}		
        
        $mail->template = 'teacher-feedback-email';
        $mail->send_after = date('Y-m-d H:i:s');      
        $mail->params = serialize(array(                                    
                                        'message' => htmlspecialchars($params['message']),
                                        'teacher_fname' => $params['teacher']->fname,
                                        'teacher_lname' => $params['teacher']->lname,

                                        'child_fname' => $params['child']->fname,
                                        'child_lname' => $params['child']->lname,                                      

                                        'realm' => $realm, 
                                        'domain' => $_SERVER['HTTP_HOST']                           
                                    ));
        
        $mail->save();
    }

		public function sendQuizMail($params) {

			if(!empty($params)){
       foreach($params as $student_id => $param){

          $mail = new Mail;
          $mail->subject = $param['subject'];
					if(!empty($student_id)){
						if(!empty($param['email'])){
							$mail->email = $param['email'];
							$mail->user_id = $student_id;
						}else{
							$mail->user_id = $student_id;
						}
					}

          $mail->template = $param['template'];
          $mail->send_after = date('Y-m-d H:i:s');
					
			if(isset($param['student_fname'])){

          $mail->params = serialize(array(                                                                         
																		'course_name' => $param['course_name'],
                                    'lesson_name' => $param['lesson_name'],
                                    'quiz_name' => $param['quiz_name'],
																		'quiz_id' => $param['quiz_id'],
																		'user_id' => $param['user_id'],
																		'student_fname' => $param['student_fname'],
																		'student_lname' => $param['student_lname'],
																		'grade' => $param['grade'],
																		'try_id' => $param['try_id'],
                                    'realm' => $param['realm'], 
                                    'domain' => $param['domain']                           
                                  ));
                    
          $mail->save();
			}else{
					$mail->params = serialize(array(                                                                         
																		'course_name' => $param['course_name'],
                                    'lesson_name' => $param['lesson_name'],
                                    'quiz_name' => $param['quiz_name'],
																		'quiz_id' => $param['quiz_id'],
																		'user_id' => $param['user_id'],
																		'grade' => $param['grade'],
																		'try_id' => $param['try_id'],
                                    'realm' => $param['realm'], 
                                    'domain' => $param['domain']                           
                                  ));
                    
          $mail->save();

			}
				if(isset($param['parents']) && !empty($param['parents'])){
					foreach($param['parents'] as $parent_id => $email){
						$parent_mail = new Mail;
						$parent_mail->subject = $param['subject'];

						if(!empty($parent_id)){
								if(!empty($email)){
										$parent_mail->email = $email;
										$parent_mail->user_id = $parent_id;
								}else{
									$parent_mail->user_id = $parent_id;
								}
	
							$parent_mail->template = $param['template'];
							$parent_mail->send_after = date('Y-m-d H:i:s');      
							$parent_mail->params = serialize(array(                                                                         
																			'course_name' => $param['course_name'],
																			'lesson_name' => $param['lesson_name'],
																			'quiz_name' => $param['quiz_name'],
																			'quiz_id' => $param['quiz_id'],
																		  'user_id' => $param['user_id'],
																			'grade' => $param['grade'],
																			'try_id' => $param['try_id'],
																			'realm' => $param['realm'], 
																			'domain' => $param['domain']                           
																		));
											
							$parent_mail->save();
						}
					}	
				}
	      }
      }
		}

}