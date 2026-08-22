(() => {
    if (!document.body || !window.location.pathname.startsWith('/admin/pool')) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrf) return;

    const laneUpdateForms = [...document.querySelectorAll('form[action*="/admin/pool/lanes/"]')]
        .filter(form => /\/admin\/pool\/lanes\/\d+$/.test(new URL(form.action, window.location.origin).pathname));

    laneUpdateForms.forEach(form => {
        if (form.dataset.deleteControlAdded === '1') return;

        const path = new URL(form.action, window.location.origin).pathname;
        const match = path.match(/\/admin\/pool\/lanes\/(\d+)$/);
        if (!match) return;

        const laneId = match[1];
        const row = form.closest('tr');
        const laneName = row?.querySelector('td strong')?.textContent?.trim() || `дорожку №${laneId}`;
        const actionCell = form.closest('td');
        if (!actionCell) return;

        form.dataset.deleteControlAdded = '1';
        form.classList.add('d-inline-flex');

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-danger ms-1';
        button.title = 'Удалить пустую дорожку';
        button.setAttribute('aria-label', `Удалить ${laneName}`);
        button.innerHTML = '<i class="bi bi-trash"></i>';

        button.addEventListener('click', () => {
            const ok = window.confirm(
                `Удалить «${laneName}»?\n\n` +
                'Удаление разрешено только для дорожки без сеансов и истории. ' +
                'Если дорожка уже использовалась, система предложит отключить её вместо удаления.'
            );
            if (!ok) return;

            const deleteForm = document.createElement('form');
            deleteForm.method = 'post';
            deleteForm.action = '/admin/pool/lanes';
            deleteForm.style.display = 'none';

            const fields = {
                _token: csrf,
                action: 'delete',
                lane_id: laneId,
            };

            Object.entries(fields).forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                deleteForm.appendChild(input);
            });

            document.body.appendChild(deleteForm);
            deleteForm.submit();
        });

        actionCell.appendChild(button);
    });
})();
