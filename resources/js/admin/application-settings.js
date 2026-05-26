(() => {
    const modalElement = document.getElementById('brandingCropModal');
    const image = document.getElementById('brandingCropImage');
    const applyButton = document.getElementById('brandingCropApply');

    if (!modalElement || !image || !applyButton || typeof bootstrap === 'undefined') {
        return;
    }

    const targets = {
        logo: {
            input: document.querySelector('[data-brand-image-input="logo"]'),
            hidden: document.getElementById('croppedLogo'),
            preview: document.querySelector('[data-brand-preview="logo"]'),
            size: 512,
        },
        favicon: {
            input: document.querySelector('[data-brand-image-input="favicon"]'),
            hidden: document.getElementById('croppedFavicon'),
            preview: document.querySelector('[data-brand-preview="favicon"]'),
            size: 256,
        },
    };
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    let cropper = null;
    let objectUrl = null;
    let activeTarget = null;
    let cropApplied = false;

    const clearCropper = () => {
        cropper?.destroy();
        cropper = null;

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        image.removeAttribute('src');
    };

    Object.values(targets).forEach((target) => {
        if (!target.input || !target.hidden || !target.preview) {
            return;
        }

        target.input.addEventListener('change', () => {
            const file = target.input.files?.[0];

            if (!file || !['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
                target.hidden.value = '';
                return;
            }

            clearCropper();
            activeTarget = target;
            cropApplied = false;
            target.hidden.value = '';
            objectUrl = URL.createObjectURL(file);
            image.src = objectUrl;
            modal.show();
        });
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
        if (!cropApplied && activeTarget) {
            activeTarget.input.value = '';
            activeTarget.hidden.value = '';
        }

        clearCropper();
        activeTarget = null;
        cropApplied = false;
    });

    document.querySelectorAll('[data-brand-crop-action]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!cropper) {
                return;
            }

            const action = button.dataset.brandCropAction;

            if (action === 'zoom-in') {
                cropper.zoom(0.1);
            } else if (action === 'zoom-out') {
                cropper.zoom(-0.1);
            } else if (action === 'rotate-left') {
                cropper.rotate(-90);
            } else if (action === 'rotate-right') {
                cropper.rotate(90);
            }
        });
    });

    applyButton.addEventListener('click', () => {
        if (!cropper || !activeTarget) {
            return;
        }

        const canvas = cropper.getCroppedCanvas({
            width: activeTarget.size,
            height: activeTarget.size,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            return;
        }

        const dataUrl = canvas.toDataURL('image/png');
        activeTarget.hidden.value = dataUrl;
        activeTarget.preview.innerHTML = `<img src="${dataUrl}" alt="">`;
        cropApplied = true;
        modal.hide();
    });
})();
