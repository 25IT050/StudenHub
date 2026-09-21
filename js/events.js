
let allEvents = [];
let filteredEvents = [];

let currentPage = 1;
let eventsPerPage = 6;

const searchField = document.getElementById("searchField");
const filterCategory = document.getElementById("filterCategory");
const sortBy = document.getElementById("sortBy");

const eventsGrid = document.getElementById("eventsGrid");
const loadingIndicator = document.getElementById("loadingIndicator");
const errorDisplay = document.getElementById("errorDisplay");

const btnPrevPage = document.getElementById("btnPrevPage");
const btnNextPage = document.getElementById("btnNextPage");
const pageTracker = document.getElementById("pageTracker");


// Load JSON data
fetch("../data/events.json")
    .then(response => {

        if (!response.ok) {
            throw new Error("JSON file not found");
        }

        return response.json();

    })
    .then(data => {

        allEvents = data;

        loadingIndicator.style.display = "none";

        filteredEvents = [...allEvents];

        applyFilters();

    })
    .catch(error => {

        loadingIndicator.style.display = "none";

        errorDisplay.style.display = "block";

        errorDisplay.textContent =
            "Error loading events: " + error.message;

        console.error(error);

    });


// Search and filter
function applyFilters() {

    let searchText = searchField.value.toLowerCase();

    let category = filterCategory.value;

    filteredEvents = allEvents.filter(event => {

        let title = event.title.toLowerCase();

        let venue = event.venue.toLowerCase();

        let description = event.description.toLowerCase();

        let matchesSearch =
            title.includes(searchText) ||
            venue.includes(searchText) ||
            description.includes(searchText);

        let matchesCategory =
            category === "all" ||
            event.category === category;

        return matchesSearch && matchesCategory;

    });

    sortEvents();

    currentPage = 1;

    displayEvents();

}


// Sort events
function sortEvents() {

    let sortValue = sortBy.value;

    if (sortValue === "titleAsc") {

        filteredEvents.sort((a, b) =>
            a.title.localeCompare(b.title)
        );

    }

    else if (sortValue === "dateAsc") {

        filteredEvents.sort((a, b) =>
            new Date(a.date) - new Date(b.date)
        );

    }

    else if (sortValue === "dateDesc") {

        filteredEvents.sort((a, b) =>
            new Date(b.date) - new Date(a.date)
        );

    }

}


// Display events
function displayEvents() {

    eventsGrid.innerHTML = "";

    let totalPages = Math.max(
        1,
        Math.ceil(filteredEvents.length / eventsPerPage)
    );

    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    let start = (currentPage - 1) * eventsPerPage;

    let pageEvents = filteredEvents.slice(
        start,
        start + eventsPerPage
    );

    if (pageEvents.length === 0) {

        eventsGrid.innerHTML =
            "<p class='status-msg'>No events found.</p>";

    }

    pageEvents.forEach(event => {

        let card = document.createElement("div");

        card.className = "event-card";

        card.innerHTML = `
            <span class="event-category">
                ${event.category}
            </span>

            <h3>${event.title}</h3>

            <p><strong>Date:</strong> ${event.date}</p>

            <p><strong>Venue:</strong> ${event.venue}</p>

            <p>${event.description}</p>
        `;

        eventsGrid.appendChild(card);

    });

    pageTracker.textContent =
        `Page ${currentPage} of ${totalPages}`;

    btnPrevPage.disabled = currentPage === 1;

    btnNextPage.disabled = currentPage === totalPages;

}


// Event listeners
searchField.addEventListener("input", applyFilters);

filterCategory.addEventListener("change", applyFilters);

sortBy.addEventListener("change", applyFilters);


// Pagination
btnPrevPage.addEventListener("click", () => {

    if (currentPage > 1) {

        currentPage--;

        displayEvents();

    }

});


btnNextPage.addEventListener("click", () => {

    let totalPages = Math.ceil(
        filteredEvents.length / eventsPerPage
    );

    if (currentPage < totalPages) {

        currentPage++;

        displayEvents();

    }

});