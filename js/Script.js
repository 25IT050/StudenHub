document.addEventListener("DOMContentLoaded", function () {

    // ========================================
    // DARK / LIGHT MODE
    // ========================================

    const themeBtn = document.getElementById("themeBtn");

    const savedTheme = localStorage.getItem("theme");

    if (savedTheme === "dark") {
        document.body.classList.add("dark");
        themeBtn.textContent = "☀️ Light Mode";
    } else {
        document.body.classList.remove("dark");
        themeBtn.textContent = "🌙 Dark Mode";
    }


    themeBtn.addEventListener("click", function () {

        document.body.classList.toggle("dark");

        if (document.body.classList.contains("dark")) {

            themeBtn.textContent = "☀️ Light Mode";
            localStorage.setItem("theme", "dark");

        } else {

            themeBtn.textContent = "🌙 Dark Mode";
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


   // ========================================
// HAMBURGER MENU
// ========================================

const menuBtn = document.getElementById("menuBtn");
const navMenu = document.getElementById("navMenu");

if (menuBtn && navMenu) {

    menuBtn.onclick = function () {

        if (navMenu.style.display === "block") {
            navMenu.style.display = "none";
        } else {
            navMenu.style.display = "block";
        }

    };

}

    // ========================================
    // FAQ
    // ========================================

    const questions =
        document.querySelectorAll(".faq-question");

    questions.forEach(function (question) {

        question.addEventListener("click", function () {

            const answer = question.nextElementSibling;

            answer.classList.toggle("show");

        });

    });


    // ========================================
    // CONTENT SLIDER
    // ========================================

    const slides =
        document.querySelectorAll(".slide");

    const nextBtn =
        document.getElementById("nextBtn");

    const prevBtn =
        document.getElementById("prevBtn");

    let currentSlide = 0;


    function showSlide(index) {

        slides.forEach(function (slide) {

            slide.classList.remove("active");

        });

        slides[index].classList.add("active");

    }


    if (slides.length > 0) {

        showSlide(currentSlide);


        // NEXT BUTTON
        nextBtn.addEventListener("click", function () {

            currentSlide++;

            if (currentSlide >= slides.length) {
                currentSlide = 0;
            }

            showSlide(currentSlide);

        });


        // PREVIOUS BUTTON
        prevBtn.addEventListener("click", function () {

            currentSlide--;

            if (currentSlide < 0) {
                currentSlide = slides.length - 1;
            }

            showSlide(currentSlide);

        });

    }

});