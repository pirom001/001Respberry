(() => {
    const refreshSeconds = Math.max(5, Number(document.body.dataset.refreshSeconds) || 8);
    const statusPill = document.getElementById('connection-status');
    const statusLabel = document.getElementById('connection-label');
    const refreshButton = document.getElementById('refresh-button');
    const exportButton = document.getElementById('export-button');
    const addChartSelect = document.getElementById('add-chart-source');
    const addChartButton = document.getElementById('add-chart-button');
    const chartsGrid = document.getElementById('charts-grid');
    const toast = document.getElementById('toast');
    const chartStates = new Map();
    const colors = ['#8b7cff', '#42c6ae', '#f0ae64', '#66a9ff', '#f07498', '#bb83ed'];
    let sources = [];
    let activeChartId = null;
    let refreshInProgress = false;
    let toastTimer = null;

    function showToast(message, isError = false) {
        toast.textContent = message;
        toast.classList.toggle('toast-error', isError);
        toast.classList.add('toast-visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.remove('toast-visible'), 4200);
    }

    function setConnection(state, message) {
        statusPill.dataset.state = state;
        statusLabel.textContent = message;
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('th-TH', { maximumFractionDigits: 4 }).format(value);
    }

    function formatDate(value) {
        if (!value) {
            return '—';
        }
        const parsed = new Date(value);
        return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString('th-TH');
    }

    function persistVisibleCharts() {
        try {
            localStorage.setItem('monitor.visibleCharts', JSON.stringify(Array.from(chartStates.keys())));
            const columns = {};
            chartStates.forEach((state, id) => {
                columns[id] = state.column || '';
            });
            localStorage.setItem('monitor.chartColumns', JSON.stringify(columns));
        } catch (error) {
            showToast('ไม่สามารถบันทึกการจัดวางกราฟในเบราว์เซอร์ได้', true);
        }
    }

    function loadSavedState() {
        let visibleIds = null;
        let columns = {};
        try {
            const savedVisibleIds = localStorage.getItem('monitor.visibleCharts');
            visibleIds = savedVisibleIds === null ? null : JSON.parse(savedVisibleIds);
            columns = JSON.parse(localStorage.getItem('monitor.chartColumns') || '{}');
        } catch (error) {
            visibleIds = null;
            columns = {};
        }

        if (!columns || typeof columns !== 'object' || Array.isArray(columns)) {
            columns = {};
        }

        const allowedIds = new Set(sources.map((source) => source.id));
        const validIds = Array.isArray(visibleIds)
            ? visibleIds.filter((id) => allowedIds.has(id))
            : [];
        const chartIds = visibleIds === null ? sources.map((source) => source.id) : validIds;
        chartIds.forEach((id) => addChart(id, columns[id] || '', false));
    }

    function sourceById(sourceId) {
        return sources.find((source) => source.id === sourceId);
    }

    function setActiveChart(sourceId) {
        activeChartId = sourceId;
        chartStates.forEach((state, id) => {
            state.card.classList.toggle('active-chart', id === sourceId);
        });
        const activeState = chartStates.get(sourceId);
        updateStatistics(activeState?.data);
        renderTable(activeState?.data);
        if (activeState?.data) {
            document.getElementById('source-file').textContent =
                `${activeState.data.source.name} · ${activeState.data.fileCount} ไฟล์ / ${activeState.data.totalRows.toLocaleString('th-TH')} แถว`;
        }
    }

    function updateAddChartOptions() {
        const visibleIds = new Set(chartStates.keys());
        const available = sources.filter((source) => !visibleIds.has(source.id));
        addChartSelect.replaceChildren();
        available.forEach((source) => {
            const option = document.createElement('option');
            option.value = source.id;
            option.textContent = source.name;
            addChartSelect.appendChild(option);
        });
        addChartSelect.disabled = available.length === 0;
        addChartButton.disabled = available.length === 0;
        if (available.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'แสดงครบทุกแหล่งข้อมูลแล้ว';
            addChartSelect.appendChild(option);
        }
    }

    function getCardMarkup(source) {
        const card = document.createElement('article');
        card.className = 'panel chart-panel';
        card.dataset.chartId = source.id;

        const heading = document.createElement('div');
        heading.className = 'panel-heading chart-card-heading';
        const titleBlock = document.createElement('div');
        const label = document.createElement('div');
        label.className = 'section-label';
        label.textContent = 'TIME SERIES';
        const title = document.createElement('h2');
        title.textContent = source.name;
        titleBlock.append(label, title);
        const controls = document.createElement('div');
        controls.className = 'chart-controls';
        const columnLabel = document.createElement('label');
        columnLabel.className = 'select-label';
        columnLabel.textContent = 'คอลัมน์';
        const select = document.createElement('select');
        select.setAttribute('aria-label', `คอลัมน์ของ ${source.name}`);
        columnLabel.htmlFor = `column-${source.id}`;
        select.id = `column-${source.id}`;
        select.addEventListener('change', () => {
            const state = chartStates.get(source.id);
            if (state) {
                state.column = select.value;
                refreshSource(source.id);
                persistVisibleCharts();
            }
        });

        const resetButton = document.createElement('button');
        resetButton.className = 'button button-ghost';
        resetButton.type = 'button';
        resetButton.textContent = 'รีเซ็ตซูม';
        resetButton.title = 'คืนค่าการซูม';
        resetButton.addEventListener('click', () => {
            const state = chartStates.get(source.id);
            if (state?.chart && typeof state.chart.resetZoom === 'function') {
                state.chart.resetZoom();
            }
        });

        const pngButton = document.createElement('button');
        pngButton.className = 'button button-ghost';
        pngButton.type = 'button';
        pngButton.textContent = 'PNG';
        pngButton.addEventListener('click', () => downloadChart(source.id));

        const removeButton = document.createElement('button');
        removeButton.className = 'button button-ghost chart-remove-button';
        removeButton.type = 'button';
        removeButton.textContent = 'ลบกราฟ';
        removeButton.setAttribute('aria-label', `ลบกราฟ ${source.name}`);
        removeButton.addEventListener('click', () => removeChart(source.id));
        controls.append(columnLabel, select, resetButton, pngButton, removeButton);
        heading.append(titleBlock, controls);

        const hint = document.createElement('div');
        hint.className = 'chart-hint';
        hint.textContent = 'ลากเพื่อซูม • เลื่อนเมาส์เพื่อซูม • กด Shift ค้างแล้วลากเพื่อเลื่อน';
        const chartWrap = document.createElement('div');
        chartWrap.className = 'chart-wrap';
        const canvas = document.createElement('canvas');
        canvas.setAttribute('aria-label', `กราฟ ${source.name}`);
        chartWrap.appendChild(canvas);
        const footer = document.createElement('div');
        footer.className = 'chart-footer';
        const summary = document.createElement('span');
        summary.textContent = 'กำลังโหลดข้อมูล...';
        const latest = document.createElement('span');
        latest.append('ข้อมูลล่าสุด: ');
        const latestTime = document.createElement('strong');
        latestTime.textContent = '—';
        latest.appendChild(latestTime);
        footer.append(summary, latest);

        const error = document.createElement('div');
        error.className = 'chart-error';
        error.hidden = true;
        card.append(heading, hint, chartWrap, footer, error);
        return {
            card,
            select,
            canvas,
            summary,
            latestTime,
            error,
        };
    }

    function addChart(sourceId, preferredColumn = '', shouldPersist = true) {
        const source = sourceById(sourceId);
        if (!source || chartStates.has(sourceId)) {
            return;
        }

        const elements = getCardMarkup(source);
        chartsGrid.querySelector('.chart-placeholder')?.remove();
        chartsGrid.appendChild(elements.card);
        chartStates.set(sourceId, {
            source,
            column: preferredColumn,
            chart: null,
            data: null,
            ...elements,
        });
        if (!activeChartId) {
            activeChartId = sourceId;
        }
        elements.card.addEventListener('click', (event) => {
            if (!event.target.closest('button, select')) {
                setActiveChart(sourceId);
            }
        });
        elements.card.classList.toggle('active-chart', activeChartId === sourceId);
        updateAddChartOptions();
        if (shouldPersist) {
            persistVisibleCharts();
        }
        if (shouldPersist) {
            refreshSource(sourceId);
        }
    }

    function removeChart(sourceId) {
        const state = chartStates.get(sourceId);
        if (!state) {
            return;
        }
        state.chart?.destroy();
        state.card.remove();
        chartStates.delete(sourceId);
        if (activeChartId === sourceId) {
            activeChartId = chartStates.keys().next().value || null;
        }
        chartStates.forEach((state, id) => {
            state.card.classList.toggle('active-chart', id === activeChartId);
        });
        if (chartStates.size === 0) {
            const placeholder = document.createElement('div');
            placeholder.className = 'panel chart-panel chart-placeholder';
            placeholder.textContent = 'ยังไม่มีกราฟที่แสดง เลือกแหล่งข้อมูลด้านบนเพื่อเพิ่มกราฟ';
            chartsGrid.appendChild(placeholder);
        }
        updateAddChartOptions();
        persistVisibleCharts();
        updateStatistics(chartStates.get(activeChartId)?.data);
        renderTable(chartStates.get(activeChartId)?.data);
    }

    function updateColumnOptions(state, columns, selectedColumn) {
        const nextColumn = columns.some((column) => column.name === state.column)
            ? state.column
            : (columns.some((column) => column.name === selectedColumn) ? selectedColumn : columns[0].name);
        state.select.replaceChildren();
        columns.forEach((column) => {
            const option = document.createElement('option');
            option.value = column.name;
            option.textContent = column.name + (column.type === 'categorical' ? ' · สถานะ' : '');
            state.select.appendChild(option);
        });
        state.column = nextColumn;
        state.select.value = nextColumn;
        return nextColumn;
    }

    function chartValues(payload, metadata) {
        const categories = [];
        const indexes = new Map();
        const values = payload.chartRows.map((row) => {
            const value = row.values[payload.selectedColumn];
            if (metadata.type === 'numeric') {
                return value === '' || !Number.isFinite(Number(value)) ? null : Number(value);
            }
            if (value === '') {
                return null;
            }
            if (!indexes.has(value)) {
                indexes.set(value, categories.length);
                categories.push(value);
            }
            return indexes.get(value);
        });
        return { values, categories };
    }

    function renderChart(state, payload) {
        const metadata = payload.columns.find((column) => column.name === payload.selectedColumn);
        const calculated = chartValues(payload, metadata);
        const labels = payload.chartRows.map((row) => row.timestamp || 'ไม่ระบุเวลา');
        const color = colors[sources.findIndex((source) => source.id === state.source.id) % colors.length];

        if (!window.Chart) {
            state.error.hidden = false;
            state.error.textContent = 'โหลด Chart.js ไม่สำเร็จ กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต';
            return;
        }

        if (state.chart) {
            state.chart.data.labels = labels;
            state.chart.data.datasets[0].label = `${state.source.name} · ${payload.selectedColumn}`;
            state.chart.data.datasets[0].data = calculated.values;
            state.chart.options.scales.y.ticks.callback = metadata.type === 'categorical'
                ? (value) => calculated.categories[value] ?? ''
                : undefined;
            state.chart.options.plugins.tooltip.callbacks.label = (context) => metadata.type === 'categorical'
                ? `${payload.selectedColumn}: ${calculated.categories[context.raw] ?? ''}`
                : `${payload.selectedColumn}: ${formatNumber(context.parsed.y)}`;
            state.chart.update('none');
        } else {
            const zoomPlugin = window.ChartZoom;
            if (zoomPlugin && !window.Chart.registry.plugins.get('zoom')) {
                window.Chart.register(zoomPlugin);
            }
            state.chart = new window.Chart(state.canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: `${state.source.name} · ${payload.selectedColumn}`,
                        data: calculated.values,
                        borderColor: color,
                        backgroundColor: `${color}24`,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        fill: true,
                        tension: 0.25,
                        spanGaps: false,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#171d29',
                            titleColor: '#f4f5fb',
                            bodyColor: '#c8ccda',
                            borderColor: 'rgba(255,255,255,.1)',
                            borderWidth: 1,
                            callbacks: {
                                label: (context) => metadata.type === 'categorical'
                                    ? `${payload.selectedColumn}: ${calculated.categories[context.raw] ?? ''}`
                                    : `${payload.selectedColumn}: ${formatNumber(context.parsed.y)}`,
                            },
                        },
                        zoom: {
                            pan: { enabled: Boolean(zoomPlugin), mode: 'x', modifierKey: 'shift' },
                            zoom: {
                                drag: { enabled: Boolean(zoomPlugin), backgroundColor: 'rgba(139,124,255,.15)' },
                                wheel: { enabled: Boolean(zoomPlugin) },
                                pinch: { enabled: Boolean(zoomPlugin) },
                                mode: 'x',
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(155,165,190,.08)' },
                            ticks: { color: '#81899c', maxTicksLimit: 8, maxRotation: 0 },
                            border: { color: 'rgba(155,165,190,.12)' },
                        },
                        y: {
                            grid: { color: 'rgba(155,165,190,.08)' },
                            ticks: {
                                color: '#81899c',
                                callback: metadata.type === 'categorical'
                                    ? (value) => calculated.categories[value] ?? ''
                                    : undefined,
                            },
                            border: { color: 'rgba(155,165,190,.12)' },
                        },
                    },
                },
            });
        }

        state.summary.textContent = `${payload.chartRows.length.toLocaleString('th-TH')} จุดจาก ${payload.fileCount} ไฟล์ · ย้อนหลัง ${payload.historyDays} วัน`;
        state.latestTime.textContent = payload.lastTimestamp || '—';
        state.error.hidden = true;
    }

    function updateStatistics(payload) {
        if (!payload) {
            ['stat-max', 'stat-min', 'stat-average', 'stat-count'].forEach((id) => {
                document.getElementById(id).textContent = '—';
            });
            document.getElementById('stat-category-summary').textContent = 'เลือกกราฟเพื่อดูสถิติ';
            return;
        }
        const stats = payload.stats;
        const numeric = stats.type === 'numeric';
        document.getElementById('stat-max').textContent = numeric ? formatNumber(stats.max) : '—';
        document.getElementById('stat-min').textContent = numeric ? formatNumber(stats.min) : '—';
        document.getElementById('stat-average').textContent = numeric ? formatNumber(stats.average) : '—';
        document.getElementById('stat-count').textContent = formatNumber(payload.totalRows);
        document.getElementById('stat-max-label').textContent = numeric
            ? `${payload.source.name} · ${payload.selectedColumn}`
            : 'คอลัมน์นี้เป็นข้อมูลหมวดหมู่';
        document.getElementById('stat-min-label').textContent = numeric
            ? `${payload.source.name} · ${payload.selectedColumn}`
            : 'คอลัมน์นี้เป็นข้อมูลหมวดหมู่';
        document.getElementById('stat-average-label').textContent = numeric
            ? `${payload.source.name} · ${payload.selectedColumn}`
            : 'คอลัมน์นี้เป็นข้อมูลหมวดหมู่';
        document.getElementById('stat-category-summary').textContent = numeric
            ? `${formatNumber(stats.count)} ค่าตัวเลขใน ${payload.historyDays} วัน`
            : (stats.categorySummary || 'แถวข้อมูลทั้งหมด');
    }

    function renderTable(payload) {
        const head = document.getElementById('data-table-head');
        const body = document.getElementById('data-table-body');
        if (!payload) {
            head.replaceChildren();
            body.innerHTML = '<tr><td class="empty-state" colspan="4">เพิ่มกราฟเพื่อดูข้อมูล</td></tr>';
            document.getElementById('table-summary').textContent = 'ไม่มีข้อมูลที่เลือก';
            return;
        }

        const headings = ['เวลา', 'ไฟล์', ...payload.columns.map((column) => column.name)];
        const headerRow = document.createElement('tr');
        headings.forEach((heading) => {
            const th = document.createElement('th');
            th.textContent = heading;
            headerRow.appendChild(th);
        });
        head.replaceChildren(headerRow);

        const fragment = document.createDocumentFragment();
        payload.tableRows.slice().reverse().forEach((row) => {
            const tr = document.createElement('tr');
            [row.timestamp || '—', row.sourceFile || '—', ...payload.columns.map((column) => row.values[column.name] ?? '')]
                .forEach((value) => {
                    const td = document.createElement('td');
                    td.textContent = value;
                    tr.appendChild(td);
                });
            fragment.appendChild(tr);
        });
        body.replaceChildren(fragment);
        document.getElementById('table-summary').textContent =
            `${payload.source.name} · แสดง ${payload.tableRows.length.toLocaleString('th-TH')} จาก ${payload.totalRows.toLocaleString('th-TH')} แถวใน ${payload.historyDays} วัน`;
    }

    async function refreshSource(sourceId) {
        const state = chartStates.get(sourceId);
        if (!state) {
            return;
        }

        const params = new URLSearchParams({ sourceId });
        if (state.column) {
            params.set('column', state.column);
        }
        try {
            const response = await fetch(`api/get_data.php?${params.toString()}`, {
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.error || 'ไม่สามารถอ่านข้อมูลแหล่งนี้ได้');
            }
            const nextColumn = updateColumnOptions(state, payload.columns, payload.selectedColumn);
            if (nextColumn !== payload.selectedColumn) {
                await refreshSource(sourceId);
                return;
            }
            state.data = payload;
            renderChart(state, payload);
            document.getElementById('source-updated').textContent =
                `${sources.length} แหล่งข้อมูล · อัปเดตล่าสุด ${formatDate(payload.modifiedAt)}`;
            if (activeChartId === sourceId) {
                updateStatistics(payload);
                renderTable(payload);
                document.getElementById('source-file').textContent =
                    `${payload.source.name} · ${payload.fileCount} ไฟล์ / ${payload.totalRows.toLocaleString('th-TH')} แถว`;
            }
            return true;
        } catch (error) {
            state.error.hidden = false;
            state.error.textContent = error.message;
            state.summary.textContent = 'ไม่สามารถโหลดแหล่งข้อมูลนี้ได้';
            return false;
        }
    }

    async function refreshAll() {
        if (refreshInProgress) {
            return;
        }
        if (chartStates.size === 0) {
            setConnection('online', 'พร้อมใช้งาน');
            document.getElementById('source-file').textContent = 'ยังไม่มีกราฟที่แสดง';
            document.getElementById('source-updated').textContent = 'เลือกแหล่งข้อมูลเพื่อเพิ่มกราฟ';
            return;
        }
        refreshInProgress = true;
        refreshButton.disabled = true;
        try {
            const results = await Promise.all(Array.from(chartStates.keys()).map((id) => refreshSource(id)));
            const successful = results.filter(Boolean).length;
            if (successful > 0) {
                setConnection(successful === chartStates.size ? 'online' : 'loading',
                    successful === chartStates.size ? 'เชื่อมต่อแล้ว' : `ใช้งานได้ ${successful}/${chartStates.size} แหล่ง`);
                document.getElementById('source-file').textContent =
                    `${successful}/${chartStates.size} แหล่งข้อมูลพร้อมใช้งาน`;
            } else {
                setConnection('offline', 'ออฟไลน์');
                document.getElementById('source-file').textContent = chartStates.size > 0
                    ? 'ไม่พบข้อมูลในช่วง 30 วันที่ผ่านมา'
                    : 'ยังไม่มีกราฟที่แสดง';
                document.getElementById('source-updated').textContent =
                    'ตรวจสอบ path, รูปแบบชื่อไฟล์ และสิทธิ์อ่านของ PHP';
            }
        } finally {
            refreshInProgress = false;
            refreshButton.disabled = false;
        }
    }

    function downloadChart(sourceId) {
        const state = chartStates.get(sourceId);
        if (!state?.chart) {
            showToast('กราฟนี้ยังไม่มีข้อมูลสำหรับส่งออก', true);
            return;
        }
        const link = document.createElement('a');
        link.href = state.chart.toBase64Image('image/png', 1);
        link.download = `monitor_${sourceId}.png`;
        link.click();
    }

    addChartButton.addEventListener('click', () => addChart(addChartSelect.value));
    refreshButton.addEventListener('click', refreshAll);
    exportButton.addEventListener('click', async () => {
        if (!activeChartId) {
            showToast('เพิ่มกราฟก่อนส่งออกข้อมูล', true);
            return;
        }
        exportButton.disabled = true;
        try {
            const sourceId = encodeURIComponent(activeChartId);
            const response = await fetch(`api/export_txt.php?sourceId=${sourceId}`, {
                method: 'POST',
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.error || 'ส่งออกข้อมูลไม่สำเร็จ');
            }
            showToast(`บันทึก ${payload.filename} แล้ว (${payload.rows.toLocaleString('th-TH')} แถว)`);
            window.location.href = payload.downloadUrl;
        } catch (error) {
            showToast(error.message, true);
        } finally {
            exportButton.disabled = false;
        }
    });

    document.getElementById('refresh-interval').textContent = String(refreshSeconds);
    fetch('api/get_data.php', { cache: 'no-store', headers: { Accept: 'application/json' } })
        .then(async (response) => {
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.error || 'ไม่สามารถอ่านการตั้งค่าแหล่งข้อมูลได้');
            }
            sources = payload.sources;
            if (sources.length === 0) {
                throw new Error('ยังไม่ได้กำหนดแหล่งข้อมูล CSV');
            }
            document.getElementById('refresh-interval').textContent = String(payload.refreshSeconds);
            updateAddChartOptions();
            loadSavedState();
            if (chartStates.size > 0) {
                refreshAll();
            }
        })
        .catch((error) => {
            setConnection('offline', 'ออฟไลน์');
            document.getElementById('source-file').textContent = error.message;
            document.getElementById('source-updated').textContent = 'ตรวจสอบการตั้งค่าแหล่งข้อมูล';
            showToast(error.message, true);
        });
    window.setInterval(refreshAll, refreshSeconds * 1000);
})();
