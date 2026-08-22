(() => {
    if (!document.body || !window.location.pathname.startsWith('/admin/pool')) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrf) return;

    const roleLabel = document.querySelector('.admin-user small')?.textContent?.trim() || '';
    const isAdmin = roleLabel === 'Администратор';

    const zoneDeleteForms = [...document.querySelectorAll('form[action*="/admin/pool/zones"]')]
        .filter(form => form.querySelector('input[name="action"][value="delete"]'));

    zoneDeleteForms.forEach(form => {
        if (!isAdmin) {
            form.remove();
            return;
        }

        const wrapper = form.closest('.border-top');
        const hint = wrapper?.querySelector('small.text-muted');
        if (hint) {
            hint.textContent = 'Safe delete: бассейн и его дорожки уйдут в архив, а все связанные данные и история останутся в базе.';
        }

        form.removeAttribute('onsubmit');
        form.addEventListener('submit', event => {
            const name = form.closest('details')?.parentElement?.querySelector('h3')?.textContent?.trim() || 'бассейн';
            if (!window.confirm(
                `Безопасно удалить «${name}»?\n\n` +
                'Бассейн исчезнет из рабочих списков. Его дорожки также будут архивированы. ' +
                'Сеансы, замеры воды, СКУД, техобслуживание, инциденты и другая история сохранятся. ' +
                'Объект можно будет восстановить из архива.'
            )) {
                event.preventDefault();
            }
        });

        const button = form.querySelector('button');
        if (button) {
            button.title = 'Безопасно удалить в архив';
            button.innerHTML = '<i class="bi bi-archive me-1"></i>Удалить безопасно';
        }
    });

    if (isAdmin && window.location.pathname === '/admin/pool') {
        const tabs = document.querySelector('.crm-tabs');
        if (tabs && !document.getElementById('poolArchiveLink')) {
            const bar = document.createElement('div');
            bar.className = 'd-flex justify-content-end mb-3';
            bar.innerHTML = '<a id="poolArchiveLink" class="btn btn-outline-secondary btn-sm" href="/admin/pool/archive"><i class="bi bi-archive me-1"></i>Архив бассейнов и дорожек</a>';
            tabs.parentNode.insertBefore(bar, tabs);
        }
    }

    const laneUpdateForms = [...document.querySelectorAll('form[action*="/admin/pool/lanes/"]')]
        .filter(form => /\/admin\/pool\/lanes\/\d+$/.test(new URL(form.action, window.location.origin).pathname));

    laneUpdateForms.forEach(form => {
        if (!isAdmin || form.dataset.deleteControlAdded === '1') return;

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
        button.title = 'Безопасно удалить дорожку в архив';
        button.setAttribute('aria-label', `Безопасно удалить ${laneName}`);
        button.innerHTML = '<i class="bi bi-archive"></i>';

        button.addEventListener('click', () => {
            const ok = window.confirm(
                `Безопасно удалить «${laneName}»?\n\n` +
                'Дорожка исчезнет из рабочих списков, но её назначения на сеансы, техобслуживание, ' +
                'эксплуатационные операции, инциденты и история школы плавания сохранятся. ' +
                'Дорожку можно будет восстановить из архива.'
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
