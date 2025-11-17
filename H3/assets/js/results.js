const FORM_KEY = "gf_criteria";

function matchBudget(product, budgetKey){
  if(!budgetKey) return true;
  const range = BUDGET_RANGES.find(r=>r.key===budgetKey);
  if(!range) return true;
  return product.price >= range.min && product.price <= range.max;
}

function matchArray(productValues, selected){
  if(!selected || selected.length===0) return true;
  return selected.every(v=>productValues.includes(v));
}

function matchSingle(productValues, value){
  if(!value) return true;
  return productValues.includes(value);
}

function filterCatalog(criteria){
  return CATALOG.filter(p=>
    matchBudget(p, criteria.budget) &&
    matchSingle(p.genders||[], criteria.gender) &&
    matchSingle(p.ageRanges||[], criteria.age) &&
    matchArray(p.interests||[], criteria.interests||[]) &&
    matchArray(p.personality||[], criteria.personality||[])
  );
}

document.addEventListener('DOMContentLoaded', ()=>{
  const raw = sessionStorage.getItem(FORM_KEY);
  const criteria = raw ? JSON.parse(raw) : {};
  const critEl = document.getElementById('criteria');
  critEl.textContent = `Budget: ${criteria.budget||'Any'} | Gender: ${criteria.gender||'Any'} | Age: ${criteria.age||'Any'}`;

  const results = filterCatalog(criteria);
  const grid = document.getElementById('results');
  const empty = document.getElementById('empty');

  if(results.length===0){ empty.style.display='block'; return; }
  grid.innerHTML = results.map(p=>`
    <div class="card">
      <img src="${p.image_url}" alt="${p.name}">
      <div class="card-body">
        <div class="badge">${p.categories[0]||'Gift'}</div>
        <strong>${p.name}</strong>
        <div class="price">${p.price.toLocaleString()} ${p.currency}</div>
        <div>${p.description}</div>
        <div class="stack">
          <a class="btn" href="product.html?id=${p.id}">View details</a>
          <button class="btn secondary" data-add="${p.id}">Add to cart</button>
        </div>
      </div>
    </div>
  `).join('');

  grid.addEventListener('click', (e)=>{
    const btn = e.target.closest('[data-add]');
    if(btn){ addToCart(Number(btn.dataset.add),1); btn.textContent='Added'; setTimeout(()=>btn.textContent='Add to cart', 1000); }
  });
});


