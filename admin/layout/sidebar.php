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
        -ms-overflow-style: none;  /* Internet Explorer 10+ */
        scrollbar-width: none;  /* Firefox */
    }
    .sidebar-menu::-webkit-scrollbar { 
        display: none;  /* Safari and Chrome */
    }
</style>

 <aside class="hidden md:block w-[320px] h-100 border-r p-4 flex flex-col gap-4 justify-between sticky top-0 bg-[#405D47] text-white">
     <div class="h-full overflow-y-auto sidebar-menu">
         <div class="flex justify-center items-center flex-col">
             <img src=<?php echo $baseFolder . "/assets/images/logo.png"  ?> class='w-[120px] h-[120px]' />
             <h1 class="text-[14px] text-center font-semibold p-4">Cavite State University - General Trias City, Campus (CvSU)</h1>
         </div>
         <nav class="my-4 h-full">
             <ul class="menu">
                 <li><a href="<?php echo $dasboard; ?>">
                         <i class='bx bxs-dashboard text-[24px]'></i>
                         <span class="text-[18px]">Dashboard</span>
                     </a></li>
                 <li>
                     <details <?php if (in_array($lastUri, ['manage-course', 'course', 'course_section', 'section_students', 'subject_instructors', 'subject_instructor_sections', 'manage-subjects', 'subject', 'manage-sections', 'sections', 'manage-schoolyear', 'academic-year'])) : ?> open <?php endif; ?>>
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
                     <details <?php if (in_array($lastUri, ['manage-student',  'student_enrolled_subjects', 'student', 'manage-instructor', 'instructor', 'manage-admin', 'admin'])) : ?> open <?php endif; ?>>
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