const FORM_KEY = "gf_criteria";
const RECIPIENTS_KEY = "gf_recipients";

function loadRecipients() {
  try {
    return JSON.parse(localStorage.getItem(RECIPIENTS_KEY)) || [];
  } catch (e) {
    return [];
  }
}

function saveRecipients(list) {
  localStorage.setItem(RECIPIENTS_KEY, JSON.stringify(list));
}


function renderInterests() {
  const target = document.getElementById('interests');
  const unique = Array.from(new Set(CATALOG.flatMap(p => p.interests)));
  target.innerHTML = unique.map(v => `<label class="pill"><input type="checkbox" value="${v}">${v}</label>`).join('');
  target.addEventListener('click', (e) => {
    const pill = e.target.closest('.pill');
    if (pill) pill.classList.toggle('active');
  });
}

document.addEventListener('DOMContentLoaded', () => {
  renderInterests();
  const form = document.getElementById('gift-form');
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const data = new FormData(form);
    const selectedInterests = qsa('#interests input:checked').map(i => i.value);
    const selectedPersonality = qsa('#personality input:checked').map(i => i.value);
    const criteria = {
      budget: data.get('budget') || '',
      name: data.get('name') || '',
      gender: data.get('gender') || '',
      age: data.get('age') || '',
      relationship: data.get('relationship') || '',
      interests: selectedInterests,
      personality: selectedPersonality,
    };
    sessionStorage.setItem(FORM_KEY, JSON.stringify(criteria));
    window.location.href = 'results.html';
  });
});


