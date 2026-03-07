let currentMemoryId = null;

async function toggleLike(btn, id) {
    const icon  = btn.querySelector('ion-icon');
    const count = btn.querySelector('span');
    btn.classList.toggle('liked');
    const liked = btn.classList.contains('liked');
    icon.setAttribute('name', liked ? 'heart' : 'heart-outline');
    icon.style.color = liked ? '#e84040' : '#323231';
    try {
        const res  = await fetch(`${BASE_URL}/controler/like.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ memory_id: id })
        });
        const data = await res.json();
        if (data.likes_count !== undefined) count.textContent = data.likes_count;
    } catch(e) {
        btn.classList.toggle('liked');
        icon.setAttribute('name', liked ? 'heart-outline' : 'heart');
        icon.style.color = '#323231';
    }
}

async function openComments(id) {
    currentMemoryId = id;
    const list = document.getElementById('comments-list');
    list.innerHTML = '<p class="comment-msg">Chargement…</p>';
    document.getElementById('overlay').classList.add('visible');
    document.getElementById('panel-comments').classList.add('open');

    try {
        const res  = await fetch(`${BASE_URL}/controler/comments.php?memory_id=${id}`);
        const data = await res.json();
        if (!data.length) {
            list.innerHTML = '<p class="comment-msg">Soyez le premier à commenter ✨</p>';
            return;
        }
        list.innerHTML = data.map(c => `
            <div class="comment-item">
                <img src="${BASE_URL}/vue/assets/images/${c.picture || 'default.jpg'}" alt="">
                <div class="comment-bubble">
                    <div class="comment-user">@${c.username}</div>
                    <div class="comment-text">${esc(c.content)}</div>
                    <div class="comment-time">${c.created_at}</div>
                </div>
            </div>
        `).join('');
    } catch(e) {
        list.innerHTML = '<p class="comment-msg">Erreur de chargement.</p>';
    }
}

async function submitComment() {
    const input = document.getElementById('comment-input');
    const text  = input.value.trim();
    if (!text || !currentMemoryId) return;
    try {
        const res  = await fetch(`${BASE_URL}/controler/comments.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ memory_id: currentMemoryId, content: text })
        });
        const data = await res.json();
        if (data.success) {
            input.value = '';
            openComments(currentMemoryId);
            showToast('Commentaire ajouté ✓');
        }
    } catch(e) {}
}

function closePanel() {
    document.getElementById('overlay').classList.remove('visible');
    document.getElementById('panel-comments').classList.remove('open');
}

function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

function esc(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}