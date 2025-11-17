/**
 * ดึงพารามิเตอร์จาก URL
 */
function getQueryParam(name) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(name);
}

/**
 * จัดรูปแบบคำอธิบายให้มีย่อหน้า (<p>) และขึ้นบรรทัดใหม่ (<br>)
 */
function formatDescription(text) {
  if (!text) return '';
  return text
    .split('\n\n')
    .map(para => {
      const trimmed = para.trim();
      return trimmed ? `<p>${trimmed.replace(/\n/g, '<br>')}</p>` : '';
    })
    .join('');
}

/**
 * จัดการปุ่ม bookmark (เช็คสถานะ + เพิ่ม/ลบ)
 */
function initBookmarkButton(productId) {
  const bookmarkBtn = document.getElementById('bookmarkBtn');
  const bookmarkText = document.getElementById('bookmarkText');

  if (!bookmarkBtn || !productId) return;

  // เช็คว่าผู้ใช้ bookmark สินค้านี้แล้วหรือยัง
  fetch(`api/check_bookmark.php?product_id=${productId}`)
    .then(res => res.json())
    .then(data => {
      if (data.isBookmarked) {
        bookmarkBtn.classList.remove('btn-outline-primary');
        bookmarkBtn.classList.add('btn-primary');
        bookmarkText.textContent = 'ลบบุ๊กมาร์ก';
      }
    })
    .catch(() => {
      // ถ้าไม่ล็อกอิน หรือ API error → ปล่อยเป็นค่าเริ่มต้น
    });

  // จัดการเมื่อกดปุ่ม
  bookmarkBtn.addEventListener('click', () => {
    const isBookmarked = bookmarkBtn.classList.contains('btn-primary');
    const url = isBookmarked ? 'api/remove_bookmark.php' : 'api/add_bookmark.php';
    const body = `product_id=${productId}&folder_id=0&custom_name=&note=`;

    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          if (isBookmarked) {
            // ลบสำเร็จ
            bookmarkBtn.classList.remove('btn-primary');
            bookmarkBtn.classList.add('btn-outline-primary');
            bookmarkText.textContent = 'บุ๊กมาร์ก';
            alert('ลบบุ๊กมาร์กเรียบร้อย');
          } else {
            // เพิ่มสำเร็จ
            bookmarkBtn.classList.remove('btn-outline-primary');
            bookmarkBtn.classList.add('btn-primary');
            bookmarkText.textContent = 'ลบบุ๊กมาร์ก';
            alert('บุ๊กมาร์กสำเร็จ!');
          }
        } else {
          alert(data.message || 'เกิดข้อผิดพลาด');
        }
      })
      .catch(() => {
        alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
      });
  });
}

// เริ่มทำงานเมื่อหน้าเว็บโหลดเสร็จ
document.addEventListener('DOMContentLoaded', () => {
  const id = Number(getQueryParam('id'));

  if (!id || isNaN(id)) {
    document.getElementById('product').innerHTML = '<div class="empty">ไม่พบสินค้า</div>';
    return;
  }

  // ดึงข้อมูลสินค้าจาก API
  fetch(`api/get_product.php?id=${id}`)
    .then(response => {
      if (!response.ok) throw new Error('ไม่พบสินค้านี้');
      return response.json();
    })
    .then(p => {
      const root = document.getElementById('product');

      // สร้างปุ่มร้านค้าจาก external_urls
      let buyButtons = '';
      if (p.external_urls && p.external_urls.length > 0) {
        p.external_urls.forEach(link => {
          const source = link.source_name || 'ซื้อเลย';
          buyButtons += `
            <a class="btn secondary" href="${link.url}" target="_blank" style="text-decoration: none; display: inline-block; margin-right: 8px;">
              ${source}
            </a>
          `;
        });
      } else if (p.external_url) {
        buyButtons = `
          <a class="btn secondary" href="${p.external_url}" target="_blank" style="text-decoration: none; display: inline-block;">
            ซื้อเลย
          </a>
        `;
      }

      // จัดรูปแบบช่วงราคา
      let priceText = '—';
      if (p.price_range && p.price_range.min !== null && p.price_range.max !== null) {
        const min = parseFloat(p.price_range.min);
        const max = parseFloat(p.price_range.max);
        const currency = p.price_range.currency || 'THB';
        if (min === max) {
          priceText = `${min.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
        } else {
          priceText = `${min.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} – ${max.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
        }
      }

      // จัดรูปแบบคำอธิบาย
      const formattedDescription = formatDescription(p.description);

      // ปุ่ม bookmark
      const bookmarkButtonHtml = `
        <div style="margin-top: 16px;">
          <button id="bookmarkBtn" class="btn btn-outline-primary">
            <i class="fas fa-bookmark"></i> <span id="bookmarkText">บุ๊กมาร์ก</span>
          </button>
        </div>
      `;

      // render หน้าสินค้าทั้งหมด
      root.innerHTML = `
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 24px;">
          <div>
            <img src="${p.image_url || ''}" alt="${p.name}" style="max-width: 100%; height: auto; border-radius: 8px;">
          </div>
          <div>
            <div class="badge">${p.categories && p.categories[0] ? p.categories[0] : 'Gift'}</div>
            <h1>${p.name}</h1>
            <div class="price" style="font-size: 20px; margin: 8px 0;">${priceText}</div>
            <div class="product-description" style="margin: 16px 0;">
              ${formattedDescription}
            </div>
            <div class="stack" style="margin: 16px 0;">
              ${buyButtons}
            </div>
            ${bookmarkButtonHtml}
          </div>
        </div>

        <div class="section" style="margin-top: 40px;">
          <h2>รีวิว</h2>
          <div class="card">
            <div class="card-body">“คุณภาพดี คุ้มค่ากับราคา” — ผู้ใช้งาน</div>
          </div>
        </div>
      `;

      // เริ่มต้นระบบ bookmark
      initBookmarkButton(p.id);
    })
    .catch(error => {
      console.error('Error loading product:', error);
      document.getElementById('product').innerHTML = 
        `<div class="empty">เกิดข้อผิดพลาด: ${error.message}</div>`;
    });
});