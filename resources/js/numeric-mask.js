const limitFor = (input) => Number(input.dataset.numericMask);

const selectionFor = (input) => {
    try {
        return [
            input.selectionStart ?? input.value.length,
            input.selectionEnd ?? input.value.length,
        ];
    } catch {
        return [input.value.length, input.value.length];
    }
};

const candidateValue = (input, inserted) => {
    const [start, end] = selectionFor(input);

    return input.value.slice(0, start) + inserted + input.value.slice(end);
};

const exceedsLimit = (input, inserted) => {
    const limit = limitFor(input);

    if (! Number.isFinite(limit)) {
        return false;
    }

    const number = Number(candidateValue(input, inserted).trim());

    return Number.isFinite(number) && number > limit;
};

const blockIfExceeds = (input, inserted, event) => {
    if (exceedsLimit(input, inserted)) {
        event.preventDefault();
    }
};

document.addEventListener('beforeinput', (event) => {
    const input = event.target;

    if (! (input instanceof HTMLInputElement) || ! input.matches('[data-numeric-mask]')) {
        return;
    }

    if ((event.inputType ?? '').startsWith('delete')) {
        return;
    }

    blockIfExceeds(input, event.data ?? '', event);
});

document.addEventListener('paste', (event) => {
    const input = event.target;

    if (! (input instanceof HTMLInputElement) || ! input.matches('[data-numeric-mask]')) {
        return;
    }

    blockIfExceeds(input, event.clipboardData?.getData('text') ?? '', event);
});
