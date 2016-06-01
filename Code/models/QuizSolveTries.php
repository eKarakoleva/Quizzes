<?php

class QuizSolveTries extends PZ_BaseModel {

    static $belongs_to = array(
    );
    static $has_many = array(
    );

    //status active = 1 => quiz is in progress
    //status active = 2 => quiz is finished

    public static function addTry($data, $user_id) {
        $active_try = self::find_by_course_id_and_lesson_id_and_quiz_id_and_status($data['course_id'],$data['lesson_id'],$data['id'],1);
        
        $sql = 'SELECT id FROM quiz_solve_tries
                WHERE course_id = ?
                AND lesson_id = ?
                AND quiz_id = ?
                AND status != 1',[$data['course_id'],$data['lesson_id'],$data['id']];

        $all_tries = self::find_by_sql($sql);
        $all_tries = count($all_tries);
        $quiz_settings = QuizCourse::find_by_course_id_and_lesson_id_and_quiz_id($data['course_id'],$data['lesson_id'],$data['id']);

        if($quiz_settings->tries - $all_tries >= 0){
            if(empty($active_try)){
                $try = new self();
                $try->quiz_id = $data['id'];
                $try->course_id = $data['course_id'];
                $try->lesson_id = $data['lesson_id'];
                $try->try_number = $all_tries+1;
                $try->user_id =  $user_id;
                $try->last_enter = date('Y-m-d H:i:s');
                $try->status = 1;
                $try->teacher_comment = "";
                $try->grade = 0;
                $try->save();
                return $try->id;
            }else{
                $active_id = $active_try->id;
                $a_try = self::find($active_id);
                $a_try->last_enter = date('Y-m-d H:i:s');
                $a_try->save();
                return $active_id;
            }  
        }
    }

    public static function endTry($try_id, $data) {
        $try = self::find($try_id);
        $all_tries = self::find_all_by_course_id_and_lesson_id_and_quiz_id($data['course_id'],$data['lesson_id'],$data['id']);
        //number of tries student already did
        $user_all_tries = count($all_tries);
        //maximum tries set by teacher
        $quiz_settings = QuizCourse::find_by_course_id_and_lesson_id_and_quiz_id($data['course_id'],$data['lesson_id'],$data['id']);
        if($quiz_settings->tries - $user_all_tries >= 0){
            if($try != "" && $quiz_settings->tries>1){
                $try->status = 2;
                $try->try_number = $user_all_tries;
                $try->save();
            }else{
                $try->status = 2;
                $try->try_number = $quiz_settings->tries;
                $try->save();
            }
        }
    }

    public static function checkLeftTries($data,$user_id) {
        $quiz_settings = QuizCourse::find_by_course_id_and_lesson_id_and_quiz_id($data['course_id'],$data['lesson_id'],$data['id']);
        $all_tries = self::find_all_by_course_id_and_lesson_id_and_quiz_id_and_user_id($data['course_id'],$data['lesson_id'],$data['id'],$user_id);
        $user_all_tries = count($all_tries);
            //if tries for quiz are reached then return false
            if($quiz_settings->tries - $user_all_tries >= 0){
                return true;
            }

            if(empty($all_tries)){
                return true;
            }
        return false;
    }

    public static function checkTime($try_id,$data) {
        $try = self::find($try_id);

        if(!empty($try)){
            $quiz_settings = QuizCourse::find_by_course_id_and_lesson_id_and_quiz_id($data['course_id'],$data['lesson_id'],$data['id']);
            $settings_time = $quiz_settings->total_time;
            if($settings_time != 0){
                $last_enter = $try->last_enter->format('Y-m-d h:i:s');
                $first_enter = $try->created_at->format('Y-m-d h:i:s');
                
                $first_enter = new DateTime($first_enter );
                $last_enter = new DateTime($last_enter);
                
                $interval = date_diff($first_enter,$last_enter);
        
                $user_pass_time = $interval->format('%h:%i:%s');
                $settings_time = gmdate("H:i:s", $settings_time * 60);
                $left_time = strtotime($settings_time) - strtotime($user_pass_time);
                if($left_time < 0){
                    return 0;
                }else{
                   return $user_left_time = gmdate("H:i:s", $left_time);
                }
            }else{
                return "tt:tt:tt";
            }
        }
    }

    function convertToHoursMins($time, $format = '%02d:%02d') {
        if ($time < 1) {
            return;
        }
        $hours = floor($time / 60);
        $minutes = ($time % 60);
    return sprintf($format, $hours, $minutes);
    }

    public static function updateStatus($try_id) {
        $try = self::find_by_id($try_id);
        $try->status = 3;
        $try->save();
    }

    public static function checkTries($quiz_id,$course_id,$lesson_id) {
        $try = self::find_all_by_quiz_id_and_course_id_and_lesson_id_and_status($quiz_id,$course_id,$lesson_id,2);
        return $try;
    }

    public static function getTryId($quiz_id,$course_id,$lesson_id) {
        $sql = 'SELECT *
                    FROM quiz_solve_tries as a
                    WHERE a.quiz_id = ? AND a.course_id =? AND lesson_id = ? ORDER BY id DESC LIMIT 1';
        return self::find_by_sql($sql,[$quiz_id,$course_id,$lesson_id]);
    }

    public static function updateTeacherComment($try_id, $teacher_comment) {
        if($try_id != 0){
            $try = self::find_by_id($try_id);
            $try->teacher_comment = $teacher_comment;
            $try->save();
            return $try;
        }
    }

    public static function setGrade($try_id, $grade) {
        if($try_id != 0){
            $try = self::find_by_id($try_id);
            $try->grade = $grade;
            $try->save();
            return $try;
        }
    }

    public static function getTryNumber($try_id) {
        $try = self::find_by_id($try_id);
        return $try;
    }

    public static function getTryInfo($try_id) {
        $try = self::find_by_id($try_id);
        return $try;
    }
}