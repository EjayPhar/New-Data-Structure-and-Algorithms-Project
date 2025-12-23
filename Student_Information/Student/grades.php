<?php
session_start();
include "../login/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login/login1.html");
    exit();
}

$student_id = $_SESSION['student_id'];

$sql = "SELECT * FROM students WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    // Student data not found, redirect to login
    header("Location: ../login/login1.html");
    exit();
}

// Assuming user_id is in students table, but to get initials, maybe need to split name
$name_parts = explode(' ', $student['name']);
$initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));

// Sample grades data (in a real application, this would come from a grades table)
$grades = [
    ['course_id' => 'CS101', 'course_name' => 'Introduction to Computer Science', 'grade' => 'A-'],
    ['course_id' => 'MATH201', 'course_name' => 'Calculus II', 'grade' => 'B+'],
    ['course_id' => 'PHY101', 'course_name' => 'Physics I', 'grade' => 'A'],
    ['course_id' => 'ENG101', 'course_name' => 'English Composition', 'grade' => 'B'],
    ['course_id' => 'CHEM101', 'course_name' => 'Chemistry I', 'grade' => 'A-']
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Student Grades</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#4F46E5", // Using Indigo as a primary color
                        "background-light": "#F8FAFC", // A very light gray, almost white
                        "background-dark": "#18181B", // A dark gray, zinc-900
                        "card-light": "#FFFFFF",
                        "card-dark": "#27272A", // zinc-800
                        "border-light": "#E2E8F0", // slate-200
                        "border-dark": "#3F3F46", // zinc-700
                        "text-primary-light": "#1E293B", // slate-800
                        "text-primary-dark": "#F8FAFC", // slate-50
                        "text-secondary-light": "#64748B", // slate-500
                        "text-secondary-dark": "#94A3B8", // slate-400
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.75rem", // 12px
                    },
                },
            },
        };
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .material-icons-outlined {
            font-size: 20px;
        }

        /* Additional styles for better responsiveness and consistency */
        @media (max-width: 768px) {
            .flex {
                flex-direction: column;
            }

            aside {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--tw-border-opacity);
            }

            main {
                padding: 4px;
            }
        }

        nav a:hover {
            background-color: rgba(79, 70, 229, 0.1);
            /* Light indigo background */
            color: #4F46E5;
            /* Indigo text color */
            transition: background-color 0.3s ease, color 0.3s ease;
        }
    </style>
</head>

<body class="font-display bg-background-light dark:bg-background-dark text-gray-700 dark:text-gray-300">
    <div class="flex h-screen">
        <aside class="w-64 bg-white dark:bg-gray-900 flex flex-col border-r border-gray-200 dark:border-gray-800">
            <header class="p-6 flex items-center gap-4 border-b border-gray-200 dark:border-gray-800">
                <div
                    class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 font-semibold">
                    <?php echo htmlspecialchars($initials); ?>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($student['name']); ?></h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Student</p>
                </div>
            </header>
            <nav class="flex-1 px-4 py-6 space-y-2">
                <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    href="student-dashboard.php">
                    <span class="material-symbols-outlined text-base">dashboard</span>
                    Dashboard
                </a>
                <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    href="profile.php">
                    <span class="material-symbols-outlined text-base">person</span>
                    Profile
                </a>
                <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    href="Course.php">
                    <span class="material-symbols-outlined text-base">menu_book</span>
                    Course
                </a>
                <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-semibold bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white"
                    href="grades.php">
                    <span class="material-symbols-outlined text-base">grading</span>
                    Grades
                </a>
                   <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    href="schedule.php">
                    <span class="material-symbols-outlined text-base">schedule</span>
                    Schedule
                </a>
                 <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    href="announcement.php">
                    <span class="material-symbols-outlined text-base">campaign</span>
                    Announcements
                </a>
            </nav>
            <div class="p-4 border-t border-gray-200 dark:border-gray-800">
                <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    href="../login/logout.php">
                    <span class="material-symbols-outlined text-base">logout</span>
                    Logout
                </a>
            </div>
        </aside>
        <main class="flex-1 p-8 overflow-y-auto">
            <header class="flex items-center justify-between pb-7 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center gap-4 text-gray-900 dark:text-white">
                    <span class="material-symbols-outlined text-xl">school</span>
                    <h1 class="text-xl font-semibold">Student Portal</h1>
                </div>
            </header>
            <div class="mt-8">
                <div class="mb-6">
                    <h2 class="text-3xl font-bold text-text-primary-light dark:text-text-primary-dark">Academic Grades</h2>
                    <p class="text-text-secondary-light dark:text-text-secondary-dark mt-1">Your grades for completed courses will appear here.</p>
                </div>
                <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg border border-border-light dark:border-border-dark mb-8">
                    <h3 class="text-lg font-semibold text-text-primary-light dark:text-text-primary-dark mb-4">Academic Transcript</h3>
                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mb-6">Your grades and academic performance are listed below.</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr>
                                    <th class="border-b border-border-light dark:border-border-dark py-3 px-4 text-text-primary-light dark:text-text-primary-dark font-semibold">Course ID</th>
                                    <th class="border-b border-border-light dark:border-border-dark py-3 px-4 text-text-primary-light dark:text-text-primary-dark font-semibold">Course Name</th>
                                    <th class="border-b border-border-light dark:border-border-dark py-3 px-4 text-text-primary-light dark:text-text-primary-dark font-semibold text-right">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $grade): ?>
                                <tr class="hover:bg-background-light dark:hover:bg-background-dark">
                                    <td class="border-b border-border-light dark:border-border-dark py-3 px-4 text-text-secondary-light dark:text-text-secondary-dark"><?php echo htmlspecialchars($grade['course_id']); ?></td>
                                    <td class="border-b border-border-light dark:border-border-dark py-3 px-4 text-text-secondary-light dark:text-text-secondary-dark"><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                    <td class="border-b border-border-light dark:border-border-dark py-3 px-4 text-text-secondary-light dark:text-text-secondary-dark text-right font-medium"><?php echo htmlspecialchars($grade['grade']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg border border-border-light dark:border-border-dark">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-text-primary-light dark:text-text-primary-dark">Cumulative GPA</h3>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Overall academic performance</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-primary"><?php echo htmlspecialchars($student['gpa']); ?></p>
                            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">Out of 10.0</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
