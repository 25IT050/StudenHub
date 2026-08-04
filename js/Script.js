const themeBtn = document.getElementById("themeBtn");

if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-mode");
    themeBtn.textContent = "☀️ Light Mode";
}

themeBtn.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode");

    if (document.body.classList.contains("dark-mode")) {
        localStorage.setItem("theme", "dark");
        themeBtn.textContent = "☀️ Light Mode";
    } else {
        localStorage.setItem("theme", "light");
        themeBtn.textContent = "🌙 Dark Mode";
    }
});
const closeBtn = document.getElementById("closeBtn");
const notification = document.getElementById("notification");

closeBtn.addEventListener("click", function () {
    notification.style.display = "none";
});