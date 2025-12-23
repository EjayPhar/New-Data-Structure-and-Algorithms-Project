<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Student Portal Course</title>
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
                    AK
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Alex Kumar</h2>
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
                <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-semibold bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white"
                    href="Course.php">
                    <span class="material-symbols-outlined text-base">menu_book</span>
                    Course
                </a>
                <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
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
            <header class="sticky top-0 z-10 flex items-center justify-between pb-7 border-b border-gray-200 dark:border-gray-800 bg-background-light dark:bg-background-dark">
                <div class="flex items-center gap-4 text-gray-900 dark:text-white">
                    <span class="material-symbols-outlined text-xl">school</span>
                    <h1 class="text-xl font-semibold">Student Portal</h1>
                </div>
            </header>
            <header class="mt-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">All Courses</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Your enrolled courses for the current and completed semesters
                </p>
            </header>
            <div class="mt-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Course 1 -->
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg border border-gray-200 dark:border-gray-800">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Course ID: CS101</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Course Name: Introduction to
                                    Programming</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Instructor: Dr. John Doe</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-green-600 dark:text-green-400">Completed</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">3 Credits</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Grade: A-</p>
                            </div>
                        </div>
                    </div>
                    <!-- Course 2 -->
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg border border-gray-200 dark:border-gray-800">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Course ID: CS102</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Course Name: Data Structures</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Instructor: Prof. Jane Smith</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-yellow-600 dark:text-yellow-400">In Progress</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">3 Credits</p>
                            </div>
                        </div>
                    </div>
                    <!-- Course 3 -->
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg border border-gray-200 dark:border-gray-800">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Course ID: CS103</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Course Name: Algorithms</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Instructor: Dr. Emily Brown</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-green-600 dark:text-green-400">Completed</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">3 Credits</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Grade: B</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
