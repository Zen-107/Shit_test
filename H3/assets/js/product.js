document.addEventListener('DOMContentLoaded', ()=>{
  const id = Number(getQueryParam('id'));
  const p = CATALOG.find(x=>x.id===id);
  const root = document.getElementById('product');
  if(!p){ root.innerHTML = '<div class="empty">Product not found.</div>'; return; }
  root.innerHTML = `
    <div class="grid" style="grid-template-columns: 1fr 1fr; gap:24px">
      <div><img src="${p.image_url}" alt="${p.name}"></div>
      <div>
        <div class="badge">${p.categories[0]||'Gift'}</div>
        <h1>${p.name}</h1>
        <div class="price" style="font-size:20px">${p.price.toLocaleString()} ${p.currency}</div>
        <p>${p.description}</p>
        <div class="stack">
          <button id="add" class="btn">Add to cart</button>
          <a class="btn secondary" href="${p.external_url}" target="_blank">Buy now</a>
        </div>
      </div>
    </div>

    <div class="section">
      <h2>Reviews</h2>
      <div class="card"><div class="card-body">“Great quality for the price.” — Guest</div></div>
    </div>
  `;

  qs('#add').addEventListener('click', ()=>{ addToCart(p.id, 1); });
});


