<?php
session_start();
include "../login/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login/login1.php");
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
    header("Location: ../login/login.php");
    exit();
}

// Assuming user_id is in students table, but to get initials, maybe need to split name
$name_parts = explode(' ', $student['name']);
$initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));

// Fetch schedule data
$sql_schedule = "SELECT * FROM schedule WHERE student_id = ? ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), time";
$stmt_schedule = $conn->prepare($sql_schedule);
$stmt_schedule->bind_param("i", $student['id']);
$stmt_schedule->execute();
$result_schedule = $stmt_schedule->get_result();

$schedule_by_day = [];
while ($row = $result_schedule->fetch_assoc()) {
    $schedule_by_day[$row['day']][] = $row;
}

$stmt_schedule->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Student Schedule</title>
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
                        primary: "#4F46E5",
                        "background-light": "#F8FAFC",
                        "background-dark": "#18181B",
                        "card-light": "#FFFFFF",
                        "card-dark": "#27272A",
                        "border-light": "#E2E8F0",
                        "border-dark": "#3F3F46",
                        "text-primary-light": "#1E293B",
                        "text-primary-dark": "#F8FAFC",
                        "text-secondary-light": "#64748B",
                        "text-secondary-dark": "#94A3B8",
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.75rem",
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
            color: #4F46E5;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
    </style>
</head>

<body class="font-display bg-background-light dark:bg-background-dark text-gray-700 dark:text-gray-300">
    <div class="flex h-screen">
        <aside class="w-64 bg-white dark:bg-gray-900 flex flex-col border-r border-gray-200 dark:border-gray-800">
            <div class="p-6 flex items-center gap-4 border-b border-gray-200 dark:border-gray-800">
                <div
                    class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 font-semibold">
                    <?php echo htmlspecialchars($initials); ?>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($student['name']); ?></h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Student</p>
                </div>
            </div>
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
                <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    href="grades.php">
                    <span class="material-symbols-outlined text-base">grading</span>
                    Grades
                </a>
                <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-semibold bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white"
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
            <header class="sticky top-0 z-10 flex items-center justify-between pb-7 border-b border-gray-200 dark:border-gray-800 bg-background-light dark:bg-background-dark">
                <div class="flex items-center gap-4 text-gray-900 dark:text-white">
                    <span class="material-symbols-outlined text-xl">schedule</span>
                    <h1 class="text-xl font-semibold">Schedule</h1>
                </div>
            </header>
            <div class="mt-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Class Schedule</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-2">Your weekly class schedule for this school year is
                    listed below.</p>
                <div class="mt-6 space-y-6">
                    <?php
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                    foreach ($days as $day) {
                        echo '<div>';
                        echo '<h3 class="text-lg font-semibold text-gray-900 dark:text-white">' . $day . '</h3>';
                        echo '<div class="mt-2 space-y-2">';
                        if (isset($schedule_by_day[$day])) {
                            foreach ($schedule_by_day[$day] as $class) {
                                echo '<div class="p-4 bg-background-light dark:bg-background-dark border border-border-light dark:border-border-dark rounded-lg">';
                                echo '<p class="font-semibold text-gray-900 dark:text-white">' . htmlspecialchars($class['course_name']) . '</p>';
                                echo '<p class="text-sm text-gray-500 dark:text-gray-400">Instructor: ' . htmlspecialchars($class['instructor']) . '</p>';
                                echo '<p class="text-sm text-gray-500 dark:text-gray-400">Time: ' . htmlspecialchars($class['time']) . '</p>';
                                echo '<p class="text-sm text-gray-500 dark:text-gray-400">Classroom: ' . htmlspecialchars($class['classroom']) . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="text-gray-500 dark:text-gray-400 italic">No classes scheduled for this day.</p>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
        </main>
    </div>
</body>

</html>
