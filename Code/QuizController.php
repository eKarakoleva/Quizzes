<?php

/**
 * Quiz controller
 * 
 */
class Academy_QuizController extends PZ_AcademyBase {

    public function init() {
        parent::init();

        $accessSetting = RealmSettings::find_by_name_and_realm_id('quiz_active', $this->realm);
        $accessSetting = $accessSetting instanceof RealmSettings ? $accessSetting->value : 0;

        if ($accessSetting != 1) {
            $this->_redirector->gotoroute(array(), 'not_found');
        }

        if (!$this->identity['id']) {
            $this->_redirector->gotoroute(array(), 'login');
        }
        //if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin')) {
         //   $this->_redirector->gotoroute(array(), 'not_found');
        //}

        $this->include_javascript(array(
            'crud',
            'academy/quiz',
            'lib/ckeditor/ckeditor',
            'lib/jquery.validate.min',
            'lib/jquploader/js/jquery.fileupload',
            'lib/jquploader/js/vendor/jquery.ui.widget',
            'lib/jquploader/js/jquery.iframe-transport',
        ));

        //If ajax requests stop render
        if ($this->_request->isXmlHttpRequest()) {
            $this->_helper->viewRenderer->setNoRender(true);
            $this->_helper->layout->disableLayout();
        }

    }

    /**
     * Here we render index template 
     */
    public function indexAction() {
         if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin')) {
            $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }

    }

    public function loadAction() {

        if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin')) {
            $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }

        $sql_params = $this->getConditions(array(
            'search' => array(
                'string' => isset($this->_request->search) ? $this->_request->search : NULL,
                'fields' => array('qd.name')
            ),
            'order' => array(
                'fields' => array('qd.created_at'),
                'value' => (isset($this->_request->order) && is_array($this->_request->order)) ? $this->_request->order['value'] : '',
                'id' => (isset($this->_request->order) && is_array($this->_request->order)) ? $this->_request->order['id'] : ''
            ),
            'filter' => array(
                'fields' => array('qd.active'),
                'value' => (isset($this->_request->filter) && is_array($this->_request->filter)) ? $this->_request->filter['value'] : '',
                'id' => (isset($this->_request->filter) && is_array($this->_request->filter)) ? $this->_request->filter['id'] : ''
            ),
            'limit' => is_numeric($this->_request->limit) ? (int) $this->_request->limit : NULL,
            'offset' => is_numeric($this->_request->offset) ? (int) $this->_request->offset : 0,
            ''
        ));

        $sql = 'SELECT qd.id, qd.name, qd.type, qd.active, qc.course_id, qc.lesson_id,  vc.name as course_name, vc.max_participants, vc.participants_left, vl.name as lesson_name
                        FROM quiz_details as qd
                        LEFT JOIN `quiz_courses` as qc ON qc.quiz_id = qd.id
                        LEFT JOIN `v_courses` as vc ON qc.course_id = vc.id
                        LEFT JOIN `v_lessons` as vl ON qc.lesson_id = vl.id
                        WHERE qd.user_id=?',[(int) $this->identity['id']];

        $quizes = QuizDetail::find_by_sql($sql . $sql_params['where'] . $sql_params['order'] . $sql_params['limit'], $sql_params['binded']);

        $quizCourses = "";
        if (!empty($quizes)) {
            foreach ($quizes as $quiz) {
                $quizCourses[$quiz->id]['id'] = $quiz->id;
                $quizCourses[$quiz->id]['name'] = $quiz->name;
                $quizCourses[$quiz->id]['type'] = $quiz->type;
                $quizCourses[$quiz->id]['active'] = $quiz->active;
                $quizCourses[$quiz->id]['course_id'] = $quiz->course_id;
                $quizCourses[$quiz->id]['lesson_id'] = $quiz->lesson_id;

                if ($quiz->course_id) {
                    $quizCourses[$quiz->id]['course'][$quiz->course_id][$quiz->lesson_id]['course_name'] = $quiz->course_name;
                    $quizCourses[$quiz->id]['course'][$quiz->course_id][$quiz->lesson_id]['lesson_name'] = $quiz->lesson_name;
                    $quizCourses[$quiz->id]['course'][$quiz->course_id][$quiz->lesson_id]['max_participants'] = $quiz->max_participants;
                    $quizCourses[$quiz->id]['course'][$quiz->course_id][$quiz->lesson_id]['participants_left'] = $quiz->participants_left;
                }
            }
        }

        $count = QuizDetail::find_by_sql($sql);
        $data = array();
        $data['rows_number'] = count($count);
        $data['rows'] = array();

        if (is_array($quizCourses) && !empty($quizCourses)) {
        foreach ($quizCourses as $row) {
            $courseHTML = '';
            $type = $this->view->t($row['type'], [], false);
            $params = array($row['id'], $row['name'], $type, $row['active'],$row['course_id'], $row['lesson_id']);
            if(!empty($row['course'])){
                if (is_array($row['course'])) {
                    $courseHTML = '<table style="width: 100%;">';
                    $courseHTML .= '<tr><th>' . $this->view->t('QUIZ_COURSE_NAME') . '</th><th>' . $this->view->t('QUIZ_LESSON_NAME') . '</th><th>' . $this->view->t('QUIZ_TYPE') . '</th><th>' . $this->view->t('QUIZ_PARTICIPANTS') . '</th><th></th></tr>';
                    foreach ($row['course'] as $course_id => $course) {
                        foreach($course as $key =>$lesson ){
                            $courseHTML .= '<tr>';
                            $courseHTML .= '<td>' . $lesson['course_name'] . '</td>';
                            $courseHTML .= '<td>' . $lesson['lesson_name'] . '</td>';
                            $courseHTML .= '<td>' . $type . '</td>';
                            $courseHTML .= '<td>' . $lesson['participants_left'] . '/' . $lesson['max_participants'] . '</td>';
                            $courseHTML .= '<td><a href="#" data-id='.$row['id'].' data-lesson-id = '.$key.' data-course-id = '.$course_id.' class="col-lg-1 delete-course"><i class="fa fa-trash"></i></a></td>';                        
                            $courseHTML .= '</tr>';
                        }   
                    }
                    $courseHTML .= '</table>';
                } else {
                    $courseHTML = $this->view->t('QUIZ_NO_SELECTED_COURSES', [], false);
                }
            }
            array_push($params, $courseHTML);
            array_push($data['rows'], $params);
        
        }
        }
        if(isset($this -> search_str)){
            $data['search_str'] = $this -> search_str;
        }
        if(isset($this -> search_id)){
            $data['search_id'] = $this -> search_id;
        }

        echo json_encode($data);

    }
 

    public function viewAction() {

        if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin')) {
           $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }

        $request_quiz_id = $this->_request->id;
        //get quiz information
        $this->view->quiz = QuizDetail::find_by_id($request_quiz_id);
        //get questions for selected quiz id
        $questions = QuizQuestion::find_all_by_quiz_id($request_quiz_id, array('order' => 'number asc'));
        //set questions to view
        $this->view->questions = QuizAnswer::setAnswers($questions);
        //set quiz id to view
        $this->view->quiz_id = $request_quiz_id;

        $quiz_courses = QuizCourse::find_all_by_quiz_id($request_quiz_id);

        if(!empty($quiz_courses)){
            $this->view->courses_exist = 1;
        }else{
            $this->view->courses_exist = 0;
        }
    }

    public function createQuizAction() {
        if ($this->getRequest()->isPost()) {
            $type = $this->_request->type;
            $quiz = QuizDetail::createQuiz($this->identity['id'], $type[0]['value']);
            echo json_encode([$quiz]);
        }

    }

    //open form for add new question
    public function openQuestionAction() {
        if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin')) {
           $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }
        $this->view->id = $this->_request->id;
        $this->view->type = $this->_request->type;
    }

    public function deleteQuizCourseAction() {
        QuizCourse::deleteQuizCourse($this->_request->id,$this->_request->course_id,$this->_request->lesson_id);
    }

    //save order of questions
    public function orderAction() {
        QuizDetail::orderQuiz($this->_request->id, $this->_request->item);
    }

    public function createQuestionAction() {
        if ($this->getRequest()->isPost()) {
            $question_id = QuizQuestion::createQuestion($this->_request->getParams());
            $current_quiz_points = QuizDetail::getQuizTotalPoints($this->_request->id);
            QuizDetail::updateQuizMaxPoints($this->_request->id,$current_quiz_points);
            if ($question_id) {
                echo json_encode(['id' => $question_id]);
            }
        }
    }

    /**
     * Open form filled with question info for edit
     */
    public function editableQuestionAction() {
        if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin')) {
            $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }
        $this->view->question = QuizQuestion::getQuestionInfoById($this->_request->id);
        $this->view->answers = QuizAnswer::getQuestionAnswers($this->_request->id, $this->view->question->type);
        
    }

    /**
     * Update DB when submit form for questions of type "open", "multichoice", "radio"
     */
    public function editQuestionAction() {
        if ($this->getRequest()->isPost()) {
            $question = QuizQuestion::updateQuestion($this->_request->getParams());
            $current_quiz_points = QuizDetail::getQuizTotalPoints($this->_request->quiz_id);
            QuizDetail::updateQuizMaxPoints($this->_request->quiz_id,$current_quiz_points);
            static::setResp($question);
        }

    }

    public function deleteQuestionAction() {
        QuizQuestion::deleteQuestion($this->_request->id);
        QuizQuestion::updateOrder($this->_request->quiz);
        $current_quiz_points = QuizDetail::getQuizTotalPoints($this->_request->quiz);
        QuizDetail::updateQuizMaxPoints($this->_request->quiz,$current_quiz_points);
    }

    public function deleteAnswerAction() {
        QuizAnswer::deleteAnswer($this->_request->id, $this->_request->type);
    }

    public function deleteQuizAction() {
        $delete = QuizDetail::deleteQuiz($this->_request->id);
        static::setResp($delete);
    }

    public function editQuizDetailsAction() {
        $edit = QuizDetail::editQuiz($this->_request->getParams());
        static::setResp($edit);
    }

    public function changeStatusAction() {
        $editStatus = QuizDetail::changeQuizStatus($this->_request->getParams());
        static::setResp($editStatus);

    }

    public function copyQuizAction() {
        $copy = QuizDetail::makeCopy($this->_request->getParams());
        static::setResp($copy);
    }

    public function addCourseAction() {
         if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin')) {
            $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }
        $quiz = QuizDetail::getQuiz($this->_request->quiz_id);

        $this->view->quiz = $quiz;

        echo json_encode(array('modal' => $this->view->render('quiz/add-course.phtml')));

    }

    public function activeSettingsAction() {
         if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin')) {
            $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }

        $quiz = QuizDetail::getQuiz($this->_request->quiz_id);
        $this->view->quiz_points = $quiz->quiz_points;
        $this->view->min_points = $quiz->min_points;
        $this->view->min_type =  $quiz->min_type;
        $this->view->quiz_id =  $this->_request->quiz_id;
        echo json_encode(array('modal' => $this->view->render('quiz/activate-quiz-modal.phtml')));
    }

    public function activateQuizAction() {
        $res = QuizDetail::updateQuizStatusAndTime($this->_request->getParams());
        static::setResp($res);
    }

    public function deactivateQuizAction() {
        $res = QuizDetail::deactivateQuiz($this->_request->quiz_id);
        static::setResp($res);
    }

    public function courseSearchAction() {
         if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin')) {
            $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }
        $search = $this->_request->search;
        //$this->identity['id']
        if ($this->view->hasRole('Organization') || $this->view->hasRole('Admin')) {
            $courses = QuizCourse::findCourses($search, 0, $this->realm);
        }

        $courses = QuizCourse::findCourses($search, $this->identity['id'], $this->realm);

        $this->view->courses = $courses;
        echo $this->view->render('quiz/suggestions.phtml');

    }

    public function courseLectionsAction() {
         if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin')) {
            $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }
        $lessons = QuizCourse::findLessons($this->_request->course_id, $this->realm, $this->_request->type);
        $this->view->lessons = $lessons;
        echo $this->view->render('quiz/course-lections.phtml');

    }

    public function saveCourseAction() {
        $course = QuizCourse::saveQuizCourse($this->_request->getParams());

        $sql = 'SELECT a.user_id, b.name as course_name, c.name as lesson_name, d.name as quiz_name, e.email, f.parent_id,
                    (select u.email from users as u where f.parent_id = u.id) as parent_mail
                    FROM v_course_users as a
                    LEFT JOIN `v_courses` as b ON b.id = a.v_course_id
                    LEFT JOIN `v_lessons` as c ON c.id = ?
                    LEFT JOIN `quiz_details` as d ON d.id =?
                    LEFT JOIN `users` as e ON e.id = a.user_id
                    LEFT JOIN `user_parents` as f ON f.user_id = e.id
                    WHERE a.v_course_id = ?',[$this->_request->lesson_id,(int) $this->_request->quiz_id,(int) $this->_request->course_id];
 
        $courses_users = VCourseUser::find_by_sql($sql);
        $data = [];
        if(!empty($courses_users) && $this->_request->course_id > 0){
            foreach($courses_users as $k=>$v){
                $data[$v->user_id]['email'] = $v->email;
                $data[$v->user_id]['parents'][$v->parent_id] = $v->parent_mail;
                $data[$v->user_id]['course_name'] = $courses_users[0]->course_name;
                $data[$v->user_id]['lesson_name'] = $courses_users[0]->lesson_name;
                $data[$v->user_id]['quiz_name'] = $courses_users[0]->quiz_name;
                $data[$v->teacher_id]['user_id'] =$v->user_id;
                $data[$v->teacher_id]['quiz_id'] = $this->_request->quiz_id;
                $data[$v->teacher_id]['try_id'] = 0;
                $data[$v->teacher_id]['grade'] = 0;
                $data[$v->user_id]['realm'] = $this->realm;
                $data[$v->user_id]['domain'] = $_SERVER['HTTP_HOST'];
                $data[$v->user_id]['subject'] = "Предстоящ тест";
                $data[$v->user_id]['template'] = 'quiz_is_ready_for_solve';
            }        
            Mail::sendQuizMail($data);
        } 
        echo self::setResp($course);
    }

    public function copyQuestionAction() {
        $question = QuizQuestion::copyQuestion($this->_request->getParams());
        echo self::setResp($question);

    }

    public static function setResp($resp) {
        if ($resp) {
            echo json_encode(['status' => 'successfully']);
        } else {
            echo json_encode(['status' => 'failed']);
        }

    }

//.......................................................................
//solve quizzes

    public function printActiveQuizAction() {
        
    }

    public function activeLoadAction() {
        if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin') && !$this->view->hasRole('User')) {
            $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }

        $sql_params = $this->getConditions(array(
            'search' => array(
                'string' => isset($this->_request->search) ? $this->_request->search : NULL,
                'fields' => array('c.name','d.name')
            ),
            'order' => array(
                'fields' => array('c.updated_at', 'course_name', 'c.name'),
                'value' => (isset($this->_request->order) && is_array($this->_request->order)) ? $this->_request->order['value'] : '',
                'id' => (isset($this->_request->order) && is_array($this->_request->order)) ? $this->_request->order['id'] : ''
            ),
            'limit' => is_numeric($this->_request->limit) ? (int) $this->_request->limit : NULL,
            'offset' => is_numeric($this->_request->offset) ? (int) $this->_request->offset : 0,
            ''
        ));


        //display active quizzes by lesson id and course id????
        $sql = 'SELECT a.lesson_id, a.id, a.course_id , b.user_id, c.name, c.updated_at,c.id, d.name as course_name, a.tries
                       FROM quiz_courses as a
                        LEFT JOIN `v_course_users` as b ON b.v_course_id = a.course_id
                        LEFT JOIN `quiz_details` as c ON c.id = a.quiz_id
                        LEFT JOIN `v_courses` as d ON a.course_id = d.id
                        WHERE c.active = 1 AND b.user_id =? 
                        AND (
                            SELECT if(count(*) < a.tries, 1, 0)
                            FROM quiz_solve_tries AS e
                            WHERE e.course_id = a.course_id
                            AND e.lesson_id = a.lesson_id
                            AND e.quiz_id = a.quiz_id
                            AND e.user_id = b.user_id
                            AND e.status != 1
                        )
                        
                        ',[(int) $this->identity['id']];

        $quizzes = QuizCourse::find_by_sql($sql . $sql_params['where'] .' GROUP BY a.id '. $sql_params['order'] . $sql_params['limit'], $sql_params['binded']);
        $quizes = QuizCourse::find_by_sql($sql);

        $data = array();
        $data['rows'] = array();
        $data['rows_number'] = count($quizes);

        if (!empty($quizes)) {
            foreach ($quizzes as $quiz) {
                $lesson_name = QuizCourse::getLessonName($quiz->lesson_id);
                $params = [$quiz->id, $quiz->name, $quiz->course_id,$quiz->lesson_id,$lesson_name[0]->name,$quiz->course_name];
                array_push($data['rows'], $params);
            }
        }
        if(isset($this -> search_str)){
            $data['search_str'] = $this -> search_str;
        }
        if(isset($this -> search_id)){
            $data['search_id'] = $this -> search_id;
        }
        echo json_encode($data);
    }

    //get all questions with their answers and print them
    public function solveQuizAction() {
            //new try begin
            if (!$this->view->hasRole('User')) {
                 $this->_redirector->gotoroute(array(), 'not_found');
                 return;
             }
            $try = QuizSolveTries::addTry($this->_request->getParams(), $this->identity['id']);
            $time = QuizSolveTries::checkTime($try,$this->_request->getParams());
            $this->view->time = $time;

            //get avtive try
            $active_try = QuizSolveTries::find_by_course_id_and_lesson_id_and_quiz_id_and_status($this->_request->course_id, $this->_request->lesson_id,$this->_request->id,1);
            //check if user have left tries and there is active try
            if(QuizSolveTries::checkLeftTries($this->_request->getParams(),$this->identity['id']) && !empty($active_try)){
                $randomQuestionOption = QuizCourse::getDisarrangeQuestionsOption($this->_request->getParams());
                $randomAnswerOption = QuizCourse::getDisarrangeAnswerOption($this->_request->getParams());
                $questions = QuizQuestion::getRandomizedQuestionsByQuizId($this->_request->id,$randomQuestionOption);
                $this->view->questions = QuizAnswer::setRandomizedAnswers($questions,$randomAnswerOption);
                $this->view->quiz = QuizDetail::find_by_id($this->_request->id);
                $this->view->quiz_id = $this->_request->id;
                $this->view->course_id = $this->_request->course_id;
                $this->view->lesson_id = $this->_request->lesson_id;

                $this->view->openChoicesLength = QuizOpenAnswerChoices::sumOpenChoiceAnswersLength($this->_request->id);
            }else{
                //here must be redirector to a page that tell user there is no tries left
                $this->_redirector->gotoroute(array(), 'not_found');
            }
    }

    public function finishQuizAction() {
        if (!$this->view->hasRole('User')) {
            $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }
        $active_try = QuizSolveTries::find_by_course_id_and_lesson_id_and_quiz_id_and_status($this->_request->course_id, $this->_request->lesson_id,
                                                                                                                        $this->_request->id,1);
        if(!empty($active_try)){
            if(QuizSolveTries::checkLeftTries($this->_request->getParams(),$this->identity['id'])){
                $return = QuizSolveAnswers::saveUserAnswers($this->_request->id, $this->identity['id'], $this->_request->getParams());
                if($return == 1){
                    QuizSolveTries::endTry($active_try->id,$this->_request->getParams());
                    //mail teacher
                    $sql = 'SELECT a.name as quiz_name, a.user_id as teacher_id, b.name as course_name, c.name as lesson_name, d.email
                            FROM quiz_details as a
                            LEFT JOIN `v_courses` as b ON b.id = ?
                            LEFT JOIN `v_lessons` as c ON c.id = ?
                            LEFT JOIN `users` as d ON d.id = a.user_id
                            WHERE a.id = ?',[$this->_request->course_id,$this->_request->lesson_id,(int) $this->_request->id];

                    $sql2 = 'SELECT a.id as try_id, b.id, b.fname, b.lname FROM quiz_solve_tries as a
                            LEFT JOIN users as b ON a.id = b.id
                            WHERE a.id =?',[$active_try->id];
                            
                    $student = QuizSolveTries::find_by_sql($sql2);

                    $params = QuizDetail::find_by_sql($sql);
                    if(!empty($params) && $this->_request->id > 0){
                        foreach($params as $k=>$v){
                            $data[$v->teacher_id]['email'] = $v->email;
                            $data[$v->teacher_id]['course_name'] = $v->course_name;
                            $data[$v->teacher_id]['lesson_name'] = $v->lesson_name;
                            $data[$v->teacher_id]['quiz_name'] = $v->quiz_name;
                            $data[$v->teacher_id]['student_fname'] =  $student[0]->fname;
                            $data[$v->teacher_id]['student_lname'] =  $student[0]->lname;
                            $data[$v->teacher_id]['user_id'] = $student[0]->id;
                            $data[$v->teacher_id]['quiz_id'] = $this->_request->id;
                            $data[$v->teacher_id]['try_id'] = $student[0]->try_id;
                            $data[$v->teacher_id]['grade'] = 0;
                            $data[$v->teacher_id]['realm'] = $this->realm;
                            $data[$v->teacher_id]['domain'] = $_SERVER['HTTP_HOST'];
                            $data[$v->teacher_id]['subject'] = "Предаден тест";
                            $data[$v->teacher_id]['template'] = 'quiz_is_ready_for_evaluation';
                        }         
                        Mail::sendQuizMail($data);
                    }
                }else{
                    $try_info =QuizSolveTries::getTryId($this->_request->id,$this->_request->course_id,$this->_request->lesson_id);
                    $total_points = QuizSolveAnswers::getStudentTotalPoints($try_info[0]->id);
                    $quiz_info = QuizDetail::getQuiz($this->_request->id);
                    $grade = self::assessmentsCriteria($quiz_info->min_points,$quiz_info->quiz_points,$total_points,$quiz_info->min_type);
                    QuizSolveTries::setGrade($try_info[0]->id, $grade);

                    //mail parents and students
                    self::sendMailForEvaluatedQuiz($this->realm,$_SERVER['HTTP_HOST'],0,$this->_request->id,$this->_request->lesson_id,$this->_request->course_id,$grade);
                }
                
            }
        }
    }

//.................................................
//evaluate

    public function listCheckQuizzesAction() {

    }

    public function listCheckQuizzesLoadAction() {

        //@todo
    
        $sql_params = $this->getConditions(array(
            'search' => array(
                'string' => isset($this->_request->search) ? $this->_request->search : NULL,
                'fields' => array('b.name','d.name')
            ),
            'order' => array(
                'fields' => array('a.id', 'b.name', 'b.updated_at','c.fname','course_name','e.meta_value_id'),
                'value' => (isset($this->_request->order) && is_array($this->_request->order)) ? $this->_request->order['value'] : '',
                'id' => (isset($this->_request->order) && is_array($this->_request->order)) ? $this->_request->order['id'] : ''
            ),
            'filter' => array(
                'fields' => array('a.status'),
                'value' => (isset($this->_request->filter) && is_array($this->_request->filter)) ? $this->_request->filter['value'] : '',
                'id' => (isset($this->_request->filter) && is_array($this->_request->filter)) ? $this->_request->filter['id'] : ''
            ),
            'limit' => is_numeric($this->_request->limit) ? (int) $this->_request->limit : NULL,
            'offset' => is_numeric($this->_request->offset) ? (int) $this->_request->offset : 0,
            ''
        ));
        
        $sql = 'SELECT a.user_id as student_id, a.status, a.try_number, b.name, b.id, b.updated_at, b.user_id as teacher_id, a.id as try_id, c.fname,c.lname, d.name as course_name, e.meta_value_id
                    FROM quiz_solve_tries as a
                    LEFT JOIN `quiz_details` as b ON a.quiz_id = b.id
                    LEFT JOIN `users` as c ON a.user_id = c.id
                    LEFT JOIN `v_courses` as d ON d.id = a.course_id
                    LEFT JOIN `item_meta_values` as e ON e.item_id = a.course_id AND e.item_type = "v_course" AND e.meta_id = 8
                    WHERE (a.status = 2 OR a.status = 3) AND b.user_id = ?', [(int) $this->identity['id']];

        $quizzes = QuizSolveTries::find_by_sql($sql . $sql_params['where'] . $sql_params['order'] . $sql_params['limit'], $sql_params['binded']);
        $data = array();
        $data['rows'] = array();
    
        $count = QuizSolveTries::find_by_sql($sql);

        $data['rows_number'] = count($count);
        if (!empty($quizzes)) {
           foreach ($quizzes as $quiz) {
                if($quiz->status == 2){
                    $status = $this->view->t('FOR_EVALUATION', 0, false);
                }else{
                    $status = $this->view->t('EVALUATED', 0, false);;
                }
                if(empty($quiz->meta_value_id)){
                    $quiz->meta_value_id = 0;
                }
                $params = [$quiz->id, $quiz->try_id, $quiz->student_id, $quiz->name, $quiz->fname,$quiz->lname, $quiz->try_number, $status, $quiz->course_name, $quiz->meta_value_id];
                array_push($data['rows'], $params);
              
            }
        }
        if(isset($this -> search_str)){
            $data['search_str'] = $this -> search_str;
        }
        if(isset($this -> search_id)){
            $data['search_id'] = $this -> search_id;
        }
        echo json_encode($data);
    }

    public function gradingAction() {

        $try_info = QuizSolveTries::getTryInfo($this->_request->try_id);
        $quiz_info = QuizDetail::getQuiz($this->_request->id);
       
            if($try_info->status == 2){
                    if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin') && ($quiz_info->user_id !=$this->identity['id'])){
                       //redirect to page - quiz is NOT evaluate yet
                        $this->_redirector->gotoroute(array(), 'not_found');
                        return;
                    }
            }else{
                if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin') && !$this->view->hasRole('User') && (($quiz_info->user_id !=$this->identity['id']) || ($try_info->user_id != $this->identity['id']))){
                    $this->_redirector->gotoroute(array(), 'not_found');
                    return;
                }
            }
           
            if(empty($try_info))
            $this->_redirector->gotoroute(array(), 'not_found');
    
    
            $this->view->quiz_id = $this->_request->id;
            $this->view->try_id = $this->_request->try_id;
            $this->view->student_id = $this->_request->student_id;
            $this->view->grade =  $try_info->grade;
            $this->view->status = $try_info->status;
            $this->view->teacher_comment = $try_info->teacher_comment;

            $sql = 'SELECT fname,lname FROM users WHERE id = ?',[$this->_request->student_id];
            $this->view->student_name = User::find_by_sql($sql);
            $questions = QuizQuestion::getQuestionsByQuizId($this->_request->id);
            $this->view->questions = QuizAnswer::setAnswers($questions);
            $this->view->quiz = QuizDetail::find_by_id($this->_request->id);
            $this->view->closeAnswers = QuizSolveAnswers::getStudentAnswers($this->_request->try_id, $this->_request->id);
            $this->view->student_points = QuizSolveAnswers::getStudentTotalPoints($this->_request->try_id);
            $this->view->comments = QuizSolveOpenAnswers::getComments($this->_request->try_id);

    }

    public function finalAssessmentAction() {

        if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin')) {
           $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }
        QuizSolveOpenAnswers::updateSolvedAnswers($this->_request->comments, $this->_request->points, $this->_request->quiz_id, $this->_request->try_id);
        QuizSolveTries::updateStatus($this->_request->try_id);
        $total_points = QuizSolveAnswers::getStudentTotalPoints($this->_request->try_id);
        $quiz_info = QuizDetail::getQuiz($this->_request->quiz_id);
        $grade = self::assessmentsCriteria($quiz_info->min_points,$quiz_info->quiz_points,$total_points,$quiz_info->min_type);
        QuizSolveTries::setGrade($this->_request->try_id, $grade);

        //$try_info = QuizSolveTries::getTryInfo($this->_request->try_id);
        // $lesson_info = VLesson::getLessonInfo($try_info->lesson_id);
        //VCourseStudentGrades::evaluateStudent($student_id, $course_id, $lesson_id, $s_grade, $grade_category, $lesson_name)

        //send email to student+ and parent
        self::sendMailForEvaluatedQuiz($this->realm,$_SERVER['HTTP_HOST'],$this->_request->try_id,0,0,0,$grade);


    }

//..................................................
//evaluated quizzes

    public function studentQuizViewAction() {

    }
    
    public function studentQuizViewLoadAction(){
        if (!$this->view->hasRole('Organization') && !$this->view->hasRole('Teacher') && !$this->view->hasRole('Admin') && !$this->view->hasRole('User')) {
            $this->_redirector->gotoroute(array(), 'not_found');
            return;
        }

        $sql_params = $this->getConditions(array(
            'search' => array(
                'string' => isset($this->_request->search) ? $this->_request->search : NULL,
                'fields' => array('b.name','d.name')
            ),
            'order' => array(
                'fields' => array('a.updated_at','course_name', 'b.name'),
                'value' => (isset($this->_request->order) && is_array($this->_request->order)) ? $this->_request->order['value'] : '',
                'id' => (isset($this->_request->order) && is_array($this->_request->order)) ? $this->_request->order['id'] : ''
            ),
            'limit' => is_numeric($this->_request->limit) ? (int) $this->_request->limit : NULL,
            'offset' => is_numeric($this->_request->offset) ? (int) $this->_request->offset : 0,
            ''
        ));


        $sql = 'SELECT a.user_id as student_id, a.status, a.try_number,a.updated_at, b.name, b.id, b.user_id as teacher_id, a.id as try_id, c.fname,c.lname, d.name as course_name
                    FROM quiz_solve_tries as a
                    LEFT JOIN `quiz_details` as b ON a.quiz_id = b.id
                    LEFT JOIN `users` as c ON b.user_id = c.id
                    LEFT JOIN `v_courses` as d ON d.id = a.course_id
                    WHERE a.status = 3 AND a.user_id = ?', [(int) $this->identity['id']];

        
        $quizzes = QuizSolveTries::find_by_sql($sql . $sql_params['where'] . $sql_params['order'] . $sql_params['limit'], $sql_params['binded']);     
        $quizes = QuizSolveTries::find_by_sql($sql);

        $data = array();
        $data['rows'] = array();
       
        $data['rows_number'] = count($quizes);
        if (!empty($quizzes)) {
            foreach ($quizzes as $quiz) {
                $params = [$quiz->id, $quiz->try_id, $quiz->student_id, $quiz->name, $quiz->fname,$quiz->lname, $quiz->try_number, $quiz->status, $quiz->course_name];
                array_push($data['rows'], $params);
                $this->view->status = $quiz->status;
            }
        }
        if(isset($this -> search_str)){
            $data['search_str'] = $this -> search_str;
        }
        if(isset($this -> search_id)){
            $data['search_id'] = $this -> search_id;
        }
        echo json_encode($data);
    }
    


    public static function assessmentsCriteria($min,$max,$student_points,$type) {

        $student_points = (float) $student_points;

        if($type == "percent"){
            $min = ($min/100)*$max;
        }
        if($student_points >= $min){
            $diff = $max - $min;
            $diff = $diff/3;
            $diff = number_format($diff,2);
            $for_4 = $min + $diff;
            $for_5 = $for_4 + $diff;

            if($student_points >= $min && $student_points <=  $for_4){
    
                if (round($student_points) == round($min)){
                    $grade = 3;
                    return (float)$grade;
                }
    
                $count = 1;
                foreach (range(($min+1), $for_4) as $point) {
                    if(round($student_points) == round($point)){
                        $decimal  =  number_format($count/$diff,2);
                        $arr = explode('.', $decimal);
                        $grade = '3.' . $arr[1];
                        break;
                    }
                    $count++;
                }
            }else{
                if($student_points >= $for_4 && $student_points <= $for_5){
                    $count = 1;
                    if (round($student_points) == round($for_4)){
                        $grade = 4;
                        return (float)$grade;
                    }

                    foreach (range($for_4+1, $for_5) as $point) {
    
                        if(round($student_points) == round($point)){
                            $decimal  =  number_format($count/$diff,2);
                            $arr = explode('.', $decimal);
                            $grade = '4.' . $arr[1];
                            break;
                            
                        }
                        $count++;
                    }
                }else{
                    if($student_points >= $for_5 && $student_points <= $max){

                        $count = 1;
    
                        if (round($student_points) == round($for_5)){
                            $grade = 5;
                            return (float)$grade;
                        }
    
                        if (round($student_points) == round($max)){
                            $grade = 6;
                            return (float)$grade;
                        }
    
                        foreach (range($for_5+1, $max) as $point) {

                            if(round($student_points) == round($point)){
                                $decimal  =  number_format($count/$diff,2);
                                $arr = explode('.', $decimal);
                                $grade = '5.' . $arr[1];
                            }
                            $count++;
                        }
                    }
                }
            }
             return ((float) $grade);
        }
        return 2;
    }


    public function sendMailForEvaluatedQuiz($realm,$domain,$try_id,$quiz_id,$lesson_id,$course_id,$grade){
        if($try_id != 0){
            $sql = 'SELECT b.name as course_name, c.name as lesson_name, d.name as quiz_name, d.id as quiz_id, e.email, f.parent_id, e.id as user_id,
                                (select u.email from users as u where f.parent_id = u.id) as parent_mail
                                FROM quiz_solve_tries as a
                                LEFT JOIN `v_courses` as b ON b.id = a.course_id
                                LEFT JOIN `v_lessons` as c ON c.id = a.lesson_id
                                LEFT JOIN `quiz_details` as d ON d.id = a.quiz_id
                                LEFT JOIN `users` as e ON e.id = a.user_id
                                LEFT JOIN `user_parents` as f ON f.user_id = e.id
                                WHERE a.id =?',[$try_id];

        }else{
            if($quiz_id > 0 && $lesson_id > 0 && $course_id > 0){
             $sql = 'SELECT b.name as course_name, a.id as try_id, c.name as lesson_name, d.name as quiz_name, d.id as quiz_id, e.email, f.parent_id, e.id as user_id,
                                (select u.email from users as u where f.parent_id = u.id) as parent_mail
                                FROM quiz_solve_tries as a
                                LEFT JOIN `v_courses` as b ON b.id = a.course_id
                                LEFT JOIN `v_lessons` as c ON c.id = a.lesson_id
                                LEFT JOIN `quiz_details` as d ON d.id = a.quiz_id
                                LEFT JOIN `users` as e ON e.id = a.user_id
                                LEFT JOIN `user_parents` as f ON f.user_id = e.id
                                WHERE a.quiz_id =? AND lesson_id =? AND course_id =?',[$quiz_id,$lesson_id,$course_id];
            }else{
                return;
            }     
        }


        $params = QuizSolveTries::find_by_sql($sql);

        if(!empty($params)){
            $data = [];
                foreach($params as $k=>$v){
                    $data[$v->user_id]['email'] = $v->email;
                    $data[$v->user_id]['parents'][$v->parent_id] = $v->parent_mail;
                    $data[$v->user_id]['course_name'] = $v->course_name;
                    $data[$v->user_id]['lesson_name'] = $v->lesson_name;
                    $data[$v->user_id]['quiz_name'] = $v->quiz_name;
                    $data[$v->user_id]['quiz_id'] = $v->quiz_id;
                    $data[$v->user_id]['user_id'] = $v->user_id;
                    if($try_id == 0){
                        $data[$v->user_id]['try_id'] = $v->try_id;
                    }else{
                        $data[$v->user_id]['try_id'] = $try_id;
                    }
                    $data[$v->user_id]['grade'] = $grade;
                    $data[$v->user_id]['realm'] = $realm;
                    $data[$v->user_id]['domain'] = $domain;
                    $data[$v->user_id]['subject'] = "Оценен тест";
                    $data[$v->user_id]['template'] = 'quiz_is_evaluated';
                }
            Mail::sendQuizMail($data);
        }
    }

}
