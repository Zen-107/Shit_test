function qs(selector, scope=document){return scope.querySelector(selector)}
function qsa(selector, scope=document){return Array.from(scope.querySelectorAll(selector))}

// Simple router helpers
function getQueryParam(name){
  const params = new URLSearchParams(window.location.search);
  return params.get(name);
}

// Cart in localStorage
const CART_KEY = "gf_cart";
function readCart(){
  try{ return JSON.parse(localStorage.getItem(CART_KEY)||"[]"); }catch{ return [] }
}
function writeCart(items){ localStorage.setItem(CART_KEY, JSON.stringify(items)); }
function addToCart(productId, quantity=1){
  const cart = readCart();
  const existing = cart.find(i=>i.productId===productId);
  if(existing){ existing.quantity += quantity; }
  else { cart.push({ productId, quantity }); }
  writeCart(cart);
}
function removeFromCart(productId){
  const cart = readCart().filter(i=>i.productId!==productId);
  writeCart(cart);
}
function updateQty(productId, quantity){
  const cart = readCart();
  const it = cart.find(i=>i.productId===productId);
  if(it){ it.quantity = Math.max(1, quantity|0); writeCart(cart); }
}

// Common header/footer
function renderLayout(){
  const header = document.createElement('header');
  header.innerHTML = `
    <div class="container">
      <div class="nav">
        <a class="brand" href="index.html">Gift Finder</a>
        <nav>
          <a href="form.html">Find Gift</a>
          <a href="results.html">Results</a>
          <a href="blog.html">Blog</a>
          <a href="about.html">About</a>
          <a href="contact.html">Contact</a>
          <a href="cart.html">Cart</a>
        </nav>
      </div>
    </div>`;
  document.body.prepend(header);

  const footer = document.createElement('div');
  footer.className = 'footer';
  footer.innerHTML = `<div class="container">© ${new Date().getFullYear()} Gift Finder</div>`;
  document.body.appendChild(footer);
}

document.addEventListener('DOMContentLoaded', renderLayout);


