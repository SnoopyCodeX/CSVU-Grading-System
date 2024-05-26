<?php 
// require("../configuration/constants.php");
// require("../configuration/config.php");

function interpretAverageScore($averageScore) {
    $averageScore = round($averageScore, 1);

    if ($averageScore >= 96.7) {
        return 1.0;
    } else if ($averageScore >= 93.4 && $averageScore <= 96.6) {
        return 1.25;
    } else if ($averageScore >= 90.1 && $averageScore <= 93.3) {
        return 1.50;
    } else if ($averageScore >= 86.7 && $averageScore <= 90.0) {
        return 1.75;
    } else if ($averageScore >= 83.4 && $averageScore <= 86.6) {
        return 2.0;
    } else if ($averageScore >= 80.1 && $averageScore <= 83.3) {
        return 2.25;
    } else if ($averageScore >= 76.7 && $averageScore <= 80.0) {
        return 2.50;
    } else if ($averageScore >= 73.4 && $averageScore <= 76.6) {
        return 2.75;
    } else if ($averageScore >= 70.0 && $averageScore <= 73.3) {
        return 3.0;
    } else if ($averageScore >= 50.0 && $averageScore <=69.9) {
        return 4.0;
    } else if ($averageScore <= 49.9) {
        return 5.0;
    }
}

function computeStudentGradesFromSubject($conn, $subjectId, $courseId, $studentId, $instructorId, $schoolYearId, $semester) {
    // Get all grading criterias of the instructor
    $gradingCriteriasQuery = $conn->query("SELECT * FROM grading_criterias WHERE instructor=$instructorId");
    $gradingCriterias = $gradingCriteriasQuery->fetch_all(MYSQLI_ASSOC);

    $criteriaGrades = [];
    $criteriaTotalPercentage = 0;

    // Loop through each criterias
    foreach($gradingCriterias as $gradingCriteria) {
        $criteriaPercentage = $gradingCriteria['percentage'];
        $typeOfActivityId = $gradingCriteria['id'];

        // Sum up criteria percentage
        $criteriaTotalPercentage += $criteriaPercentage;

        // Get all activities for the subject
        $activitiesQuery = $conn->query("SELECT 
            * 
            FROM activities 
            WHERE subject='$subjectId' AND 
            school_year='$schoolYearId' AND 
            term='$semester' AND 
            course='$courseId' AND 
            type='$typeOfActivityId'
        ");
        $activities = $activitiesQuery->fetch_all(MYSQLI_ASSOC);
        $totalActivities = count($activities);

        // Get the activity scores of the student
        $activityScoresQuery = $conn->query("SELECT 
            activity_scores.*,
            activities.max_score AS max_score
            FROM 
            activity_scores
            LEFT JOIN activities ON activity_scores.activity_id = activities.id
            WHERE activity_scores.student_id='$studentId' AND 
            activities.subject='$subjectId' AND 
            activities.course='$courseId' AND 
            activities.school_year='$schoolYearId' AND 
            activities.type='$typeOfActivityId' AND
            activity_scores.term='$semester'
        ");
        $activityScores = $activityScoresQuery->fetch_all(MYSQLI_ASSOC);

        // If no activity scores found, skip
        if (count($activityScores) == 0) {
            return -1;
        }

        // Sum all activityScores
        $totalActivityScores = 0;

        foreach ($activityScores as $activityScore) {
            $totalActivityScores += $activityScore['score'];
        }
        
        // Sum all max scores of the activities
        $maxTotalActivityScores = 0;
        
        foreach ($activities as $activity) {
            $maxTotalActivityScores += $activity['max_score'];
        }

        // Get average score of the activities
        $averageScore = $totalActivityScores / $maxTotalActivityScores;
        
        // Multiply the average by the criteria percentage
        $activityGrade = $averageScore * $criteriaPercentage;

        // Add the result to the array of criteria grades
        $criteriaGrades[] = $activityGrade;
    }

    // Add every criteria grade
    $sumCriteriaGrades = 0;
    foreach ($criteriaGrades as $criteriaGrade) {
        $sumCriteriaGrades += $criteriaGrade;
    }

    // Divide sum by the total criteria percentage
    $finishedComputedGrade = $sumCriteriaGrades / $criteriaTotalPercentage;

    // Interpret average score
    return interpretAverageScore($finishedComputedGrade * 100);
}

function computeStudentGrades($conn, $studentId, $instructorId, $courseId, $schoolYearId, $yearLevel, $semester) {
    // Get all subjects from the course
    $subjectsQuery = $conn->query("SELECT 
        *
        FROM subjects
        WHERE course='$courseId' AND 
        term='$semester' AND 
        year_level='$yearLevel'
    ");
    $subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);

    // Array to store all student grades for each subject
    $subjectGrades = [];

    // Loop through each subjects and compute the grade of the student for each subject
    foreach($subjects as $subject) {
        $subject['grade'] = computeStudentGradesFromSubject($conn, $subject['id'], $courseId, $yearLevel, $studentId, $instructorId, $schoolYearId, $semester);
        $subjectGrades[] = $subject;
    }

    return $subjectGrades;
}

// echo computeStudentGradesFromSubject(
//     $dbCon,
//     11,
//     4,
//     '1st Year',
//     53,
//     50,
//     1,
//     '1st Sem'
// );

?>