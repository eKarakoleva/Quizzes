<?php

/**
 * QuizDetail
 */
class QuizCourse extends PZ_BaseModel {

    static $belongs_to = array(
    );
    static $has_many = array(
    );

    public static function findCourses($search, $user_id, $realm_id) {
        $courses = [];
        if ($search != '') {
            //teacher
            $sql = 'SELECT * FROM user_roles as a
                    WHERE user_id ='.$user_id.' AND (role_id = 1 OR role_id = 9 OR role_id = 12) ';
            
            $user_rights = self::find_by_sql($sql);
            if ($user_id > 0) {
                if(empty($user_rights)){
                    $SQL = '
                    LEFT JOIN
                        v_course_teachers as vct
                    ON
                        vct.v_course_id = vc.id
                    WHERE
                        vct.user_id = ? AND vc.name LIKE ? AND realm_id = ?';

                    $courses = self::find_by_sql('SELECT vc.id, vc.name FROM v_courses as vc ' . $SQL . '', [$user_id, '%' . $search . '%', $realm_id]);
                }else{
                    //admin or organization
                    $courses = self::find_by_sql('SELECT vc.id, vc.name FROM v_courses as vc WHERE vc.status_id = 3 AND vc.name LIKE ? AND realm_id = ?', ['%' . $search . '%', $realm_id]);
                }
            }            
        }
        return $courses;
    }

    public static function findLessons($course_id, $realm_id,$type) {
        if ($course_id > 0) {
            if($type == "mid-term"){
                return self::find_by_sql('SELECT name, id FROM v_lessons WHERE v_course_id = ? AND realm_id = ?', [$course_id, $realm_id]);
            }else{
                if($type == "entry-level"){
                    return self::find_by_sql('SELECT name, id FROM v_lessons WHERE v_course_id = ? AND realm_id = ? ORDER BY id ASC LIMIT 1', [$course_id, $realm_id]);
                }else{
                    return self::find_by_sql('SELECT name, id FROM v_lessons WHERE v_course_id = ? AND realm_id = ? ORDER BY id DESC LIMIT 1', [$course_id, $realm_id]);
                }
            }
        }
    }

    public static function saveQuizCourse($params) {
        if(is_array($params) && !empty($params)){
            if (!$params['quiz_id'] || !$params['course_id']) {
                return false;
            }
    
            //$type = QuizDetail::getQuizType($params['quiz_id']);
            $check = self::find_by_quiz_id_and_course_id_and_lesson_id($params['quiz_id'],$params['course_id'],$params['lesson_id']);
           if(empty($check)){
                $quizCourse = new self;
        
                if (!($params['lesson_id'] > 0)) {
                    return false;
                } else {
                    $quizCourse->lesson_id = $params['lesson_id'];
                }
        
                $quizCourse->quiz_id = $params['quiz_id'];
                $quizCourse->course_id = $params['course_id'];
                $quizCourse->active = 1;
                if($params['tries'] == 0 || $params['tries'] == ""){
                    $params['tries'] = 1;
                }
                $quizCourse->tries = $params['tries'];
                $quizCourse->mandatory = $params['mandatory'];
                $quizCourse->total_time = $params['duration'];
                $quizCourse->disarrange_questions = $params['disarrange_questions'];
                $quizCourse->disarrange_answers = $params['disarrange_answers'];
                if ($quizCourse->save()) {
                    return true;
                }
                return false;
            }else{return false;}
        }
    }

    //get option set for disarrange questions
    public static function getDisarrangeQuestionsOption($params) {
        if(!empty($params) && is_array($params)){
            $quizOptions = self::find_by_quiz_id_and_course_id_and_lesson_id($params['id'],$params['course_id'],$params['lesson_id']);
            return $quizOptions->disarrange_questions;
        }
    }
    
    //get option set for disarrange answers
    public static function getDisarrangeAnswerOption($params) {
        if(!empty($params) && is_array($params)){
            $quizOptions = self::find_by_quiz_id_and_course_id_and_lesson_id($params['id'],$params['course_id'],$params['lesson_id']);
            return $quizOptions->disarrange_answers;
        }
    }

    public static function deleteQuizCourse($id,$course_id,$lesson_id) {
        if($id != 0){
            $courseDelete = self::find_by_quiz_id_and_course_id_and_lesson_id($id,$course_id,$lesson_id);
            $courseDelete->delete();
        }
    }

    public static function getLessonName($lesson_id) {
        if($lesson_id != 0){
            $sql = 'SELECT a.name
                       FROM v_lessons as a
                        WHERE a.id = ' .$lesson_id;
//pr(self::find_by_sql($sql));
            return self::find_by_sql($sql);
        }
    }
    
    

}
