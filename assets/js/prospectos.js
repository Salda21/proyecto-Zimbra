document.addEventListener('DOMContentLoaded', () => {
  const storageKey = 'zimbraConnect.prospectos.v1';
  const form = document.querySelector('#prospectForm');
  const tableBody = document.querySelector('#prospectTable tbody');
  const modal = document.querySelector('#prospectModal');
  const search = document.querySelector('[data-prospect-search]');
  const levelFilter = document.querySelector('[data-prospect-level]');
  const dateFilter = document.querySelector('[data-prospect-date]');
  const exportButton = document.querySelector('[data-prospect-export]');
  let prospects;

  function showError(message) {
    if (window.ZCNotify) {
      window.ZCNotify.error(message);
    } else {
      window.alert(message);
    }
  }

  function normalize(value) {
    return String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
  }

  function dateFromActivity(label) {
    const date = new Date();
    const match = label.match(/Hace (\d+) (h|día|días)/i);

    if (match) {
      const amount = Number(match[1]);
      if (match[2].toLowerCase() === 'h') {
        date.setHours(date.getHours() - amount);
      } else {
        date.setDate(date.getDate() - amount);
      }
    }

    return date.toISOString();
  }

  function readInitialProspects() {
    return Array.from(tableBody.querySelectorAll('tr')).map((row, index) => {
      const cells = row.querySelectorAll('td');
      return {
        id: `initial-${index}`,
        nombre: cells[0].textContent.trim(),
        nivel: cells[1].textContent.trim(),
        actividad: dateFromActivity(cells[2].textContent.trim()),
        asignado: cells[3].textContent.trim(),
        estado: cells[4].textContent.trim()
      };
    });
  }

  function isValidProspect(prospect) {
    return prospect && typeof prospect.id === 'string' &&
      typeof prospect.nombre === 'string' &&
      typeof prospect.nivel === 'string' &&
      typeof prospect.actividad === 'string' &&
      !Number.isNaN(Date.parse(prospect.actividad)) &&
      typeof prospect.asignado === 'string' &&
      typeof prospect.estado === 'string';
  }

  function loadProspects() {
    const stored = window.localStorage.getItem(storageKey);
    if (stored === null) {
      const initial = readInitialProspects();
      window.localStorage.setItem(storageKey, JSON.stringify(initial));
      return initial;
    }

    const parsed = JSON.parse(stored);
    if (!Array.isArray(parsed) || !parsed.every(isValidProspect)) {
      throw new Error('Los datos guardados de prospectos no tienen un formato válido.');
    }
    return parsed;
  }

  function saveProspects(nextProspects) {
    window.localStorage.setItem(storageKey, JSON.stringify(nextProspects));
  }

  function activityLabel(timestamp) {
    const elapsedHours = Math.max(0, Math.floor((Date.now() - Date.parse(timestamp)) / 3600000));
    if (elapsedHours === 0) return 'Ahora';
    if (elapsedHours < 24) return `Hace ${elapsedHours} h`;
    const elapsedDays = Math.floor(elapsedHours / 24);
    return `Hace ${elapsedDays} ${elapsedDays === 1 ? 'día' : 'días'}`;
  }

  function createCell(row, value) {
    const cell = document.createElement('td');
    cell.textContent = value;
    row.appendChild(cell);
    return cell;
  }

  function filteredProspects() {
    const query = normalize(search.value.trim());
    const level = levelFilter.value;
    const dateRange = dateFilter.value;
    const now = new Date();
    let cutoff = null;

    if (dateRange === 'year') {
      cutoff = new Date(now.getFullYear(), 0, 1);
    } else if (dateRange) {
      cutoff = new Date(now.getTime() - Number(dateRange) * 24 * 60 * 60 * 1000);
    }

    return prospects.filter(prospect => {
      const searchable = normalize([
        prospect.nombre,
        prospect.nivel,
        activityLabel(prospect.actividad),
        prospect.asignado,
        prospect.estado
      ].join(' '));
      return (!query || searchable.includes(query)) &&
        (!level || prospect.nivel === level) &&
        (!cutoff || Date.parse(prospect.actividad) >= cutoff.getTime());
    });
  }

  function renderProspects() {
    const visibleProspects = filteredProspects();
    tableBody.replaceChildren();

    if (!visibleProspects.length) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.colSpan = 6;
      cell.className = 'empty';
      cell.textContent = 'No se encontraron prospectos con estos filtros.';
      row.appendChild(cell);
      tableBody.appendChild(row);
      return;
    }

    visibleProspects.forEach(prospect => {
      const row = document.createElement('tr');
      createCell(row, prospect.nombre);

      const levelCell = document.createElement('td');
      const pill = document.createElement('span');
      pill.className = `pill${prospect.nivel === 'Alto' ? ' red' : prospect.nivel === 'Medio' ? ' orange' : ''}`;
      pill.textContent = prospect.nivel;
      levelCell.appendChild(pill);
      row.appendChild(levelCell);

      createCell(row, activityLabel(prospect.actividad));
      createCell(row, prospect.asignado);
      createCell(row, prospect.estado);
      createCell(row, '◉　♙　•••');
      tableBody.appendChild(row);
    });
  }

  try {
    prospects = loadProspects();
    renderProspects();
  } catch (error) {
    showError(`No se pudieron cargar los prospectos: ${error.message}`);
    return;
  }

  [search, levelFilter, dateFilter].forEach(control => {
    control.addEventListener(control === search ? 'input' : 'change', renderProspects);
  });

  form.addEventListener('submit', event => {
    event.preventDefault();
    if (!form.reportValidity()) return;

    const values = new FormData(form);
    const nombre = values.get('nombre').trim();
    if (!nombre) {
      form.elements.nombre.setCustomValidity('Ingresa el nombre del prospecto.');
      form.reportValidity();
      form.elements.nombre.addEventListener('input', () => form.elements.nombre.setCustomValidity(''), { once: true });
      return;
    }

    const nextProspects = [{
      id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
      nombre,
      nivel: values.get('nivel'),
      actividad: new Date().toISOString(),
      asignado: values.get('asignado'),
      estado: 'Nuevo'
    }, ...prospects];

    try {
      saveProspects(nextProspects);
      prospects = nextProspects;
      renderProspects();
      modal.classList.remove('show');
      form.reset();
      window.ZCNotify.success('El prospecto se guardó correctamente.');
    } catch (error) {
      showError(`No se pudo guardar el prospecto: ${error.message}`);
    }
  });

  exportButton.addEventListener('click', () => {
    const rows = filteredProspects();
    if (!rows.length) {
      window.ZCNotify.info('No hay prospectos para exportar con los filtros actuales.');
      return;
    }

    const csvValue = value => `"${String(value).replace(/"/g, '""')}"`;
    const csv = [
      ['Usuario', 'Nivel de interés', 'Última actividad', 'Asignado a', 'Estado'],
      ...rows.map(prospect => [
        prospect.nombre,
        prospect.nivel,
        new Date(prospect.actividad).toLocaleString('es-CO'),
        prospect.asignado,
        prospect.estado
      ])
    ].map(row => row.map(csvValue).join(',')).join('\r\n');

    const blob = new Blob(['\uFEFF', csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'prospectos.csv';
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  });
});
