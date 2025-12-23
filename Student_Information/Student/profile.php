<?php
session_start();
include "../login/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login/login1.html");
    exit();
}

$student_id = $_SESSION['student_id'];

$sql = "SELECT s.*, u.email FROM students s JOIN user u ON s.user_id = u.id WHERE s.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Assuming user_id is in students table, but to get initials, maybe need to split name
$name_parts = explode(' ', $student['name']);
$initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Student Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#111827",
                        "background-light": "#F9FAFB",
                        "background-dark": "#0D1117",
                    },
                    fontFamily: {
                        display: ["Sora", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.5rem",
                    },
                },
            },
        };
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings:
                'FILL' 0,
                'wght' 400,
                'GRAD' 0,
                'opsz' 24
        }

        /* Enhanced styles for better responsiveness and modern design */
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
                padding: 16px;
            }
        }

        header {
            margin-bottom: 24px;
            border-bottom-width: 3px;
            /* Adjusted border width for better visibility */
        }

        h2 {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        p {
            line-height: 1.6;
        }

        button {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .rounded-lg {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Fixing border line styles for better appearance */
        aside {
            border-right: 2px solid rgba(229, 231, 235, 1);
            /* Slightly thicker and consistent border */
            padding-right: 1rem;
            /* Add spacing for better alignment */
        }

        @media (max-width: 768px) {
            aside {
                border-right: none;
                border-bottom: 2px solid rgba(229, 231, 235, 1);
                /* Adjust border for smaller screens */
                padding-right: 0;
                /* Remove padding for smaller screens */
            }
        }

        nav a {
            padding-left: 1rem;
            /* Align navigation links with the border */
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
                <a class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-semibold bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white"
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
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">My Profile</h2>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your personal information and academic
                            details</p>
                    </div>
                    <button id="editProfileBtn"
                        class="bg-primary text-white text-sm font-semibold py-2 px-4 rounded-md flex items-center gap-2 hover:bg-opacity-90 transition-colors">
                        <span class="material-symbols-outlined text-base">edit</span>
                        Edit Profile
                    </button>
                </div>
                <div class="space-y-8">
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg border border-gray-200 dark:border-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Picture</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Update your profile picture</p>
                        <div class="flex items-center">
                            <div
                                class="w-20 h-20 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 font-semibold text-xl">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg border border-gray-200 dark:border-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Personal Information</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">Your basic personal details</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-8">
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Full
                                        Name</label>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span
                                            class="material-symbols-outlined text-lg text-gray-400 dark:text-gray-500">person</span>
                                        <p class="text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($student['name']); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone
                                        Number</label>
                                    <a class="flex items-center gap-3 mt-2 group" href="tel:+15551234567">
                                        <span
                                            class="material-symbols-outlined text-lg text-gray-400 dark:text-gray-500">call</span>
                                        <p class="text-gray-800 dark:text-gray-200 group-hover:underline">+639
                                            123-4567</p>
                                    </a>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Blood
                                        Group</label>
                                    <div class="flex items-center gap-3 mt-2">
                                        <div
                                            class="w-6 h-6 border-2 border-red-500 rounded-full flex items-center justify-center text-red-500 text-xs font-bold">
                                            O+</div>
                                        <p class="text-gray-800 dark:text-gray-200">O+</p>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-8">
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</label>
                                    <a class="flex items-center gap-3 mt-2 group" href="mailto:<?php echo htmlspecialchars($student['email']); ?>">
                                        <span
                                            class="material-symbols-outlined text-lg text-gray-400 dark:text-gray-500">mail</span>
                                        <p class="text-gray-800 dark:text-gray-200 group-hover:underline">
                                            <?php echo htmlspecialchars($student['email']); ?></p>
                                    </a>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Date of
                                        Birth</label>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span
                                            class="material-symbols-outlined text-lg text-gray-400 dark:text-gray-500">cake</span>
                                        <p class="text-gray-800 dark:text-gray-200">3/15/2005</p>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Roll
                                        Number</label>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span
                                            class="material-symbols-outlined text-lg text-gray-400 dark:text-gray-500">badge</span>
                                        <p class="text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($student['student_id']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</label>
                                <div class="flex items-center gap-3 mt-2">
                                    <span
                                        class="material-symbols-outlined text-lg text-gray-400 dark:text-gray-500">location_on</span>
                                    <p class="text-gray-800 dark:text-gray-200">123 Main Street, City, State 12345</p>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Bio</label>
                                <p class="text-gray-800 dark:text-gray-200 mt-2 leading-relaxed">A dedicated student
                                    with a passion for science and technology. Actively involved in various
                                    extracurricular activities.</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-6 rounded-lg border border-gray-200 dark:border-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Guardian Information</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">Emergency contact details</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Guardian
                                    Name</label>
                                <div class="flex items-center gap-3 mt-2">
                                    <span
                                        class="material-symbols-outlined text-lg text-gray-400 dark:text-gray-500">person</span>
                                    <p class="text-gray-800 dark:text-gray-200">Jabbiana Pajet Padilgo Camit </p>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Guardian
                                    Phone</label>
                                <a class="flex items-center gap-3 mt-2 group" href="tel:+15559876543">
                                    <span
                                        class="material-symbols-outlined text-lg text-gray-400 dark:text-gray-500">call</span>
                                    <p class="text-gray-800 dark:text-gray-200 group-hover:underline">+63 953- 987-6543
                                    </p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Profile</h3>
                        <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form id="editProfileForm" action="update_student.php" method="POST">
                        <div class="space-y-4">
                            <div>
                                <label for="editName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                                <input type="text" id="editName" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white" required>
                            </div>
                            <div>
                                <label for="editEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <input type="email" id="editEmail" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white" required>
                            </div>
                            <div>
                                <label for="editStudentId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Student ID</label>
                                <input type="text" id="editStudentId" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white" required>
                            </div>
                            <div>
                                <label for="editPassword" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password (leave blank to keep current)</label>
                                <input type="password" id="editPassword" name="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" id="cancelBtn" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md hover:bg-opacity-90">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        const editProfileBtn = document.getElementById('editProfileBtn');
        const editProfileModal = document.getElementById('editProfileModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');

        function openModal() {
            editProfileModal.classList.remove('hidden');
        }

        function closeModal() {
            editProfileModal.classList.add('hidden');
        }

        editProfileBtn.addEventListener('click', openModal);
        closeModalBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Close modal when clicking outside
        editProfileModal.addEventListener('click', function(e) {
            if (e.target === editProfileModal) {
                closeModal();
            }
        });
    </script>

</body>

</html>
