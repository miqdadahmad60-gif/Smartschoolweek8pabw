(function () {
    'use strict';

    var toast = document.querySelector('[data-toast]');
    var toastTimer;
    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2600);
    }

    document.querySelectorAll('[data-toast-message]').forEach(function (element) {
        element.addEventListener('click', function () {
            showToast(element.getAttribute('data-toast-message'));
        });
    });

    var mobileToggle = document.querySelector('[data-mobile-toggle]');
    var mobileMenu = document.querySelector('[data-mobile-menu]');
    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', function () {
            mobileMenu.classList.toggle('open');
        });
    }

    var sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    var sidebar = document.querySelector('[data-sidebar]');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    var password = document.querySelector('[data-password]');
    var passwordToggle = document.querySelector('[data-password-toggle]');
    if (password && passwordToggle) {
        passwordToggle.addEventListener('click', function () {
            var reveal = password.type === 'password';
            password.type = reveal ? 'text' : 'password';
            passwordToggle.textContent = reveal ? 'Hide' : 'Show';
        });
    }

    var roleSelect = document.querySelector('[data-role-select]');
    if (roleSelect) {
        function updateRoleFields() {
            document.querySelectorAll('[data-role-field]').forEach(function (field) {
                field.hidden = field.getAttribute('data-role-field') !== roleSelect.value;
            });
        }
        roleSelect.addEventListener('change', updateRoleFields);
        updateRoleFields();
    }

    document.querySelectorAll('[data-demo-email]').forEach(function (button) {
        button.addEventListener('click', function () {
            var email = document.querySelector('input[name="email"]');
            var pass = document.querySelector('input[name="password"]');
            if (email && pass) {
                email.value = button.getAttribute('data-demo-email');
                pass.value = button.getAttribute('data-demo-password');
                showToast('Akun demo ' + button.textContent.trim() + ' siap digunakan');
            }
        });
    });

    document.querySelectorAll('[data-facility-select]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.querySelector('[data-facility-input]');
            if (input) input.value = button.getAttribute('data-facility-select');
            var form = document.getElementById('borrow-form');
            if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    var globalSearch = document.querySelector('[data-global-search]');
    if (globalSearch) {
        globalSearch.addEventListener('input', function () {
            var term = globalSearch.value.toLowerCase().trim();
            document.querySelectorAll('[data-search-item]').forEach(function (item) {
                item.classList.toggle('hidden-item', term !== '' && item.textContent.toLowerCase().indexOf(term) === -1);
            });
        });
    }

    var filterButton = document.querySelector('[data-material-filter]');
    if (filterButton) {
        var newestOnly = false;
        filterButton.addEventListener('click', function () {
            newestOnly = !newestOnly;
            document.querySelectorAll('.subject-card').forEach(function (card, index) {
                card.classList.toggle('hidden-item', newestOnly && index > 1);
            });
            filterButton.textContent = newestOnly ? '× Reset Filter' : '☷ Filter';
            showToast(newestOnly ? 'Menampilkan materi terbaru' : 'Filter direset');
        });
    }

    var quiz = document.querySelector('[data-quiz]');
    if (quiz) {
        quiz.querySelectorAll('[data-choice]').forEach(function (choice) {
            choice.addEventListener('click', function () {
                var question = choice.closest('[data-answer]');
                question.querySelectorAll('[data-choice]').forEach(function (button) {
                    button.classList.remove('selected', 'correct', 'wrong');
                });
                choice.classList.add('selected');
                question.setAttribute('data-selected', choice.getAttribute('data-choice'));
            });
        });

        var submit = quiz.querySelector('[data-quiz-submit]');
        submit.addEventListener('click', function () {
            var questions = quiz.querySelectorAll('[data-answer]');
            var answered = 0;
            var score = 0;
            questions.forEach(function (question) {
                var selected = question.getAttribute('data-selected');
                var answer = question.getAttribute('data-answer');
                if (selected) answered++;
                question.querySelectorAll('[data-choice]').forEach(function (button) {
                    var value = button.getAttribute('data-choice');
                    button.classList.remove('correct', 'wrong');
                    if (value === answer) button.classList.add('correct');
                    if (value === selected && selected !== answer) button.classList.add('wrong');
                });
                if (selected === answer) score++;
            });

            var result = quiz.querySelector('[data-quiz-result]');
            var scorePill = document.querySelector('[data-score]');
            if (answered < questions.length) {
                result.textContent = 'Masih ada soal yang belum dijawab.';
                showToast('Lengkapi semua jawaban terlebih dahulu');
                return;
            }
            result.textContent = 'Kuis selesai. Kamu menjawab ' + score + ' dari ' + questions.length + ' soal dengan benar.';
            scorePill.textContent = 'Skor: ' + score + ' / ' + questions.length;
            showToast(score === questions.length ? 'Hebat! Semua jawaban benar.' : 'Jawaban sudah diperiksa.');
        });
    }
})();
