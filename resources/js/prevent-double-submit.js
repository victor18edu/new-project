const SUBMIT_SELECTOR = 'button[type="submit"], input[type="submit"], button:not([type])';

function submitButtons(form) {
    return Array.from(form.elements).filter((el) => el.matches(SUBMIT_SELECTOR));
}

function isLivewireForm(form) {
    return form.hasAttribute('wire:submit') || form.hasAttribute('wire:submit.prevent');
}

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) return;
    if (event.defaultPrevented) return;
    if (isLivewireForm(form)) return;
    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }

    const buttons = submitButtons(form).filter((el) => !el.disabled);
    if (buttons.length === 0) return;

    form.dataset.submitting = 'true';
    buttons.forEach((el) => {
        el.disabled = true;
        el.dataset.doubleSubmitDisabled = 'true';
        el.classList.add('opacity-50', 'pointer-events-none');
    });
}, true);

window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;

    document.querySelectorAll('form[data-submitting="true"]').forEach((form) => {
        delete form.dataset.submitting;

        submitButtons(form).forEach((el) => {
            if (el.dataset.doubleSubmitDisabled !== 'true') return;
            el.disabled = false;
            delete el.dataset.doubleSubmitDisabled;
            el.classList.remove('opacity-50', 'pointer-events-none');
        });
    });
});
