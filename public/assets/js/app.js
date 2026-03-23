document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => {
        new bootstrap.Popover(el);
    });

    const dropzone = document.getElementById('dropzone');
    const input = document.getElementById('file-input');
    const pickBtn = document.getElementById('pick-file-btn');
    const selectedFile = document.getElementById('selected-file');
    const uploadForm = document.getElementById('upload-form');
    const submitBtn = document.getElementById('upload-submit-btn');

    const progressWrapper = document.getElementById('upload-progress-wrapper');
    const progressBar = document.getElementById('upload-progress-bar');
    const statusText = document.getElementById('upload-status-text');

    if (dropzone && input) {
        const updateLabel = () => {
            if (input.files.length > 0) {
                selectedFile.textContent = input.files[0].name;
            } else {
                selectedFile.textContent = '';
            }
        };

        if (pickBtn) {
            pickBtn.addEventListener('click', () => input.click());
        }

        dropzone.addEventListener('click', (e) => {
            if (e.target.tagName !== 'BUTTON') {
                input.click();
            }
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');

            if (e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                updateLabel();
            }
        });

        input.addEventListener('change', updateLabel);
    }

    if (uploadForm && input && progressWrapper && progressBar && statusText) {
        const CHUNK_THRESHOLD = 5 * 1024 * 1024;  // 5 Mo
        const CHUNK_SIZE      = 2 * 1024 * 1024;  // 2 Mo par chunk

        async function uploadChunked(file, csrfToken) {
            const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

            // 1. Init de la session
            const initData = new FormData();
            initData.append('_csrf', csrfToken);
            initData.append('original_name', file.name);
            initData.append('total_size', file.size);
            initData.append('total_chunks', totalChunks);

            const initResp = await fetch('?action=upload_chunk_init', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: initData,
            });

            const initJson = await initResp.json();
            if (!initJson.success) {
                throw new Error(initJson.message || "Erreur d\u0027initialisation.");
            }

            const uploadId = initJson.upload_id;

            // 2. Envoi des chunks
            for (let i = 0; i < totalChunks; i++) {
                const start = i * CHUNK_SIZE;
                const end   = Math.min(start + CHUNK_SIZE, file.size);
                const blob  = file.slice(start, end);

                const chunkData = new FormData();
                chunkData.append('_csrf', csrfToken);
                chunkData.append('upload_id', uploadId);
                chunkData.append('chunk_index', i);
                chunkData.append('chunk', blob, file.name);

                const bytesSentBefore = i * CHUNK_SIZE;

                await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '?action=upload_chunk', true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    xhr.upload.addEventListener('progress', (event) => {
                        if (event.lengthComputable) {
                            const totalSent = bytesSentBefore + event.loaded;
                            const percent   = Math.min(99, Math.round((totalSent / file.size) * 100));
                            progressBar.style.width = percent + '%';
                            progressBar.textContent = percent + '%';
                        }
                    });

                    xhr.addEventListener('load', () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                const json = JSON.parse(xhr.responseText);
                                json.success ? resolve(json) : reject(new Error(json.message || 'Erreur chunk.'));
                            } catch (ex) {
                                reject(new Error('Réponse invalide du serveur.'));
                            }
                        } else {
                            let msg = "Erreur lors de l\u0027envoi du chunk.";
                            try { msg = JSON.parse(xhr.responseText).message || msg; } catch (ex) {}
                            reject(new Error(msg));
                        }
                    });

                    xhr.addEventListener('error', () => reject(new Error('Erreur réseau.')));
                    xhr.send(chunkData);
                });
            }

            // 3. Finalisation
            const finalData = new FormData();
            finalData.append('_csrf', csrfToken);
            finalData.append('upload_id', uploadId);

            const finalResp = await fetch('?action=upload_chunk_finalize', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: finalData,
            });

            const finalJson = await finalResp.json();
            if (!finalJson.success) {
                throw new Error(finalJson.message || 'Erreur de finalisation.');
            }
        }

        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!input.files || input.files.length === 0) {
                alert('Veuillez sélectionner un fichier.');
                return;
            }

            const file      = input.files[0];
            const csrfToken = uploadForm.querySelector('input[name="_csrf"]').value;

            progressWrapper.classList.remove('d-none');
            uploadForm.classList.add('upload-is-busy');
            if (submitBtn) { submitBtn.disabled = true; }

            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            statusText.textContent  = 'Upload en cours...';

            if (file.size > CHUNK_THRESHOLD) {
                // Upload découpe en chunks
                try {
                    await uploadChunked(file, csrfToken);
                    progressBar.style.width = '100%';
                    progressBar.textContent = '100%';
                    statusText.textContent  = 'Upload terminé.';
                    setTimeout(() => { window.location.href = '?action=dashboard'; }, 500);
                } catch (err) {
                    statusText.textContent = err.message || "Erreur pendant l\u0027upload.";
                    uploadForm.classList.remove('upload-is-busy');
                    if (submitBtn) { submitBtn.disabled = false; }
                }
            } else {
                // Upload standard (fichiers <= 5 Mo)
                const formData = new FormData(uploadForm);
                const xhr      = new XMLHttpRequest();

                xhr.open('POST', uploadForm.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        progressBar.style.width = percent + '%';
                        progressBar.textContent = percent + '%';
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        progressBar.style.width = '100%';
                        progressBar.textContent = '100%';
                        statusText.textContent  = 'Upload terminé.';
                        setTimeout(() => { window.location.href = '?action=dashboard'; }, 500);
                    } else {
                        let msg = "Erreur pendant l\u0027upload.";
                        try { msg = JSON.parse(xhr.responseText).message || msg; } catch (ex) {}
                        statusText.textContent = msg;
                        uploadForm.classList.remove('upload-is-busy');
                        if (submitBtn) { submitBtn.disabled = false; }
                    }
                });

                xhr.addEventListener('error', () => {
                    statusText.textContent = "Erreur réseau pendant l\u0027upload.";
                    uploadForm.classList.remove('upload-is-busy');
                    if (submitBtn) { submitBtn.disabled = false; }
                });

                xhr.send(formData);
            }
        });
    }

    const alerts = document.querySelectorAll('.alert');

    alerts.forEach((alert) => {
        setTimeout(() => {
            alert.classList.add('flash-hide');

            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 3000);
    });

     const modalElement = document.getElementById('filePreviewModal');

    if (!modalElement || typeof bootstrap === 'undefined') {
        return;
    }

    const modal = new bootstrap.Modal(modalElement);
    const previewButtons = document.querySelectorAll('.js-preview-btn');
    const previewContent = document.getElementById('file-preview-content');
    const previewTitle = document.getElementById('filePreviewModalLabel');
    const previewDownloadLink = document.getElementById('file-preview-download-link');

    const setLoadingState = () => {
        previewContent.innerHTML = `
            <div class="file-preview-loading">
                <div class="spinner-border text-warning" role="status" aria-hidden="true"></div>
                <div class="small app-muted mt-3">Chargement de l\u0027aper\u00e7u...</div>
            </div>
        `;
    };

    const setErrorState = (message) => {
        previewContent.innerHTML = `
            <div class="file-preview-empty">
                <div class="mb-2"><i class="bi bi-exclamation-circle"></i></div>
                <div>${message}</div>
            </div>
        `;
    };

    previewButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const previewUrl = button.dataset.previewUrl || '';
            const downloadUrl = button.dataset.downloadUrl || '#';
            const fileName = button.dataset.fileName || 'Aperçu du fichier';

            previewTitle.textContent = fileName;
            previewDownloadLink.href = downloadUrl;
            previewDownloadLink.classList.remove('d-none');

            setLoadingState();
            modal.show();

            try {
                const response = await fetch(previewUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error("Impossible de charger l\u0027aper\u00e7u.");
                }

                const html = await response.text();
                previewContent.innerHTML = html;
            } catch (error) {
                setErrorState("Impossible de charger l\u0027aper\u00e7u du fichier.");
            }
        });
    });

    previewContent.addEventListener('click', (event) => {
        const image = event.target.closest('.file-preview-image');

        if (!image) {
            return;
        }

        const pane = image.closest('.file-preview-pane');
        const isZoomed = image.classList.toggle('is-zoomed');

        if (pane) {
            pane.classList.toggle('is-zoomed', isZoomed);

            if (!isZoomed) {
                pane.scrollTop = 0;
                pane.scrollLeft = 0;
            }
        }
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        previewTitle.textContent = 'Aperçu du fichier';
        previewContent.innerHTML = `
            <div class="file-preview-empty">
                Sélectionnez un fichier à prévisualiser.
            </div>
        `;
        previewDownloadLink.href = '#';
        previewDownloadLink.classList.add('d-none');
    });

    // Share modal
    const shareModalEl = document.getElementById('shareModal');
    const shareModalBody = document.getElementById('share-modal-body');

    if (shareModalEl && shareModalBody) {
        shareModalEl.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger) return;

            const fileId = trigger.dataset.fileId;
            const contentEl = document.getElementById('share-content-' + fileId);

            shareModalBody.innerHTML = contentEl
                ? contentEl.innerHTML
                : '<p class="app-muted">Contenu introuvable.</p>';
        });

        shareModalEl.addEventListener('hidden.bs.modal', () => {
            shareModalBody.innerHTML = '<p class="app-muted">Chargement\u2026</p>';
        });

        shareModalBody.addEventListener('click', (e) => {
            const btn = e.target.closest('.js-copy-btn');
            if (!btn) return;

            const text = btn.dataset.copy || '';
            if (!text) return;

            navigator.clipboard.writeText(text).then(() => {
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = 'bi bi-check2';
                    setTimeout(() => { icon.className = 'bi bi-copy'; }, 1500);
                }
            });
        });
    }

});
