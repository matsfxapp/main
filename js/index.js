function initializeArtistSections() {
    const artistSections = document.querySelectorAll('.artist-section');

    artistSections.forEach(section => {
        const container = section.querySelector('.artist-songs-container');
        const prevButton = section.querySelector('.nav-prev');
        const nextButton = section.querySelector('.nav-next');

        if (prevButton && container) {
            prevButton.addEventListener('click', () => {
                container.scrollBy({
                    left: -container.clientWidth,
                    behavior: 'smooth'
                });
            });
        }

        if (nextButton && container) {
            nextButton.addEventListener('click', () => {
                container.scrollBy({
                    left: container.clientWidth,
                    behavior: 'smooth'
                });
            });
        }

        if (container) {
            container.addEventListener('wheel', (e) => {
                e.preventDefault();
                container.scrollBy({
                    left: e.deltaY < 0 ? -container.clientWidth : container.clientWidth,
                    behavior: 'smooth'
                });
            });
        }
    });
}