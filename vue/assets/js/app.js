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