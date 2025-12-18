<?php
session_start();
include '../login/db_connect.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login/login.html");
    exit();
}

// Function to automatically mark overdue books and calculate penalties
function updateOverdueBooks($conn) {
    // Get all borrowed books that are past due date
    $check_sql = "SELECT id, due_date FROM borrowings WHERE status = 'borrowed' AND due_date < CURDATE()";
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $borrowing_id = $row['id'];
            
            // Calculate penalty: 10 pesos per week overdue
            $due_date = new DateTime($row['due_date']);
            $current_date = new DateTime();
            $days_overdue = max(0, $current_date->diff($due_date)->days);
            $weeks_overdue = ceil($days_overdue / 7);
            $penalty_amount = $weeks_overdue * 10;
            
            // Update borrowing status and penalty
            $update_sql = "UPDATE borrowings SET status='overdue', penalty_amount = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('di', $penalty_amount, $borrowing_id);
            $update_stmt->execute();
            
            // Insert or update penalty record in penalties table
            $penalty_check = "SELECT id FROM penalties WHERE borrowing_id = ? LIMIT 1";
            $check_stmt = $conn->prepare($penalty_check);
            $check_stmt->bind_param('i', $borrowing_id);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();
            
            if ($check_res->num_rows > 0) {
                // Update existing penalty record
                $penalty_update = "UPDATE penalties SET amount = ?, date_assessed = CURDATE() WHERE borrowing_id = ?";
                $penalty_stmt = $conn->prepare($penalty_update);
                $penalty_stmt->bind_param('di', $penalty_amount, $borrowing_id);
                $penalty_stmt->execute();
            } else {
                // Insert new penalty record
                $penalty_insert = "INSERT INTO penalties (borrowing_id, amount, paid, date_assessed) VALUES (?, ?, 0, CURDATE())";
                $penalty_stmt = $conn->prepare($penalty_insert);
                $penalty_stmt->bind_param('id', $borrowing_id, $penalty_amount);
                $penalty_stmt->execute();
            }
        }
    }
}

// Auto-update overdue books when page loads
updateOverdueBooks($conn);

// Function to get overdue alerts
function getOverdueAlerts($conn) {
    $sql = "SELECT br.id, bk.title, bk.author, bk.isbn, br.borrow_date, br.due_date, br.status, br.penalty_amount,
                   u.username, u.email, u.id as user_id,
                   COUNT(r.id) as reminders_sent
            FROM borrowings br
            JOIN books bk ON br.book_id = bk.id
            JOIN users u ON br.user_id = u.id
            LEFT JOIN reminders r ON br.id = r.borrowing_id
            WHERE br.status = 'overdue' AND br.due_date < CURDATE()
            GROUP BY br.id
            ORDER BY br.due_date ASC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get overdue statistics
function getOverdueStats($conn) {
    $stats = [
        'due_soon' => 0,
        'overdue' => 0,
        'reminders_sent' => 0
    ];

    // Due soon (within 7 days)
    $sql = "SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    $result = $conn->query($sql);
    $stats['due_soon'] = $result->fetch_assoc()['count'];

    // Overdue
    $sql = "SELECT COUNT(*) as count FROM borrowings WHERE status = 'overdue' AND due_date < CURDATE()";
    $result = $conn->query($sql);
    $stats['overdue'] = $result->fetch_assoc()['count'];

    // Reminders sent (total this week)
    $sql = "SELECT COUNT(*) as count FROM reminders WHERE sent_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    $result = $conn->query($sql);
    $stats['reminders_sent'] = $result->fetch_assoc()['count'];

    return $stats;
}

// Function to send email reminder
function sendEmailReminder($conn, $borrowing_id, $book_title, $student_email, $student_name, $days_overdue, $message) {
    $subject = "Overdue Book Reminder: $book_title";
    $body = "Dear $student_name,\n\nYour book '$book_title' is $days_overdue days overdue. Please return it as soon as possible.\n\n$message\n\nRegards,\nLibrary Staff";
    $headers = "From: library@yourdomain.com"; // Update with your email

    if (mail($student_email, $subject, $body, $headers)) {
        // Insert into reminders table
        $stmt = $conn->prepare("INSERT INTO reminders (borrowing_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $borrowing_id, $message);
        $stmt->execute();
        $stmt->close();

        // Update status if overdue by 14+ days and 3+ reminders
        $reminders_count = $conn->query("SELECT COUNT(*) as count FROM reminders WHERE borrowing_id = $borrowing_id")->fetch_assoc()['count'];
        if ($days_overdue >= 14 && $reminders_count >= 3) {
            $conn->query("UPDATE borrowings SET status = 'lost' WHERE id = $borrowing_id");
        }
        return true;
    }
    return false;
}

// Handle POST requests for sending reminders
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_all_reminders'])) {
        $overdue_alerts = getOverdueAlerts($conn);
        $sent_count = 0;
        foreach ($overdue_alerts as $alert) {
            $days_overdue = floor((time() - strtotime($alert['due_date'])) / 86400);
            $message = "Please return the book promptly to avoid fines.";
            if (sendEmailReminder($conn, $alert['id'], $alert['title'], $alert['email'], $alert['username'], $days_overdue, $message)) {
                $sent_count++;
            }
        }
        echo "<script>alert('$sent_count reminders sent successfully!');</script>";
    } elseif (isset($_POST['send_single_reminder'])) {
        $borrowing_id = intval($_POST['borrowing_id']);
        $book_title = htmlspecialchars($_POST['book_title']);
        $student_email = htmlspecialchars($_POST['student_email']);
        $student_name = htmlspecialchars($_POST['student_name']);
        $days_overdue = intval($_POST['days_overdue']);
        $message = htmlspecialchars($_POST['message']);
        if (sendEmailReminder($conn, $borrowing_id, $book_title, $student_email, $student_name, $days_overdue, $message)) {
            echo "<script>alert('Reminder sent successfully!');</script>";
        } else {
            echo "<script>alert('Failed to send reminder.');</script>";
        }
    }
}

// Get data
$overdue_alerts = getOverdueAlerts($conn);
$stats = getOverdueStats($conn);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Overdue Alerts</title>
     <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/icon?family=Material+Icons"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/icon?family=Material+Icons"
      rel="stylesheet"
    />
      <link
      href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined"
      rel="stylesheet"
    />
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              primary: "#31694E",
              "background-light": "#f8fafc",
              "background-dark": "#1e293b",
            },
            fontFamily: {
              display: ["Poppins", "sans-serif"],
            },
            borderRadius: {
              DEFAULT: "0.5rem",
            },
          },
        },
      };
    </script>
    <script>
      // Auto highlight active sidebar link
      (function () {
        var links = document.querySelectorAll('aside nav a');
        var current = location.pathname.split('/').pop();
        if (!current) current = 'overdue-alerts.php';
        links.forEach(function (a) {
          var href = a.getAttribute('href');
          if (!href) return;
          var name = href.split('/').pop();
          if (!name) return;
          if (name === current) {
            a.classList.add('bg-primary', 'text-white');
            a.setAttribute('aria-current', 'page');
          } else {
            a.classList.remove('bg-primary', 'text-white');
            a.removeAttribute('aria-current');
          }
        });
      })();
    </script>
    <script>
      // Sidebar toggle for small screens (show/hide with backdrop)
      (function () {
        var btn = document.getElementById('menu-btn');
        var closeBtn = document.getElementById('menu-close');
        var sidebar = document.querySelector('aside');
        var backdrop = document.getElementById('backdrop');

        function showSidebar() {
          sidebar.classList.remove('-translate-x-full');
          sidebar.classList.add('translate-x-0');
          backdrop.classList.remove('hidden');
          document.body.style.overflow = 'hidden';
          if (btn) btn.setAttribute('aria-expanded', 'true');
        }

        function hideSidebar() {
          sidebar.classList.add('-translate-x-full');
          sidebar.classList.remove('translate-x-0');
          backdrop.classList.add('hidden');
          document.body.style.overflow = '';
          if (btn) btn.setAttribute('aria-expanded', 'false');
        }

        document.addEventListener('keydown', function (ev) {
          if (ev.key === 'Escape' && window.innerWidth < 768) hideSidebar();
        });

        if (btn && sidebar && backdrop) btn.addEventListener('click', showSidebar);
        if (closeBtn && sidebar && backdrop) closeBtn.addEventListener('click', hideSidebar);
        if (backdrop) backdrop.addEventListener('click', hideSidebar);
      })();
    </script>
  </head>
  <body
    @keydown.escape.window="isEditModalOpen = false; isDeleteModalOpen = false; isAddModalOpen = false; isEmailModalOpen = false"
    x-data="{ isEditModalOpen: false, isDeleteModalOpen: false, deleteBookTitle: '', isAddModalOpen: false, isEmailModalOpen: false }"
    class="font-display bg-background-light dark:bg-background-dark text-slate-700 dark:text-slate-300"
  >
    <main class="flex h-screen">
        <div id="backdrop" class="fixed inset-0 bg-black/40 z-40 hidden md:hidden"></div>
      <aside id="sidebar"
        class="fixed inset-y-0 left-0 z-50 w-64 transform -translate-x-full md:translate-x-0 md:static md:flex bg-slate-50 dark:bg-slate-800 flex flex-col border-r border-slate-200 dark:border-slate-700 transition-transform duration-200"
      >
        <div
          class="h-16 flex items-center px-6 border-b border-slate-200 dark:border-slate-700"
        >
          <span class="material-icons text-primary mr-2">school</span>
          <span class="font-bold text-lg text-slate-800 dark:text-slate-100"
            >Library System</span
          >
          <button id="menu-close" class="md:hidden p-2 text-slate-500 dark:text-slate-300 hover:text-slate-700 dark:hover:text-slate-200 ml-auto">
            <span class="material-icons">close</span>
          </button>
        </div>
        <nav class="flex-1 p-4 space-y-2">
          <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md" href="librarian-dashboard.php">
            <span class="material-icons mr-3">dashboard</span>
            Dashboard
          </a>
          <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md" href="Book_Management.php">
            <span class="material-icons mr-3">menu_book</span>
            Book Management
          </a>
          <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md" href="user-management.php">
            <span class="material-icons mr-3">group</span>
            User Management
          </a>
          <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md" href="borrow.php">
            <span class="material-icons mr-3">history</span>
            Borrowing History
          </a>
          <a class="flex items-center px-4 py-2 text-sm font-medium bg-primary text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md" href="overdue-alerts.php">
            <span class="material-icons mr-3">warning</span>
            Overdue Alerts
          </a>
          <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md" href="backup_restore.php">
            <span class="material-icons mr-3">backup</span>
            Backup & Restore
          </a>
          <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md"
                    href="Attendance-logs.php">
                    <span class="material-icons mr-3">event_available</span>
                    Attendance Logs
                </a>
          <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md" href="change-password.php">
            <span class="material-icons mr-3">lock</span>
            Change Password
          </a>
        </nav>
      </aside>
    <div class="flex-1 flex flex-col">
        <header class="h-16 flex items-center justify-between px-8 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
          <div class="flex items-center md:hidden mr-4">
            <button id="menu-btn" aria-expanded="false" class="p-2 text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-slate-100" aria-label="Open sidebar">
              <span class="material-icons">menu</span>
            </button>
          </div>
          <h1 class="text-xl font-semibold text-slate-800 dark:text-slate-100">Librarian Dashboard</h1>
          <div class="flex items-center gap-4">
            <div class="text-right">
              <p class="font-medium text-sm text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
              <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
            </div>
          </div>
        </header>
      <div class="flex-1 p-8 overflow-y-auto">
          <div class="bg-white dark:bg-gray-900 rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
              <div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                  Overdue Alerts
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mt-1">
                  Monitor and manage overdue books and student notifications.
                </p>
              </div>
              <div class="flex items-center gap-4">
                <form method="POST" style="display: inline;">
                  <button type="submit" name="send_all_reminders" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-md hover:opacity-90 transition-opacity">
                    <span class="material-icons-outlined">send</span>
                    Send Reminders
                  </button>
                </form>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 ">
            <div
              class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border-l-4 border-blue-400
          transition duration-200 hover:bg-blue-50 dark:hover:bg-gray-700 hover:shadow-md hover:-translate-y-1"
          >
              <div class="flex justify-between items-start">
                <p class="text-gray-500 dark:text-gray-400">Due Soon</p>
                <span class="material-symbols-outlined text-blue-500"
                  >calendar_today</span
                >
              </div>
              <p class="text-4xl font-bold mt-2 text-gray-800 dark:text-white">
                <?php echo $stats['due_soon']; ?>
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                within next 7 days
              </p>
            </div>
            <div
              class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border-l-4 border-green-400
         transition duration-200 hover:bg-green-50 dark:hover:bg-gray-700 hover:shadow-md hover:-translate-y-1"
>
              <div class="flex justify-between items-start">
                <p class="text-gray-500 dark:text-gray-400">Reminders Sent</p>
               <span class="material-symbols-outlined text-green-500"
                    >mail</span
                  >
              </div>
              <p class="text-4xl font-bold mt-2 text-gray-800 dark:text-white">
                <?php echo $stats['reminders_sent']; ?>
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400">this week</p>
            </div>
            <div
              class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border-l-4 border-red-400
              transition duration-200 hover:bg-red-50 dark:hover:bg-gray-700 hover:shadow-md hover:-translate-y-1">
              <div class="flex justify-between items-start">
                <p class="text-gray-500 dark:text-gray-400">Overdue Books</p>
                <span class="material-icons text-red-400">warning</span>
              </div>
              <p class="text-4xl font-bold mt-2 text-gray-800 dark:text-white">
               <?php echo $stats['overdue']; ?>
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                require immediate action
              </p>
            </div>
            </div>
            <br>
            <div
              class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <h3
                class="text-lg font-semibold text-slate-800 dark:text-slate-200"
              >
                Search &amp; Filter Options
              </h3>
              <br>
              <div class="flex items-center gap-4">
                <div class="relative flex-grow">
                  <span
                    class="material-icons-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                    >search</span
                  >
                  <input
                    id="search-input"
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-transparent focus:ring-primary focus:border-primary transition-all hover:border-gray-400 dark:hover:border-gray-500"
                    placeholder="Search by title, author, or ISBN..."
                    type="text"
                  />
                </div>
               
                <select
                  id="status-select"
                  class="w-48 border border-gray-300 dark:border-gray-600 rounded-md bg-transparent focus:ring-primary focus:border-primary transition-all hover:border-gray-400 dark:hover:border-gray-500"
                >
                  <option>All Status</option>
                  <option>Recently Overdue</option>
                  <option>Critical(7+ days)</option>
                  <option>Potentially Lost</option>
                </select>
              </div>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Overdue books requiring attention (<?php echo count($overdue_alerts); ?> overdue <?php echo count($overdue_alerts) === 1 ? 'item' : 'items'; ?>)
              </p>
              <div class="overflow-x-auto">
                <table class="w-full text-left">
                  <thead>
                  <tr
                      class="border-b border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400"
                    >
                      <th class="py-3 px-4 font-medium">Book Information</th>
                      <th class="py-3 px-4 font-medium">Student Details</th>
                      <th class="py-3 px-2 font-medium">Overdue Information</th>
                      <th class="py-3 px-4 font-medium">Penalty</th>
                      <th class="py-3 px-4 font-medium">Status</th>
                      <th class="py-3 px-4 font-medium">Actions</th>
                    </tr>  
                  </thead>
                  <tbody>
                    <?php if (empty($overdue_alerts)): ?>
                      <tr>
                        <td colspan="6" class="py-8 px-4 text-center text-gray-500 dark:text-gray-400">
                          <div class="flex flex-col items-center gap-2">
                            <span class="material-icons-outlined text-4xl text-gray-300">check_circle</span>
                            <p class="font-medium">No overdue books found</p>
                            <p class="text-sm">All books are returned on time!</p>
                          </div>
                        </td>
                      </tr>
                    <?php else: ?>
                    <?php foreach ($overdue_alerts as $alert): 
                      $days_overdue = floor((time() - strtotime($alert['due_date'])) / 86400);
                      $status = $days_overdue >= 14 ? 'lost' : ($days_overdue >= 7 ? 'critical' : 'overdue');
                    ?>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                      <td class="py-4 px-4">
                        <p class="font-medium text-text-light dark:text-text-dark">
                        <?php echo htmlspecialchars($alert['title']); ?>
                    </p>
                    <p
                      class="text-xs text-subtext-light dark:text-subtext-dark"
                    >
                      by <?php echo htmlspecialchars($alert['author']); ?>
                    </p>
                    <p
                      class="text-xs text-subtext-light dark:text-subtext-dark"
                    >
                      ISBN: <?php echo htmlspecialchars($alert['isbn']); ?>
                    </p>
                      </td>
                      <td class="py-4 px-2">
                        <p class="font-medium text-text-light dark:text-text-dark">
                        <?php echo htmlspecialchars($alert['username']); ?>
                    </p>
                    <p
                      class="text-xs text-subtext-light dark:text-subtext-dark"
                    >
                     ID: <?php echo htmlspecialchars($alert['user_id']); ?>
                    </p>
                    <p
                      class="text-xs text-subtext-light dark:text-subtext-dark"
                    >
                      <?php echo htmlspecialchars($alert['email']); ?>
                    </p>
                      </td>
                      <td class="py-4 px-2">
                        <p
                      class="text-xs text-subtext-light dark:text-subtext-dark"
                    >
                      Due: <?php echo date('M d, Y', strtotime($alert['due_date'])); ?>
                    </p>
                    <p class="font-medium text-text-light dark:text-text-dark">
                      <?php echo $days_overdue; ?> days overdue
                    </p>
                    <p
                      class="text-xs text-subtext-light dark:text-subtext-dark"
                    >
                      Borrowed: <?php echo date('M d, Y', strtotime($alert['borrow_date'])); ?>
                    </p>
                    <p
                      class="text-xs text-subtext-light dark:text-subtext-dark"
                    >
                      <?php echo $alert['reminders_sent']; ?> reminders sent
                    </p>
                      </td>
                      <td class="py-4 px-4">
                        <?php if (!empty($alert['penalty_amount']) && $alert['penalty_amount'] > 0): ?>
                          <span class="text-red-600 font-semibold">₱<?php echo number_format($alert['penalty_amount'], 2); ?></span>
                        <?php else: ?>
                          <span class="text-gray-400">—</span>
                        <?php endif; ?>
                      </td>
                      <td class="py-4 px-4">
                        <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-semibold"><?php echo $status; ?></span>
                      </td>
                      <td class="py-4 px-4">
                        <div class="flex items-center gap-2">
                          <form method="POST" style="display: inline;">
                            <input type="hidden" name="borrowing_id" value="<?php echo $alert['id']; ?>">
                            <input type="hidden" name="book_title" value="<?php echo htmlspecialchars($alert['title']); ?>">
                            <input type="hidden" name="student_email" value="<?php echo htmlspecialchars($alert['email']); ?>">
                            <input type="hidden" name="student_name" value="<?php echo htmlspecialchars($alert['username']); ?>">
                            <input type="hidden" name="days_overdue" value="<?php echo $days_overdue; ?>">
                            <input type="hidden" name="message" value="Please return the book promptly.">
                            <button type="submit" name="send_single_reminder" class="text-green-500 hover:text-green-700 h-6 w-16 flex items-center justify-center rounded-full hover:bg-green-100 transition-colors border border-green-300">
                              Send <span class="material-icons-outlined text-base">mail</span>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <footer class="h-14 flex items-center justify-between px-8 bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
          <div class="text-sm">© 2025 OMSC Library</div>
          <div class="text-sm text-slate-500 space-x-4">
            <a href="/privacy.html" class="hover:text-primary">Privacy</a>
            <a href="/terms.html" class="hover:text-primary">Terms</a>
          </div>
        </footer>
    </div>
    </main>
     <script>
      // Filtering functionality for search and status
      const searchInput = document.getElementById('search-input');
      const statusSelect = document.getElementById('status-select');
      const tableBody = document.querySelector('tbody');

      function filterBooks() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedStatus = statusSelect.value;
        const rows = tableBody.querySelectorAll('tr');

        rows.forEach(row => {
          const bookInfo = row.querySelector('td:first-child').textContent.toLowerCase();
          const statusSpan = row.querySelector('td:nth-child(5) span');
          const statusText = statusSpan ? statusSpan.textContent.trim().toLowerCase() : '';

          const searchMatch = searchTerm === '' || bookInfo.includes(searchTerm);

          let statusMatch = true;
          if (selectedStatus !== 'All Status') {
            if (selectedStatus === 'Recently Overdue') {
              statusMatch = statusText === 'overdue';
            } else if (selectedStatus === 'Critical(7+ days)') {
              statusMatch = statusText === 'critical';
            } else if (selectedStatus === 'Potentially Lost') {
              statusMatch = statusText === 'lost';
            }
          }

          if (searchMatch && statusMatch) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }

      // Add event listeners
      searchInput.addEventListener('input', filterBooks);
      statusSelect.addEventListener('change', filterBooks);

      // Initial filter
      filterBooks();
    </script>
    <script
      defer=""
      src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js"
    ></script>
  </body>
</html>
<?php
$conn->close();
?>