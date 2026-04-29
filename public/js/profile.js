(() => {
    const input = document.querySelector('[data-profile-photo-input]');
    const hiddenInput = document.getElementById('profileCroppedPhoto');
    const preview = document.querySelector('[data-profile-photo-preview]');
    const image = document.getElementById('profileCropImage');
    const modalElement = document.getElementById('profileCropModal');
    const submitButton = document.getElementById('profileCropSubmit');
    const form = input?.closest('form');

    if (!input || !hiddenInput || !preview || !image || !modalElement || !submitButton) {
        return;
    }

    let cropper = null;
    let objectUrl = null;
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

    const clearCropper = () => {
        cropper?.destroy();
        cropper = null;

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        image.removeAttribute('src');
    };

    input.addEventListener('change', () => {
        const file = input.files?.[0];

        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        clearCropper();
        objectUrl = URL.createObjectURL(file);
        image.src = objectUrl;
        modal.show();
    });

    modalElement.addEventListener('shown.bs.modal', () => {
        if (!image.src || typeof Cropper === 'undefined') {
            return;
        }

        cropper = new Cropper(image, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            background: false,
            responsive: true,
            movable: true,
            zoomable: true,
            rotatable: true,
            scalable: false,
        });
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        if (!hiddenInput.value) {
            input.value = '';
        }

        clearCropper();
    });

    document.querySelectorAll('[data-cropper-action]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!cropper) {
                return;
            }

            if (button.dataset.cropperAction === 'zoom-in') {
                cropper.zoom(0.1);
            }

            if (button.dataset.cropperAction === 'zoom-out') {
                cropper.zoom(-0.1);
            }

            if (button.dataset.cropperAction === 'rotate-left') {
                cropper.rotate(-90);
            }

            if (button.dataset.cropperAction === 'rotate-right') {
                cropper.rotate(90);
            }
        });
    });

    submitButton.addEventListener('click', () => {
        if (!cropper || !form) {
            return;
        }

        const canvas = cropper.getCroppedCanvas({
            width: 512,
            height: 512,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            return;
        }

        const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
        hiddenInput.value = dataUrl;
        preview.innerHTML = `<img src="${dataUrl}" alt="">`;
        modal.hide();

        window.setTimeout(() => form.requestSubmit(), 120);
    });
})();
