// 1. Confirm destructive actions before submitting their forms.
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

// 2. Add and remove ingredient inputs.
const ingredientList = document.getElementById('ingredients');
const addButton = document.getElementById('add-ingredient');

function numberIngredients() {
    const rows = ingredientList.querySelectorAll('.ingredient-row');
    rows.forEach(function (row, index) {
        row.querySelector('label').firstChild.textContent = 'Ingredient ' + (index + 1);
        const remove = row.querySelector('.remove-ingredient');
        remove.disabled = rows.length === 1;
        remove.setAttribute('aria-label', 'Remove ingredient ' + (index + 1));
    });
    addButton.disabled = rows.length >= 40;
}

if (ingredientList) {
    numberIngredients();
    addButton.addEventListener('click', function () {
        if (ingredientList.children.length >= 40) return;
        const row = ingredientList.firstElementChild.cloneNode(true);
        row.querySelector('input').value = '';
        row.querySelector('input').setCustomValidity('');
        ingredientList.appendChild(row);
        numberIngredients();
        row.querySelector('input').focus();
    });
    ingredientList.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-ingredient');
        if (button && ingredientList.children.length > 1) {
            const row = button.closest('.ingredient-row');
            const next = row.nextElementSibling || row.previousElementSibling;
            row.remove();
            numberIngredients();
            next.querySelector('input').focus();
        }
    });
}

// 3. Save/remove favorites with Fetch, without a page reload.
const feedback = document.getElementById('feedback');
let feedbackTimer;
function showFeedback(message) {
    clearTimeout(feedbackTimer);
    feedback.textContent = message;
    feedback.hidden = false;
    feedbackTimer = setTimeout(function () { feedback.hidden = true; }, 5000);
}

document.querySelectorAll('.favorite-button').forEach(function (button) {
    button.addEventListener('click', async function () {
        if (button.disabled) return;
        button.disabled = true;
        const saved = button.dataset.saved !== '1';
        const data = new URLSearchParams({
            recipe_id: button.dataset.id,
            saved: saved ? '1' : '0',
            csrf: document.querySelector('meta[name="csrf-token"]').content
        });
        try {
            const response = await fetch('favorite.php', { method: 'POST', body: data });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Could not save your favorite.');
            button.dataset.saved = result.saved ? '1' : '0';
            button.textContent = result.saved ? 'Saved' : 'Save';
            button.setAttribute('aria-pressed', String(result.saved));
            button.setAttribute('aria-label', (result.saved ? 'Unsave ' : 'Save ') + button.dataset.title);

            const grid = document.querySelector('[data-view="favorites"]');
            if (grid && !result.saved) {
                const card = button.closest('.recipe-card');
                const next = card.nextElementSibling || card.previousElementSibling;
                card.remove();
                document.getElementById('recipe-count').textContent = grid.children.length;
                document.getElementById('recipe-word').textContent = grid.children.length === 1 ? 'recipe' : 'recipes';
                if (next) {
                    next.querySelector('button').focus();
                } else {
                    document.getElementById('empty-state').hidden = false;
                    document.querySelector('#empty-state a').focus();
                }
            }
            showFeedback(result.saved ? 'Recipe saved.' : 'Recipe removed from favorites.');
        } catch (error) {
            showFeedback(error.message);
        } finally {
            button.disabled = false;
        }
    });
});

// 4. Check trimmed text lengths and password confirmation in the browser.
// PHP repeats the checks so disabling JavaScript cannot bypass validation.
function checkText(input) {
    const length = Array.from(input.value.trim()).length;
    const min = Number(input.getAttribute('minlength'));
    const max = Number(input.getAttribute('maxlength'));
    input.setCustomValidity(length < min || length > max
        ? 'Enter between ' + min + ' and ' + max + ' characters.' : '');
}

document.addEventListener('input', function (event) {
    if (event.target.matches('input[minlength]:not([type="password"]), textarea[minlength]')) {
        checkText(event.target);
    }
});

document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        form.querySelectorAll('input[minlength]:not([type="password"]), textarea[minlength]').forEach(checkText);
        if (!form.reportValidity()) event.preventDefault();
    });
});

const registerForm = document.getElementById('register-form');
if (registerForm) {
    function checkPasswords() {
        const password = registerForm.elements.password;
        const confirmPassword = registerForm.elements.confirm;
        const bytes = new TextEncoder().encode(password.value).length;
        const length = Array.from(password.value).length;
        password.setCustomValidity(length < 8 || bytes > 72
            ? 'Use at least 8 characters and no more than 72 bytes.' : '');
        confirmPassword.setCustomValidity(password.value !== confirmPassword.value
            ? 'Passwords do not match.' : '');
    }
    registerForm.addEventListener('input', checkPasswords);
    registerForm.addEventListener('submit', function (event) {
        checkPasswords();
        if (!registerForm.reportValidity()) event.preventDefault();
    });
}
