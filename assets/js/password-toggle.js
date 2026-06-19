(function () {
    'use strict';

    function initPasswordToggles() {
        var passwordInputs = document.querySelectorAll('input[type="password"]');

        passwordInputs.forEach(function (input, index) {
            if (input.dataset.passwordToggleApplied === '1') {
                return;
            }

            var wrapper = document.createElement('div');
            wrapper.className = 'password-toggle-wrap';

            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'password-toggle-btn';
            button.setAttribute('aria-label', 'Show password');
            button.setAttribute('aria-pressed', 'false');

            var icon = document.createElement('span');
            icon.className = 'eye-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML =
                '<svg class="icon-show" viewBox="0 0 24 24" focusable="false" aria-hidden="true">' +
                '<ellipse cx="12" cy="12" rx="9" ry="6"></ellipse>' +
                '<circle cx="12" cy="12" r="2.2"></circle>' +
                '</svg>' +
                '<svg class="icon-hide" viewBox="0 0 24 24" focusable="false" aria-hidden="true">' +
                '<ellipse cx="12" cy="12" rx="9" ry="6"></ellipse>' +
                '<circle cx="12" cy="12" r="2.2"></circle>' +
                '<path d="M4 20L20 4"></path>' +
                '</svg>';
            button.appendChild(icon);

            var buttonId = 'toggle-password-' + index;
            button.id = buttonId;
            input.setAttribute('aria-describedby', buttonId);

            button.addEventListener('click', function () {
                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';

                if (isPassword) {
                    button.classList.add('is-hidden');
                    button.setAttribute('aria-label', 'Hide password');
                    button.setAttribute('aria-pressed', 'true');
                } else {
                    button.classList.remove('is-hidden');
                    button.setAttribute('aria-label', 'Show password');
                    button.setAttribute('aria-pressed', 'false');
                }
            });

            wrapper.appendChild(button);
            input.dataset.passwordToggleApplied = '1';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggles);
    } else {
        initPasswordToggles();
    }
})();
