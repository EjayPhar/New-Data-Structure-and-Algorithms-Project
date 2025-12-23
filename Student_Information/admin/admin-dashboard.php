<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') header('Location: ../login/login1.php');
require '../login/db.php';
$students = $conn->query("SELECT s.*, u.email FROM students s JOIN user u ON s.user_id = u.id ORDER BY s.id DESC");
$total_students = $students->num_rows;
$avg_gpa = 0;
if($total_students > 0){
    $gpas = [];
    $students->data_seek(0);
    while($s = $students->fetch_assoc()) $gpas[] = $s['gpa'];
    $avg_gpa = round(array_sum($gpas) / count($gpas), 2);
}
$students->data_seek(0);
$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<button onclick="toggleDarkMode()" class="toggle" title="Toggle Dark Mode"><i class="fas fa-moon"></i></button>
<div class="container">
<h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
<div class="dashboard-stats">
<div class="stat-card">
<h3><i class="fas fa-users"></i> Total Students</h3>
<p><?php echo $total_students; ?></p>
</div>
<div class="stat-card">
<h3><i class="fas fa-chart-line"></i> Average GPA</h3>
<p><?php echo $avg_gpa; ?></p>
</div>
</div>
<div class="dashboard-header">
<p><a href="../login/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></p>
<div class="dashboard-actions">
<a href="add_student.php" class="add-btn"><i class="fas fa-plus"></i> Add Student</a>
<button onclick="exportCSV()" class="export-btn"><i class="fas fa-download"></i> Export CSV</button>
</div>
</div>
<div class="search-container">
<input type="text" id="searchInput" placeholder="Search students by name, email, or course..." class="search-input">
<i class="fas fa-search search-icon"></i>
</div>
<div class="table-container">
<table class="student-table">
<thead>
<tr>
<th data-sort="student_id" class="sortable">ID <i class="fas fa-sort"></i></th>
<th data-sort="name" class="sortable">Name <i class="fas fa-sort"></i></th>
<th data-sort="email" class="sortable">Email <i class="fas fa-sort"></i></th>
<th data-sort="course" class="sortable">Course <i class="fas fa-sort"></i></th>
<th data-sort="gpa" class="sortable">GPA <i class="fas fa-sort"></i></th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($s=$students->fetch_assoc()): ?>
<tr>
<td><?= $s['student_id'] ?></td>
<td><?= $s['name'] ?></td>
<td><?= $s['email'] ?></td>
<td><?= $s['course'] ?></td>
<td><?= $s['gpa'] ?></td>
<td class="actions">
<a href="edit_student.php?id=<?= $s['id'] ?>" class="edit-btn" title="Edit Student"><i class="fas fa-edit"></i></a>
<a href="delete_student.php?id=<?= $s['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this student?')" title="Delete Student"><i class="fas fa-trash"></i></a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
<script>
function toggleDarkMode(){
document.body.classList.toggle('dark');
}
<?php if($success): ?>
showNotification('Student added successfully!');
<?php endif; ?>
function exportCSV(){
let table=document.querySelector('table');
let csv=['ID,Name,Email,Course,GPA'];
for(let row of table.rows){
if(row.rowIndex === 0) continue; // Skip header
let rowData=[row.cells[0].innerText, row.cells[1].innerText, row.cells[2].innerText, row.cells[3].innerText, row.cells[4].innerText];
csv.push(rowData.join(','));
}
let blob=new Blob([csv.join('\n')],{type:'text/csv'});
let a=document.createElement('a');
a.href=URL.createObjectURL(blob);
a.download='students.csv';
a.click();
showNotification('CSV exported successfully!');
}
function showNotification(message, isError = false){
let notification = document.createElement('div');
notification.className = 'notification' + (isError ? ' error' : '');
notification.textContent = message;
document.body.appendChild(notification);
setTimeout(() => notification.classList.add('show'), 100);
setTimeout(() => {
notification.classList.remove('show');
setTimeout(() => document.body.removeChild(notification), 300);
}, 3000);
}
document.addEventListener('DOMContentLoaded', function(){
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', function(){
const filter = searchInput.value.toLowerCase();
const rows = document.querySelectorAll('.student-table tbody tr');
rows.forEach(row => {
const text = row.textContent.toLowerCase();
row.style.display = text.includes(filter) ? '' : 'none';
});
});
const sortableHeaders = document.querySelectorAll('.sortable');
sortableHeaders.forEach(header => {
header.addEventListener('click', function(){
const table = header.closest('table');
const tbody = table.querySelector('tbody');
const rows = Array.from(tbody.rows);
const column = header.getAttribute('data-sort');
const isAsc = header.classList.contains('asc');
rows.sort((a, b) => {
let aVal = a.querySelector(`[data-sort="${column}"]`) ? a.querySelector(`[data-sort="${column}"]`).textContent : a.cells[column === 'student_id' ? 0 : column === 'name' ? 1 : column === 'email' ? 2 : column === 'course' ? 3 : 4].textContent;
let bVal = b.querySelector(`[data-sort="${column}"]`) ? b.querySelector(`[data-sort="${column}"]`).textContent : b.cells[column === 'student_id' ? 0 : column === 'name' ? 1 : column === 'email' ? 2 : column === 'course' ? 3 : 4].textContent;
if(column === 'gpa'){
aVal = parseFloat(aVal);
bVal = parseFloat(bVal);
}
if(isAsc){
return aVal > bVal ? 1 : -1;
} else {
return aVal < bVal ? 1 : -1;
}
});
sortableHeaders.forEach(h => h.classList.remove('asc', 'desc'));
header.classList.toggle('asc', !isAsc);
header.classList.toggle('desc', isAsc);
tbody.innerHTML = '';
rows.forEach(row => tbody.appendChild(row));
});
});
});
</script>
</body>
</html>
