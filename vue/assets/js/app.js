function openModal(src) {
        document.getElementById('modalContent').innerHTML = `<img src="${src}" alt="">`;
        document.getElementById('modal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(e) {
        if (e && e.target !== document.getElementById('modal') &&
            !e.target.closest('.ci-modal-x')) return;
        document.getElementById('modal').classList.remove('open');
        document.getElementById('modalContent').innerHTML = '';
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

const acceptMap = { photo:'image/*', video:'video/*', audio:'audio/*' };
const iconMap   = { photo:'image-outline', video:'videocam-outline', audio:'musical-notes-outline', note:'document-text-outline' };

function selectType(type) {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    document.querySelector(`[data-type="${type}"]`).classList.add('selected');
    document.getElementById('input-type').value = type;

    const form      = document.getElementById('ajout-form');
    const fileZone  = document.getElementById('file-zone');
    const noteZone  = document.getElementById('note-zone');
    const preview   = document.getElementById('preview-zone');
    const fileInput = document.getElementById('file-input');

    form.classList.remove('hidden');
    preview.classList.add('hidden');
    preview.innerHTML = '';

    if (type === 'note') {
        fileZone.classList.add('hidden');
        noteZone.classList.remove('hidden');
        fileInput.removeAttribute('name');
    } else {
        noteZone.classList.add('hidden');
        fileZone.classList.remove('hidden');
        fileInput.setAttribute('name', 'file');
        fileInput.setAttribute('accept', acceptMap[type]);
        document.getElementById('upload-icon').setAttribute('name', iconMap[type]);
        const labels = { photo:'Importer une photo', video:'Importer une vidéo', audio:'Importer un audio' };
        document.getElementById('upload-text').textContent = labels[type];
        document.getElementById('file-name').textContent = '';
    }
    form.scrollIntoView({ behavior:'smooth', block:'start' });
}

function previewFile(input) {
    if (!input.files || !input.files[0]) return;
    const file    = input.files[0];
    const type    = document.getElementById('input-type').value;
    const preview = document.getElementById('preview-zone');
    const url     = URL.createObjectURL(file);
    document.getElementById('file-name').textContent = file.name;
    preview.innerHTML = '';
    preview.classList.remove('hidden');
    if (type === 'photo') {
        const img = document.createElement('img'); img.src = url; img.className = 'preview-img'; preview.appendChild(img);
    } else if (type === 'video') {
        const vid = document.createElement('video'); vid.src = url; vid.controls = true; vid.className = 'preview-video'; preview.appendChild(vid);
    } else if (type === 'audio') {
        const aud = document.createElement('audio'); aud.src = url; aud.controls = true; aud.className = 'preview-audio'; preview.appendChild(aud);
    }
}

document.getElementById('file-label').addEventListener('click', () => {
    document.getElementById('file-input').click();
});

function toggleExtra(blockId) {
    const block   = document.getElementById(blockId);
    const chevron = document.getElementById('chevron-' + blockId.replace('-block',''));
    block.classList.toggle('hidden');
    if (chevron) chevron.style.transform = block.classList.contains('hidden') ? '' : 'rotate(90deg)';
}

let capsuleOn = false;
function toggleCapsule() {
    capsuleOn = !capsuleOn;
    const block  = document.getElementById('capsule-block');
    const sw     = document.getElementById('capsule-toggle-switch');
    const input  = document.getElementById('is-capsule-input');
    const sub    = document.getElementById('capsule-sub');

    block.classList.toggle('hidden', !capsuleOn);
    sw.classList.toggle('on', capsuleOn);
    input.value = capsuleOn ? '1' : '0';
    sub.textContent = capsuleOn ? 'Activée — choisissez une date' : 'Ce souvenir s\'ouvrira à une date choisie';
}

function updateAlbumSub(radio, name) {
    const sub = document.getElementById('album-sub');
    sub.textContent = name ? name : 'Aucun album sélectionné';
}

function createAlbum() {
    const input = document.getElementById('new-album-input');
    const name  = input.value.trim();
    if (!name) return;

    const form = new FormData();
    form.append('new_album_title', name);

    fetch('', { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const list = document.querySelector('.album-list');
            const label = document.createElement('label');
            label.className = 'album-option';
            label.innerHTML = `
                <input type="radio" name="album_id" value="${data.id}"
                       onchange="updateAlbumSub(this, '${data.title.replace(/'/g,"\\'")}')">
                <span class="album-option-content">
                    <ion-icon name="albums-outline"></ion-icon>
                    ${data.title}
                </span>`;
            list.appendChild(label);

            label.querySelector('input').click();
            input.value = '';
        });
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const targetTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    // Appliquer le thème
    document.documentElement.setAttribute('data-theme', targetTheme);
    
    // Sauvegarder le choix
    localStorage.setItem('theme', targetTheme);
    
    const status = document.getElementById('theme-status');
    if(status) status.innerText = targetTheme === 'dark' ? 'Sombre' : 'Clair';
}

// Appliquer le thème sauvegardé au chargement de la page
(function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    const status = document.getElementById('theme-status');
    if(status) status.innerText = savedTheme === 'dark' ? 'Sombre' : 'Clair';
})();


// mise à jour de l'icône selon l'état du toggle de partage
    document.getElementById('new-album-shared')?.addEventListener('change', function () {
        const label = document.getElementById('share-toggle-label');
        const icon  = document.getElementById('share-icon');
        if (this.checked) {
            label.classList.add('active');
            icon.setAttribute('name', 'people');        
        } else {
            label.classList.remove('active');
            icon.setAttribute('name', 'people-outline');
        }
    });