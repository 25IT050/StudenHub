document.addEventListener("DOMContentLoaded", function () {

    // ========================================
    // DARK / LIGHT MODE (Safe Version)
    // ========================================
    const themeBtn = document.getElementById("themeBtn");
    if (themeBtn) {
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
    }

    // ========================================
    // NOTIFICATION CLOSE (Safe Version)
    // ========================================
    const closeBtn = document.getElementById("closeBtn");
    const notification = document.getElementById("notification");
    if (closeBtn && notification) {
        closeBtn.addEventListener("click", function () {
            notification.style.display = "none";
        });
    }

    // ========================================
    // HAMBURGER MENU (Safe Version)
    // ========================================
    const menuBtn = document.getElementById("menuBtn");
    const navMenu = document.getElementById("navMenu");
    if (menuBtn && navMenu) {
        menuBtn.onclick = function () {
            if (navMenu.style.display === "block" || navMenu.style.style === "block") {
                navMenu.style.display = "none";
            } else {
                navMenu.style.display = "block";
            }
        };
    }

    // ========================================
    // FAQ COLLAPSIBLE MECHANISM (Safe Version)
    // ========================================
    const questions = document.querySelectorAll(".faq-question");
    if (questions.length > 0) {
        questions.forEach(function (question) {
            question.addEventListener("click", function () {
                const answer = question.nextElementSibling;
                if (answer) {
                    answer.classList.toggle("show");
                }
            });
        });
    }

    // ========================================
    // CONTENT SLIDER SYSTEM (Safe Version)
    // ========================================
    const slides = document.querySelectorAll(".slide");
    const nextBtn = document.getElementById("nextBtn");
    const prevBtn = document.getElementById("prevBtn");
    let currentSlide = 0;

    function showSlide(index) {
        if (slides.length === 0 || !slides[index]) return;
        slides.forEach(function (slide) {
            slide.classList.remove("active");
        });
        slides[index].classList.add("active");
    }

    if (slides.length > 0 && nextBtn && prevBtn) {
        showSlide(currentSlide);
        nextBtn.addEventListener("click", function () {
            currentSlide++;
            if (currentSlide >= slides.length) {
                currentSlide = 0;
            }
            showSlide(currentSlide);
        });
        prevBtn.addEventListener("click", function () {
            currentSlide--;
            if (currentSlide < 0) {
                currentSlide = slides.length - 1;
            }
            showSlide(currentSlide);
        });
    }

    // ========================================
    // PRACTICAL 5: REGISTRATION FORM VALIDATION Engine
    // ========================================
    const regForm = document.getElementById("studentRegisterForm");
    if (regForm) {
        const regexPatterns = {
            fullName: /^[a-zA-Z\s]{3,50}$/,
            email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
            mobile: /^[6-9]\d{9}$/
        };

        const fieldValidators = {
            fullName: (val) => regexPatterns.fullName.test(val) ? "" : "Name must be letters only & min 3 chars.",
            email: (val) => regexPatterns.email.test(val) ? "" : "Please enter a valid email address.",
            mobile: (val) => regexPatterns.mobile.test(val) ? "" : "Please enter a valid 10-digit mobile number.",
            password: (val) => {
                if (val.length < 8) return "Password must be at least 8 characters.";
                if (!/[A-Z]/.test(val)) return "Include at least one uppercase letter.";
                if (!/[0-9]/.test(val)) return "Include at least one number.";
                if (!/[@$!%*?&]/.test(val)) return "Include a special character (@$!%*?&).";
                return "";
            },
            confirmPassword: (val) => {
                const primaryPass = document.getElementById("password").value;
                return val === primaryPass ? "" : "Passwords do not match.";
            },
            course: (val) => val !== "" ? "" : "Please select a course.",
            year: (val) => val !== "" ? "" : "Please select a study year.",
            gender: () => document.querySelector('input[name="gender"]:checked') ? "" : "Please select your gender.",
            terms: () => document.getElementById("terms").checked ? "" : "You must accept the terms guidelines."
        };

        function checkFieldValidity(id) {
            const entryTarget = document.getElementById(id);
            const alertMount = document.getElementById(`${id}Error`);
            if (!entryTarget || !alertMount) return true;

            const targetVal = entryTarget.value.trim();
            const failureString = fieldValidators[id](targetVal);

            if (failureString) {
                alertMount.textContent = failureString;
                entryTarget.setAttribute("aria-invalid", "true");
                return false;
            } else {
                alertMount.textContent = "";
                entryTarget.removeAttribute("aria-invalid");
                return true;
            }
        }

        function checkGroupValidity(name, targetErrorId) {
            const errorElement = document.getElementById(targetErrorId);
            if (!errorElement) return true;
            const systemFailureMsg = fieldValidators[name]();
            if (systemFailureMsg) {
                errorElement.textContent = systemFailureMsg;
                return false;
            } else {
                errorElement.textContent = "";
                return true;
            }
        }

        ['fullName', 'email', 'mobile', 'confirmPassword', 'course', 'year'].forEach(fieldId => {
            const inputNode = document.getElementById(fieldId);
            if (inputNode) {
                inputNode.addEventListener("input", () => checkFieldValidity(fieldId));
                inputNode.addEventListener("blur", () => checkFieldValidity(fieldId));
            }
        });

        const termsCheckbox = document.getElementById("terms");
        if(termsCheckbox) termsCheckbox.addEventListener("change", () => checkGroupValidity("terms", "termsError"));
        
        document.querySelectorAll('input[name="gender"]').forEach(radioNode => {
            radioNode.addEventListener("change", () => checkGroupValidity("gender", "genderError"));
        });

        const passNode = document.getElementById("password");
        if (passNode) {
            passNode.addEventListener("input", function() {
                checkFieldValidity("password");
                const confirmNode = document.getElementById("confirmPassword");
                if (confirmNode && confirmNode.value) checkFieldValidity("confirmPassword");
                
                const value = this.value;
                const progressMeter = document.getElementById("strengthBar");
                const descriptionLabel = document.getElementById("strengthText");
                if (!progressMeter || !descriptionLabel) return;

                let gradingWeight = 0;
                if (value.length >= 8) gradingWeight++;
                if (/[A-Z]/.test(value)) gradingWeight++;
                if (/[0-9]/.test(value)) gradingWeight++;
                if (/[@$!%*?&]/.test(value)) gradingWeight++;

                if (value === "") {
                    progressMeter.style.width = "0%";
                    descriptionLabel.textContent = "";
                } else if (gradingWeight <= 2) {
                    progressMeter.style.width = "30%";
                    progressMeter.style.backgroundColor = "#dc2626";
                    descriptionLabel.textContent = "Weak ❌";
                } else if (gradingWeight === 3) {
                    progressMeter.style.width = "65%";
                    progressMeter.style.backgroundColor = "#f59e0b";
                    descriptionLabel.textContent = "Medium ⚠️";
                } else {
                    progressMeter.style.width = "100%";
                    progressMeter.style.backgroundColor = "#16a34a";
                    descriptionLabel.textContent = "Strong ✅";
                }
            });
        }

        regForm.addEventListener("submit", function (event) {
            event.preventDefault();
            let isFormClean = true;

            const textValidationSequence = ['fullName', 'email', 'mobile', 'password', 'confirmPassword', 'course', 'year'];
            textValidationSequence.forEach(id => {
                if (!checkFieldValidity(id)) isFormClean = false;
            });

            if (!checkGroupValidity("gender", "genderError")) isFormClean = false;
            if (!checkGroupValidity("terms", "termsError")) isFormClean = false;
        }
    }
}
