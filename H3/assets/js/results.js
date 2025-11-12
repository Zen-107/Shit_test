// results.js — แสดงสินค้าทั้งหมดจากฐานข้อมูลจริง
document.addEventListener('DOMContentLoaded', () => {
  const critEl = document.getElementById('criteria');
  const grid = document.getElementById('results');
  const empty = document.getElementById('empty');

  critEl.textContent = 'แสดงสินค้าทั้งหมด';

  fetch('api/get_all_product.php')
    .then(response => {
      if (!response.ok) throw new Error('ไม่สามารถโหลดสินค้าได้');
      return response.json();
    })
    .then(products => {
      if (!Array.isArray(products) || products.length === 0) {
        empty.style.display = 'block';
        return;
      }

      grid.innerHTML = products.map(p => {
        let priceText = '—';
        if (p.min_price !== null && p.max_price !== null) {
          const min = parseFloat(p.min_price);
          const max = parseFloat(p.max_price);
          const currency = p.currency || 'THB';
          if (min === max) {
            priceText = `${min.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
          } else {
            priceText = `${min.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} – ${max.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
          }
        }

        return `
          <div class="card">
            <img src="${p.image_url || ''}" alt="${p.name}" onerror="this.src='assets/images/placeholder.png'">
            <div class="card-body">
              <div class="badge">${p.categories?.[0] || 'Gift'}</div>
              <strong>${p.name}</strong>
              <div class="price">${priceText}</div>
              <div>${p.description ? p.description.substring(0, 100) + '...' : ''}</div>
              <div class="stack">
                <a class="btn" href="product.html?id=${p.id}">View details</a>
              </div>
            </div>
          </div>
        `;
      }).join('');
    })
    .catch(error => {
      console.error('Error:', error);
      empty.textContent = 'ไม่สามารถโหลดสินค้าได้';
      empty.style.display = 'block';
    });
});