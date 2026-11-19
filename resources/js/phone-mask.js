const formatPhone = (digits) => {
    const values = digits.slice(0, 11);

    if (values.length === 0) {
        return '';
    }

    if (values.length <= 2) {
        return `(${values}`;
    }

    const area = values.slice(0, 2);
    const number = values.slice(2);

    if (number.length <= 4) {
        return `(${area}) ${number}`;
    }

    if (values.length >= 11) {
        return `(${area}) ${number.slice(0, 5)}-${number.slice(5)}`;
    }

    return `(${area}) ${number.slice(0, 4)}-${number.slice(4)}`;
};

document.addEventListener('input', (event) => {
    const input = event.target;

    if (! (input instanceof HTMLInputElement) || ! input.matches('[data-phone-mask]')) {
        return;
    }

    const digits = input.value.replace(/\D/g, '').slice(0, 11);
    const formatted = formatPhone(digits);
    const cursorAtEnd = input.selectionStart === input.value.length;

    input.value = formatted;

    if (cursorAtEnd) {
        const position = formatted.length;
        input.setSelectionRange(position, position);
    }
});
