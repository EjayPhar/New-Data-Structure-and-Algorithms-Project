let students = JSON.parse(localStorage.getItem('students')) || [];
const form = document.getElementById('studentForm');
const searchInput = document.getElementById('search');
const studentList = document.getElementById('studentList');
const exportBtn = document.getElementById('exportBtn');

// Render students
function renderStudents(filteredStudents = students) {
    studentList.innerHTML = '';
    filteredStudents.forEach((student, index) => {
        const card = document.createElement('div');
        card.className = 'student-card';
        card.innerHTML = `
            <h3>${student.name}</h3>
            <p><strong>ID:</strong> ${student.id}</p>
            <p><strong>Email:</strong> ${student.email}</p>
            <p><strong>Course:</strong> ${student.course}</p>
            <p><strong>GPA:</strong> ${student.gpa}</p>
            <p><strong>Password:</strong> ${student.password}</p>
            <div class="actions">
                <button onclick="editStudent(${index})">Edit</button>
                <button onclick="deleteStudent(${index})">Delete</button>
            </div>
        `;
        studentList.appendChild(card);
    });
}

// Add student
form.addEventListener('submit', (e) => {
    e.preventDefault();
    const newStudent = {
        name: document.getElementById('name').value,
        id: document.getElementById('id').value,
        email: document.getElementById('email').value,
        course: document.getElementById('course').value,
        gpa: parseFloat(document.getElementById('gpa').value),
        password: document.getElementById('password').value
    };
    students.push(newStudent);
    saveStudents();
    renderStudents();
    form.reset();
});

// Edit student
function editStudent(index) {
    const card = studentList.children[index];
    card.classList.add('edit-mode');
    const student = students[index];
    card.innerHTML = `
        <input type="text" value="${student.name}" id="editName">
        <input type="text" value="${student.id}" id="editId">
        <input type="email" value="${student.email}" id="editEmail">
        <input type="text" value="${student.course}" id="editCourse">
        <input type="number" value="${student.gpa}" id="editGpa" min="0" max="4" step="0.1">
        <input type="password" value="${student.password}" id="editPassword">
        <div class="actions">
            <button onclick="saveEdit(${index})">Save</button>
            <button onclick="cancelEdit(${index})">Cancel</button>
        </div>
    `;
}

function saveEdit(index) {
    students[index] = {
        name: document.getElementById('editName').value,
        id: document.getElementById('editId').value,
        email: document.getElementById('editEmail').value,
        course: document.getElementById('editCourse').value,
        gpa: parseFloat(document.getElementById('editGpa').value),
        password: document.getElementById('editPassword').value
    };
    saveStudents();
    renderStudents();
}

function cancelEdit(index) {
    renderStudents();
}

// Delete student
function deleteStudent(index) {
    students.splice(index, 1);
    saveStudents();
    renderStudents();
}

// Search
searchInput.addEventListener('input', () => {
    const query = searchInput.value.toLowerCase();
    const filtered = students.filter(student =>
        student.name.toLowerCase().includes(query) ||
        student.course.toLowerCase().includes(query) ||
        student.gpa.toString().includes(query)
    );
    renderStudents(filtered);
});

// Export CSV
exportBtn.addEventListener('click', () => {
    let csv = 'Name,ID,Email,Course,GPA,Password\n';
    students.forEach(s => {
        csv += `${s.name},${s.id},${s.email},${s.course},${s.gpa},${s.password}\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'students.csv';
    a.click();
});

// Dark Mode
function toggleDarkMode() {
    document.body.classList.toggle('dark');
    const toggleBtn = document.querySelector('.toggle');
    toggleBtn.textContent = document.body.classList.contains('dark') ? '☀️' : '🌙';
}

// Save to localStorage
function saveStudents() {
    localStorage.setItem('students', JSON.stringify(students));
}

// Initial render
renderStudents();
