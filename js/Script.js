document.addEventListener("DOMContentLoaded", function () {

    // ========================================
    // DARK / LIGHT MODE
    // ========================================

    const themeBtn = document.getElementById("themeBtn");

    // Page load hone par saved theme check karo
    const savedTheme = localStorage.getItem("theme");

    if (savedTheme === "dark") {
        document.body.classList.add("dark");
        themeBtn.textContent = "☀️ Light Mode";
    } else {
        document.body.classList.remove("dark");
        themeBtn.textContent = "🌙 Dark Mode";
    }


    // Dark / Light button
    themeBtn.addEventListener("click", function () {

        document.body.classList.toggle("dark");

        // Dark mode
        if (document.body.classList.contains("dark")) {

            themeBtn.textContent = "☀️ Light Mode";

            // Browser me theme save karo
            localStorage.setItem("theme", "dark");

        }

        // Light mode
        else {

            themeBtn.textContent = "🌙 Dark Mode";

            // Browser me theme save karo
            localStorage.setItem("theme", "light");

        }

    });


    // ========================================
    // NOTIFICATION CLOSE
    // ========================================

    const closeBtn = document.getElementById("closeBtn");
    const notification = document.getElementById("notification");

    if (closeBtn && notification) {

        closeBtn.addEventListener("click", function () {

            notification.style.display = "none";

        });

    }

});