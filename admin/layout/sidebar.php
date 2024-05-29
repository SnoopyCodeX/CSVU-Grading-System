<?php
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$baseFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'];
$rootFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/admin/views";
$dasboard = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/admin/dashboard.php";

// get the request uri
$requestUri = $_SERVER['REQUEST_URI'];
$requestUri = explode('/', $requestUri);

// get the last
$lastUri = strtolower(end($requestUri));

// remove query string
$lastUri = explode('?', $lastUri);
$lastUri = $lastUri[0];

// remove .php
$lastUri = explode('.', $lastUri);
$lastUri = $lastUri[0];
?>

<style>
.sidebar-menu {
    -ms-overflow-style: none;
    /* Internet Explorer 10+ */
    scrollbar-width: none;
    /* Firefox */
}

.sidebar-menu::-webkit-scrollbar {
    display: none;
    /* Safari and Chrome */
}
</style>

<aside
    class="relative hidden md:block w-[350px] h-100 border-r p-4 flex flex-col gap-4 justify-between sticky top-0 bg-[#1b7a58] text-white">
    <div class="h-full overflow-y-auto sidebar-menu">
        <div class="flex justify-center items-center flex-col">
            <img src=<?php echo $baseFolder . "/assets/images/logo.png" ?> class='w-[120px] h-[120px]' />
            <h1 class="text-[18px] text-center font-semibold p-4">Web-based Grading System</h1>
        </div>
        <nav class="my-4 h-full">
            <ul class="menu">
                <li><a href="<?php echo $dasboard; ?>">
                        <i class='bx bxs-dashboard text-[24px]'></i>
                        <span class="text-[18px]">Dashboard</span>
                    </a></li>
                <li>
                    <details
                        <?php if (in_array($lastUri, ['manage-course', 'course', 'course_section', 'section_students', 'subject_instructors', 'subject_instructor_sections', 'manage-subjects', 'subject', 'manage-sections', 'sections', 'manage-schoolyear', 'academic-year'])): ?>
                        open <?php endif; ?>>
                        <summary>
                            <i class='bx bxs-buildings text-[24px]'></i>
                            <span class="text-[18px]">Department</span>
                        </summary>
                        <ul>
                            <li><a href="<?php echo $rootFolder; ?>/manage-course.php">Manage Course</a></li>
                            <li><a href="<?php echo $rootFolder; ?>/manage-subjects.php">Manage Subjects</a></li>
                            <li><a href="<?php echo $rootFolder; ?>/manage-sections.php">Manage Sections</a></li>
                            <li><a href="<?php echo $rootFolder; ?>/manage-schoolyear.php">Manage School Year</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details
                        <?php if (in_array($lastUri, ['manage-student', 'student_enrolled_subjects', 'student', 'manage-instructor', 'instructor', 'manage-admin', 'admin'])): ?>
                        open <?php endif; ?>>
                        <summary>
                            <i class='bx bxs-user text-[24px]'></i>
                            <span class="text-[18px]">Users</span>
                        </summary>
                        <ul>
                            <li><a href="<?php echo $rootFolder; ?>/manage-student.php">Manage Students</a></li>
                            <li><a href="<?php echo $rootFolder; ?>/manage-instructor.php">Manage Instructor</a></li>
                            <li><a href="<?php echo $rootFolder; ?>/manage-admin.php">Manage Admin</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/manage-grade-release-requests.php">
                        <i class='bx bxs-notepad text-[24px]'></i>
                        <span class="text-[18px]">Requests for Grade Release</span>
                    </a>
                </li>
                <!-- <li>
                     <a href="<?php echo $rootFolder; ?>/grade-request.php">
                         <i class='bx bxs-notepad text-[24px]'></i>
                         <span class="text-[18px]">Student Grade Requests</span>
                     </a>
                 </li> -->
            </ul>
        </nav>
    </div>

    <button class="btn btn-sm btn-circle absolute top-[8px] right-[-18px]">
        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
            <title>left_line</title>
            <g id="left_line" fill='none' fill-rule='evenodd'>
                <path
                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                <path fill='currentColor'
                    d='M8.293 12.707a1 1 0 0 1 0-1.414l5.657-5.657a1 1 0 1 1 1.414 1.414L10.414 12l4.95 4.95a1 1 0 0 1-1.414 1.414l-5.657-5.657Z' />
            </g>
        </svg>
    </button>
</aside>

<script>
// Fetch all the details element.
const details = document.querySelectorAll("details");

// Add the onclick listeners.
details.forEach((detail) => {
    detail.addEventListener("toggle", () => {
        if (detail.open) setTargetDetail(detail);
    });
});

// Close all the details that are not targetDetail.
function setTargetDetail(targetDetail) {
    details.forEach((detail) => {
        if (detail !== targetDetail) {
            detail.open = false;
        }
    });
}
</script>