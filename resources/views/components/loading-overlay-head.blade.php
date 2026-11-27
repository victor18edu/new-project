{{-- Reidrata o overlay antes da primeira pintura quando a navegação anterior ainda está pendente. --}}
<script>
    (() => {
        const key = 'app:loading-navigation';

        try {
            const startedAt = Number(sessionStorage.getItem(key) || 0);

            if (startedAt && Date.now() - startedAt < 15000) {
                document.documentElement.classList.add('app-is-loading');
            } else {
                sessionStorage.removeItem(key);
            }
        } catch (error) {
            // sessionStorage pode estar indisponível; o overlay segue os eventos da página normalmente.
        }
    })();
</script>
