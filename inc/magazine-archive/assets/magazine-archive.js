class MagazineArchiveHandler extends elementorModules.frontend.handlers.Base {
    getDefaultSettings() {
        return {
            selectors: {
                searchInput: '.magazine-search-input',
                cards: '.magazine-card',
                noResults: '.magazine-no-results',
                grid: '.magazine-grid'
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings('selectors');
        return {
            $searchInput: this.$element.find(selectors.searchInput),
            $cards: this.$element.find(selectors.cards),
            $noResults: this.$element.find(selectors.noResults),
            $grid: this.$element.find(selectors.grid)
        };
    }

    bindEvents() {
        if (this.elements.$searchInput.length) {
            this.elements.$searchInput.on('input', this.handleSearch.bind(this));
        }
    }

    handleSearch(event) {
        const query = event.target.value.toLowerCase().trim();
        let visibleCount = 0;

        this.elements.$cards.each((index, card) => {
            const $card = jQuery(card);
            const title = $card.data('title') || '';

            if (title.indexOf(query) > -1) {
                $card.show();
                visibleCount++;
            } else {
                $card.hide();
            }
        });

        if (visibleCount === 0) {
            this.elements.$noResults.show();
            this.elements.$grid.css('opacity', '0.2');
        } else {
            this.elements.$noResults.hide();
            this.elements.$grid.css('opacity', '1');
        }
    }
}

jQuery(window).on('elementor/frontend/init', () => {
    const addHandler = ($element) => {
        elementorFrontend.elementsHandler.addHandler(MagazineArchiveHandler, { $element });
    };
    elementorFrontend.hooks.addAction('frontend/element_ready/magazine_archive_e4c52962.default', addHandler);
});
