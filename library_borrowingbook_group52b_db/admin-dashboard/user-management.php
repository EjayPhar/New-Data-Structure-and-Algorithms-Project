<?php
session_start();

function require_admin_login(): void
{
  if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login/login.html');
    exit();
  }
}
require_admin_login();

include '../login/db_connect.php';

// Ensure soft-delete columns exist on users table
function user_column_exists(mysqli $conn, string $column): bool
{
  $sql = "SELECT COUNT(*) AS cnt
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = ?";
  $stmt = $conn->prepare($sql);
  if (!$stmt) return false;
  $stmt->bind_param('s', $column);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res) return false;
  $row = $res->fetch_assoc();
  return isset($row['cnt']) && (int)$row['cnt'] > 0;
}

function ensure_users_soft_delete_columns(mysqli $conn): void
{
  if (!user_column_exists($conn, 'deleted_at')) {
    $conn->query("ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL");
  }
  if (!user_column_exists($conn, 'deleted_by')) {
    $conn->query("ALTER TABLE users ADD COLUMN deleted_by VARCHAR(255) NULL DEFAULT NULL");
  }
}
ensure_users_soft_delete_columns($conn);

function normalize_role_for_db(string $role): string
{
  $role = strtolower(trim($role));
  if ($role === 'student')
    return 'user';
  if ($role === 'staff')
    return 'staff';
  if ($role === 'admin')
    return 'admin';
  return 'user';
}
function display_role(string $dbRole): string
{
  $r = strtolower($dbRole);
  if ($r === 'user')
    return 'Student';
  if ($r === 'staff')
    return 'Librarian';
  if ($r === 'admin')
    return 'Admin';
  return ucfirst($r);
}

function fetch_users(mysqli $conn, ?string $q = null, ?string $role = null, ?string $status = null): array
{
  $sql = "SELECT id, username, email, role, status, created_at, last_login FROM users WHERE 1=1";
  $types = '';
  $params = [];
  if ($q) {
    $like = '%' . $q . '%';
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
  }
  if ($role) {
    $sql .= " AND role = ?";
    $types .= 's';
    $params[] = normalize_role_for_db($role);
  }
  if ($status) {
    $sql .= " AND status = ?";
    $types .= 's';
    $params[] = strtolower($status);
  }
  $sql .= ' ORDER BY username ASC';

  if ($types) {
    $stmt = $conn->prepare($sql);
    if (!$stmt)
      return [];
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
  } else {
    $res = $conn->query($sql);
  }
  if (!$res)
    return [];
  return $res->fetch_all(MYSQLI_ASSOC);
}

function add_user(mysqli $conn, array $data, ?string &$error = null): bool
{
  $username = trim($data['username'] ?? '');
  $email = trim($data['email'] ?? '');
  $password = $data['password'] ?? '';
  $role = normalize_role_for_db($data['role'] ?? 'user');
  $status = strtolower(trim($data['status'] ?? 'active'));
  if ($username === '' || $email === '' || $password === '') {
    $error = 'Username, Email and Password are required.';
    return false;
  }
  // unique email
  $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
  if ($stmt) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      $error = 'Email already exists.';
      return false;
    }
  }
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $conn->prepare('INSERT INTO users (username, email, password, role, status) VALUES (?,?,?,?,?)');
  if (!$stmt) {
    $error = 'Failed to prepare insert.';
    return false;
  }
  $stmt->bind_param('sssss', $username, $email, $hash, $role, $status);
  return $stmt->execute();
}

function update_user(mysqli $conn, int $id, array $data, ?string &$error = null): bool
{
  if ($id <= 0) {
    $error = 'Invalid user ID.';
    return false;
  }
  $username = trim($data['username'] ?? '');
  $email = trim($data['email'] ?? '');
  $role = normalize_role_for_db($data['role'] ?? 'user');
  $status = strtolower(trim($data['status'] ?? 'active'));
  $new_password = $data['new_password'] ?? '';

  if ($username === '' || $email === '') {
    $error = 'Username and Email are required.';
    return false;
  }

  if ($new_password !== '') {
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE users SET username=?, email=?, role=?, status=?, password=? WHERE id=?');
    if (!$stmt) {
      $error = 'Failed to prepare update.';
      return false;
    }
    $stmt->bind_param('sssssi', $username, $email, $role, $status, $hash, $id);
    return $stmt->execute();
  } else {
    $stmt = $conn->prepare('UPDATE users SET username=?, email=?, role=?, status=? WHERE id=?');
    if (!$stmt) {
      $error = 'Failed to prepare update.';
      return false;
    }
    $stmt->bind_param('ssssi', $username, $email, $role, $status, $id);
    return $stmt->execute();
  }
}

function toggle_user_status(mysqli $conn, int $id, string $to, string $deleted_by): bool
{
  $to = strtolower($to) === 'disabled' ? 'disabled' : 'active';
  
  if ($to === 'disabled') {
    // Soft delete: set status to disabled and record who deleted it
    $stmt = $conn->prepare('UPDATE users SET status=?, deleted_at=NOW(), deleted_by=? WHERE id=?');
    if (!$stmt)
      return false;
    $stmt->bind_param('ssi', $to, $deleted_by, $id);
  } else {
    // Restore: set status to active and clear deletion info
    $stmt = $conn->prepare('UPDATE users SET status=?, deleted_at=NULL, deleted_by=NULL WHERE id=?');
    if (!$stmt)
      return false;
    $stmt->bind_param('si', $to, $id);
  }
  
  return $stmt->execute();
}

function delete_user(mysqli $conn, int $id, string $deleted_by): bool
{
  // Soft delete: mark as disabled and record deletion info
  $status = 'disabled';
  $stmt = $conn->prepare('UPDATE users SET status=?, deleted_at=NOW(), deleted_by=? WHERE id=?');
  if (!$stmt)
    return false;
  $stmt->bind_param('ssi', $status, $deleted_by, $id);
  return $stmt->execute();
}

$success = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $deleted_by = $_SESSION['username'] ?? 'Unknown';
  
  if ($action === 'add') {
    if (add_user($conn, $_POST, $error)) {
      $success = 'User added successfully.';
    }
  } elseif ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id === (int) ($_SESSION['user_id'] ?? 0) && strtolower($_POST['status'] ?? '') === 'disabled') {
      $error = 'You cannot disable your own account.';
    } else {
      if (update_user($conn, $id, $_POST, $error)) {
        $success = 'User updated successfully.';
      }
    }
  } elseif ($action === 'toggle_status') {
    $id = (int) ($_POST['id'] ?? 0);
    $to = $_POST['to'] ?? 'active';
    if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
      $error = 'You cannot change your own status.';
    } else {
      if (toggle_user_status($conn, $id, $to, $deleted_by)) {
        $success = 'User status updated.';
      } else {
        $error = 'Failed to update status.';
      }
    }
  } elseif ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
      $error = 'You cannot delete your own account.';
    } else {
      if (delete_user($conn, $id, $deleted_by)) {
        $success = 'User deleted successfully.';
      } else {
        $error = 'Failed to delete user.';
      }
    }
  }
}

$q = isset($_GET['q']) ? trim($_GET['q']) : null;
$fRole = isset($_GET['role']) && $_GET['role'] !== '' ? $_GET['role'] : null;
$fStatus = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$users = fetch_users($conn, $q, $fRole, $fStatus);

$roleOptions = ['admin' => 'Admin', 'staff' => 'Librarian', 'student' => 'Student'];
$statusOptions = ['active' => 'Active', 'disabled' => 'Disabled'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>User Management</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: { primary: "#31694E", "background-light": "#f8fafc", "background-dark": "#1e293b" },
          fontFamily: { display: ["Poppins", "sans-serif"] },
          borderRadius: { DEFAULT: "0.5rem" },
        },
      },
    };
  </script>
</head>

<body class="font-display bg-background-light dark:bg-background-dark text-slate-700 dark:text-slate-300">
  <main class="flex h-screen">
    <aside class="w-64 hidden md:block bg-slate-50 dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700">
      <div class="h-16 flex items-center px-6 border-b border-slate-200 dark:border-slate-700">
        <span class="material-icons text-primary mr-2">school</span>
        <span class="font-bold text-lg">Library System</span>
      </div>
      <nav class="p-4 space-y-2">
        <a class="block px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md"
          href="admin.php">
          <span class="material-icons align-middle mr-2">dashboard</span> Dashboard
        </a>
        <a class="block px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md"
          href="book-management.php">
          <span class="material-icons align-middle mr-2">menu_book</span> Book Management
        </a>
        <a class="block px-4 py-2 text-sm font-medium bg-primary text-white rounded-md" href="user-management.php">
          <span class="material-icons align-middle mr-2">group</span> User Management
        </a>
        <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md"
          href="borrow.php">
          <span class="material-icons align-middle mr-2">history</span> Borrowing History
        </a>
        <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md"
          href="Overdue-alerts.php">
          <span class="material-icons mr-3">warning</span>
          Overdue Alerts
        </a>
        <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md"
          href="Global-logs.php">
          <span class="material-icons mr-3">analytics</span>
          Global Logs
        </a>
        <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md"
          href="backup-restore.php">
          <span class="material-icons mr-3">backup</span>
          Backup & Restore
        </a>
        <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md"
          href="Attendance-logs.php">
          <span class="material-icons mr-3">event_available</span>
          Attendance Logs
        </a>
        <a class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-primary hover:text-white rounded-md transform transition-all duration-150 hover:translate-x-1 hover:shadow-md"
          href="change-password.php">
          <span class="material-icons mr-3">lock</span>
          Change Password
        </a>
      </nav>
    </aside>
    <div class="flex-1 flex flex-col">
      <header
        class="h-16 flex items-center justify-between px-8 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
        <h1 class="text-xl font-semibold">Admin Dashboard</h1>
        <div class="text-right">
          <div class="font-medium text-sm"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></div>
          <div class="text-xs text-slate-500"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
        </div>
      </header>
      <div class="flex-1 p-8 overflow-y-auto">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white">User Management</h2>
              <p class="text-gray-500 dark:text-gray-400">Manage students, librarians, and system access.</p>
            </div>
            <button class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-md hover:opacity-90"
              onclick="openAddUser()">
              <span class="material-icons-outlined">add</span>
              Add New User
            </button>
          </div>

          <?php if ($success): ?>
            <div class="mb-6 p-3 border border-green-300 bg-green-50 text-green-800 rounded">
              <?php echo htmlspecialchars($success); ?></div>
          <?php endif; ?>
          <?php if ($error): ?>
            <div class="mb-6 p-3 border border-red-300 bg-red-50 text-red-800 rounded">
              <?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>

          <div class="mb-6 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
            <h3 class="text-lg font-semibold mb-3">User Database</h3>
            <form method="GET" class="flex flex-col md:flex-row gap-3 md:items-end">
              <div class="flex-1 relative">
                <span
                  class="material-icons-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                <input name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>"
                  class="w-full pl-10 pr-4 py-2 border rounded bg-transparent focus:ring-primary focus:border-primary"
                  placeholder="Search by username or email" />
              </div>
              <div>
                <label class="block text-xs text-slate-500 mb-1">Role</label>
                <select name="role"
                  class="px-3 py-2 border rounded bg-transparent focus:ring-primary focus:border-primary">
                  <option value="">All</option>
                  <?php foreach ($roleOptions as $rv => $rl): ?>
                    <option value="<?php echo $rv; ?>" <?php echo ($fRole === $rv ? 'selected' : ''); ?>><?php echo $rl; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="block text-xs text-slate-500 mb-1">Status</label>
                <select name="status"
                  class="px-3 py-2 border rounded bg-transparent focus:ring-primary focus:border-primary">
                  <option value="">All</option>
                  <?php foreach ($statusOptions as $sv => $sl): ?>
                    <option value="<?php echo $sv; ?>" <?php echo ($fStatus === $sv ? 'selected' : ''); ?>><?php echo $sl; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <button class="px-4 py-2 border rounded hover:bg-gray-50">Apply</button>
              </div>
            </form>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead>
                <tr class="border-b border-black-200 dark:border-black-700 text-sm text-black-500 dark:text-black-400">
                  <th class="py-3 px-4 font-medium">User</th>
                  <th class="py-3 px-8 font-medium">Role</th>
                  <th class="py-2 px-4 font-medium">Join Date</th>
                  <th class="py-3 px-3 font-medium">Last Active</th>
                  <th class="py-3 px-5 font-medium">Status</th>
                  <th class="py-3 px-4 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($users)): ?>
                  <tr>
                    <td colspan="6" class="py-6 px-4 text-center text-gray-500">No users found.</td>
                  </tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                  <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="py-4 px-4">
                      <p class="font-medium text-text-light dark:text-text-dark">
                        <?php echo htmlspecialchars($u['username']); ?></p>
                      <p class="text-xs text-subtext-light dark:text-subtext-dark">
                        <?php echo htmlspecialchars($u['email']); ?></p>
                    </td>
                    <td class="py-4 px-4"><?php echo display_role($u['role']); ?></td>
                    <td class="py-4 px-4">
                      <?php echo $u['created_at'] ? date('M d, Y', strtotime($u['created_at'])) : 'N/A'; ?></td>
                    <td class="py-4 px-4">
                      <?php echo $u['last_login'] ? date('M d, Y H:i', strtotime($u['last_login'])) : 'Never'; ?></td>
                    <td class="py-4 px-4">
                      <span
                        class="px-2 py-1 text-xs rounded <?php echo $u['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo ucfirst($u['status']); ?>
                      </span>
                    </td>
                    <td class="py-4 px-4">
                      <div class="flex gap-2">
                        <button
                          onclick="editUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>', '<?php echo htmlspecialchars($u['email']); ?>', '<?php echo $u['role']; ?>', '<?php echo $u['status']; ?>')"
                          class="text-blue-600 hover:text-blue-800">
                          <span class="material-icons">edit</span>
                        </button>
                        <button
                          onclick="toggleStatus(<?php echo $u['id']; ?>, '<?php echo $u['status'] === 'active' ? 'disabled' : 'active'; ?>')"
                          class="text-yellow-600 hover:text-yellow-800">
                          <span
                            class="material-icons"><?php echo $u['status'] === 'active' ? 'block' : 'check_circle'; ?></span>
                        </button>
                        <button onclick="deleteUser(<?php echo $u['id']; ?>)" class="text-red-600 hover:text-red-800">
                          <span class="material-icons">delete</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div id="addUser" class="mt-10">
            <h3 class="text-lg font-semibold mb-3">Add User</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <input type="hidden" name="action" value="add" />
              <div>
                <label class="block text-sm font-medium">Username</label>
                <input name="username" required class="mt-1 block w-full border rounded px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm font-medium">Email</label>
                <input name="email" type="email" required class="mt-1 block w-full border rounded px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm font-medium">Password</label>
                <input name="password" type="password" required class="mt-1 block w-full border rounded px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm font-medium">Role</label>
                <select name="role" class="mt-1 block w-full border rounded px-3 py-2">
                  <?php foreach ($roleOptions as $rv => $rl): ?>
                    <option value="<?php echo $rv; ?>"><?php echo $rl; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium">Status</label>
                <select name="status" class="mt-1 block w-full border rounded px-3 py-2">
                  <?php foreach ($statusOptions as $sv => $sl): ?>
                    <option value="<?php echo $sv; ?>"><?php echo $sl; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="md:col-span-2">
                <button class="px-4 py-2 bg-primary text-white rounded">Create User</button>
              </div>
            </form>
          </div>

        </div>
      </div>
      <footer
        class="h-14 flex items-center justify-between px-8 bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
        <div class="text-sm">© 2025 OMSC Library</div>
        <div class="text-sm text-slate-500 space-x-4">
          <a href="/privacy.html" class="hover:text-primary">Privacy</a>
          <a href="/terms.html" class="hover:text-primary">Terms</a>
        </div>
      </footer>
    </div>
  </main>

  <!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
  <div class="flex items-center justify-center min-h-screen">
    <div class="bg-white dark:bg-gray-900 rounded-lg p-6 w-full max-w-md">
      <h3 class="text-lg font-semibold mb-4">Add New User</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="mb-3">
          <label class="block text-sm font-medium">Username</label>
          <input type="text" name="username" required class="w-full px-3 py-2 border rounded bg-transparent">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium">Email</label>
          <input type="email" name="email" required class="w-full px-3 py-2 border rounded bg-transparent">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium">Password</label>
          <input type="password" name="password" required class="w-full px-3 py-2 border rounded bg-transparent">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium">Role</label>
          <select name="role" class="w-full px-3 py-2 border rounded bg-transparent">
            <option value="student">Student</option>
            <option value="staff">Librarian</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium">Status</label>
          <select name="status" class="w-full px-3 py-2 border rounded bg-transparent">
            <option value="active">Active</option>
            <option value="disabled">Disabled</option>
          </select>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" onclick="closeAddUser()" class="px-4 py-2 border rounded">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-primary text-white rounded">Add User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
  <div class="flex items-center justify-center min-h-screen">
    <div class="bg-white dark:bg-gray-900 rounded-lg p-6 w-full max-w-md">
      <h3 class="text-lg font-semibold mb-4">Edit User</h3>
      <form method="POST">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="editUserId">
        <div class="mb-3">
          <label class="block text-sm font-medium">Username</label>
          <input type="text" name="username" id="editUsername" required class="w-full px-3 py-2 border rounded bg-transparent">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium">Email</label>
          <input type="email" name="email" id="editEmail" required class="w-full px-3 py-2 border rounded bg-transparent">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium">New Password (leave blank to keep current)</label>
          <input type="password" name="new_password" class="w-full px-3 py-2 border rounded bg-transparent">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium">Role</label>
          <select name="role" id="editRole" class="w-full px-3 py-2 border rounded bg-transparent">
            <option value="student">Student</option>
            <option value="staff">Librarian</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium">Status</label>
          <select name="status" id="editStatus" class="w-full px-3 py-2 border rounded bg-transparent">
            <option value="active">Active</option>
            <option value="disabled">Disabled</option>
          </select>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" onclick="closeEditUser()" class="px-4 py-2 border rounded">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-primary text-white rounded">Update User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openAddUser() {
  document.getElementById('addUserModal').classList.remove('hidden');
}

function closeAddUser() {
  document.getElementById('addUserModal').classList.add('hidden');
}

function editUser(id, username, email, role, status) {
  document.getElementById('editUserId').value = id;
  document.getElementById('editUsername').value = username;
  document.getElementById('editEmail').value = email;
  document.getElementById('editRole').value = role;
  document.getElementById('editStatus').value = status;
  document.getElementById('editUserModal').classList.remove('hidden');
}

function closeEditUser() {
  document.getElementById('editUserModal').classList.add('hidden');
}

function toggleStatus(id, to) {
  if (confirm('Are you sure you want to ' + (to === 'disabled' ? 'disable' : 'activate') + ' this user?')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="action" value="toggle_status">
      <input type="hidden" name="id" value="${id}">
      <input type="hidden" name="to" value="${to}">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}

function deleteUser(id) {
  if (confirm('Are you sure you want to delete this user?')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}
</script>
</div>
</div>
</main>
</body>
</html>
