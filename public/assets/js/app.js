document.addEventListener('DOMContentLoaded', () => {
    const dropzone = document.getElementById('dropzone');
    const input = document.getElementById('file-input');
    const pickBtn = document.getElementById('pick-file-btn');
    const selectedFile = document.getElementById('selected-file');

    if (dropzone && input) {
        const updateLabel = () => {
            if (input.files.length > 0) {
                selectedFile.textContent = input.files[0].name;
            } else {
                selectedFile.textContent = '';
            }
        };

        if (pickBtn) {
            pickBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                input.click();
            });
        }

        dropzone.addEventListener('click', (e) => {
            if (e.target !== pickBtn) {
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

    const alerts = document.querySelectorAll('.alert');

    alerts.forEach((alert) => {
        setTimeout(() => {
            alert.classList.add('flash-hide');

            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 3000);
    });
});