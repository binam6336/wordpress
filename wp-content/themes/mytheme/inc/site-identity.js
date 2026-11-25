document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-media-field]').forEach(field => {
    const id = field.querySelector('[data-media-id]');
    const preview = field.querySelector('[data-media-preview]');
    const remove = field.querySelector('[data-media-remove]');
    field.querySelector('[data-media-select]').addEventListener('click', () => {
      const frame = wp.media({ title: 'انتخاب تصویر', button: { text: 'استفاده از تصویر' }, multiple: false, library: { type: 'image' } });
      frame.on('select', () => {
        const image = frame.state().get('selection').first().toJSON();
        id.value = image.id;
        const src = image.sizes?.thumbnail?.url || image.url;
        preview.replaceChildren(Object.assign(document.createElement('img'), { src, alt: image.alt || '' }));
        remove.disabled = false;
      });
      frame.open();
    });
    remove.addEventListener('click', () => { id.value = ''; preview.replaceChildren(); remove.disabled = true; });
  });

  const form = document.querySelector('[data-mytheme-menu]');
  if (!form) return;
  const rows = form.querySelector('[data-menu-rows]');
  let index = rows.children.length;
  form.querySelector('[data-menu-add]').addEventListener('click', () => {
    if (rows.children.length >= 20) return;
    const html = document.querySelector('#mytheme-menu-template').innerHTML.replaceAll('__INDEX__', String(index++));
    rows.insertAdjacentHTML('beforeend', html);
  });
  rows.addEventListener('click', event => {
    const row = event.target.closest('[data-menu-row]');
    if (!row) return;
    if (event.target.closest('[data-menu-remove]')) row.remove();
    else if (event.target.closest('[data-menu-up]') && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
    else if (event.target.closest('[data-menu-down]') && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
  });
  rows.addEventListener('change', event => {
    if (!event.target.matches('[data-menu-target]')) return;
    const url = event.target.closest('[data-menu-row]').querySelector('[data-menu-url-wrap]');
    url.hidden = event.target.value !== 'custom';
    url.querySelector('input').required = !url.hidden;
  });
});
