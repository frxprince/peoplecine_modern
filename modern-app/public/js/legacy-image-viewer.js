(function () {
    const viewer = document.querySelector('[data-image-viewer]');

    if (!viewer) {
        return;
    }

    const image = viewer.querySelector('[data-image-viewer-image]');
    const caption = viewer.querySelector('[data-image-viewer-caption]');
    const closeButtons = viewer.querySelectorAll('[data-image-viewer-close]');
    const prevButton = viewer.querySelector('[data-image-viewer-prev]');
    const nextButton = viewer.querySelector('[data-image-viewer-next]');
    const body = document.body;

    let currentGroup = [];
    let currentIndex = 0;

    const getCaption = (link) => link.getAttribute('data-lightbox-caption') || link.querySelector('img')?.getAttribute('alt') || '';

    const updateViewer = () => {
        if (!currentGroup.length) {
            return;
        }

        const currentLink = currentGroup[currentIndex];
        image.src = currentLink.href;
        image.alt = getCaption(currentLink);
        caption.textContent = getCaption(currentLink);

        const showNav = currentGroup.length > 1;
        prevButton.hidden = !showNav;
        nextButton.hidden = !showNav;
    };

    const openViewer = (link) => {
        const groupName = link.getAttribute('data-lightbox-group') || '__default__';
        currentGroup = Array.from(document.querySelectorAll('.js-lightbox-item')).filter((item) => {
            return (item.getAttribute('data-lightbox-group') || '__default__') === groupName;
        });
        currentIndex = Math.max(0, currentGroup.indexOf(link));

        updateViewer();
        viewer.hidden = false;
        body.classList.add('image-viewer-open');
    };

    const closeViewer = () => {
        viewer.hidden = true;
        image.src = '';
        image.alt = '';
        caption.textContent = '';
        body.classList.remove('image-viewer-open');
        currentGroup = [];
        currentIndex = 0;
    };

    const move = (step) => {
        if (currentGroup.length < 2) {
            return;
        }

        currentIndex = (currentIndex + step + currentGroup.length) % currentGroup.length;
        updateViewer();
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('.js-lightbox-item');

        if (!link) {
            return;
        }

        event.preventDefault();
        openViewer(link);
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeViewer);
    });

    prevButton.addEventListener('click', () => move(-1));
    nextButton.addEventListener('click', () => move(1));

    document.addEventListener('keydown', (event) => {
        if (viewer.hidden) {
            return;
        }

        if (event.key === 'Escape') {
            closeViewer();
        } else if (event.key === 'ArrowLeft') {
            move(-1);
        } else if (event.key === 'ArrowRight') {
            move(1);
        }
    });
})();
