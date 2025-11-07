function renderCart(){
  const mount = document.getElementById('cartView');
  const items = readCart();
  if(items.length===0){ mount.innerHTML = '<div class="empty">Your cart is empty.</div>'; return; }
  const withProducts = items.map(i=>({
    ...i,
    product: CATALOG.find(p=>p.id===i.productId)
  })).filter(x=>x.product);
  const subtotal = withProducts.reduce((s,x)=> s + x.quantity*x.product.price, 0);
  mount.innerHTML = `
    <table class="cart-table">
      <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr></thead>
      <tbody>
        ${withProducts.map(x=>`
          <tr>
            <td>${x.product.name}</td>
            <td>${x.product.price.toLocaleString()} ${x.product.currency}</td>
            <td><input type="number" min="1" value="${x.quantity}" data-qty="${x.product.id}" style="width:80px"></td>
            <td>${(x.quantity*x.product.price).toLocaleString()} ${x.product.currency}</td>
            <td><button data-remove="${x.product.id}" class="btn secondary">Remove</button></td>
          </tr>
        `).join('')}
      </tbody>
    </table>
    <div class="total"><strong>Subtotal:</strong> <div>${subtotal.toLocaleString()} THB</div></div>
  `;

  mount.addEventListener('change', (e)=>{
    const t = e.target.closest('[data-qty]');
    if(t){ updateQty(Number(t.dataset.qty), Number(t.value)); renderCart(); }
  });
  mount.addEventListener('click', (e)=>{
    const btn = e.target.closest('[data-remove]');
    if(btn){ removeFromCart(Number(btn.dataset.remove)); renderCart(); }
  });
}

document.addEventListener('DOMContentLoaded', ()=>{
  renderCart();
  const form = document.getElementById('checkoutForm');
  form.addEventListener('submit', (e)=>{
    e.preventDefault();
    alert('Order placed! (demo)');
    localStorage.removeItem(CART_KEY);
    window.location.href = 'index.html';
  });
});


