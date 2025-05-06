// load frame
// function loadFrame(page,role,department) {
//     const basePath = "/facultyloading/frame/";
//     document.getElementById("frame").src = basePath + page + ".php?role="+role+"&department="+department;

// }

function loadFrame(page, role, department, event) {
    if (event) event.preventDefault();
    const basePath = "./frame/";
    const newSrc = basePath + page + ".php?role=" + role + "&department=" + department;
    document.getElementById("frame").src = newSrc;

    const newUrl = "?page=" + page;
    window.history.pushState({ page: page }, "", newUrl);

    highlightActiveNav(page);
}

function highlightActiveNav(page) {
    const navLinks = document.querySelectorAll(".nav-item");
    navLinks.forEach(link => {
        link.classList.remove("active");
        const href = link.getAttribute("href");
        if (href && href.includes(`page=${page}`)) {
            link.classList.add("active");
        }
    });
}

window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page') || 'dashboard';
    loadFrame(currentPage, '<?php echo $role; ?>', '<?php echo $departmentId; ?>');
    highlightActiveNav(currentPage); 
};

window.onpopstate = function(event) {
    if (event.state && event.state.page) {
        loadFrame(event.state.page, '<?php echo $role; ?>', '<?php echo $departmentId; ?>');
        highlightActiveNav(event.state.page);
    }
};


document.addEventListener("DOMContentLoaded", function() {
    const navLinks = document.querySelectorAll(".nav-item");

    navLinks.forEach(link => {
        link.addEventListener("click", function() {
            navLinks.forEach(nav => nav.classList.remove("active"));
            this.classList.add("active");
        });
    });
});



// modal for editting program
function openEditProgramModal(programCode, programName, college) {
    try {

        document.getElementById('editProgramCode').value = programCode || '';
        document.getElementById('editProgramName').value = programName || '';
        document.getElementById('editCollege').value = college || '';

        document.getElementById('editProgramModal').style.display = 'block';
    } catch (error) {
        console.error('Error opening edit program modal:', error);
        alert('Failed to open the edit program modal. Please try again.');
    }
}

function closeEditModal() {
    document.getElementById('editProgramModal').style.display = 'none';
}



// modal for adding course
function openAddCourseModal() {
    document.getElementById('addCourseModal').style.display = 'block';
}
function closeAddCourseModal() {
    document.getElementById('addCourseModal').style.display = 'none';
}

// Modal for editting course
function openEditCourseModal(course) {
    try {
        let courseData = JSON.parse(course);

        document.getElementById('edit_course_id').value = courseData.course_id || '';
        document.getElementById('edit_subject_code').value = courseData.subject_code || '';
        document.getElementById('edit_course_title').value = courseData.course_title || '';
        document.getElementById('edit_year_level').value = courseData.year_level || '';
        document.getElementById('edit_semester').value = courseData.semester || '';
        document.getElementById('edit_lecture_hours').value = courseData.lecture_hours || '';
        document.getElementById('edit_lab_hours').value = courseData.lab_hours || '';
        document.getElementById('edit_credit_units').value = courseData.credit_units || '';
        document.getElementById('edit_slots').value = courseData.slots || '';

        document.getElementById('editCourseModal').style.display = 'block';
    } catch (error) {
        console.error('Error opening edit course modal:', error);
        alert('Failed to open the edit modal. Please try again.');
    }
}

function closeEditCourseModal() {
    document.getElementById('editCourseModal').style.display = 'none';
}

function isCourseCodeDuplicate(courseCode) {
    let duplicate = false;
    document.querySelectorAll('table tbody tr').forEach(function(row) {
        const existingCourseCode = row.querySelector('td:first-child').textContent.trim();
        if (existingCourseCode === courseCode) {
            duplicate = true;
        }
    });
    return duplicate;
}

document.getElementById('addCourseForm').onsubmit = function(event) {
    const courseCode = document.getElementById('subject_code').value;
    if (isCourseCodeDuplicate(courseCode)) {
        alert('Course code already exists!');
        event.preventDefault();
    }
};

//modal for class adding
function openAddClassModal() {
    document.getElementById('addClassModal').style.display = 'block';
}

function closeAddClassModal() {
    document.getElementById('addClassModal').style.display = 'none';
}


//modal for editting class
// // Open the Edit Class Modal
function openEditClassModal(sectionId, yearSection) {
    document.getElementById("edit_section_id").value = sectionId;
    document.getElementById("edit_year_section").value = yearSection;
    document.getElementById("editClassModal").style.display = "block";
}

function closeEditClassModal() {
    document.getElementById("editClassModal").style.display = "none";
}

// modal for adding faculty
function openAddNewModal() {
    const modal = document.getElementById('newFacultyModal'); 
    modal.style.display = 'block';
}
function closeAddNewModal() {
    const modal = document.getElementById('newFacultyModal'); 
    modal.style.display = 'none';
}
//******************************* */
// modal for editting faculty
function openEditFacultyModal() {
    const modal = document.getElementById('editFacultyModal'); 
    modal.style.display = 'block';
}
function closeEditFacultyModal() {
    const modal = document.getElementById('editFacultyModal'); 
    modal.style.display = 'none';
}
//******************************* */

function confirmDeleteFaculty(selectedFaculty, department) {
    if (confirm("Are you sure you want to delete this program?")) {
        window.location.href = "/facultyloading/back/faculty.php?action=delete&id=" + selectedFaculty+"&department="+ department;
    }
}

// modal for adding room
function openAddRoomModal() {
    const modal = document.getElementById('openAddRoomModal'); 
    modal.style.display = 'block';
}

function closeAddRoomModal() {
    const modal = document.getElementById('openAddRoomModal'); 
    modal.style.display = 'none';
}

// delete confirmation for room
function deleteRoomComfirm(building, room){
    if(confirm("are you sure you want to delete this room?")){
        window.location.href = "/facultyloading/back/rooms.php?building="+building+"&room="+room+"&action=delete&role=Department Head";
    }
}

// openEditRoomModal
function openEditRoomModal() {
    
    document.getElementById('openEditRoomModal').style.display = 'block';
}

function closeEditRoomModal() {

    document.getElementById('openEditRoomModal').style.display = 'none';

}

//notification
function toggleNotification(){
    
    const notificationPopup = document.getElementById('notificationPopup');
    notificationPopup.style.display = notificationPopup.style.display === 'none' ? 'block' : 'none';

    const notifications = [
        { id: 1, title: "New message from John", message: "Hey, how are you?", time: "2 minutes ago", unread: true },
        { id: 2, title: "Meeting reminder", message: "Team meeting at 3 PM", time: "1 hour ago", unread: true },
        { id: 3, title: "System update", message: "The system will be down for maintenance tonight", time: "Yesterday", unread: false },
    ];

        notificationList.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.unread ? 'unread' : ''}">
                <div class="title">${notification.title}</div>
                <div class="message">${notification.message}</div>
                <div class="time">${notification.time}</div>
            </div>
        `).join('');


}

//viewschedule
function viewSchedule(facultyId) {
    window.location.href = "../frame/info.php?id=" + facultyId;
}