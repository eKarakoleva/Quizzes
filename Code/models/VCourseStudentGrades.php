<?php

class VCourseStudentGrades extends PZ_BaseModel {

    public static function getStudentGrades($course, $students) {
        $student_id = array_map(function($o) { return $o->id; }, $students);

        $sql = "SELECT grade, student_id, course_id, id, grade_category 
            FROM v_course_student_grades  
            WHERE course_id = ? 
            AND student_id IN (".implode(", ", $student_id).")
        ";
        $result = VCourseStudentGrades::find_by_sql($sql, array($course));

        foreach ($students as $student) {
            $grades[$student->id] = array();

            foreach ($result as $res) {
                if ($student->id == $res->student_id)
                    array_push($grades[$student->id], $res);
            }
        }
        
        return $grades;
    }

    public static function gradeStudent($params) {
        if (is_array($params) && !empty($params)) {
            $grade = new self;
            $grade->student_id = $params['student'];
            $grade->course_id = $params['course'];
            $grade->grade = $params['grade'];
            $grade->grade_category = $params['category'];
            $grade->lesson = $params['lesson'];
            $grade->lesson_id = $params['lesson_id'];

            $grade->save();
        }
    }

    public static function filterGrades($course, $students, $filter_val) {
        $student_id = array_map(function($o) { return $o->id; }, $students);

        $sql = "SELECT grade, student_id, course_id, id, grade_category
            FROM v_course_student_grades as vcg 
            WHERE vcg.course_id = ? 
            AND vcg.grade_category = ?
            AND student_id IN (".implode(", ", $student_id).")
        ";
        $result = VCourseStudentGrades::find_by_sql($sql, array($course, $filter_val));

        foreach ($students as $student) {
            $grades[$student->id] = array();

            foreach ($result as $res) {
                if ($student->id == $res->student_id)
                    array_push($grades[$student->id], $res);
            }
        }

        return $grades;
    }

    public static function calculateGPA($student_id, $course) {
		$sql = "SELECT
			AVG(grade) as avg FROM v_course_student_grades
			WHERE student_id = ? AND course_id = ?
			GROUP BY student_id";
		$result = VCourseStudentGrades::find_by_sql($sql, array($student_id, $course));	
		if(count($result) > 0) { 
            $GPA = $result[0]->avg;

	        return $GPA;
        }
	}

    public static function getStudentData($course, $student) {
        $sql = "SELECT vl.name, 
            GROUP_CONCAT(DISTINCT CASE WHEN vcg.grade_category = 'Classwork' THEN vcg.grade ELSE NULL END) as class_grades,
            GROUP_CONCAT(DISTINCT CASE WHEN vcg.grade_category = 'Homework' THEN vcg.grade ELSE NULL END) as home_grades,
            GROUP_CONCAT(DISTINCT CASE WHEN vcg.grade_category = 'Quiz' THEN vcg.grade ELSE NULL END) as quiz_grades 
        FROM v_lessons as vl
        LEFT JOIN v_course_student_grades as vcg 
        ON vcg.lesson_id = vl.id AND
        vcg.student_id = ?
        INNER JOIN v_course_users as vcu 
        ON vcu.v_course_id = vl.v_course_id
        WHERE vl.v_course_id = ? 
        AND vcu.user_id = ?
        AND vl.status_id = 12 
        GROUP BY vl.id";

        $data = VCourseStudentGrades::find_by_sql($sql, array($student, $course, $student));

        return $data;
    }

    public static function checkIfGradeExists($params) {
        $result = self::find_by_lesson_id_and_student_id_and_grade_category(
            $params['lesson_id'], $params['student'], $params['category']);
        
        return $result;
    }

		public static function evaluateStudent($student_id, $course_id, $lesson_id, $quiz_id, $s_grade, $grade_category, $lesson_name) {

				$sql = 'SELECT MAX(grade) as max_grade FROM quiz_solve_tries
								WHERE user_id = ? AND quiz_id = ? AND lesson_id= ? AND course_id = ?';

				$grades = self::find_by_sql($sql,[$student_id,$quiz_id,$lesson_id,$course_id]);
				$grade_record = self::find_by_student_id_and_quiz_id_and_lesson_id_and_course_id_and_grade_category($student_id,$quiz_id,$lesson_id,$course_id,$grade_category);
        if ($student_id != 0 && $course_id !=0 && $lesson_id != 0){
						if(empty($grade_record)){
								$grade = new self;
								$grade->student_id = $student_id;
								$grade->course_id = $course_id;
								$grade->grade = round($grades[0]->max_grade);
								$grade->grade_category = $grade_category;
								$grade->lesson = $lesson_name;
								$grade->lesson_id = $lesson_id;
								$grade->quiz_id = $quiz_id;
								$grade->save();
						}else{
								$grade_record->grade = round($grades[0]->max_grade);
								$grade_record->save();
						}
       }
    }
}