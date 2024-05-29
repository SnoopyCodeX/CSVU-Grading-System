<?php
session_start();
// kung walang session mag reredirect sa login //

require ("../../../configuration/config.php");
require ('../../../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once ("../../../components/header.php");

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

if (isset($_POST['create_course'])) {
    $course = $dbCon->real_escape_string($_POST['course']);
    $courseCode = $dbCon->real_escape_string($_POST['course_code']);
    $adviser = $dbCon->real_escape_string($_POST['adviser']);

    if (strlen(trim($course)) < 6) {
        $hasError = true;
        $message = "Course name must be 6 characters long";
    } else if (empty($adviser)) {
        $hasError = true;
        $message = "Please select a course adviser!";
    } else {
        $courseCodeExistQuery = $dbCon->query("SELECT * FROM courses WHERE course_code = '$courseCode'");

        if ($courseCodeExistQuery->num_rows > 0) {
            $hasError = true;
            $hasSuccess = false;
            $message = "Course code already exists!";
        } else {
            $query = "INSERT INTO courses (course, course_code, adviser) VALUES ('$course', '$courseCode', '$adviser')";
            $result = mysqli_query($dbCon, $query);

            if ($result) {
                $hasError = false;
                $hasSuccess = true;
                $message = "Course created successfully!";
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Course creation failed!";
            }
        }
    }
}

// $instructorsQuery = "SELECT 
//     *, 
//     CONCAT(firstName, ' ', middleName, ' ', lastName) as fullName 
//     FROM userdetails 
//     WHERE roles='instructor' 
//     AND id NOT IN (SELECT adviser FROM courses) 
//     ORDER BY fullName ASC
// ";

$instructorsQuery = "SELECT 
    *, 
    CONCAT(firstName, ' ', middleName, ' ', lastName) as fullName 
    FROM userdetails 
    WHERE roles='instructor' 
    ORDER BY fullName ASC
";
$instructorsQueryResult = $dbCon->query($instructorsQuery);
?>

<main class="w-screen h-screen overflow-hidden flex">
    <?php require_once ("../../layout/sidebar.php") ?>
    <section class="border w-full px-4">
        <?php require_once ("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center w-full h-[70%]">
            <div class="w-full flex justify-center items-center flex-col gap-4">
                <h2 class="text-[28px] md:text-[38px] font-bold mb-8">Create Course</h2>

                <form class="flex flex-col gap-4  max-w-[600px] px-auto md:px-[32px] w-full mb-auto" method="post"
                    action="<?= $_SERVER['PHP_SELF'] ?>">
                    <?php if ($hasError) { ?>
                    <div role="alert" class="alert alert-error mb-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?= $message ?></span>
                    </div>
                    <?php } ?>

                    <?php if ($hasSuccess) { ?>
                    <div role="alert" class="alert alert-success mb-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?= $message ?></span>
                    </div>
                    <?php } ?>

                    <!-- Name -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Course</span>
                        <input type="text" class="border border-gray-400 input input-bordered" placeholder="Course Name"
                            name="course" <?php if ($instructorsQueryResult->num_rows == 0): ?> disabled
                            <?php endif; ?>>
                    </label>

                    <label class="flex flex-col gap-2" x-data>
                        <span class="font-bold text-[18px]">Course Code</span>
                        <input type="text" class="border border-gray-400 input input-bordered" placeholder="Course Code"
                            name="course_code" x-mask="************"
                            <?php if ($instructorsQueryResult->num_rows == 0): ?> disabled <?php endif; ?>>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Assign Adviser</span>
                        <?php if ($instructorsQueryResult->num_rows > 0): ?>
                        <select class="select select-bordered" name="adviser" required>
                            <option value="" disabled selected>Select Adviser</option>
                            <?php while ($instructor = $instructorsQueryResult->fetch_assoc()) { ?>
                            <option value="<?php echo $instructor['id'] ?>"><?= $instructor['fullName'] ?></option>
                            <?php } ?>
                        </select>
                        <?php else: ?>
                        <div role="alert" class="alert alert-error mb-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="flex space-between items-center gap-4">
                                <span>No advisers available.</span>
                                <a class="btn" href="../manage-instructor.php">
                                    <span class="bx bx-plus"></span>
                                    Add Instructor
                                </a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </label>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <button class="btn bg-[#276bae] text-white" name="create_course">
                            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                <title>add_circle_fill</title>
                                <g id="add_circle_fill" fill='none' fill-rule='nonzero'>
                                    <path
                                        d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                    <path fill='currentColor'
                                        d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm0 5a1 1 0 0 0-.993.883L11 8v3H8a1 1 0 0 0-.117 1.993L8 13h3v3a1 1 0 0 0 1.993.117L13 16v-3h3a1 1 0 0 0 .117-1.993L16 11h-3V8a1 1 0 0 0-1-1Z' />
                                </g>
                            </svg>

                            <span>
                                Create
                            </span>
                        </button>
                        <a class="btn btn-error text-base" href="../manage-course.php">
                            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                <title>delete_2_fill</title>
                                <g id="delete_2_fill" fill='none' fill-rule='evenodd'>
                                    <path
                                        d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                    <path fill='currentColor'
                                        d='M14.28 2a2 2 0 0 1 1.897 1.368L16.72 5H20a1 1 0 1 1 0 2l-.003.071-.867 12.143A3 3 0 0 1 16.138 22H7.862a3 3 0 0 1-2.992-2.786L4.003 7.07A1.01 1.01 0 0 1 4 7a1 1 0 0 1 0-2h3.28l.543-1.632A2 2 0 0 1 9.721 2h4.558ZM9 10a1 1 0 0 0-.993.883L8 11v6a1 1 0 0 0 1.993.117L10 17v-6a1 1 0 0 0-1-1Zm6 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm-.72-6H9.72l-.333 1h5.226l-.334-1Z' />
                                </g>
                            </svg>
                            <span>
                                Cancel
                            </span>
                        </a>
                    </div>
                </form>
            </div>
        </div>

</main>