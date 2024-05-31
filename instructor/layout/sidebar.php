<?php
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$baseFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'];
$rootFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/instructor/view";
$dashboard = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/instructor";
?>

<aside class="relative hidden  md:block w-[350px] border-r p-4 flex flex-col gap-4 justify-between sticky top-0 bg-[#1b7a58] text-white" id="sidebar">
    <div class="h-full w-full ">
        <div class="flex justify-center items-center flex-col">
            <img src=<?php echo $baseFolder . "/assets/images/logo.png" ?> class='w-[120px] h-[120px]' id="logo-image"/>
            <h1 class="text-[14px] text-center font-semibold p-4" id="brand-name">Web-based Grading System</h1>
        </div>
        <nav class="my-4 h-full">
            <ul class="menu">
                <li>
                    <a href="<?php echo $dashboard; ?>/dashboard.php">
                        <i class='bx bxs-dashboard text-[24px]'></i>
                        <span class="text-[18px]" id="dashboard-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/manage-sections.php">
                        <i class='bx bxs-school text-[24px]'></i>
                        <span class="text-[18px]" id="sections-text"> Sections</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/manage-activity.php">
                        <i class='bx bx-math text-[24px]'></i>
                        <span class="text-[18px]" id="activities-text"> Activities</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/manage-grading-criteria.php">
                        <i class='bx bx-calculator text-[24px]'></i>
                        <span class="text-[18px]" id="grading-criteria-text"> Grading Criteria</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/manage-release-requests.php">
                        <i class='bx bx-briefcase-alt-2 text-[24px]'></i>
                        <span class="text-[18px]" id="grade-release-request-text">Grade Release Requests</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/manage-change-grade-requests.php">
                        <i class='bx bx-book-content text-[24px]'></i>
                        <span class="text-[18px]" id="change-grade-request-text"> Change Grade Requests</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/view-grades.php">
                        <i class='bx bxs-graduation text-[24px]'></i>
                        <span class="text-[18px]" id="view-grades-text">View Grades</span>
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

function toggleSidebarCollapse() {
    const collapseButton = document.querySelector("#collapse-button");
    const sidebar = document.querySelector("#sidebar");
    const logoImage = document.querySelector("#logo-image");
    const brandName = document.querySelector("#brand-name");

    const dashboardText = document.querySelector("#dashboard-text");
    const sectionText = document.querySelector("#sections-text");
    const activitiesText = document.querySelector("#activities-text");
    const gradingCriteriaText = document.querySelector("#grading-criteria-text");
    const gradeReleaseRequestText = document.querySelector("#grade-release-request-text");
    const changeGradeRequestText = document.querySelector("#change-grade-request-text");
    const viewGradesText = document.querySelector("#view-grades-text");

    if (sidebarOpen) {
        sidebar.classList.replace("w-[350px]", "w-[120px]");

        logoImage.classList.replace("w-[120px]", "w-[48px]");
        logoImage.classList.replace("h-[120px]", "h-[48px]");

        brandName.style.display = "none";

        dashboardText.textContent = "";
        sectionText.textContent = "";
        activitiesText.textContent = "";
        gradingCriteriaText.textContent = "";
        gradeReleaseRequestText.textContent = "";
        changeGradeRequestText.textContent = "";
        viewGradesText.textContent = "";

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

        dashboardText.textContent = "Dashboard";
        sectionText.textContent = "Sections";
        activitiesText.textContent = "Activities";
        gradingCriteriaText.textContent = "Grading Criteria";
        gradeReleaseRequestText.textContent = "Grade Release Requests";
        changeGradeRequestText.textContent = "Change of Grade Requests";
        viewGradesText.textContent = "View Grades";

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