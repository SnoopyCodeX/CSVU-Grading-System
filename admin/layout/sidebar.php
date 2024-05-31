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

<aside class="relative hidden md:block w-[350px] h-100 border-r p-4 flex flex-col gap-4 justify-between sticky top-0 bg-[#1b7a58] text-white" id="sidebar">
    <div class="h-full overflow-y-auto sidebar-menu">
        <div class="flex justify-center items-center flex-col">
            <img src=<?php echo $baseFolder . "/assets/images/logo.png" ?> class='w-[120px] h-[120px]' id="logo-image"/>
            <h1 class="text-[18px] text-center font-semibold p-4" id="brand-name">Web-based Grading System</h1>
        </div>
        <nav class="my-4 h-full">
            <ul class="menu">
                <li>
                    <a href="<?php echo $dasboard; ?>">
                        <i class='bx bxs-dashboard text-[24px]'></i>
                        <span class="text-[18px]" id="dashboard-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <details
                        id="department-details"
                        <?php if (in_array($lastUri, ['manage-course', 'course', 'course_section', 'section_students', 'subject_instructors', 'subject_instructor_sections', 'manage-subjects', 'subject', 'manage-sections', 'sections', 'manage-schoolyear', 'academic-year'])): ?>
                        open <?php endif; ?>>
                        <summary>
                            <i class='bx bxs-buildings text-[24px]'></i>
                            <span class="text-[18px]" id="department-text">Department</span>
                        </summary>
                        <ul>
                            <li>
                                <a href="<?php echo $rootFolder; ?>/manage-course.php" id="manage-course-text">Manage Course</a>
                            </li>
                            <li><a href="<?php echo $rootFolder; ?>/manage-subjects.php" id="manage-subject-text">Manage Subjects</a></li>
                            <li><a href="<?php echo $rootFolder; ?>/manage-sections.php" id="manage-section-text">Manage Sections</a></li>
                            <li><a href="<?php echo $rootFolder; ?>/manage-schoolyear.php" id="manage-sy-text">Manage School Year</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details
                        id="users-details"
                        <?php if (in_array($lastUri, ['manage-student', 'student_enrolled_subjects', 'student', 'manage-instructor', 'instructor', 'manage-admin', 'admin'])): ?>
                        open <?php endif; ?>>
                        <summary>
                            <i class='bx bxs-user text-[24px]'></i>
                            <span class="text-[18px]" id="users-text">Users</span>
                        </summary>
                        <ul>
                            <li><a href="<?php echo $rootFolder; ?>/manage-student.php" id="manage-student-text">Manage Students</a></li>
                            <li><a href="<?php echo $rootFolder; ?>/manage-instructor.php" id="manage-instructor-text">Manage Instructor</a></li>
                            <li><a href="<?php echo $rootFolder; ?>/manage-admin.php" id="manage-admin-text">Manage Admin</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/manage-grade-release-requests.php">
                        <i class='bx bxs-notepad text-[24px]'></i>
                        <span class="text-[18px]"  id="manage-gr-text">Requests for Grade Release</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/grade-change-request.php">
                        <i class='bx bx-briefcase-alt-2 text-[24px]'></i>
                        <span class="text-[18px]" id="change-of-grade-text">Change of Grade Requests</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <button class="btn btn-sm btn-circle absolute top-[8px] right-[-18px]" onclick="toggleSidebarCollapse()" id="collapse-button">
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
let sidebarOpen = true;
let detailsTogggleListeners = new Map();

// Fetch all the details element.
const details = document.querySelectorAll("details");

// Add the onclick listeners.
details.forEach((detail) => {
    const listener = () => toggleDetails(detail);
    
    detail.addEventListener("toggle", listener);

    detailsTogggleListeners.set(detail.id, listener);
});

function toggleDetails(detail) {
    if (detail.open) setTargetDetail(detail);

    if (!sidebarOpen) toggleSidebarCollapse();
}

// Close all the details that are not targetDetail.
function setTargetDetail(targetDetail) {
    details.forEach((detail) => {
        if (detail !== targetDetail) {
            detail.open = false;
        }
    });
}

function toggleSidebarCollapse() {
    const collapseButton = document.querySelector("#collapse-button");
    const sidebar = document.querySelector("#sidebar");
    const logoImage = document.querySelector("#logo-image");
    const brandName = document.querySelector("#brand-name");
    const dashboardText = document.querySelector("#dashboard-text");
    const departmentText = document.querySelector("#department-text");
    const usersText = document.querySelector("#users-text");
    const manageCourseText = document.querySelector("#manage-course-text");
    const manageSubjectText = document.querySelector("#manage-subject-text");
    const manageSectionText = document.querySelector("#manage-section-text");
    const manageSYText = document.querySelector("#manage-sy-text");
    const manageStudentText = document.querySelector("#manage-student-text");
    const manageInstructorText = document.querySelector("#manage-instructor-text");
    const manageAdminText = document.querySelector("#manage-admin-text");
    const manageGRText = document.querySelector("#manage-gr-text");
    const changeOfGradeText = document.querySelector("#change-of-grade-text");

    if (sidebarOpen) {
        sidebar.classList.replace("w-[350px]", "w-[120px]");

        logoImage.classList.replace("w-[120px]", "w-[48px]");
        logoImage.classList.replace("h-[120px]", "h-[48px]");

        brandName.style.display = "none";

        departmentText.style.display = "none";
        usersText.style.display = "none";

        dashboardText.textContent = "";
        manageCourseText.textContent = "";
        manageSubjectText.textContent = "";
        manageSectionText.textContent = "";
        manageSYText.textContent = "";
        manageStudentText.textContent = "";
        manageInstructorText.textContent = "";
        manageAdminText.textContent = "";
        manageGRText.textContent = "";
        changeOfGradeText.textContent = "";

        const _details = document.querySelectorAll("details");
        for(let detail of _details) {
            if (!detail.open) continue;

            const listener = detailsTogggleListeners.get(detail.id);

            detail.removeEventListener('toggle', listener);
            detail.open = false;

            setTimeout(() => detail.addEventListener('toggle', listener), 1000);
        };

        collapseButton.innerHTML = `
            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                <title>right_line</title>
                <g id="right_line" fill='none' fill-rule='evenodd'>
                    <path 
                        d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z'/>
                    <path 
                        fill='currentColor' 
                        d='M15.707 11.293a1 1 0 0 1 0 1.414l-5.657 5.657a1 1 0 1 1-1.414-1.414l4.95-4.95-4.95-4.95a1 1 0 0 1 1.414-1.414l5.657 5.657Z'/>
                </g>
            </svg>
        `;
    } else {
        sidebar.classList.replace("w-[120px]", "w-[350px]");

        logoImage.classList.replace("w-[48px]", "w-[120px]");
        logoImage.classList.replace("h-[48px]", "h-[120px]");

        brandName.style.display = "block";

        departmentText.style.display = "block";
        usersText.style.display = "block";

        dashboardText.textContent = "Dashboard";
        manageCourseText.textContent = "Manage Courses";
        manageSubjectText.textContent = "Manage Subjects";
        manageSectionText.textContent = "Manage Sections";
        manageSYText.textContent = "Manage School Year";
        manageStudentText.textContent = "Manage Students";
        manageInstructorText.textContent = "Manage Instructor";
        manageAdminText.textContent = "Manage Admin";
        manageGRText.textContent = "Requests for Grade Release";
        changeOfGradeText.textContent = "Change of Grade Requests";

        collapseButton.innerHTML = `
            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                <title>left_line</title>
                <g id="left_line" fill='none' fill-rule='evenodd'>
                    <path
                        d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                    <path fill='currentColor'
                        d='M8.293 12.707a1 1 0 0 1 0-1.414l5.657-5.657a1 1 0 1 1 1.414 1.414L10.414 12l4.95 4.95a1 1 0 0 1-1.414 1.414l-5.657-5.657Z' />
                </g>
            </svg>
        `;
    }

    sidebarOpen = !sidebarOpen;
}
</script>