(function () {
    'use strict';

    var root = document.getElementById('react-login-root');
    if (!root || !window.React || !window.ReactDOM) return;

    var apiUrl = root.getAttribute('data-api-url');
    var fallbacks = document.querySelectorAll('[data-login-fallback="true"]');
    var serverError = document.querySelector('[data-server-error]');
    var e = React.createElement;

    function LoginApp() {
        var state = React.useState({ email: '', password: '', loading: false, message: '', type: '', reveal: false });
        var form = state[0];
        var setForm = state[1];

        React.useEffect(function () {
            fallbacks.forEach(function (fallback) { fallback.hidden = true; });
        }, []);

        function update(field, value) {
            var next = {};
            Object.keys(form).forEach(function (key) { next[key] = form[key]; });
            next[field] = value;
            next.message = '';
            next.type = '';
            setForm(next);
            if (serverError) serverError.hidden = true;
        }

        function fillDemo(email, password) {
            setForm({ email: email, password: password, loading: false, message: 'Akun demo siap dipakai.', type: 'success' });
            if (serverError) serverError.hidden = true;
        }

        function submit(event) {
            event.preventDefault();
            if (form.loading) return;

            if (!form.email || !form.password) {
                setForm({
                    email: form.email,
                    password: form.password,
                    loading: false,
                    message: 'Email dan password wajib diisi.',
                    type: 'error',
                    reveal: form.reveal
                });
                return;
            }

            setForm({
                email: form.email,
                password: form.password,
                loading: true,
                message: 'Memeriksa akun...',
                type: 'success',
                reveal: form.reveal
            });

            fetch(apiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ email: form.email, password: form.password })
            })
                .then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (payload) {
                        if (!response.ok || !payload.ok) throw payload;
                        return payload;
                    });
                })
                .then(function (payload) {
                    setForm({
                        email: form.email,
                        password: '',
                        loading: true,
                        message: payload.message || 'Login berhasil.',
                        type: 'success',
                        reveal: false
                    });
                    window.location.href = payload.redirect || root.getAttribute('data-dashboard-url');
                })
                .catch(function (payload) {
                    setForm({
                        email: form.email,
                        password: '',
                        loading: false,
                        message: payload && payload.message ? payload.message : 'Login gagal. Coba lagi.',
                        type: 'error',
                        reveal: false
                    });
                });
        }

        return e('form', { className: 'auth-form react-auth-form', onSubmit: submit },
            form.message ? e('div', { className: 'inline-alert ' + (form.type === 'success' ? 'success' : 'error'), role: 'status' }, form.message) : null,
            e('label', null,
                'Email',
                e('input', {
                    type: 'email',
                    name: 'email',
                    placeholder: 'nama@smartschool.test',
                    required: true,
                    value: form.email,
                    onChange: function (event) { update('email', event.target.value); }
                })
            ),
            e('label', null,
                'Password',
                e('span', { className: 'input-group' },
                    e('input', {
                        type: form.reveal ? 'text' : 'password',
                        name: 'password',
                        placeholder: 'Minimal 6 karakter',
                        required: true,
                        value: form.password,
                        onChange: function (event) { update('password', event.target.value); }
                    }),
                    e('button', {
                        type: 'button',
                        onClick: function () {
                            setForm({
                                email: form.email,
                                password: form.password,
                                loading: form.loading,
                                message: form.message,
                                type: form.type,
                                reveal: !form.reveal
                            });
                        }
                    }, form.reveal ? 'Hide' : 'Show')
                )
            ),
            e('button', { className: 'btn btn-orange auth-submit', type: 'submit', disabled: form.loading },
                form.loading ? 'Sedang masuk...' : 'Masuk ke Portal'
            ),
            e('div', { className: 'demo-accounts react-demo-accounts' },
                e('strong', null, 'Akun demo'),
                e('button', { type: 'button', onClick: function () { fillDemo('siswa@smartschool.test', 'siswa123'); } }, 'Siswa'),
                e('button', { type: 'button', onClick: function () { fillDemo('guru@smartschool.test', 'guru123'); } }, 'Guru'),
                e('button', { type: 'button', onClick: function () { fillDemo('ortu@smartschool.test', 'ortu123'); } }, 'Orang Tua')
            )
        );
    }

    ReactDOM.createRoot(root).render(e(LoginApp));
})();
