(() => {
    const form = document.getElementById('settings-form');
    const saveButton = document.getElementById('save-settings-button');
    const addSourceButton = document.getElementById('add-source-button');
    const sourceList = document.getElementById('source-list');
    const message = document.getElementById('settings-message');
    const toast = document.getElementById('toast');
    let toastTimer = null;

    function showMessage(text, isError) {
        message.textContent = text;
        message.classList.toggle('message-error', isError);
        message.classList.toggle('message-success', !isError);
        toast.textContent = text;
        toast.classList.toggle('toast-error', isError);
        toast.classList.add('toast-visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.remove('toast-visible'), 4200);
    }

    function createSourceId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return `source-${window.crypto.randomUUID().replace(/-/g, '')}`;
        }
        return `source-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
    }

    function updateSourceTitles() {
        sourceList.querySelectorAll('[data-source-row]').forEach((row) => {
            const name = row.querySelector('[data-field="name"]').value.trim();
            row.querySelector('[data-source-title]').textContent = name || 'แหล่งข้อมูลใหม่';
        });
        addSourceButton.disabled = sourceList.querySelectorAll('[data-source-row]').length >= 20;
    }

    function createSourceRow(source = {}) {
        const row = document.createElement('article');
        row.className = 'source-config-card';
        row.dataset.sourceRow = '';
        const hiddenId = document.createElement('input');
        hiddenId.type = 'hidden';
        hiddenId.dataset.field = 'id';
        hiddenId.value = source.id || createSourceId();

        const heading = document.createElement('div');
        heading.className = 'source-config-heading';
        const title = document.createElement('strong');
        title.dataset.sourceTitle = '';
        title.textContent = source.name || 'แหล่งข้อมูลใหม่';
        const removeButton = document.createElement('button');
        removeButton.className = 'button button-ghost remove-source-button';
        removeButton.type = 'button';
        removeButton.textContent = 'ลบแหล่งนี้';
        removeButton.setAttribute('aria-label', 'ลบแหล่งข้อมูล');
        heading.append(title, removeButton);

        const fields = document.createElement('div');
        fields.className = 'source-config-fields';
        const definitions = [
            { key: 'name', label: 'ชื่อแหล่งข้อมูล', value: source.name || '', maxLength: 100 },
            { key: 'dataDirectory', label: 'โฟลเดอร์ CSV', value: source.dataDirectory || '', maxLength: null },
            { key: 'filePattern', label: 'รูปแบบชื่อไฟล์', value: source.filePattern || '', maxLength: null },
        ];
        definitions.forEach((definition) => {
            const label = document.createElement('label');
            label.className = 'form-field';
            const caption = document.createElement('span');
            caption.textContent = definition.label;
            const input = document.createElement('input');
            input.type = 'text';
            input.dataset.field = definition.key;
            input.value = definition.value;
            input.required = true;
            input.autocomplete = 'off';
            if (definition.maxLength) {
                input.maxLength = definition.maxLength;
            }
            if (definition.key === 'name') {
                input.addEventListener('input', updateSourceTitles);
            }
            label.append(caption, input);
            fields.appendChild(label);
        });
        row.append(hiddenId, heading, fields);
        return row;
    }

    function collectSources() {
        return Array.from(sourceList.querySelectorAll('[data-source-row]')).map((row) => {
            const source = {};
            row.querySelectorAll('[data-field]').forEach((field) => {
                source[field.dataset.field] = field.value.trim();
            });
            return source;
        });
    }

    sourceList.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-source-button');
        if (!removeButton) {
            return;
        }
        const row = removeButton.closest('[data-source-row]');
        if (sourceList.querySelectorAll('[data-source-row]').length <= 1) {
            showMessage('ต้องเหลือแหล่งข้อมูลอย่างน้อย 1 แหล่ง', true);
            return;
        }
        row.remove();
        updateSourceTitles();
    });

    addSourceButton.addEventListener('click', () => {
        if (sourceList.querySelectorAll('[data-source-row]').length >= 20) {
            showMessage('เพิ่มแหล่งข้อมูลได้สูงสุด 20 แหล่ง', true);
            return;
        }
        sourceList.appendChild(createSourceRow());
        updateSourceTitles();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        message.textContent = '';
        saveButton.disabled = true;
        const settings = {
            sources: collectSources(),
            refreshSeconds: Number(document.getElementById('refresh-seconds').value),
            exportDirectory: document.getElementById('export-directory').value.trim(),
        };

        try {
            const response = await fetch('api/save_config.php', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(settings),
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.error || 'บันทึกการตั้งค่าไม่สำเร็จ');
            }
            showMessage(payload.message, false);
        } catch (error) {
            showMessage(error.message, true);
        } finally {
            saveButton.disabled = false;
        }
    });

    updateSourceTitles();
})();
