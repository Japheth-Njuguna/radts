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
            button.textContent = 'Show';

            var buttonId = 'toggle-password-' + index;
            button.id = buttonId;
            input.setAttribute('aria-describedby', buttonId);

            button.addEventListener('click', function () {
                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                button.textContent = isPassword ? 'Hide' : 'Show';
                button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
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
