let allStudents = [];
let filteredStudents = [];

let currentPage = 1;
let studentsPerPage = 2;

const studentSearch = document.getElementById("studentSearch");
const studentList = document.getElementById("studentList");

const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
const pageNumber = document.getElementById("pageNumber");

fetch("../data/students.json")
    .then(response => response.json())
    .then(data => {
        allStudents = data;
        filteredStudents = allStudents;

        displayStudents();
    })
    .catch(error => {
        studentList.innerHTML = "<p>Error loading student data.</p>";
        console.error(error);
    });

function displayStudents() {

    studentList.innerHTML = "";

    let start = (currentPage - 1) * studentsPerPage;
    let end = start + studentsPerPage;

    let studentsToShow = filteredStudents.slice(start, end);

    if (studentsToShow.length === 0) {
        studentList.innerHTML = "<p>No students found.</p>";
    }

    studentsToShow.forEach(student => {

        let card = document.createElement("div");

        card.className = "card";

        card.innerHTML = `
            <h3>${student.name}</h3>
            <p><strong>Course:</strong> ${student.course}</p>
            <p><strong>Year:</strong> ${student.year}</p>
            <p><strong>Roll No:</strong> ${student.rollNo}</p>
            <p><strong>Email:</strong> ${student.email}</p>
        `;

        studentList.appendChild(card);
    });

    let totalPages = Math.ceil(
        filteredStudents.length / studentsPerPage
    );

    pageNumber.textContent = `Page ${currentPage} of ${totalPages}`;

    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages;
}

studentSearch.addEventListener("input", () => {

    let searchText = studentSearch.value.toLowerCase();

    filteredStudents = allStudents.filter(student =>
        student.name.toLowerCase().includes(searchText)
    );

    currentPage = 1;

    displayStudents();
});

nextBtn.addEventListener("click", () => {

    let totalPages = Math.ceil(
        filteredStudents.length / studentsPerPage
    );

    if (currentPage < totalPages) {
        currentPage++;
        displayStudents();
    }
});

prevBtn.addEventListener("click", () => {

    if (currentPage > 1) {
        currentPage--;
        displayStudents();
    }
});