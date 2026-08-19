class MagazineArchiveHandler extends elementorModules.frontend.handlers.Base {
    getDefaultSettings() {
        return {
            selectors: {
                wrapper: '.magazine-grid-wrapper',
                searchInput: '.magazine-search-input',
                results: '.magazine-results'
            },
            debounceMs: 350
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings('selectors');
        return {
            $wrapper: this.$element.find(selectors.wrapper),
            $searchInput: this.$element.find(selectors.searchInput),
            $results: this.$element.find(selectors.results)
        };
    }

    bindEvents() {
        if (this.elements.$searchInput.length) {
            this.elements.$searchInput.on('input', this.handleInput.bind(this));
        }
    }

    onInit(...args) {
        super.onInit(...args);
        // Cache the server-rendered results (grid + pagination) so we can
        // restore them exactly when the search box is cleared.
        this.originalResultsHtml = this.elements.$results.html();
        this.searchTimeout = null;
        this.activeRequest = null;
    }

    handleInput(event) {
        const query = event.target.value.trim();

        clearTimeout(this.searchTimeout);

        if ('' === query) {
            this.restoreOriginalResults();
            return;
        }

        this.searchTimeout = setTimeout(() => this.runSearch(query), this.getSettings('debounceMs'));
    }

    runSearch(query) {
        if (this.activeRequest) {
            this.activeRequest.abort();
        }

        const $wrapper = this.elements.$wrapper;
        const category = $wrapper.data('category') || 'e-magazines';

        this.elements.$results.css('opacity', '0.4');

        this.activeRequest = jQuery.ajax({
            url: hseMagazineSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'hse_magazine_search',
                nonce: hseMagazineSearch.nonce,
                search: query,
                category: category
            }
        }).done((response) => {
            this.elements.$results.css('opacity', '1');

            if (!response || !response.success) {
                return;
            }

            if (response.data.count > 0) {
                this.elements.$results.html(
                    '<div class="magazine-grid">' + response.data.html + '</div>'
                );
            } else {
                this.elements.$results.html(
                    '<p class="magazine-no-results" style="text-align:center; padding: 40px; color:#7A7A7A;">No matching editions found.</p>'
                );
            }
        }).fail((jqXHR, textStatus) => {
            if ('abort' !== textStatus) {
                this.elements.$results.css('opacity', '1');
            }
        });
    }

    restoreOriginalResults() {
        if (this.activeRequest) {
            this.activeRequest.abort();
        }
        this.elements.$results.css('opacity', '1');
        this.elements.$results.html(this.originalResultsHtml);
    }
}

jQuery(window).on('elementor/frontend/init', () => {
    const addHandler = ($element) => {
        elementorFrontend.elementsHandler.addHandler(MagazineArchiveHandler, { $element });
    };
    elementorFrontend.hooks.addAction('frontend/element_ready/magazine_archive_e4c52962.default', addHandler);
});
