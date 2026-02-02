jQuery(function ($) {

  function getOps($wrap) {
    return {
      tax_relation: ($wrap.data('tax-relation') || 'AND'),
      product_op: ($wrap.data('product-op') || 'IN'),
      color_op: ($wrap.data('color-op') || 'IN'),
      style_op: ($wrap.data('style-op') || 'IN')
    };
  }

  function collectFilters($wrap) {
    const data = { product: [], color: [], style: [] };

    $wrap.find('input[type="checkbox"]:checked').each(function () {
      const key = $(this).data('filter-key');
      if (data[key]) data[key].push($(this).val());
    });

    return data;
  }

  function fetchPage($wrap, page, append) {
    const perPage = parseInt($wrap.data('per-page'), 10) || 24;
    const filters = collectFilters($wrap);
    const ops = getOps($wrap);

    const payload = {
      action: 'htc_gallery_filter',
      nonce: HTCGallery.nonce,
      page: page,
      per_page: perPage,
      product: filters.product,
      color: filters.color,
      style: filters.style,
      tax_relation: ops.tax_relation,
      product_op: ops.product_op,
      color_op: ops.color_op,
      style_op: ops.style_op
    };

    const $grid = $wrap.find('.htc-gallery__grid');
    const $btn = $wrap.find('.htc-gallery__loadmore');

    $btn.prop('disabled', true).text('Loading...');

    $.post(HTCGallery.ajaxUrl, payload)
      .done(function (res) {
        if (!res || !res.success) return;

        if (append) $grid.append(res.data.html);
        else $grid.html(res.data.html);

        $grid.attr('data-page', res.data.page);

        const more = res.data.page < res.data.maxPages;
        $btn.toggle(more);
      })
      .always(function () {
        $btn.prop('disabled', false).text('Load more');
      });
  }

  function refreshDependentFilters($wrap) {
    const filters = collectFilters($wrap);
    const ops = getOps($wrap);

    const payload = {
      action: 'htc_gallery_terms',
      nonce: HTCGallery.nonce,
      product: filters.product,
      color: filters.color,
      style: filters.style,
      tax_relation: ops.tax_relation,
      product_op: ops.product_op,
      color_op: ops.color_op,
      style_op: ops.style_op
    };

    $.post(HTCGallery.ajaxUrl, payload)
      .done(function (res) {
        if (!res || !res.success) return;

        // For each group, rebuild list w/ counts & preserve checked states
        ['product', 'color', 'style'].forEach(function (key) {
          const items = res.data[key] || [];
          const $group = $wrap.find('.htc-gallery__filtergroup[data-group="' + key + '"]');
          if (!$group.length) return;

          const selected = new Set(filters[key] || []);

          let html = '';
          items.forEach(function (t) {
            const checked = selected.has(t.slug) ? ' checked' : '';
            html += `
              <label class="htc-gallery__filteritem">
                <input type="checkbox" data-filter-key="${key}" value="${t.slug}"${checked}>
                ${escapeHtml(t.name)} <span class="htc-gallery__count">(${t.count})</span>
              </label>
            `;
          });

          // If no items returned, keep any selected values visible (so user can uncheck)
          if (!items.length && selected.size) {
            selected.forEach(function (slug) {
              html += `
                <label class="htc-gallery__filteritem">
                  <input type="checkbox" data-filter-key="${key}" value="${slug}" checked>
                  ${escapeHtml(slug)} <span class="htc-gallery__count">(0)</span>
                </label>
              `;
            });
          }

          $group.find('.htc-gallery__filterlist').html(html);
        });
      });
  }

  function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}


  // Change handler: refresh filters + reload page 1
  $(document).on('change', '.htc-gallery input[type="checkbox"]', function () {
    const $wrap = $(this).closest('.htc-gallery');
    refreshDependentFilters($wrap);
    fetchPage($wrap, 1, false);
  });

  // Load more
  $(document).on('click', '.htc-gallery__loadmore', function () {
    const $wrap = $(this).closest('.htc-gallery');
    const $grid = $wrap.find('.htc-gallery__grid');
    const current = parseInt($grid.attr('data-page') || '1', 10);
    fetchPage($wrap, current + 1, true);
  });

  // Initial dependent refresh (so counts appear immediately)
  $('.htc-gallery').each(function () {
    refreshDependentFilters($(this));
  });

});
