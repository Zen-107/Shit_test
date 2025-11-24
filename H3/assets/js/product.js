function getQueryParam(name) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(name);
}

// ✅ ฟังก์ชันแปลงคำอธิบายให้มีย่อหน้า
function formatDescription(text) {
  if (!text) return '';
  // แยกย่อหน้าโดยใช้ \n\n (2 บรรทัดว่าง) → สร้าง <p>
  return text
    .split('\n\n')
    .map(para => {
      // ลบช่องว่างที่หัว-ท้าย และแปลง \n เดี่ยว → <br>
      const trimmed = para.trim();
      return trimmed ? `<p>${trimmed.replace(/\n/g, '<br>')}</p>` : '';
    })
    .join('');
}

async function loadBookmarkStatus(productId) {
  try {
    const res = await fetch(`/H3/api/check_bookmark.php?product_id=${productId}`);
    const data = await res.json();
    return data.isBookmarked;
  } catch (e) {
    console.warn('ไม่สามารถตรวจสอบ bookmark ได้');
    return false;
  }
}

async function getOrCreateFavoriteFolder(userId){
  try {
    const res = await fetch('/H3/api/get_folder_by_name.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user_id: userId, name: 'Favorites' })
    });
    const data = await res.json();
    if(data.success && data.folder){
      return data.folder.id;
    }else{
      const createRes = await fetch('/H3/api/create_folder.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId, name: 'Favorites' })
      });
      const createData = await createRes.json();
      if(createData.success){
        return createData.folder_id;
      }else{
        throw new Error(createData.message || 'ไม่สามารถสร้างโฟลเดอร์ Favorites ได้');
      }
    }
  }catch(e){
    console.error('Error in getOrCreateFavoriteFolder:', e);
    throw e;
  }
}

/////////////////////////////////////////////////////////////// for folder selection ///////////////////////////////////////////////////////
// ✅ ฟังก์ชันดึงโฟลเดอร์ของผู้ใช้
async function getUserFolders() {
  try {
    const res = await fetch('/H3/api/get_user_folders.php');
    const data = await res.json();
    if (data.success) {
      return data.folders;
    } else {
      throw new Error(data.message || 'ไม่สามารถดึงข้อมูลโฟลเดอร์ได้');
    }
  } catch (e) {
    console.error('Error fetching folders:', e);
    alert('⚠️ เกิดข้อผิดพลาดในการดึงข้อมูลโฟลเดอร์');
    return [];
  }
}

// ✅ ฟังก์ชันสร้าง modal สำหรับเลือกโฟลเดอร์
function showFolderSelectionModal(folders, onConfirm) {
  // ลบ modal เก่า (ถ้ามี)
  const existingModal = document.getElementById('folder-modal');
  if (existingModal) existingModal.remove();

  // สร้าง modal
  const modal = document.createElement('div');
  modal.id = 'folder-modal';
  modal.style.cssText = `
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000;
  `;
  modal.innerHTML = `
    <div style="background: white; padding: 20px; border-radius: 8px; width: 400px; max-width: 90%;">
      <h3>เลือกโฟลเดอร์</h3>
      <div style="max-height: 300px; overflow-y: auto;">
        ${folders.map(f => `<label style="display: block; margin: 8px 0;"><input type="radio" name="folder" value="${f.id}"> ${f.name}</label>`).join('')}
        <label style="display: block; margin: 8px 0;"><input type="radio" name="folder" value="new"> สร้างโฟลเดอร์ใหม่</label>
      </div>
      <div style="margin-top: 16px;">
        <input type="text" id="new-folder-name" placeholder="ชื่อโฟลเดอร์ใหม่ (ถ้าเลือก 'สร้างโฟลเดอร์ใหม่')" style="width: 100%; padding: 8px; margin-bottom: 8px; display: none;" />
        <button onclick="confirmFolderSelection()" style="margin-right: 8px;">ยืนยัน</button>
        <button onclick="closeFolderModal()">ยกเลิก</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);

  // ซ่อน/แสดงช่องพิมพ์ชื่อโฟลเดอร์ใหม่
  document.querySelectorAll('input[name="folder"]').forEach(radio => {
    radio.addEventListener('change', () => {
      const newFolderInput = document.getElementById('new-folder-name');
      if (radio.value === 'new') {
        newFolderInput.style.display = 'block';
      } else {
        newFolderInput.style.display = 'none';
      }
    });
  });

  window.confirmFolderSelection = () => {
    const selectedRadio = document.querySelector('input[name="folder"]:checked');
    if (!selectedRadio) {
      alert('กรุณาเลือกโฟลเดอร์');
      return;
    }

    if (selectedRadio.value === 'new') {
      const newFolderName = document.getElementById('new-folder-name').value.trim();
      if (!newFolderName) {
        alert('กรุณากรอกชื่อโฟลเดอร์ใหม่');
        return;
      }
      onConfirm(null, newFolderName);
    } else {
      onConfirm(parseInt(selectedRadio.value), null);
    }
    closeFolderModal();
  };

  window.closeFolderModal = () => {
    document.body.removeChild(modal);
  };
}



//////////////////////////////////////////////////////////////////////// end for folder selection ///////////////////////////////////////////////////////




async function toggleBookmark(productId, buttonElement) {
  const isCurrentlyBookmarked = buttonElement.classList.contains('bookmarked');

  if (isCurrentlyBookmarked) {
    // ✅ ลบบุ๊กมาร์ก — ให้ผู้ใช้เลือก folder ที่จะลบ
    if (!USER_ID) {
      alert('⚠️ กรุณาล็อกอินก่อน');
      return;
    }

    const folders = await getUserFolders();
    if (folders.length === 0) {
      alert('⚠️ ไม่มีโฟลเดอร์ที่บุ๊กมาร์กสินค้านี้ไว้');
      return;
    }

    // ✅ ดึงข้อมูลว่าสินค้านี้อยู่ใน folder ไหนบ้าง
    try {
      const res = await fetch(`/H3/api/get_bookmarks_by_product.php?product_id=${productId}`);
      const data = await res.json();
      if (!data.success) {
        alert('⚠️ ' + data.message);
        return;
      }

      const bookmarkedFolders = data.bookmarks; // เช่น [{id: 1, folder_id: 1, folder_name: 'Favorite'}, ...]
      if (bookmarkedFolders.length === 0) {
        alert('⚠️ ไม่พบบุ๊กมาร์กในโฟลเดอร์ใดเลย');
        return;
      }

      // ✅ แสดง modal ให้เลือก folder ที่จะลบ
      showFolderSelectionModal(bookmarkedFolders.map(b => ({ id: b.folder_id, name: b.folder_name })), async (selectedFolderId) => {
        if (!selectedFolderId) {
          alert('กรุณาเลือกโฟลเดอร์ที่จะลบ');
          return;
        }

        // ✅ ส่ง folder_id ไปยัง remove_bookmark.php
        const url = '/H3/api/remove_bookmark.php';
        const res = await fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ product_id: productId, folder_id: selectedFolderId })
        });
        const data = await res.json();
        if (data.success) {
          alert('ลบบุ๊กมาร์กสำเร็จ');
          // ✅ ตรวจสอบว่าสินค้ายังมีบุ๊กมาร์กใน folder อื่นอีกไหม
          const stillBookmarked = await loadBookmarkStatus(productId);
          if (!stillBookmarked) {
            buttonElement.classList.remove('bookmarked');
            buttonElement.innerHTML = '<i class="far fa-bookmark"></i> บุ๊กมาร์ก';
          } else {
            // ยังมีอยู่ใน folder อื่น → ไม่เปลี่ยนสถานะปุ่ม
            alert('สินค้ายังอยู่ในโฟลเดอร์อื่นอยู่');
          }
        } else {
          alert('⚠️ ' + data.message);
        }
      });
    } catch (e) {
      console.error('Error fetching bookmarks:', e);
      alert('⚠️ เกิดข้อผิดพลาดในการดึงข้อมูลบุ๊กมาร์ก');
    }
    return; // จบการทำงานที่นี่ถ้าเป็นการลบ
  }

  // ✅ ถ้ายังไม่ได้บุ๊กมาร์ก ให้เพิ่มใหม่ (เหมือนเดิม)
  if (!USER_ID) {
    alert('⚠️ กรุณาล็อกอินก่อน');
    return;
  }

  let folderId = null;
  let newFolderName = null;

  const folders = await getUserFolders();
  showFolderSelectionModal(folders, async (selectedFolderId, newFolderNameInput) => {
    if (selectedFolderId) {
      folderId = selectedFolderId;
    } else if (newFolderNameInput) {
      try {
        const createRes = await fetch('/H3/api/create_folder.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ user_id: USER_ID, name: newFolderNameInput.trim() })
        });
        const createData = await createRes.json();
        if (createData.success) {
          folderId = createData.folder_id;
        } else {
          alert('⚠️ ' + createData.message);
          return;
        }
      } catch (e) {
        console.error('Error creating folder:', e);
        alert('⚠️ เกิดข้อผิดพลาดในการสร้างโฟลเดอร์');
        return;
      }
    } else {
      return;
    }

    const url = '/H3/api/add_bookmark.php';
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ product_id: productId, folder_id: folderId })
    });
    const data = await res.json();
    if (data.success) {
      buttonElement.classList.add('bookmarked');
      buttonElement.innerHTML = '<i class="fas fa-bookmark"></i> ลบบุ๊กมาร์ก';
    } else {
      alert('⚠️ ' + data.message);
    }
  });
}



document.addEventListener('DOMContentLoaded', async () => { // ✅ เพิ่ม async ที่นี่
  const id = Number(getQueryParam('id'));
  
  if (!id) {
    document.getElementById('product').innerHTML = '<div class="empty">Product not found.</div>';
    return;
  }

  try {
    const response = await fetch(`api/get_product.php?id=${id}`);
    if (!response.ok) throw new Error('ไม่พบสินค้านี้');
    const p = await response.json();

    const root = document.getElementById('product');

    // สร้างปุ่มร้านค้าจาก external_urls
    let buyButtons = '';
    if (p.external_urls && p.external_urls.length > 0) {
      p.external_urls.forEach(link => {
        const source = link.source_name || 'ซื้อเลย';
        buyButtons += `
          <a class="btn secondary" href="${link.url}" target="_blank" style="text-decoration:none; display:inline-block; margin-right:8px;">
            ${source}
          </a>
        `;
      });
    } else if (p.external_url) {
      buyButtons = `
        <a class="btn secondary" href="${p.external_url}" target="_blank" style="text-decoration:none; display:inline-block;">
          ซื้อเลย
        </a>
      `;
    }

    // ✅ สร้างข้อความช่วงราคาจาก price_range
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

    const formattedDescription = formatDescription(p.description);

    // ✅ โหลดสถานะ bookmark
    const isBookmarked = await loadBookmarkStatus(p.id); // ✅ ตอนนี้ใช้ await ได้แล้ว

    // สร้าง HTML
    root.innerHTML = `
      <div class="grid" style="grid-template-columns: 1fr 1fr; gap:24px">
        <div>
          <img src="${p.image_url || ''}" alt="${p.name}" style="max-width:100%; height:auto;">
        </div>
        <div>
          <div class="badge">${p.categories && Array.isArray(p.categories) && p.categories.length > 0 ? p.categories[0] : 'Gift'}</div>
          <h1>${p.name}</h1>
          <div class="price" style="font-size:20px">${priceText}</div>
          <div class="product-description">${formattedDescription}</div>
          <div class="stack">
            ${buyButtons}
            <button class="btn bookmark-btn ${isBookmarked ? 'bookmarked' : ''}">
              ${isBookmarked ? '<i class="fas fa-bookmark"></i> ลบบุ๊กมาร์ก' : '<i class="far fa-bookmark"></i> บุ๊กมาร์ก'}
            </button>
          </div>
        </div>
      </div>


      
      <div class="section">
        <h2>รีวิว</h2>
        <div class="card">
          <div class="card-body">“คุณภาพดี คุ้มค่ากับราคา” — ผู้ใช้งาน</div>
        </div>
      </div>
      
    `;

    // ✅ เพิ่ม event listener
    const bookmarkBtn = root.querySelector('.bookmark-btn');
    if (bookmarkBtn) {
      bookmarkBtn.addEventListener('click', () => {
        toggleBookmark(p.id, bookmarkBtn);
      });
    }

  } catch (error) {
    console.error('❌ Error:', error);
    document.getElementById('product').innerHTML = 
      `<div class="empty">เกิดข้อผิดพลาด: ${error.message}</div>`;
  }
});
